<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Models\YeekeeShootRewardLog;
use Illuminate\Support\Facades\DB;

class YeekeeRewardService
{
    /**
     * @deprecated Legacy/manual helper.
     *             BOA-233 settle runtime reward payout must use YeekeeShootingRewardService::applyForRound().
     */
    /**
     * @param  array<int,array{position:int,credit_amount:float}>  $rewardPositions
     * @return array{credited:int,skipped:int}
     */
    public function rewardRound(int $roundId, array $rewardPositions, float $minBetAmount = 0.0): array
    {
        return DB::transaction(function () use ($roundId, $rewardPositions, $minBetAmount) {
            $round = YeekeeRound::query()->lockForUpdate()->findOrFail($roundId);
            $credited = 0;
            $skipped = 0;

            foreach ($rewardPositions as $rewardPosition) {
                $position = (int) ($rewardPosition['position'] ?? 0);
                $creditAmount = (float) ($rewardPosition['credit_amount'] ?? 0);
                if ($position <= 0 || $creditAmount <= 0) {
                    $skipped++;

                    continue;
                }

                $shoot = YeekeeShoot::query()
                    ->where('yeekee_round_id', $roundId)
                    ->where('position', $position)
                    ->first();

                if (! $shoot) {
                    $skipped++;

                    continue;
                }

                $memberRoundBet = (float) LottoTicket::query()
                    ->where('draw_id', (int) $round->lotto_draw_id)
                    ->where('member_id', (int) $shoot->member_id)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_net_amount');

                if ($memberRoundBet < $minBetAmount) {
                    $skipped++;

                    continue;
                }

                $exists = YeekeeShootRewardLog::query()
                    ->where('yeekee_round_id', $roundId)
                    ->where('member_id', (int) $shoot->member_id)
                    ->where('position', $position)
                    ->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }

                YeekeeShootRewardLog::query()->create([
                    'yeekee_round_id' => $roundId,
                    'member_id' => (int) $shoot->member_id,
                    'position' => $position,
                    'credit_amount' => $creditAmount,
                    'reward_ref_type' => 'YEEKEE_SHOOT_REWARD',
                ]);

                $credited++;
            }

            return [
                'credited' => $credited,
                'skipped' => $skipped,
            ];
        });
    }
}
