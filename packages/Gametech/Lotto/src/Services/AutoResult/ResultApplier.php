<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Jobs\MirrorDrawToArchiveJob;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Support\ResultHash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ResultApplier
{
    public function __construct(
        private SettlementService $settlementService,
        private DrawCancelAllRefundService $drawCancelAllRefundService
    ) {}

    /**
     * @param  array<string,mixed>  $validated
     * @param  array<string,mixed>  $rawPayload
     * @return array<string,mixed>
     */
    public function apply(LottoDraw $draw, array $validated, array $rawPayload, bool $dryRun = false): array
    {
        $resultHash = ResultHash::fromPayload($validated);

        if ($dryRun) {
            return [
                'status' => 'APPLIED',
                'result_hash' => $resultHash,
                'validated' => $validated,
            ];
        }

        return DB::transaction(function () use ($draw, $validated, $rawPayload, $resultHash) {
            $locked = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            if (! $this->isNoResultPayload($validated) && $this->isDuplicateOfPreviousDraw($locked, $validated)) {
                $locked->forceFill([
                    'result_fetch_status' => 'SKIPPED_DUPLICATE_PREVIOUS',
                    'result_fetch_error' => 'ผลรางวัลตรงกับงวดก่อนหน้า ข้ามการออกผล',
                    'result_fetched_at' => now(),
                ])->save();

                Log::warning('LOTTO_DUPLICATE_PREVIOUS_DRAW_RESULT', [
                    'draw_id' => (int) $locked->id,
                    'market_id' => (int) $locked->market_id,
                    'result_hash' => $resultHash,
                ]);

                return [
                    'status' => 'SKIPPED_DUPLICATE_PREVIOUS',
                    'result_hash' => $resultHash,
                ];
            }

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

            if ($this->isNoResultPayload($validated)) {
                $reason = trim((string) ($validated['no_result_reason'] ?? '')) ?: 'งดออกผล';
                $resultNumber = [
                    'no_result' => true,
                    'status' => 'no_result',
                    'label' => $reason,
                    'no_result_reason' => $reason,
                ];
                $autoRefundSummary = null;

                if ($this->shouldAutoRefundOnNoResult($locked)) {
                    $autoRefundSummary = $this->drawCancelAllRefundService->cancelAllActiveTickets(
                        lockedDraw: $locked,
                        reason: $reason,
                        createdByType: 'system',
                        createdById: null,
                        groupCode: 'LOTTO_DRAW_CANCEL_AUTO_'.(int) $locked->id.'_'.now()->format('YmdHis')
                    );
                    $resultNumber['manual_cancelled_all_tickets'] = true;
                }

                $locked->forceFill([
                    'result_number' => $resultNumber,
                    'status' => 'resulted',
                    'result_at' => now(),
                    'result_fetch_status' => 'APPLIED',
                    'result_fetch_error' => null,
                    'result_hash' => $resultHash,
                    'result_raw_payload_json' => $rawPayload,
                    'result_normalized_payload_json' => $validated,
                    'result_applied_at' => now(),
                    'result_fetched_at' => now(),
                ])->save();

                DB::afterCommit(function () use ($locked) {
                    MirrorDrawToArchiveJob::dispatch(
                        $locked->id,
                        (string) Str::uuid(),
                    );
                });

                return [
                    'status' => 'APPLIED',
                    'result_hash' => $resultHash,
                    'no_result' => true,
                    'deferred_settlement' => false,
                    'auto_refund' => $autoRefundSummary,
                ];
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

            DB::afterCommit(function () use ($reloaded) {
                MirrorDrawToArchiveJob::dispatch(
                    $reloaded->id,
                    (string) Str::uuid(),
                );
            });

            return [
                'status' => 'APPLIED',
                'result_hash' => $resultHash,
                'settlement_summary' => $summary,
                'deferred_settlement' => false,
            ];
        });
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

    private function shouldAutoRefundOnNoResult(LottoDraw $draw): bool
    {
        $market = LotteryMarket::query()
            ->select(['id', 'auto_refund_on_no_result'])
            ->find((int) $draw->market_id);

        if (! $market instanceof LotteryMarket) {
            return false;
        }

        return (bool) ($market->auto_refund_on_no_result ?? false);
    }

    /**
     * @param  array<string,mixed>  $validated
     */
    private function isNoResultPayload(array $validated): bool
    {
        return (bool) ($validated['no_result'] ?? false);
    }

    private function isDuplicateOfPreviousDraw(LottoDraw $draw, array $resultNumber): bool
    {
        if (! $draw->relationLoaded('market')) {
            $draw->load('market');
        }

        $market = $draw->market;
        if (! $market instanceof LotteryMarket) {
            return false;
        }

        if ((string) $market->result_mode !== LotteryMarket::RESULT_MODE_NORMAL) {
            return false;
        }

        $previousDraw = LottoDraw::query()
            ->where('market_id', (int) $draw->market_id)
            ->where('status', 'resulted')
            ->where('id', '!=', (int) $draw->id)
            ->where('draw_date', '<', $draw->draw_date)
            ->whereNotNull('result_number')
            ->orderByDesc('draw_date')
            ->first();

        if (! $previousDraw instanceof LottoDraw) {
            return false;
        }

        if (! is_array($previousDraw->result_number)) {
            return false;
        }

        // Compare only number fields, ignoring metadata like time/draw_date/no_result
        $numFields = ['first_prize', 'last_2_digits', 'top_3', 'top_2', 'bottom_2'];
        $prev = array_intersect_key($previousDraw->result_number, array_flip($numFields));
        $curr = array_intersect_key($resultNumber, array_flip($numFields));

        return $prev === $curr;
    }
}
