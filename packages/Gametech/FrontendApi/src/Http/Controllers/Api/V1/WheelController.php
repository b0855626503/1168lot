<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class WheelController extends BaseController
{
    public function list(Request $request)
    {
        try {
            $spins = $this->loadSpin()->values();

            return $this->sendResponse([
                'wheel' => $spins,
                'enabled' => ((string) (core()->getConfigData()->wheel_open ?? 'N') === 'Y'),
            ], 'ดึงข้อมูลวงล้อสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลวงล้อได้ในขณะนี้', 422);
        }
    }

    public function spin(Request $request)
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $ip = $request->ip();
            $config = core()->getConfigData();
            $maxBonus = (float) ($config->maxspin ?? 0);
            $bonusSpinRepository = app('Gametech\Payment\Repositories\BonusSpinRepository');

            $bonusToday = (float) $bonusSpinRepository->scopeQuery(function ($query) {
                return $query->where('enable', 'Y')->whereDate('date_create', now()->toDateString());
            })->sum('amount');

            $spins = $this->loadSpin();
            if ($spins->count() <= 0) {
                return $this->sendError(Lang::get('app.spin.fail'), 200);
            }

            $range = 360 / $spins->count();
            $change = [];
            $noChange = [];
            $wheel = [];
            $random = [];

            foreach ($spins as $i => $item) {
                $change[$item['code']] = $item['winloss'];
                if ((float) $item['amount'] == 0) {
                    $noChange[$item['code']] = $item['winloss'];
                }

                $start = (($i * $range) + 1);
                $stop = (($i + 1) * $range);

                $wheel[] = [
                    'text' => $item['amount'],
                    'image' => $item['image'],
                ];

                $random[$item['code']] = [
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'start' => $start,
                    'stop' => $stop,
                    'types' => $item['types'],
                ];
            }

            $spinId = $this->pickWeighted(($maxBonus > 0 && $bonusToday >= $maxBonus) ? $noChange : $change);
            if ($spinId === null || ! isset($random[$spinId])) {
                $spinId = $this->pickWeighted($change);
            }
            if ($spinId === null || ! isset($random[$spinId])) {
                return $this->sendError(Lang::get('app.spin.fail'), 200);
            }

            $point = rand((int) $random[$spinId]['start'], (int) $random[$spinId]['stop']);
            $nameStop = (string) $random[$spinId]['name'];
            $amountStop = (float) $random[$spinId]['amount'];
            $rewardType = (string) $random[$spinId]['types'];

            try {
                $payload = DB::transaction(function () use ($member, $ip, $config, $nameStop, $amountStop, $rewardType, $bonusSpinRepository) {
                    $memberTable = method_exists($member, 'getTable') ? $member->getTable() : 'members';
                    $memberId = (int) $member->code;

                    $currentDiamond = (int) DB::table($memberTable)
                        ->where('code', $memberId)
                        ->lockForUpdate()
                        ->value('diamond');

                    if (($currentDiamond - 1) < 0) {
                        return ['ok' => false, 'error' => Lang::get('app.spin.usediamond')];
                    }

                    $diamondResult = app('Gametech\Member\Repositories\MemberDiamondLogRepository')->setDiamond([
                        'remark' => 'ร่วมสนุกการหมุนวงล้อ',
                        'amount' => 1,
                        'method' => 'W',
                        'member_code' => $memberId,
                        'emp_code' => 0,
                        'emp_name' => $member->name,
                    ]);

                    if (! $diamondResult) {
                        return ['ok' => false, 'error' => Lang::get('app.spin.fail')];
                    }

                    $diamondAfterDb = (int) DB::table($memberTable)
                        ->where('code', $memberId)
                        ->value('diamond');

                    $spinRecord = $bonusSpinRepository->create([
                        'member_code' => $memberId,
                        'bonus_name' => $nameStop,
                        'reward_type' => $rewardType,
                        'amount' => $amountStop,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'diamond_balance' => $diamondAfterDb,
                        'ip' => $ip,
                        'user_create' => $member->name,
                        'user_update' => $member->name,
                    ]);

                    if (! $spinRecord) {
                        return ['ok' => false, 'error' => Lang::get('app.spin.fail')];
                    }

                    if ($amountStop > 0 && ! $this->grantSpinReward($config, $memberId, $member->name, $amountStop, $rewardType, (int) $spinRecord->code)) {
                        return ['ok' => false, 'error' => Lang::get('app.spin.fail')];
                    }

                    return ['ok' => true, 'diamond' => $diamondAfterDb];
                });

                if (empty($payload['ok'])) {
                    return $this->sendError($payload['error'] ?? Lang::get('app.spin.fail'), 200);
                }

                $diamond = (int) ($payload['diamond'] ?? 0);
            } catch (\Throwable $e) {
                report($e);

                return $this->sendError(Lang::get('app.spin.fail'), 200);
            }

            $result = [
                'diamond' => $diamond,
                'format' => $amountStop > 0
                    ? [
                        'title' => Lang::get('app.spin.win') . $nameStop,
                        'msg' => Lang::get('app.spin.win_msg'),
                        'img' => Storage::url('spin_img/spin-win.png'),
                        'point' => $point,
                        'diamond' => $diamond,
                    ]
                    : [
                        'title' => Lang::get('app.spin.lost'),
                        'msg' => Lang::get('app.spin.lost_msg'),
                        'img' => Storage::url('spin_img/spin-loss.png'),
                        'point' => $point,
                        'diamond' => $diamond,
                    ],
                'spin' => $wheel,
            ];

            return $this->sendResponseNew($result, 'complete');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถหมุนวงล้อได้ในขณะนี้', 422);
        }
    }

    public function history(Request $request)
    {
        try {
            return $this->sendResponse([
                'history' => $this->loadHistory($request),
            ], 'ดึงประวัติวงล้อสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงประวัติวงล้อได้ในขณะนี้', 422);
        }
    }

    private function loadSpin()
    {
        return collect(app('Gametech\Core\Repositories\SpinRepository')->findWhere(['enable' => 'Y'])->toArray())
            ->map(function ($items) {
                $item = (object) $items;

                return [
                    'fillStyle' => $item->spincolor,
                    'image' => Storage::url('spin_img/' . $item->filepic),
                    'text' => number_format($item->amount, 0),
                    'code' => $item->code,
                    'amount' => $item->amount,
                    'winloss' => $item->winloss,
                    'spincolor' => $item->spincolor,
                    'name' => $item->name,
                    'types' => $item->types,
                ];
            });
    }

    private function pickWeighted(array $weightedValues): ?string
    {
        if (empty($weightedValues)) {
            return null;
        }

        $sum = (int) array_sum($weightedValues);
        if ($sum <= 0) {
            $keys = array_keys($weightedValues);

            return (string) $keys[array_rand($keys)];
        }

        $rand = mt_rand(1, $sum);
        foreach ($weightedValues as $key => $value) {
            $rand -= (int) $value;
            if ($rand <= 0) {
                return (string) $key;
            }
        }

        $keys = array_keys($weightedValues);

        return (string) end($keys);
    }

    private function grantSpinReward($config, int $memberId, string $memberName, float $amount, string $rewardType, int $spinRecordCode): bool
    {
        if ($amount <= 0) {
            return true;
        }

        if ($rewardType === 'WALLET') {
            $setdata = [
                'kind' => 'SPIN',
                'remark' => 'ได้รับรางวัลจากการหมุนวงล้อ',
                'amount' => $amount,
                'method' => 'D',
                'member_code' => $memberId,
                'refer_code' => $spinRecordCode,
                'refer_table' => 'bonus_spin',
                'emp_code' => 0,
                'emp_name' => $memberName,
            ];

            if (($config->seamless ?? 'N') === 'Y') {
                return (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setBonus($setdata);
            }

            if (($config->multigame_open ?? 'N') === 'Y') {
                return (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setWallet($setdata);
            }

            if (($config->freecredit_open ?? 'N') === 'Y') {
                return (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setBonus($setdata);
            }

            return (bool) app('Gametech\Member\Repositories\MemberCreditLogRepository')->setBonus($setdata);
        }

        if ($rewardType === 'CREDIT') {
            return (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setWallet([
                'kind' => 'SPIN',
                'remark' => 'ได้รับรางวัลจากการหมุนวงล้อ',
                'amount' => $amount,
                'method' => 'D',
                'refer_code' => $spinRecordCode,
                'refer_table' => 'bonus_spin',
                'member_code' => $memberId,
                'emp_code' => 0,
                'emp_name' => $memberName,
            ]);
        }

        if ($rewardType === 'DIAMOND') {
            return (bool) app('Gametech\Member\Repositories\MemberDiamondLogRepository')->setDiamond([
                'remark' => 'ได้รับรางวัลจากการหมุนวงล้อ',
                'amount' => $amount,
                'method' => 'D',
                'member_code' => $memberId,
                'emp_code' => 0,
                'emp_name' => $memberName,
            ]);
        }

        if ($rewardType === 'POINT') {
            return (bool) app('Gametech\Member\Repositories\MemberPointLogRepository')->setPoint([
                'remark' => 'ได้รับรางวัลจากการหมุนวงล้อ',
                'amount' => $amount,
                'method' => 'D',
                'member_code' => $memberId,
                'emp_code' => 0,
                'emp_name' => $memberName,
            ]);
        }

        if ($rewardType === 'BONUS') {
            return (bool) app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->setBonus([
                'kind' => 'SPIN',
                'remark' => 'ได้รับรางวัลจากการหมุนวงล้อ',
                'amount' => $amount,
                'method' => 'D',
                'member_code' => $memberId,
                'refer_code' => $spinRecordCode,
                'refer_table' => 'bonus_spin',
                'emp_code' => 0,
                'emp_name' => $memberName,
            ]);
        }

        return false;
    }

    private function loadHistory(Request $request): array
    {
        $member = $request->user() ?: $request->user('customer');
        if (! $member) {
            return [];
        }

        $responses = [];
        $result = [];

        $results = DB::table('bonus_spin')
            ->select(
                'bonus_name',
                'reward_type',
                'amount',
                DB::raw("DATE_FORMAT(date_create,'%d/%m/%Y') as date"),
                DB::raw("DATE_FORMAT(date_create,'%H:%i') as time")
            )
            ->where('member_code', (int) $member->code)
            ->orderByDesc('code')
            ->get();

        foreach ($results as $item) {
            $credit = (float) $item->amount > 0
                ? 'ได้รับรางวัล ' . $item->bonus_name . ' จำนวน ' . $item->amount
                : 'ไม่ได้รับรางวัล';

            $responses[$item->date]['date'] = $item->date;
            $responses[$item->date]['data'][] = ['credit' => $credit, 'time' => $item->time];
        }

        foreach ($responses as $value) {
            $result[] = $value;
        }

        return $result;
    }
}
