<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Observers\LottoDrawRealtimeObserver;
use Gametech\Lotto\Observers\LottoTicketRealtimeObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class LottoRealtimeObserverTest extends TestCase
{
    public function test_ticket_observer_does_not_broadcast_resulted_updates(): void
    {
        $observer = new class extends LottoTicketRealtimeObserver
        {
            public int $ticketListBroadcasts = 0;
            public int $publicActivityBroadcasts = 0;

            protected function resolveTotalTickets(): int
            {
                return 12;
            }

            protected function afterCommit(callable $callback): void
            {
                $callback();
            }

            protected function broadcastTicketListChanged(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void
            {
                $this->ticketListBroadcasts++;
            }

            protected function broadcastPublicActivityUpdated(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void
            {
                $this->publicActivityBroadcasts++;
            }
        };

        $market = new LotteryMarket(['name' => 'หวยมาเลเซีย']);
        $draw = new LottoDraw(['draw_date' => '2026-04-04']);
        $draw->setRelation('market', $market);

        $ticket = new LottoTicket(['status' => 'active']);
        $ticket->setRelation('draw', $draw);
        $ticket->syncOriginal();
        $ticket->status = 'resulted';
        $ticket->syncChanges();

        $observer->updated($ticket);

        $this->assertSame(0, $observer->ticketListBroadcasts);
        $this->assertSame(0, $observer->publicActivityBroadcasts);
    }

    public function test_ticket_observer_still_broadcasts_cancelled_updates(): void
    {
        $observer = new class extends LottoTicketRealtimeObserver
        {
            public array $ticketEvents = [];
            public array $publicEvents = [];

            protected function resolveTotalTickets(): int
            {
                return 12;
            }

            protected function afterCommit(callable $callback): void
            {
                $callback();
            }

            protected function broadcastTicketListChanged(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void
            {
                $this->ticketEvents[] = compact('action', 'total', 'marketName', 'drawDate', 'ownerId', 'actorId', 'amount');
            }

            protected function broadcastPublicActivityUpdated(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void
            {
                $this->publicEvents[] = compact('action', 'total', 'marketName', 'drawDate', 'ownerId', 'actorId', 'amount');
            }
        };

        Schema::create('employees', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('ref_type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        DB::table('employees')->insert([
            'code' => 9001,
            'user_name' => 'staff01',
            'name' => 'Staff 01',
        ]);

        $market = new LotteryMarket(['name' => 'หวยออมสิน']);
        $draw = new LottoDraw(['draw_date' => '2026-04-04']);
        $draw->setRelation('market', $market);

        $member = new \Gametech\Member\Models\Member([
            'user_name' => '0855626503',
            'tel' => '0855626503',
        ]);

        $ticket = new LottoTicket(['id' => 321, 'status' => 'active', 'cancelled_by' => 9001]);
        $ticket->setRelation('draw', $draw);
        $ticket->setRelation('member', $member);
        $ticket->syncOriginal();
        $ticket->status = 'cancelled';
        $ticket->syncChanges();

        DB::table('wallet_transactions')->insert([
            'ref_type' => 'LOTTO_CANCEL',
            'ref_id' => 321,
            'created_by_type' => 'admin',
            'created_by_id' => 9001,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $observer->updated($ticket);

        $this->assertCount(1, $observer->ticketEvents);
        $this->assertSame('cancelled', $observer->ticketEvents[0]['action']);
        $this->assertSame('0855626503', $observer->ticketEvents[0]['ownerId']);
        $this->assertSame('staff01', $observer->ticketEvents[0]['actorId']);
        $this->assertNull($observer->ticketEvents[0]['amount']);
        $this->assertCount(1, $observer->publicEvents);
        $this->assertSame('cancelled', $observer->publicEvents[0]['action']);
        $this->assertSame('0855626503', $observer->publicEvents[0]['ownerId']);
        $this->assertSame('staff01', $observer->publicEvents[0]['actorId']);
        $this->assertNull($observer->publicEvents[0]['amount']);

        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('employees');
    }

    public function test_ticket_observer_created_message_includes_actor_and_amount(): void
    {
        $observer = new class extends LottoTicketRealtimeObserver
        {
            public array $ticketEvents = [];
            public array $publicEvents = [];

            protected function resolveTotalTickets(): int
            {
                return 7;
            }

            protected function afterCommit(callable $callback): void
            {
                $callback();
            }

            protected function broadcastTicketListChanged(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void {
                $this->ticketEvents[] = compact('action', 'total', 'marketName', 'drawDate', 'ownerId', 'actorId', 'amount');
            }

            protected function broadcastPublicActivityUpdated(
                string $action,
                int $total,
                ?string $marketName,
                ?string $drawDate,
                ?string $ownerId,
                ?string $actorId,
                ?float $amount
            ): void {
                $this->publicEvents[] = compact('action', 'total', 'marketName', 'drawDate', 'ownerId', 'actorId', 'amount');
            }
        };

        $market = new LotteryMarket(['name' => 'หวยมาเลเซีย']);
        $draw = new LottoDraw(['draw_date' => '2026-04-05']);
        $draw->setRelation('market', $market);

        $member = new \Gametech\Member\Models\Member([
            'user_name' => '0855626503',
            'tel' => '0855626503',
        ]);

        $ticket = new LottoTicket([
            'status' => 'active',
            'member_id' => 52,
            'total_amount' => 200,
        ]);
        $ticket->setRelation('draw', $draw);
        $ticket->setRelation('member', $member);

        $observer->created($ticket);

        $this->assertCount(1, $observer->ticketEvents);
        $this->assertSame('created', $observer->ticketEvents[0]['action']);
        $this->assertSame('0855626503', $observer->ticketEvents[0]['actorId']);
        $this->assertNull($observer->ticketEvents[0]['ownerId']);
        $this->assertSame(200.0, $observer->ticketEvents[0]['amount']);
        $this->assertCount(1, $observer->publicEvents);
        $this->assertSame('0855626503', $observer->publicEvents[0]['actorId']);
        $this->assertNull($observer->publicEvents[0]['ownerId']);
        $this->assertSame(200.0, $observer->publicEvents[0]['amount']);

        $event = new \App\Events\LottoTicketListChanged('created', 7, 'หวยมาเลเซีย', '2026-04-05', null, '0855626503', 200.0);
        $this->assertSame('มีรายการโพยหวยใหม่: หวยมาเลเซีย งวดวันที่ 2026-04-05 โดย 0855626503 จำนวน 200', $event->message);
    }

    public function test_cancelled_event_message_includes_ticket_owner_and_actor(): void
    {
        $event = new \App\Events\LottoTicketListChanged(
            'cancelled',
            3,
            'หวย ธกส.',
            '2026-04-16',
            'xxx',
            'xxxx',
            null
        );

        $this->assertSame('มีการคืนโพยหวย: หวย ธกส. งวดวันที่ 2026-04-16 ของ xxx โดย xxxx', $event->message);
        $this->assertSame('xxx', $event->broadcastWith()['owner_id']);
        $this->assertSame('xxxx', $event->broadcastWith()['actor_id']);
    }

    public function test_draw_observer_broadcasts_resulted_ticket_notification_once_per_draw(): void
    {
        $observer = new class extends LottoDrawRealtimeObserver
        {
            public int $drawStatusBroadcasts = 0;
            public int $drawActivityBroadcasts = 0;
            public array $ticketResultedEvents = [];
            public array $telegramDispatches = [];

            protected function afterCommit(callable $callback): void
            {
                $callback();
            }

            protected function broadcastDrawStatusChanged(
                int $drawId,
                string $marketName,
                string $drawDate,
                string $status,
                string $statusLabel,
                string $actor,
                string $changedAt
            ): void {
                $this->drawStatusBroadcasts++;
            }

            protected function broadcastDrawPublicActivityUpdated(string $method, string $event, array $data): void
            {
                $this->drawActivityBroadcasts++;
            }

            protected function broadcastResultedTicketListChanged(LottoDraw $draw, string $marketName, string $drawDate): void
            {
                $this->ticketResultedEvents[] = [
                    'draw_id' => (int) $draw->id,
                    'market_name' => $marketName,
                    'draw_date' => $drawDate,
                ];
            }

            protected function dispatchResultSummaryTelegram(int $drawId): void
            {
                $this->telegramDispatches[] = $drawId;
            }
        };

        $market = new LotteryMarket(['name' => 'หวยมาเลเซีย']);
        $draw = new LottoDraw([
            'status' => 'closed',
            'draw_date' => '2026-04-04',
            'updated_at' => Carbon::parse('2026-04-04 23:20:00'),
        ]);
        $draw->id = 548;
        $draw->setRelation('market', $market);
        $draw->syncOriginal();
        $draw->status = 'resulted';
        $draw->updated_at = Carbon::parse('2026-04-04 23:20:01');
        $draw->syncChanges();

        $observer->updated($draw);

        $this->assertSame(1, $observer->drawStatusBroadcasts);
        $this->assertSame(1, $observer->drawActivityBroadcasts);
        $this->assertCount(1, $observer->ticketResultedEvents);
        $this->assertSame(548, $observer->ticketResultedEvents[0]['draw_id']);
        $this->assertSame('หวยมาเลเซีย', $observer->ticketResultedEvents[0]['market_name']);
        $this->assertSame('2026-04-04', $observer->ticketResultedEvents[0]['draw_date']);
        $this->assertSame([548], $observer->telegramDispatches);
    }

    public function test_ticket_observer_total_counts_only_active_tickets(): void
    {
        $this->recreateMinimalLottoTicketsTable();

        DB::table('lotto_tickets')->insert([
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'cancelled'],
            ['id' => 3, 'status' => 'resulted'],
            ['id' => 4, 'status' => 'active'],
        ]);

        $observer = new class extends LottoTicketRealtimeObserver
        {
            public function exposedResolveTotalTickets(): int
            {
                return $this->resolveTotalTickets();
            }
        };

        $this->assertSame(2, $observer->exposedResolveTotalTickets());

        Schema::dropIfExists('lotto_tickets');
    }

    public function test_draw_observer_total_counts_only_active_tickets(): void
    {
        $this->recreateMinimalLottoTicketsTable();

        DB::table('lotto_tickets')->insert([
            ['id' => 11, 'status' => 'active'],
            ['id' => 12, 'status' => 'cancelled'],
            ['id' => 13, 'status' => 'active'],
            ['id' => 14, 'status' => 'resulted'],
            ['id' => 15, 'status' => 'cancelled'],
        ]);

        $observer = new class extends LottoDrawRealtimeObserver
        {
            public function exposedResolveTotalTickets(): int
            {
                return $this->resolveTotalTickets();
            }
        };

        $this->assertSame(2, $observer->exposedResolveTotalTickets());

        Schema::dropIfExists('lotto_tickets');
    }

    private function recreateMinimalLottoTicketsTable(): void
    {
        Schema::dropIfExists('lotto_tickets');

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('status')->nullable();
        });
    }
}
