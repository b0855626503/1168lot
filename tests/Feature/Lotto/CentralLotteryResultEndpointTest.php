<?php

namespace Tests\Feature\Lotto;

use Illuminate\Database\Schema\Blueprint;
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
    }

    private function getLotteryJson(string $uri)
    {
        return $this->withServerVariables([
            'HTTP_HOST' => 'api.localhost',
        ])->getJson('http://api.localhost'.$uri);
    }

    public function test_get_lottery_returns_central_contract_for_exphuay_type(): void
    {
        Http::fake([
            'https://exphuay.com/backward/*' => Http::response($this->fakeExphuayPayload(), 200),
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

    /**
     * @return array<string, mixed>
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
                        [2],
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
                        '2026-03-27T17:00:00.000Z',
                        '21:30',
                        '18413',
                        '84',
                        '2026-03-28T14:30:17.111Z',
                        '2026-03-28T14:30:17.113Z',
                    ],
                ],
            ],
        ];
    }
}
