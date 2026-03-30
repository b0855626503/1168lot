<?php

namespace Tests\Feature\Lotto;

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
}
