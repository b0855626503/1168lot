<?php

namespace Gametech\Lotto\Services\AutoResult;

use App\Jobs\SendTelegramBot;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\SettlementService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResultApplier
{
    public function __construct(
        private SettlementService $settlementService
    ) {
    }

    /**
     * @param array{first_prize:string,last_2_digits:string} $validated
     * @param array<string,mixed> $rawPayload
     * @return array<string,mixed>
     */
    public function apply(LottoDraw $draw, array $validated, array $rawPayload, bool $dryRun = false): array
    {
        $resultHash = $this->computeHash($validated);

        if ($dryRun) {
            return [
                'status' => 'APPLIED',
                'result_hash' => $resultHash,
                'validated' => $validated,
            ];
        }

        return DB::transaction(function () use ($draw, $validated, $rawPayload, $resultHash) {
            $locked = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            if ((string) $locked->status === 'resulted') {
                $existingHash = (string) ($locked->result_hash ?? '');
                if ($existingHash !== '' && $existingHash !== $resultHash) {
                    $locked->forceFill([
                        'result_fetch_status' => 'CONFLICT',
                        'result_conflicted_at' => now(),
                        'result_conflict_payload_json' => $rawPayload,
                        'result_fetch_error' => 'Result hash conflict detected',
                    ])->save();

                    return [
                        'status' => 'CONFLICT',
                        'result_hash' => $resultHash,
                        'existing_hash' => $existingHash,
                    ];
                }

                return [
                    'status' => 'APPLIED',
                    'result_hash' => $existingHash !== '' ? $existingHash : $resultHash,
                ];
            }

            if ((string) $locked->status !== 'closed') {
                throw new InvalidArgumentException('apply ได้เฉพาะ draw สถานะ closed');
            }

            if (! $this->shouldAutoSettleOnResult($locked)) {
                $locked->forceFill([
                    'result_number' => $validated,
                    'result_fetch_status' => 'APPLIED',
                    'result_fetch_error' => null,
                    'result_hash' => $resultHash,
                    'result_raw_payload_json' => $rawPayload,
                    'result_normalized_payload_json' => $validated,
                    'result_applied_at' => now(),
                    'result_fetched_at' => now(),
                ])->save();

                $this->sendFetchedResultTelegram($locked, $validated, false);

                return [
                    'status' => 'APPLIED',
                    'result_hash' => $resultHash,
                    'deferred_settlement' => true,
                ];
            }

            $summary = $this->settlementService->settleDraw($locked, $validated);

            $reloaded = LottoDraw::query()->findOrFail($locked->id);
            $reloaded->forceFill([
                'result_fetch_status' => 'APPLIED',
                'result_fetch_error' => null,
                'result_hash' => $resultHash,
                'result_raw_payload_json' => $rawPayload,
                'result_normalized_payload_json' => $validated,
                'result_applied_at' => now(),
                'result_fetched_at' => now(),
            ])->save();

            return [
                'status' => 'APPLIED',
                'result_hash' => $resultHash,
                'settlement_summary' => $summary,
                'deferred_settlement' => false,
            ];
        });
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function computeHash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function shouldAutoSettleOnResult(LottoDraw $draw): bool
    {
        $market = LotteryMarket::query()
            ->select(['id', 'auto_settle_on_result'])
            ->find((int) $draw->market_id);

        if (! $market instanceof LotteryMarket) {
            return true;
        }

        return (bool) ($market->auto_settle_on_result ?? true);
    }

    /**
     * @param array<string,mixed> $validated
     */
    private function sendFetchedResultTelegram(LottoDraw $draw, array $validated, bool $settled): void
    {
        $market = LotteryMarket::query()
            ->select(['id', 'name', 'notify_result_telegram'])
            ->find((int) $draw->market_id);

        if ($market instanceof LotteryMarket && ! (bool) ($market->notify_result_telegram ?? true)) {
            return;
        }

        $marketName = (string) ($market?->name ?? ('Market #' . (int) $draw->market_id));
        $drawDate = $draw->draw_date ? $draw->draw_date->format('Y-m-d') : '-';
        $firstPrize = preg_replace('/\D+/', '', (string) ($validated['first_prize'] ?? ''));
        $last2Digits = preg_replace('/\D+/', '', (string) ($validated['last_2_digits'] ?? ''));

        $firstLabel = strlen((string) $firstPrize) <= 3 ? 'เลข 3 ตัวบน' : 'รางวัลที่ 1';
        $last2Label = strlen((string) $firstPrize) <= 3 ? 'เลข 2 ตัวล่าง' : 'รางวัล เลข 2 ตัว';
        $statusLine = $settled ? 'คำนวนเงินรางวัลแล้ว' : 'รอทีมงานอนุมัติการคำนวน';

        $message = sprintf(
            'หวย%s งวดวันที่ %s' . PHP_EOL .
            '%s : %s' . PHP_EOL .
            '%s : %s' . PHP_EOL .
            '%s',
            $marketName,
            $drawDate,
            $firstLabel,
            $firstPrize !== '' ? $firstPrize : '-',
            $last2Label,
            $last2Digits !== '' ? $last2Digits : '-',
            $statusLine
        );

        SendTelegramBot::dispatch('notify/send', $message)->onQueue('cashback');
    }
}
