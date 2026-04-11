<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralLotteryResultEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('failed_requests');
        Schema::create('failed_requests', function (Blueprint $table): void {
            $table->string('trace_id')->primary();
            $table->text('url')->nullable();
            $table->string('method', 16)->nullable();
            $table->longText('headers')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 16)->nullable();
            $table->longText('response')->nullable();
            $table->decimal('duration', 10, 3)->nullable();
            $table->text('txid')->nullable();
            $table->text('roundId')->nullable();
            $table->string('company', 255)->nullable();
            $table->string('game_user', 255)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('lotto_result_sources');
        Schema::dropIfExists('lotto_markets');
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
        });
        Schema::create('lotto_result_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_active')->default(true);
            $table->string('lookup_date_mode')->default('ROUND_DATE');
            $table->integer('lookup_date_offset_days')->default(0);
        });

        // downjone-star uses ROUND_DATE_MINUS_DAYS offset 1 (dowjonestar type)
        DB::table('lotto_markets')->insert(['id' => 38, 'code' => 'downjone-star']);
        DB::table('lotto_result_sources')->insert([
            'market_id' => 38, 'is_active' => 1,
            'lookup_date_mode' => 'ROUND_DATE_MINUS_DAYS', 'lookup_date_offset_days' => 1,
        ]);
    }

    private function getLotteryJson(string $uri)
    {
        return $this->withServerVariables([
            'HTTP_HOST' => 'api.localhost',
        ])->getJson('http://api.localhost'.$uri);
    }

    public function test_get_lottery_returns_central_contract_for_upstream_type(): void
    {
        config()->set('lottery_result_relay.upstream_get_lottery_url', 'http://203.146.127.170/~anan/get_lottery.php');

        Http::fake([
            'http://203.146.127.170/~anan/get_lottery.php*' => Http::response([
                'type' => 'laosvip',
                'nameTH' => 'ลาว VIP',
                'date' => '2026-03-28',
                'page' => 1,
                'count' => 1,
                'results' => [[
                    'lottosName' => 'laosvip',
                    'lottosTH' => 'ลาว VIP',
                    'lottosDate' => '2026-03-27T17:00:00.000Z',
                    'lottosTime' => '21:30',
                    'lottosNumber' => '18413',
                    'lottosUnder' => '84',
                ]],
            ], 200),
        ]);

        $response = $this->getLotteryJson('/api/v1/get_lottery?type=laosvip&date=2026-03-28');

        $response->assertOk();
        $response->assertJson([
            'type' => 'laosvip',
            'nameTH' => 'ลาว VIP',
            'date' => '2026-03-28',
            'page' => 1,
            'count' => 1,
        ]);
        $response->assertJsonPath('results.0.lottosName', 'laosvip');
        $response->assertJsonPath('results.0.lottosTH', 'ลาว VIP');
        $response->assertJsonPath('results.0.lottosDate', '2026-03-27T17:00:00.000Z');
        $response->assertJsonPath('results.0.lottosTime', '21:30');
        $response->assertJsonPath('results.0.lottosNumber', '18413');
        $response->assertJsonPath('results.0.lottosUnder', '84');
    }

    public function test_get_lottery_returns_central_contract_for_internal_type(): void
    {
        Http::fake([
            'https://api.dowjones-midnight.com/result*' => Http::response([
                'status' => true,
                'data' => [
                    'lotto_date' => '2026-03-30',
                    'start_spin' => '00:02',
                    'results' => [
                        'digit5' => '12345',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getLotteryJson('/api/v1/get_lottery?type=dowjones-midnight&date=2026-03-30');

        $response->assertOk();
        $response->assertJson([
            'type' => 'dowjones-midnight',
            'nameTH' => 'ดาวโจนส์เที่ยงคืน',
            'date' => '2026-03-30',
            'page' => 1,
            'count' => 1,
        ]);
        $response->assertJsonPath('results.0.lottosName', 'dowjones-midnight');
        $response->assertJsonPath('results.0.lottosTH', 'ดาวโจนส์เที่ยงคืน');
        $response->assertJsonPath('results.0.lottosDate', '2026-03-29T17:00:00.000Z');
        $response->assertJsonPath('results.0.lottosTime', '00:02');
        $response->assertJsonPath('results.0.lottosNumber', '12345');
        $response->assertJsonPath('results.0.lottosUnder', '12');
    }

    public function test_get_lottery_returns_422_for_unsupported_type(): void
    {
        $response = $this->getLotteryJson('/api/v1/get_lottery?type=unknown-type&date=2026-03-30');

        $response->assertStatus(422);
        $response->assertJson([
            'type' => 'unknown-type',
            'date' => '2026-03-30',
            'page' => 1,
            'count' => 0,
            'results' => [],
        ]);
        $response->assertJsonPath('errors.0.code', 'UNSUPPORTED_TYPE');
    }

    public function test_get_lottery_validates_business_date_format(): void
    {
        $response = $this->getLotteryJson('/api/v1/get_lottery?type=laosvip&date=30-03-2026');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    public function test_get_lottery_applies_lookup_date_offset_for_downjone_star(): void
    {
        config()->set('lottery_result_relay.upstream_get_lottery_url', 'http://203.146.127.170/~anan/get_lottery.php');

        $capturedUrl = null;
        Http::fake([
            'http://203.146.127.170/~anan/get_lottery.php*' => function (Request $request) use (&$capturedUrl) {
                $capturedUrl = $request->url();

                return Http::response([
                    'type' => 'downjone-star',
                    'nameTH' => 'ดาวโจนส์ Star',
                    'date' => '2026-04-10',
                    'page' => 1,
                    'count' => 1,
                    'results' => [[
                        'lottosName' => 'downjone-star',
                        'lottosTH' => 'ดาวโจนส์ Star',
                        'lottosDate' => '2026-04-09T17:00:00.000Z',
                        'lottosTime' => '16:30',
                        'lottosNumber' => '99999',
                        'lottosUnder' => '55',
                    ]],
                ], 200);
            },
        ]);

        // business date = 2026-04-11, but offset -1 → upstream should receive 2026-04-10
        // canonical type is 'dowjonestar' (value in registry), market code is 'downjone-star' (key)
        $this->getLotteryJson('/api/v1/get_lottery?type=dowjonestar&date=2026-04-11');

        $this->assertNotNull($capturedUrl, 'Upstream was never called');
        $this->assertStringContainsString('date=2026-04-10', $capturedUrl, 'Upstream should receive date minus 1 day');
        $this->assertStringNotContainsString('date=2026-04-11', $capturedUrl, 'Upstream must not receive the original business date');
    }
}
