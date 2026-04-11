<?php

namespace Gametech\Lotto\Services\Relay;

class LotteryRelayRuntime
{
    public function enabled(): bool
    {
        return (bool) config('lottery_result_relay.enabled', false);
    }

    public function mode(): string
    {
        $mode = strtolower(trim((string) config('lottery_result_relay.mode', 'disabled')));

        return in_array($mode, ['primary', 'clone', 'disabled'], true) ? $mode : 'disabled';
    }

    public function isPrimary(): bool
    {
        return $this->enabled() && $this->mode() === 'primary';
    }

    public function isClone(): bool
    {
        return $this->enabled() && $this->mode() === 'clone';
    }

    public function isDisabled(): bool
    {
        return ! $this->enabled() || $this->mode() === 'disabled';
    }

    public function shouldPublishReadySignals(): bool
    {
        return $this->isPrimary();
    }

    public function shouldConsumeRelayStream(): bool
    {
        return $this->isClone();
    }

    public function shouldRunLegacyAutoResultSweep(): bool
    {
        return ! $this->isClone();
    }

    public function streamConnection(): string
    {
        return (string) config('lottery_result_relay.stream_connection', 'lotto');
    }

    public function streamKey(): string
    {
        return (string) config('lottery_result_relay.stream_key', 'lotto:stream:results');
    }

    public function streamMaxLen(): int
    {
        return max(100, (int) config('lottery_result_relay.stream_maxlen', 10000));
    }

    public function queue(): string
    {
        return (string) config('lottery_result_relay.queue', 'lotto');
    }

    public function consumerGroup(): string
    {
        return trim((string) config('lottery_result_relay.consumer_group', 'relay-default'));
    }

    public function consumerName(): string
    {
        return trim((string) config('lottery_result_relay.consumer_name', 'relay-consumer'));
    }

    public function apiBaseUrl(): string
    {
        return rtrim((string) config('lottery_result_relay.api_base_url', ''), '/');
    }

    public function publishedMarkerPrefix(): string
    {
        return trim((string) config('lottery_result_relay.published_marker_prefix', 'lotto:relay:published'));
    }

    public function latestMarkerPrefix(): string
    {
        return trim((string) config('lottery_result_relay.latest_marker_prefix', 'lotto:relay:latest'));
    }

    public function logChannel(): string
    {
        return (string) config('lottery_result_relay.log_channel', 'daily');
    }
}
