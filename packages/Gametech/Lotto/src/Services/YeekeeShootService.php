<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class YeekeeShootService
{
    public function submitShoot(
        int $memberId,
        int $roundId,
        string $numberText,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): YeekeeShoot {
        $normalizedNumberText = $this->normalizeNumberText($numberText);

        return DB::transaction(function () use ($memberId, $roundId, $normalizedNumberText, $ipAddress, $userAgent) {
            $round = YeekeeRound::query()->lockForUpdate()->find($roundId);
            if (! $round) {
                throw new InvalidArgumentException('ไม่พบรอบยี่กี่ที่ระบุ');
            }

            $market = LotteryMarket::query()->find((int) $round->market_id);
            if (! $market || (string) ($market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL) !== LotteryMarket::RESULT_MODE_YEEKEE) {
                throw new InvalidArgumentException('รายการหวยนี้ไม่รองรับการยิงเลข');
            }

            $now = Carbon::now();
            $shootOpenAt = Carbon::parse((string) $round->shoot_open_at);
            $shootCloseAt = Carbon::parse((string) $round->shoot_close_at);

            if ($now->lt($shootOpenAt)) {
                throw new InvalidArgumentException('ยังไม่ถึงเวลายิงเลข');
            }

            if (! $now->lt($shootCloseAt)) {
                throw new InvalidArgumentException('หมดเวลายิงเลขแล้ว');
            }

            if (in_array((string) $round->status, ['voided', 'resulted', 'settled'], true)) {
                throw new InvalidArgumentException('รอบนี้ไม่สามารถยิงเลขได้');
            }

            $maxShootPerMemberPerRound = (int) config('yeekee.max_shoots_per_member_per_round', 100);
            $memberShootCount = YeekeeShoot::query()
                ->where('yeekee_round_id', (int) $round->id)
                ->where('member_id', $memberId)
                ->count();

            if ($memberShootCount >= $maxShootPerMemberPerRound) {
                throw new InvalidArgumentException('เกินจำนวนการยิงเลขสูงสุดต่อรอบ');
            }

            $nextPosition = (int) YeekeeShoot::query()
                ->where('yeekee_round_id', (int) $round->id)
                ->lockForUpdate()
                ->max('position') + 1;

            return YeekeeShoot::query()->create([
                'yeekee_round_id' => (int) $round->id,
                'lotto_draw_id' => (int) $round->lotto_draw_id,
                'market_id' => (int) $round->market_id,
                'member_id' => $memberId,
                'position' => $nextPosition,
                'number_text' => $normalizedNumberText,
                'number_value' => (int) $normalizedNumberText,
                'submitted_at' => $now->format('Y-m-d H:i:s'),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
                'metadata_json' => null,
            ]);
        });
    }

    private function normalizeNumberText(string $numberText): string
    {
        $trimmed = trim($numberText);
        if (! preg_match('/^\d{5}$/', $trimmed)) {
            throw new InvalidArgumentException('กรุณากรอกเลข 5 หลัก');
        }

        return $trimmed;
    }
}
