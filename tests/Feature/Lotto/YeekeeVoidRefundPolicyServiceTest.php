<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\YeekeeVoidRefundPolicyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YeekeeVoidRefundPolicyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        parent::tearDown();
    }

    public function test_all_ticket_items_mode_counts_all_rows(): void
    {
        $result = app(YeekeeVoidRefundPolicyService::class)->evaluateNumberThreshold(
            101,
            '2_bottom',
            '09',
            3,
            'all_ticket_items_by_number'
        );

        $this->assertSame(3, $result['count']);
        $this->assertFalse($result['should_void']);
    }

    public function test_unique_members_mode_counts_distinct_members(): void
    {
        $result = app(YeekeeVoidRefundPolicyService::class)->evaluateNumberThreshold(
            101,
            '2_bottom',
            '09',
            3,
            'unique_members_by_number'
        );

        $this->assertSame(2, $result['count']);
        $this->assertTrue($result['should_void']);
    }

    private function prepareSchema(): void
    {
        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->enum('status', ['active', 'cancelled', 'resulted'])->default('active');
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedData(): void
    {
        DB::table('lotto_tickets')->insert([
            ['id' => 1, 'member_id' => 1001, 'draw_id' => 101, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'member_id' => 1001, 'draw_id' => 101, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'member_id' => 1002, 'draw_id' => 101, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('lotto_ticket_items')->insert([
            ['ticket_id' => 1, 'bet_type' => '2_bottom', 'number' => '09', 'amount' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['ticket_id' => 2, 'bet_type' => '2_bottom', 'number' => '09', 'amount' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['ticket_id' => 3, 'bet_type' => '2_bottom', 'number' => '09', 'amount' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
