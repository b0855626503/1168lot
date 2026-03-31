<?php

namespace Tests\Feature\Lotto;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternalResultEndpointsTest extends TestCase
{
    public function test_dowjones_midnight_accepts_legacy_date_format_and_normalizes_draw_date(): void
    {
        Http::fake([
            'https://api.dowjones-midnight.com/result*' => Http::response([
                'status' => true,
                'data' => [
                    'lotto_date' => '2026-03-30',
                    'start_spin' => '00:02',
                    'show_result' => true,
                    'results' => [
                        'digit5' => '12345',
                    ],
                ],
                'now' => '2026-03-30 00:05:00',
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/dowjones-midnight?date=30/03/2026');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-30');
        $response->assertJsonPath('normalized_result.digit_5', '12345');
        $response->assertJsonPath('normalized_result.top_3', '345');
        $response->assertJsonPath('normalized_result.top_2', '45');
        $response->assertJsonPath('normalized_result.bottom_2', '12');
        $response->assertJsonPath('meta.dowjones_supplemental.start_spin', '00:02');
        $response->assertJsonPath('errors', []);
    }

    public function test_invalid_date_returns_canonical_error_shape(): void
    {
        $response = $this->getJson('/internal/lottery/results/dowjones-extra?date=2026/03/30');
        $response->assertOk();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('source', 'dowjones-extra');
        $response->assertJsonPath('errors.0.code', 'INVALID_DATE_FORMAT');
        $response->assertJsonStructure([
            'success',
            'source',
            'type',
            'draw_date',
            'raw_result',
            'normalized_result' => [
                'first_prize',
                'top_3',
                'top_2',
                'bottom_2',
                'digit_4',
                'digit_5',
            ],
            'meta' => [
                'remote_url',
                'request_params',
                'fetched_at',
                'latency_ms',
            ],
            'errors',
        ]);
    }

    public function test_dowjones_extra_uses_result_for_today_without_date_query_and_derives_front_two_digits(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 30, 12, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://api.dowjonesextra.com/result*' => Http::response([
                'status' => true,
                'data' => [
                    'lotto_date' => '2026-03-30',
                    'results' => [
                        'digit5' => '94561',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/dowjones-extra?date=2026-03-30');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('normalized_result.first_prize', '94561');
        $response->assertJsonPath('normalized_result.top_3', '561');
        $response->assertJsonPath('normalized_result.top_2', '61');
        $response->assertJsonPath('normalized_result.bottom_2', '94');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.dowjonesextra.com/result';
        });

        Carbon::setTestNow();
    }

    public function test_dowjones_extra_uses_history_for_past_date_and_selects_matching_draw(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 30, 12, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://api.dowjonesextra.com/history*' => Http::response([
                'status' => 'success',
                'data' => [
                    ['lotto_date' => '2026-03-29', 'results' => ['digit5' => '17450']],
                    ['lotto_date' => '2026-03-28', 'results' => ['digit5' => '01567']],
                    ['lotto_date' => '2026-03-27', 'results' => ['digit5' => '40223']],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/dowjones-extra?date=2026-03-28');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-28');
        $response->assertJsonPath('raw_result.lotto_date', '2026-03-28');
        $response->assertJsonPath('normalized_result.first_prize', '01567');
        $response->assertJsonPath('normalized_result.top_3', '567');
        $response->assertJsonPath('normalized_result.top_2', '67');
        $response->assertJsonPath('normalized_result.bottom_2', '01');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.dowjonesextra.com/history';
        });

        Carbon::setTestNow();
    }

    public function test_dowjones_extra_returns_explicit_error_when_history_date_is_missing(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 30, 12, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://api.dowjonesextra.com/history*' => Http::response([
                'status' => 'success',
                'data' => [
                    ['lotto_date' => '2026-03-29', 'results' => ['digit5' => '17450']],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/dowjones-extra?date=2026-03-28');
        $response->assertOk();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('draw_date', '2026-03-28');
        $response->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');

        Carbon::setTestNow();
    }

    public function test_exphuay_route_forwards_type_page_and_date(): void
    {
        Http::fake([
            'https://exphuay.com/backward/*' => Http::response([
                'some' => 'payload',
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/exphuay/list?date=30-03-2026&page=2');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-30');

        Http::assertSent(function ($request): bool {
            return Str::contains($request->url(), 'https://exphuay.com/backward/list/__data.json')
                && (string) $request['page'] === '2'
                && (string) $request['date'] === '2026-03-30'
                && (string) $request['x-sveltekit-invalidated'] === '01';
        });
    }

    public function test_exphuay_selects_matching_draw_from_payload_and_derives_result_fields(): void
    {
        Http::fake([
            'https://exphuay.com/backward/*' => Http::response($this->fakeExphuayPayload(), 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/exphuay/laosvip?date=2026-03-28&page=1');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-28');
        $response->assertJsonPath('raw_result.lottosNumber', '18413');
        $response->assertJsonPath('raw_result.lottosUnder', '84');
        $response->assertJsonPath('normalized_result.first_prize', '18413');
        $response->assertJsonPath('normalized_result.top_3', '413');
        $response->assertJsonPath('normalized_result.top_2', '13');
        $response->assertJsonPath('normalized_result.bottom_2', '84');
        $response->assertJsonPath('normalized_result.digit_5', '18413');
    }

    public function test_exphuay_matches_previous_local_day_via_upstream_utc_timestamp(): void
    {
        Http::fake([
            'https://exphuay.com/backward/*' => Http::response($this->fakeExphuayPayload(), 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/exphuay/laosvip?date=2026-03-27&page=1');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-27');
        $response->assertJsonPath('raw_result.lottosNumber', '21503');
        $response->assertJsonPath('raw_result.lottosUnder', '15');
        $response->assertJsonPath('normalized_result.top_3', '503');
        $response->assertJsonPath('normalized_result.top_2', '03');
        $response->assertJsonPath('normalized_result.bottom_2', '15');
    }

    public function test_exphuay_returns_explicit_error_when_requested_draw_date_is_missing(): void
    {
        Http::fake([
            'https://exphuay.com/backward/*' => Http::response($this->fakeExphuayPayload(), 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/exphuay/laosvip?date=2026-03-01&page=1');
        $response->assertOk();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('draw_date', '2026-03-01');
        $response->assertJsonPath('errors.0.code', 'DRAW_DATE_NOT_FOUND');
        $response->assertJsonPath('normalized_result.first_prize', null);
    }

    public function test_date_is_optional_and_not_forced_when_request_omits_date(): void
    {
        Http::fake([
            'https://api.dowjones-midnight.com/result*' => Http::response([
                'status' => true,
                'data' => [
                    'lotto_date' => '2026-03-29',
                    'results' => ['digit5' => '98765'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/internal/lottery/results/dowjones-midnight');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draw_date', '2026-03-29');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.dowjones-midnight.com/result';
        });
    }

    public function test_internal_shared_key_is_enforced_when_configured(): void
    {
        config()->set('lotto_auto_result.internal_result_sources.shared_key', 'secret-key');

        $unauthorized = $this->getJson('/internal/lottery/results/dowjones-midnight');
        $unauthorized->assertStatus(401);
        $unauthorized->assertJsonPath('errors.0.code', 'UNAUTHORIZED_INTERNAL_REQUEST');

        Http::fake([
            'https://api.dowjones-midnight.com/result*' => Http::response([
                'status' => true,
                'data' => ['results' => ['digit5' => '12345']],
            ], 200),
        ]);

        $authorized = $this->withHeaders([
            'X-Lotto-Internal-Key' => 'secret-key',
        ])->getJson('/internal/lottery/results/dowjones-midnight?date=2026-03-30');
        $authorized->assertOk();
        $authorized->assertJsonPath('success', true);
    }

    /**
     * Minimal exphuay/Svelte serialized payload with 3 records.
     *
     * @return array<string,mixed>
     */
    private function fakeExphuayPayload(): array
    {
        return [
            'type' => 'data',
            'nodes' => [
                ['type' => 'skip'],
                [
                    'type' => 'data',
                    'data' => [
                        ['result' => 1],
                        [2, 13, 19],
                        [
                            'id' => 3,
                            'lottosType' => 4,
                            'lottosFlag' => 4,
                            'lottosName' => 5,
                            'lottosTH' => 6,
                            'lottosDate' => 7,
                            'lottosTime' => 8,
                            'lottosNumber' => 9,
                            'lottosUnder' => 10,
                            'logTime' => 11,
                            'createdAt' => 12,
                            'updatedAt' => 12,
                        ],
                        113613,
                        'laos',
                        'laosvip',
                        'ลาว VIP',
                        '2026-03-28T17:00:00.000Z',
                        '21:30',
                        '69701',
                        '97',
                        '2026-03-29T14:30:14.534Z',
                        '2026-03-29T14:30:14.535Z',
                        [
                            'id' => 3,
                            'lottosType' => 4,
                            'lottosFlag' => 4,
                            'lottosName' => 5,
                            'lottosTH' => 6,
                            'lottosDate' => 14,
                            'lottosTime' => 8,
                            'lottosNumber' => 15,
                            'lottosUnder' => 16,
                            'logTime' => 17,
                            'createdAt' => 18,
                            'updatedAt' => 18,
                        ],
                        '2026-03-27T17:00:00.000Z',
                        '18413',
                        '84',
                        '2026-03-28T14:30:17.111Z',
                        '2026-03-28T14:30:17.113Z',
                        [
                            'id' => 3,
                            'lottosType' => 4,
                            'lottosFlag' => 4,
                            'lottosName' => 5,
                            'lottosTH' => 6,
                            'lottosDate' => 20,
                            'lottosTime' => 8,
                            'lottosNumber' => 21,
                            'lottosUnder' => 22,
                            'logTime' => 23,
                            'createdAt' => 24,
                            'updatedAt' => 24,
                        ],
                        '2026-03-26T17:00:00.000Z',
                        '21503',
                        '15',
                        '2026-03-27T14:30:16.684Z',
                        '2026-03-27T14:30:16.686Z',
                    ],
                ],
            ],
        ];
    }
}
