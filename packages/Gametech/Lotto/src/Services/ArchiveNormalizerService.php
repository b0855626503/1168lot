<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Support\Facades\Log;

class ArchiveNormalizerService
{
    /**
     * Maps bet_type → [result_key, draw_key]
     *
     * result_key: key inside lotto_draws.result_number JSON
     * draw_key:   stable public-facing key for the archive
     */
    protected const BET_TYPE_MAP = [
        'top_3' => ['result_key' => 'top_3', 'draw_key' => 'three_up'],
        'top_2' => ['result_key' => 'top_2', 'draw_key' => 'two_up'],
        'bottom_2' => ['result_key' => 'bottom_2', 'draw_key' => 'two_down'],
    ];

    protected ArchiveChecksumService $checksum;

    public function __construct(?ArchiveChecksumService $checksum = null)
    {
        $this->checksum = $checksum ?? new ArchiveChecksumService;
    }

    /**
     * Normalize a resulted draw into one or more archive rows.
     *
     * @return array<int, array{market_code: string, draw_date: string, draw_key: string, result_set: array<string>, result_hash: string}>
     */
    public function normalizeDraw(object $draw): array
    {
        $market = $draw->market;
        if (($market->result_mode ?? null) === LotteryMarket::RESULT_MODE_YEEKEE) {
            return [];
        }

        $rows = [];
        $marketCode = $market->code;
        $drawDate = $draw->draw_date->format('Y-m-d');
        $resultNumber = $draw->result_number;

        if (empty($resultNumber)) {
            return [];
        }

        foreach ($draw->betSettings as $betSetting) {
            $betType = $betSetting->bet_type;

            if (! isset(static::BET_TYPE_MAP[$betType])) {
                Log::warning('ArchiveNormalizer: unknown bet_type, skipping', [
                    'draw_id' => $draw->id,
                    'market_code' => $marketCode,
                    'bet_type' => $betType,
                ]);

                continue;
            }

            ['result_key' => $resultKey, 'draw_key' => $drawKey] = static::BET_TYPE_MAP[$betType];

            if (! isset($resultNumber[$resultKey])) {
                Log::warning('ArchiveNormalizer: result_key not found in result_number', [
                    'draw_id' => $draw->id,
                    'market_code' => $marketCode,
                    'result_key' => $resultKey,
                ]);

                continue;
            }

            $raw = $resultNumber[$resultKey];

            $resultSet = is_array($raw) ? array_map('strval', $raw) : [(string) $raw];

            $rows[] = [
                'market_code' => $marketCode,
                'draw_date' => $drawDate,
                'draw_key' => $drawKey,
                'result_set' => $resultSet,
                'result_hash' => $this->checksum->computeResultHash($resultSet),
            ];
        }

        return $rows;
    }
}
