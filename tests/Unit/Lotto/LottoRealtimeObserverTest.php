<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Observers\LottoDrawRealtimeObserver;
use Gametech\Lotto\Observers\LottoTicketRealtimeObserver;
use Illuminate\Support\Carbon;
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

            protected function broadcastTicketListChanged(string $action, int $total, ?string $marketName, ?string $drawDate): void
            {
                $this->ticketListBroadcasts++;
            }

            protected function broadcastPublicActivityUpdated(string $action, int $total, ?string $marketName, ?string $drawDate): void
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

            protected function broadcastTicketListChanged(string $action, int $total, ?string $marketName, ?string $drawDate): void
            {
                $this->ticketEvents[] = compact('action', 'total', 'marketName', 'drawDate');
            }

            protected function broadcastPublicActivityUpdated(string $action, int $total, ?string $marketName, ?string $drawDate): void
            {
                $this->publicEvents[] = compact('action', 'total', 'marketName', 'drawDate');
            }
        };

        $market = new LotteryMarket(['name' => 'หวยออมสิน']);
        $draw = new LottoDraw(['draw_date' => '2026-04-04']);
        $draw->setRelation('market', $market);

        $ticket = new LottoTicket(['status' => 'active']);
        $ticket->setRelation('draw', $draw);
        $ticket->syncOriginal();
        $ticket->status = 'cancelled';
        $ticket->syncChanges();

        $observer->updated($ticket);

        $this->assertCount(1, $observer->ticketEvents);
        $this->assertSame('cancelled', $observer->ticketEvents[0]['action']);
        $this->assertCount(1, $observer->publicEvents);
        $this->assertSame('cancelled', $observer->publicEvents[0]['action']);
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
}
