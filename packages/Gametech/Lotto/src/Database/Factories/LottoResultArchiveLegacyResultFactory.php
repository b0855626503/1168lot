<?php

namespace Gametech\Lotto\Database\Factories;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class LottoResultArchiveLegacyResultFactory extends Factory
{
    protected $model = LottoResultArchiveLegacyResult::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['egx30', 'thai_gov', 'hanoi']);
        $requestDate = fake()->date();
        $sourceResultId = fake()->numberBetween(1000, 999999);

        return [
            'type' => $type,
            'name_th' => fake()->optional()->word(),
            'request_date' => $requestDate,
            'page' => fake()->optional()->numberBetween(1, 10),
            'source_result_id' => $sourceResultId,
            'lottos_name' => strtoupper($type),
            'lottos_th' => fake()->optional()->word(),
            'lottos_date' => fake()->optional()->dateTime(),
            'lottos_date_raw' => fake()->optional()->numerify('##/##/####'),
            'lottos_time' => fake()->optional()->time('H:i'),
            'lottos_number' => fake()->optional()->numerify('######'),
            'lottos_under' => fake()->optional()->numerify('##'),
            'market_code' => fake()->optional()->lexify('??_???'),
            'market_id' => null,
            'source_url' => fake()->optional()->url(),
            'fetched_at' => now(),
            'fetch_status' => 'success',
            'last_error' => null,
            'checksum' => null,
            'payload_json' => null,
            'unique_key' => hash('sha256', implode('|', [$type, $requestDate, $sourceResultId])),
        ];
    }
}
