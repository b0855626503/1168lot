<?php

namespace Gametech\Lotto\Services\Relay;

use Gametech\Lotto\Models\LottoDraw;
use Illuminate\Support\Facades\Log;

class LotteryRelayPublisher
{
    public function __construct(
        private LotteryRelayRuntime $runtime,
        private LotteryRelayTypeRegistry $typeRegistry,
        private LotteryRelayStream $stream
    ) {}

    public function publishIfReady(LottoDraw $draw): ?string
    {
        if (! $this->runtime->shouldPublishReadySignals()) {
            return null;
        }

        if ((string) $draw->result_fetch_status !== 'APPLIED') {
            return null;
        }

        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : null;
        $checksum = trim((string) ($draw->result_hash ?? ''));
        $marketCode = strtolower(trim((string) data_get($draw, 'market.code', '')));
        $canonicalType = $this->typeRegistry->canonicalTypeForMarketCode($marketCode);

        if ($canonicalType === null || $drawDate === null || $checksum === '') {
            return null;
        }

        $markerKey = sprintf('%s:%s:%s', $this->runtime->publishedMarkerPrefix(), $canonicalType, $drawDate);
        $currentMarker = $this->stream->get($this->runtime->streamConnection(), $markerKey);
        if ($currentMarker === $checksum) {
            return null;
        }

        $eventId = sprintf('%s:%s:%s', $canonicalType, $drawDate, substr($checksum, 0, 16));
        $payload = [
            'event' => 'lottery.ready',
            'event_id' => $eventId,
            'type' => $canonicalType,
            'date' => $drawDate,
            'checksum' => $checksum,
            'published_at' => now()->toIso8601String(),
        ];

        $streamId = $this->stream->publish(
            $this->runtime->streamConnection(),
            $this->runtime->streamKey(),
            $payload,
            $this->runtime->streamMaxLen()
        );

        $this->stream->set($this->runtime->streamConnection(), $markerKey, $checksum);
        $this->stream->set(
            $this->runtime->streamConnection(),
            sprintf('%s:%s', $this->runtime->latestMarkerPrefix(), $canonicalType),
            json_encode(array_merge($payload, ['stream_id' => $streamId]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        Log::channel($this->runtime->logChannel())->info('LOTTERY_RELAY_READY_PUBLISHED', [
            'stream_id' => $streamId,
            'event_id' => $eventId,
            'type' => $canonicalType,
            'date' => $drawDate,
            'draw_id' => (int) $draw->id,
            'market_id' => (int) $draw->market_id,
            'checksum' => $checksum,
        ]);

        return $streamId;
    }
}
