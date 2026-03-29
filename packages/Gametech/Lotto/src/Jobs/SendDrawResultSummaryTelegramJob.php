<?php

namespace Gametech\Lotto\Jobs;

use App\Jobs\SendTelegramBot;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Bus\Queueable;
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
    ) {
    }

    public function handle(): void
    {
        $draw = LottoDraw::query()
            ->with('market:id,name,notify_result_telegram')
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
        $marketName = (string) ($draw->market->name ?? ('Market #' . (int) $draw->market_id));
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $result = is_array($draw->result_number) ? $draw->result_number : [];
        $firstPrize = preg_replace('/\D+/', '', (string) ($result['first_prize'] ?? ''));
        $last2Digits = preg_replace('/\D+/', '', (string) ($result['last_2_digits'] ?? ''));
        $is3DigitMarket = strlen((string) $firstPrize) <= 3;
        $firstLabel = $is3DigitMarket ? '3 ตัวบน' : 'รางวัลที่ 1';
        $lastLabel = $is3DigitMarket ? '2 ตัวล่าง' : 'เลข 2 ตัว';

        $netAmount = (float) ($summary['net_amount'] ?? 0);
        $netEmoji = $netAmount >= 0 ? '🟢' : '🔴';
        $netPrefix = $netAmount >= 0 ? '+' : '-';

        return sprintf(
            '🚨 ออกผลแล้ว! หวย%s' . PHP_EOL .
            'งวดวันที่ %s' . PHP_EOL . PHP_EOL .
            '🎯 %s: %s' . PHP_EOL .
            '🎯 %s: %s' . PHP_EOL . PHP_EOL .
            '━━━━━━━━━━━━━━━' . PHP_EOL .
            '📊 สรุปทันที' . PHP_EOL . PHP_EOL .
            '• บิลทั้งหมด: %s' . PHP_EOL .
            '• ชนะ: %s | 💰 %s บาท' . PHP_EOL .
            '• แพ้: %s | 💸 %s บาท' . PHP_EOL . PHP_EOL .
            '━━━━━━━━━━━━━━━' . PHP_EOL .
            '💵 กำไร/ขาดทุนสุทธิ: %s %s%s บาท',
            $marketName,
            $drawDate,
            $firstLabel,
            $firstPrize !== '' ? $firstPrize : '-',
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
