<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoTicketsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        parent::tearDown();
    }

    public function test_tickets_endpoint_returns_clear_result_fields(): void
    {
        $member = $this->customer();
        $this->seedBaseData();

        $request = Request::create('/api/v1/lotto/tickets', 'GET');
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);

        $response = TestResponse::fromBaseResponse(
            app(LottoController::class)->tickets($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'data');

        $response->assertJsonFragment([
            'id' => 1001,
            'status' => 'resulted',
            'status_label' => 'ตัดสินผลแล้ว',
            'draw_status' => 'resulted',
            'draw_status_label' => 'ออกผลแล้ว',
            'result_outcome' => 'won',
            'result_outcome_label' => 'ถูกรางวัล',
            'is_final' => true,
            'is_winner' => true,
            'item_count' => 2,
            'winning_item_count' => 1,
            'losing_item_count' => 1,
            'pending_item_count' => 0,
            'total_win_amount' => 540.0,
            'result_message' => 'โพยนี้ถูกรางวัล 540.00 บาท',
        ]);

        $response->assertJsonFragment([
            'id' => 1002,
            'status' => 'active',
            'status_label' => 'ใช้งานอยู่',
            'draw_status' => 'closed',
            'draw_status_label' => 'รอผล',
            'result_outcome' => 'pending_result',
            'result_outcome_label' => 'รอผล',
            'is_final' => false,
            'is_winner' => false,
            'item_count' => 1,
            'winning_item_count' => 0,
            'losing_item_count' => 0,
            'pending_item_count' => 1,
            'result_message' => 'โพยนี้กำลังรอผล',
        ]);
    }

    public function test_ticket_detail_endpoint_returns_clear_ticket_and_item_result_fields(): void
    {
        $member = $this->customer();
        $this->seedBaseData();

        $request = Request::create('/api/v1/lotto/tickets/1001', 'GET');
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);

        $response = TestResponse::fromBaseResponse(
            app(LottoController::class)->ticket($request, 1001)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', 1001);
        $response->assertJsonPath('data.result_outcome', 'won');
        $response->assertJsonPath('data.result_outcome_label', 'ถูกรางวัล');
        $response->assertJsonPath('data.result_message', 'โพยนี้ถูกรางวัล 540.00 บาท');
        $response->assertJsonPath('data.draw_status', 'resulted');
        $response->assertJsonPath('data.draw_status_label', 'ออกผลแล้ว');
        $response->assertJsonPath('data.status_label', 'ตัดสินผลแล้ว');
        $response->assertJsonPath('data.items.0.result_status', 'win');
        $response->assertJsonPath('data.items.0.raw_result_status', 'win');
        $response->assertJsonPath('data.items.0.result_status_label', 'ถูกรางวัล');
        $response->assertJsonPath('data.items.0.result_message', 'รายการนี้ถูกรางวัล 450.00 บาท');
        $response->assertJsonPath('data.items.0.is_winner', true);
        $response->assertJsonPath('data.items.1.result_status', 'lose');
        $response->assertJsonPath('data.items.1.raw_result_status', 'lose');
        $response->assertJsonPath('data.items.1.result_status_label', 'ไม่ถูกรางวัล');
        $response->assertJsonPath('data.items.1.result_message', 'รายการนี้ไม่ถูกรางวัล');
        $response->assertJsonPath('data.items.1.is_winner', false);
    }

    private function customer(int $memberCode = 9001): Member
    {
        $member = new Member();
        $member->code = $memberCode;
        $member->name = 'Ticket Member';
        $member->exists = true;

        return $member;
    }

    private function seedBaseData(): void
    {
        \DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'หวยไทย',
            'name_en' => 'Thai Lotto',
        ]);

        \DB::table('lotto_markets')->insert([
            'id' => 10,
            'group_id' => 1,
            'name' => 'หวยออมสิน',
            'name_en' => 'GSB Lotto',
            'logo' => '/storage/lotto/markets/gsb-logo.png',
            'icon' => '/storage/lotto/markets/gsb-icon.png',
            'is_enabled' => 1,
        ]);

        \DB::table('lotto_draws')->insert([
            [
                'id' => 101,
                'market_id' => 10,
                'draw_date' => '2026-04-05',
                'result_at' => '2026-04-05 16:00:00',
                'status' => 'resulted',
                'result_number' => json_encode([
                    'first_prize' => '123450',
                    'top_3' => '450',
                    'last_2_digits' => '45',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 102,
                'market_id' => 10,
                'draw_date' => '2026-04-06',
                'result_at' => '2026-04-06 16:00:00',
                'status' => 'closed',
                'result_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \DB::table('lotto_tickets')->insert([
            [
                'id' => 1001,
                'member_id' => 9001,
                'draw_id' => 101,
                'total_amount' => 90,
                'total_bet_amount' => 100,
                'total_discount_amount' => 10,
                'total_net_amount' => 90,
                'total_win_amount' => 540,
                'status' => 'resulted',
                'refund_amount' => 0,
                'created_at' => '2026-04-05 12:00:00',
                'updated_at' => now(),
            ],
            [
                'id' => 1002,
                'member_id' => 9001,
                'draw_id' => 102,
                'total_amount' => 50,
                'total_bet_amount' => 50,
                'total_discount_amount' => 0,
                'total_net_amount' => 50,
                'total_win_amount' => 0,
                'status' => 'active',
                'refund_amount' => 0,
                'created_at' => '2026-04-06 12:00:00',
                'updated_at' => now(),
            ],
        ]);

        \DB::table('lotto_ticket_items')->insert([
            [
                'id' => 1,
                'ticket_id' => 1001,
                'bet_type' => 'top_3',
                'number' => '450',
                'amount' => 50,
                'payout_at_time' => 9,
                'discount_percent_at_time' => 10,
                'discount_amount_at_time' => 5,
                'payable_amount_at_time' => 45,
                'potential_win_amount_at_time' => 450,
                'result_status' => 'win',
                'win_amount' => 450,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'ticket_id' => 1001,
                'bet_type' => 'run_bottom',
                'number' => '5',
                'amount' => 10,
                'payout_at_time' => 9,
                'discount_percent_at_time' => 0,
                'discount_amount_at_time' => 0,
                'payable_amount_at_time' => 10,
                'potential_win_amount_at_time' => 90,
                'result_status' => 'lose',
                'win_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'ticket_id' => 1002,
                'bet_type' => 'bottom_2',
                'number' => '45',
                'amount' => 50,
                'payout_at_time' => 90,
                'discount_percent_at_time' => 0,
                'discount_amount_at_time' => 0,
                'payable_amount_at_time' => 50,
                'potential_win_amount_at_time' => 4500,
                'result_status' => null,
                'win_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_kh')->nullable();
            $table->string('name_laos')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('result_number')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_bet_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->decimal('discount_percent_at_time', 12, 2)->default(0);
            $table->decimal('discount_amount_at_time', 12, 2)->default(0);
            $table->decimal('payable_amount_at_time', 12, 2)->default(0);
            $table->decimal('potential_win_amount_at_time', 12, 2)->default(0);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }
}
