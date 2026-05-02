<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeShootCooldownException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $shoot = DB::transaction(function () use ($memberId, $roundId, $normalizedNumberText, $ipAddress, $userAgent) {
            $legacyShootEnabled = (bool) config('yeekee.shoot_enabled', false);
            $hardeningShootEnabled = (bool) config('yeekee.shooting_enabled', true);
            if (! $legacyShootEnabled || ! $hardeningShootEnabled) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'SHOOTING_DISABLED',
                    'member_id' => $memberId,
                    'yeekee_round_id' => $roundId,
                ]);
                throw new InvalidArgumentException('ระบบยิงเลขถูกปิดใช้งานชั่วคราว');
            }

            $round = YeekeeRound::query()->lockForUpdate()->find($roundId);
            if (! $round) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'ROUND_NOT_FOUND',
                    'member_id' => $memberId,
                    'yeekee_round_id' => $roundId,
                ]);
                throw new InvalidArgumentException('ไม่พบรอบยี่กี่ที่ระบุ');
            }

            $market = LotteryMarket::query()->find((int) $round->market_id);
            if (! $market || (string) ($market->result_mode ?? LotteryMarket::RESULT_MODE_NORMAL) !== LotteryMarket::RESULT_MODE_YEEKEE) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'MARKET_NOT_YEEKEE',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'market_id' => (int) $round->market_id,
                ]);
                throw new InvalidArgumentException('รายการหวยนี้ไม่รองรับการยิงเลข');
            }

            $now = Carbon::now();
            $shootOpenAt = Carbon::parse((string) $round->shoot_open_at);
            $shootCloseAt = Carbon::parse((string) $round->shoot_close_at);

            if ($now->lt($shootOpenAt)) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'BEFORE_SHOOT_OPEN',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'shoot_open_at' => $shootOpenAt->format('Y-m-d H:i:s'),
                ]);
                throw new InvalidArgumentException('ยังไม่ถึงเวลายิงเลข');
            }

            if (! $now->lt($shootCloseAt)) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'AFTER_SHOOT_CLOSE',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'shoot_close_at' => $shootCloseAt->format('Y-m-d H:i:s'),
                ]);
                throw new InvalidArgumentException('หมดเวลายิงเลขแล้ว');
            }

            if ($round->shoot_closed_at !== null) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'ROUND_ALREADY_FROZEN',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'shoot_closed_at' => (string) $round->shoot_closed_at,
                ]);
                throw new InvalidArgumentException('รอบนี้ปิดรับยิงเลขแล้ว');
            }

            if (in_array((string) $round->status, ['voided', 'resulted', 'settled'], true)) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'ROUND_STATUS_FINAL',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'round_status' => (string) $round->status,
                ]);
                throw new InvalidArgumentException('รอบนี้ไม่สามารถยิงเลขได้');
            }

            $maxShootPerMemberPerRound = (int) config('yeekee.max_shoots_per_member_per_round', 100);
            $memberShootCount = YeekeeShoot::query()
                ->where('yeekee_round_id', (int) $round->id)
                ->where('member_id', $memberId)
                ->count();

            if ($memberShootCount >= $maxShootPerMemberPerRound) {
                Log::warning('yeekee.shoot.rejected', [
                    'reason' => 'MEMBER_ROUND_LIMIT_EXCEEDED',
                    'member_id' => $memberId,
                    'yeekee_round_id' => (int) $round->id,
                    'member_shoot_count' => $memberShootCount,
                    'max_shoots_per_member_per_round' => $maxShootPerMemberPerRound,
                ]);
                throw new InvalidArgumentException('เกินจำนวนการยิงเลขสูงสุดต่อรอบ');
            }

            $cooldownSeconds = max((int) config('yeekee.shoot_cooldown_seconds', 0), 0);
            if ($cooldownSeconds > 0) {
                $memberLastShoot = YeekeeShoot::query()
                    ->where('yeekee_round_id', (int) $round->id)
                    ->where('member_id', $memberId)
                    ->orderByDesc('submitted_at')
                    ->lockForUpdate()
                    ->first(['submitted_at']);

                if ($memberLastShoot && $memberLastShoot->submitted_at) {
                    $memberLastSubmittedAt = Carbon::parse((string) $memberLastShoot->submitted_at);
                    $nextAllowedAt = $memberLastSubmittedAt->copy()->addSeconds($cooldownSeconds);
                    if ($nextAllowedAt->gt($now)) {
                        $remainingCooldownSeconds = max((int) $now->diffInSeconds($nextAllowedAt, false), 1);
                        Log::warning('yeekee.shoot.rejected', [
                            'reason' => 'COOLDOWN_ACTIVE',
                            'member_id' => $memberId,
                            'yeekee_round_id' => (int) $round->id,
                            'cooldown_seconds' => $cooldownSeconds,
                            'remaining_cooldown_seconds' => $remainingCooldownSeconds,
                            'next_allowed_at' => $nextAllowedAt->format('Y-m-d H:i:s'),
                        ]);

                        throw new YeekeeShootCooldownException(
                            cooldownSeconds: $cooldownSeconds,
                            remainingCooldownSeconds: $remainingCooldownSeconds,
                            nextAllowedAt: $nextAllowedAt->format('Y-m-d H:i:s')
                        );
                    }
                }
            }

            $maxShootsPerIpPerMinute = max((int) config('yeekee.max_shoots_per_ip_per_minute', 0), 0);
            if ($maxShootsPerIpPerMinute > 0 && $ipAddress !== null && $ipAddress !== '') {
                $windowStart = $now->copy()->subMinute();
                $ipShootCount = YeekeeShoot::query()
                    ->where('yeekee_round_id', (int) $round->id)
                    ->where('ip_address', $ipAddress)
                    ->where('submitted_at', '>=', $windowStart->format('Y-m-d H:i:s'))
                    ->lockForUpdate()
                    ->count();

                if ($ipShootCount >= $maxShootsPerIpPerMinute) {
                    Log::warning('yeekee.shoot.rejected', [
                        'reason' => 'IP_RATE_LIMIT_EXCEEDED',
                        'member_id' => $memberId,
                        'yeekee_round_id' => (int) $round->id,
                        'ip_address' => $ipAddress,
                        'ip_shoot_count' => $ipShootCount,
                        'max_shoots_per_ip_per_minute' => $maxShootsPerIpPerMinute,
                    ]);
                    throw new InvalidArgumentException('เกินจำนวนการยิงเลขสูงสุดจาก IP เดียวกัน');
                }
            }

            $nextPosition = (int) $round->last_shoot_position + 1;
            $round->forceFill([
                'last_shoot_position' => $nextPosition,
                'shoot_count' => (int) $round->shoot_count + 1,
            ])->save();

            $shoot = YeekeeShoot::query()->create([
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

            return $shoot;
        });

        Log::info('yeekee.shoot.accepted', [
            'yeekee_round_id' => (int) $shoot->yeekee_round_id,
            'lotto_draw_id' => (int) $shoot->lotto_draw_id,
            'market_id' => (int) $shoot->market_id,
            'member_id' => (int) $shoot->member_id,
            'position' => (int) $shoot->position,
        ]);

        return $shoot;
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
