<?php

namespace Gametech\Lotto\Jobs;

use App\Jobs\SendTelegramBot;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendDrawResultSummaryTelegramJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 15, 30];

    public function __construct(
        public int $drawId
    ) {}

    public function handle(): void
    {
        $draw = LottoDraw::query()
            ->with([
                'market:id,name,notify_result_telegram,auto_result_time,result_mode',
                'yeekeeRound:id,lotto_draw_id,round_no',
            ])
            ->find($this->drawId);

        if (! $draw instanceof LottoDraw) {
            return;
        }

        if ((string) $draw->status !== 'resulted') {
            return;
        }

        $market = $draw->market;
        if ($market && ! (bool) ($market->notify_result_telegram ?? true)) {
            return;
        }

        if (! $this->claimNotificationSlot($draw->id)) {
            return;
        }

        $summary = $this->buildSummary($draw->id);
        $message = $this->formatMessage($draw, $summary);

        SendTelegramBot::dispatch('notify/send', $message)->onQueue('cashback');
    }

    /**
     * @return array<string,float|int>
     */
    private function buildSummary(int $drawId): array
    {
        $base = LottoTicket::query()
            ->where('draw_id', $drawId)
            ->where('status', '!=', 'cancelled');

        $ticketCount = (int) (clone $base)->count();
        $winningTicketCount = (int) (clone $base)
            ->where('total_win_amount', '>', 0)
            ->count();
        $losingTicketCount = max(0, $ticketCount - $winningTicketCount);

        $totalWinAmount = (float) ((clone $base)->sum('total_win_amount') ?: 0);
        $totalLoseAmount = (float) ((clone $base)
            ->where(function ($q): void {
                $q->whereNull('total_win_amount')
                    ->orWhere('total_win_amount', '<=', 0);
            })
            ->sum('total_amount') ?: 0);

        return [
            'ticket_count' => $ticketCount,
            'winning_ticket_count' => $winningTicketCount,
            'losing_ticket_count' => $losingTicketCount,
            'total_win_amount' => round($totalWinAmount, 2),
            'total_lose_amount' => round($totalLoseAmount, 2),
            'net_amount' => round($totalLoseAmount - $totalWinAmount, 2),
        ];
    }

    private function formatMessage(LottoDraw $draw, array $summary): string
    {
        $marketName = (string) ($draw->market->name ?? ('Market #'.(int) $draw->market_id));
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $resultMode = (string) ($draw->market->result_mode ?? '');
        $roundNo = $resultMode === LotteryMarket::RESULT_MODE_YEEKEE
            ? (int) ($draw->yeekeeRound->round_no ?? 0)
            : 0;
        $roundLabel = $roundNo > 0 ? " รอบที่ {$roundNo}" : '';
        $scheduledResultTime = $this->resolveScheduledResultTime($draw);
        [$announceOriginLabel, $announceTime] = $this->resolveAnnounceOriginAndTime($draw);
        $result = is_array($draw->result_number) ? $draw->result_number : [];
        $isNoResult = (bool) ($result['no_result'] ?? false)
            || (string) ($result['status'] ?? '') === 'no_result';
        $firstPrize = preg_replace('/\D+/', '', (string) ($result['first_prize'] ?? ''));
        $top3 = $isNoResult
            ? 'งดออกผล'
            : (strlen($firstPrize) >= 3 ? substr($firstPrize, -3) : '');
        $last2Digits = preg_replace('/\D+/', '', (string) ($result['last_2_digits'] ?? ''));
        if ($isNoResult) {
            $last2Digits = 'งดออกผล';
        }
        $firstLabel = '3 ตัวบน';
        $lastLabel = '2 ตัวล่าง';

        $netAmount = (float) ($summary['net_amount'] ?? 0);
        $netEmoji = $netAmount >= 0 ? '🟢' : '🔴';
        $netPrefix = $netAmount >= 0 ? '+' : '-';

        return sprintf(
            '🚨 ออกผลแล้ว! หวย%s%s'.PHP_EOL.
            'งวดวันที่ %s เวลาออกผล %s'.PHP_EOL.PHP_EOL.
            '🕒 ออกผลโดย%s เวลา %s'.PHP_EOL.PHP_EOL.
            '🎯 %s: %s'.PHP_EOL.
            '🎯 %s: %s'.PHP_EOL.PHP_EOL.
            '━━━━━━━━━━━━━━━'.PHP_EOL.
            '📊 สรุปทันที'.PHP_EOL.PHP_EOL.
            '• บิลทั้งหมด: %s'.PHP_EOL.
            '• ชนะ: %s | 💰 %s บาท'.PHP_EOL.
            '• แพ้: %s | 💸 %s บาท'.PHP_EOL.PHP_EOL.
            '━━━━━━━━━━━━━━━'.PHP_EOL.
            '💵 กำไร/ขาดทุนสุทธิ: %s %s%s บาท',
            $marketName,
            $roundLabel,
            $drawDate,
            $scheduledResultTime,
            $announceOriginLabel,
            $announceTime,
            $firstLabel,
            $top3 !== '' ? $top3 : '-',
            $lastLabel,
            $last2Digits !== '' ? $last2Digits : '-',
            number_format((float) ($summary['ticket_count'] ?? 0), 0),
            number_format((float) ($summary['winning_ticket_count'] ?? 0), 0),
            number_format((float) ($summary['total_win_amount'] ?? 0), 2),
            number_format((float) ($summary['losing_ticket_count'] ?? 0), 0),
            number_format((float) ($summary['total_lose_amount'] ?? 0), 2),
            $netEmoji,
            $netPrefix,
            number_format(abs($netAmount), 2),
        );
    }

    private function resolveScheduledResultTime(LottoDraw $draw): string
    {
        $autoResultTime = trim((string) ($draw->market->auto_result_time ?? ''));
        if ($autoResultTime !== '') {
            return substr($autoResultTime, 0, 5);
        }

        if ($draw->result_at) {
            return $draw->result_at->format('H:i');
        }

        return now($this->resolveTimezone())->format('H:i');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveAnnounceOriginAndTime(LottoDraw $draw): array
    {
        $result = is_array($draw->result_number) ? $draw->result_number : [];

        $isAuto = false;
        if ((int) ($draw->result_source_id ?? 0) > 0) {
            $isAuto = true;
        }

        if ((string) ($draw->result_fetch_status ?? '') === 'APPLIED' && (int) ($draw->result_source_id ?? 0) > 0) {
            $isAuto = true;
        }

        if ((bool) ($result['manual_marked_no_result'] ?? false)) {
            $isAuto = false;
        }

        $originLabel = $isAuto ? 'ระบบออโต้' : 'ทีมงาน';
        $announceAt = $draw->result_at ?: $draw->result_applied_at;
        $announceTime = $announceAt
            ? $announceAt->format('H:i')
            : now($this->resolveTimezone())->format('H:i');

        return [$originLabel, $announceTime];
    }

    private function resolveTimezone(): string
    {
        $container = Container::getInstance();
        if ($container && $container->bound('config')) {
            return (string) $container->make('config')->get('app.timezone', 'Asia/Bangkok');
        }

        return (string) (date_default_timezone_get() ?: 'Asia/Bangkok');
    }

    private function claimNotificationSlot(int $drawId): bool
    {
        if (! Schema::hasColumn('lotto_draws', 'telegram_sent_at')) {
            return true;
        }

        return DB::table('lotto_draws')
            ->where('id', $drawId)
            ->where('status', 'resulted')
            ->whereNull('telegram_sent_at')
            ->update([
                'telegram_sent_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }
}
