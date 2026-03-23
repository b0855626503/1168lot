<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoDrawStatusChanged;
use Gametech\Lotto\Models\LottoDraw;
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

        $draw->loadMissing('market:id,name');

        $marketName = (string) ($draw->market->name ?? '-');
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $statusLabel = $this->statusLabel($toStatus);
        $actor = $this->resolveActor();
        $changedAt = $this->resolveChangedAt($draw);

        DB::afterCommit(function () use ($draw, $marketName, $drawDate, $toStatus, $statusLabel, $actor, $changedAt): void {
            broadcast(new LottoDrawStatusChanged(
                (int) $draw->id,
                $marketName,
                $drawDate,
                $toStatus,
                $statusLabel,
                $actor,
                $changedAt
            ));
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
}

