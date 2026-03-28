<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoDrawStatusChanged;
use App\Events\RealtimePublicActivityUpdated;
use App\Jobs\SendTelegramBot;
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

        $draw->loadMissing('market:id,name,notify_result_telegram');

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

            $activityEvent = 'lotto.draw_status_changed';
            if ($toStatus === 'closed') {
                $activityEvent = 'lotto.draw_closed';
            } elseif ($toStatus === 'resulted') {
                $activityEvent = 'lotto.draw_resulted';
            }

            broadcast(new RealtimePublicActivityUpdated(
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
            ));

            if ($toStatus === 'resulted') {
                $this->sendResultTelegramIfEnabled($draw, $marketName, $drawDate);
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

    private function sendResultTelegramIfEnabled(LottoDraw $draw, string $marketName, string $drawDate): void
    {
        $market = $draw->market;
        if ($market && ! (bool) ($market->notify_result_telegram ?? true)) {
            return;
        }

        $result = is_array($draw->result_number) ? $draw->result_number : [];
        $firstPrize = preg_replace('/\D+/', '', (string) ($result['first_prize'] ?? ''));
        $last2 = preg_replace('/\D+/', '', (string) ($result['last_2_digits'] ?? ''));
        $firstLabel = strlen((string) $firstPrize) <= 3 ? 'เลข 3 ตัวบน' : 'รางวัลที่ 1';
        $lastLabel = strlen((string) $firstPrize) <= 3 ? 'เลข 2 ตัวล่าง' : 'รางวัล เลข 2 ตัว';

        $message = sprintf(
            'หวย%s งวดวันที่ %s' . PHP_EOL .
            '%s : %s' . PHP_EOL .
            '%s : %s' . PHP_EOL .
            'คำนวนเงินรางวัลแล้ว',
            $marketName,
            $drawDate,
            $firstLabel,
            $firstPrize !== '' ? $firstPrize : '-',
            $lastLabel,
            $last2 !== '' ? $last2 : '-'
        );

        SendTelegramBot::dispatch('notify/send', $message)->onQueue('cashback');
    }
}
