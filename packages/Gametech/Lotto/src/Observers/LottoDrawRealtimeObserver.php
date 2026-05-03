<?php

namespace Gametech\Lotto\Observers;

use App\Events\LottoDrawStatusChanged;
use App\Events\LottoTicketListChanged;
use App\Events\RealtimePublicActivityUpdated;
use Gametech\Lotto\Jobs\SendDrawResultSummaryTelegramJob;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $draw->loadMissing('market:id,name,result_mode,notify_result_telegram');

        $marketName = (string) ($draw->market->name ?? '-');
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $resultMode = (string) ($draw->market->result_mode ?? '');
        $roundNo = $this->resolveYeekeeRoundNo($draw, $resultMode);
        $statusLabel = $this->statusLabel($toStatus);
        $actor = $this->resolveActor();
        $changedAt = $this->resolveChangedAt($draw);

        $this->afterCommit(function () use ($draw, $marketName, $drawDate, $resultMode, $roundNo, $toStatus, $statusLabel, $actor, $changedAt): void {
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
                    'result_mode' => $resultMode,
                    'round_no' => $roundNo,
                    'status' => $toStatus,
                    'status_label' => $statusLabel,
                    'actor' => $actor,
                    'changed_at' => $changedAt,
                ]
            );

            if ($toStatus === 'resulted') {
                $this->broadcastResultedTicketListChanged($draw, $marketName, $drawDate, $resultMode, $roundNo);
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
     * @param  array<string,mixed>  $data
     */
    protected function broadcastDrawPublicActivityUpdated(string $method, string $event, array $data): void
    {
        $message = $this->buildDrawActivityMessage(
            $event,
            (string) ($data['market_name'] ?? '-'),
            (string) ($data['draw_date'] ?? '-'),
            (string) ($data['result_mode'] ?? ''),
            isset($data['round_no']) ? (int) $data['round_no'] : null,
        );

        broadcast(new RealtimePublicActivityUpdated($method, $event, $data, $message));
    }

    protected function broadcastResultedTicketListChanged(
        LottoDraw $draw,
        string $marketName,
        string $drawDate,
        string $resultMode = '',
        ?int $roundNo = null
    ): void {
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
                'result_mode' => $resultMode,
                'round_no' => $roundNo,
                'actor_id' => null,
                'amount' => null,
            ],
            $this->buildTicketListActivityMessage('resulted', $marketName, $drawDate, null, null, $resultMode, $roundNo)
        ));
    }

    private function buildDrawActivityMessage(
        string $event,
        string $marketName,
        string $drawDate,
        string $resultMode = '',
        ?int $roundNo = null
    ): string {
        $suffix = match ($event) {
            'lotto.draw_closed' => 'ปิดรับแล้ว',
            'lotto.draw_resulted' => 'ออกผลแล้ว',
            default => 'เปิดรับแล้ว',
        };

        return (new LottoMarketDisplayFormatter)->formatStatusMessage(
            $marketName,
            $drawDate,
            $suffix,
            $resultMode !== '' ? $resultMode : null,
            $roundNo,
        );
    }

    private function buildTicketListActivityMessage(
        string $action,
        string $marketName,
        string $drawDate,
        ?string $actorId,
        ?float $amount,
        string $resultMode = '',
        ?int $roundNo = null
    ): string {
        $formatter = new LottoMarketDisplayFormatter;
        $rm = $resultMode !== '' ? $resultMode : null;

        if ($action === 'resulted') {
            return $formatter->formatStatusMessage($marketName, $drawDate, 'อัปเดตรายการโพยหลังออกผลแล้ว', $rm, $roundNo);
        }

        if ($action === 'created') {
            $subject = $formatter->formatDrawSubject($marketName, $drawDate, $rm, $roundNo);
            $message = "มีรายการโพยหวยใหม่: {$subject}";

            if ($actorId !== null && $actorId !== '') {
                $message .= " โดย {$actorId}";
            }

            if ($amount !== null) {
                $message .= ' จำนวน '.number_format($amount, 2, '.', '');
            }

            return $message;
        }

        $subject = $formatter->formatDrawSubject($marketName, $drawDate, $rm, $roundNo);

        return "มีการอัปเดตรายการโพยหวย: {$subject}";
    }

    private function resolveYeekeeRoundNo(LottoDraw $draw, string $resultMode): ?int
    {
        if ($resultMode !== LotteryMarket::RESULT_MODE_YEEKEE) {
            return null;
        }

        $roundNo = (int) YeekeeRound::query()
            ->where('lotto_draw_id', (int) $draw->id)
            ->value('round_no');

        if ($roundNo <= 0) {
            Log::warning('yeekee draw missing round_no', ['draw_id' => (int) $draw->id]);

            return null;
        }

        return $roundNo;
    }

    protected function dispatchResultSummaryTelegram(int $drawId): void
    {
        SendDrawResultSummaryTelegramJob::dispatch($drawId)
            ->delay(now()->addSeconds(2))
            ->onQueue('cashback');
    }

    protected function resolveTotalTickets(): int
    {
        return (int) LottoTicket::query()
            ->where('status', 'active')
            ->count();
    }
}
