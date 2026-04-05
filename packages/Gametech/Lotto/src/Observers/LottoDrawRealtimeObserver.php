<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoTicketListChanged;
use App\Events\LottoDrawStatusChanged;
use App\Events\RealtimePublicActivityUpdated;
use Gametech\Lotto\Jobs\SendDrawResultSummaryTelegramJob;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LottoDrawRealtimeObserver
{
    public function updated(LottoDraw $draw): void
    {
        if (! $draw->wasChanged('status')) {
            return;
        }

        $fromStatus = (string) $draw->getOriginal('status');
        $toStatus = (string) $draw->status;

        if ($fromStatus === $toStatus) {
            return;
        }

        if (! $this->shouldBroadcast($fromStatus, $toStatus)) {
            return;
        }

        $draw->loadMissing('market:id,name,notify_result_telegram');

        $marketName = (string) ($draw->market->name ?? '-');
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $statusLabel = $this->statusLabel($toStatus);
        $actor = $this->resolveActor();
        $changedAt = $this->resolveChangedAt($draw);

        $this->afterCommit(function () use ($draw, $marketName, $drawDate, $toStatus, $statusLabel, $actor, $changedAt): void {
            $this->broadcastDrawStatusChanged(
                (int) $draw->id,
                $marketName,
                $drawDate,
                $toStatus,
                $statusLabel,
                $actor,
                $changedAt
            );

            $activityEvent = 'lotto.draw_status_changed';
            if ($toStatus === 'closed') {
                $activityEvent = 'lotto.draw_closed';
            } elseif ($toStatus === 'resulted') {
                $activityEvent = 'lotto.draw_resulted';
            }

            $this->broadcastDrawPublicActivityUpdated(
                'lotto',
                $activityEvent,
                [
                    'draw_id' => (int) $draw->id,
                    'market_name' => $marketName,
                    'draw_date' => $drawDate,
                    'status' => $toStatus,
                    'status_label' => $statusLabel,
                    'actor' => $actor,
                    'changed_at' => $changedAt,
                ]
            );

            if ($toStatus === 'resulted') {
                $this->broadcastResultedTicketListChanged($draw, $marketName, $drawDate);
                $this->dispatchResultSummaryTelegram((int) $draw->id);
            }
        });
    }

    private function shouldBroadcast(string $fromStatus, string $toStatus): bool
    {
        if ($toStatus === 'closed') {
            return true;
        }

        if ($toStatus === 'resulted') {
            return true;
        }

        return $fromStatus === 'closed' && $toStatus === 'open';
    }

    private function statusLabel(string $status): string
    {
        if ($status === 'open') {
            return 'เปิดรับ';
        }

        if ($status === 'closed') {
            return 'ปิดรับ';
        }

        if ($status === 'resulted') {
            return 'ออกผล';
        }

        return $status;
    }

    private function resolveActor(): string
    {
        $admin = Auth::guard('admin')->user();
        if ($admin) {
            return (string) ($admin->user_name ?? $admin->username ?? $admin->name ?? $admin->code ?? 'SYSTEM');
        }

        $user = Auth::user();
        if ($user) {
            return (string) ($user->user_name ?? $user->username ?? $user->name ?? $user->code ?? 'SYSTEM');
        }

        return 'SYSTEM';
    }

    private function resolveChangedAt(LottoDraw $draw): string
    {
        $time = $draw->updated_at instanceof Carbon ? $draw->updated_at : now();

        return $time
            ->copy()
            ->timezone((string) config('app.timezone', 'Asia/Bangkok'))
            ->format('Y-m-d H:i:s');
    }

    protected function afterCommit(callable $callback): void
    {
        DB::afterCommit($callback);
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
        broadcast(new LottoDrawStatusChanged(
            $drawId,
            $marketName,
            $drawDate,
            $status,
            $statusLabel,
            $actor,
            $changedAt
        ));
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function broadcastDrawPublicActivityUpdated(string $method, string $event, array $data): void
    {
        broadcast(new RealtimePublicActivityUpdated($method, $event, $data));
    }

    protected function broadcastResultedTicketListChanged(LottoDraw $draw, string $marketName, string $drawDate): void
    {
        $total = $this->resolveTotalTickets();

        broadcast(new LottoTicketListChanged('resulted', $total, $marketName, $drawDate));
        broadcast(new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.ticket.list.changed',
            [
                'action' => 'resulted',
                'total' => $total,
                'market_name' => $marketName,
                'draw_date' => $drawDate,
                'actor_id' => null,
                'amount' => null,
            ]
        ));
    }

    protected function dispatchResultSummaryTelegram(int $drawId): void
    {
        SendDrawResultSummaryTelegramJob::dispatch($drawId)
            ->delay(now()->addSeconds(2))
            ->onQueue('cashback');
    }

    protected function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()->count();
    }
}
