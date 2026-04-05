<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberController extends BaseController
{
    public function profile(Request $request)
    {
        return $this->buildBalanceResponse($request, true);
    }

    public function balance(Request $request)
    {
        return $this->buildBalanceResponse($request, false);
    }

    public function loadBalance(Request $request)
    {
        return $this->buildBalanceResponse($request, false);
    }

    public function changePassword(Request $request)
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $payload = (array) $request->all();
            if (! array_key_exists('password_confirmation', $payload) && array_key_exists('password_confirm', $payload)) {
                $payload['password_confirmation'] = $payload['password_confirm'];
            }

            $validated = validator($payload, [
                'password' => ['required', 'string', 'min:6', 'max:10'],
                'password_confirmation' => ['required', 'same:password'],
            ])->validate();

            app('Gametech\Member\Repositories\MemberRepository')->update([
                'user_pass' => (string) $validated['password'],
                'password' => Hash::make((string) $validated['password']),
            ], (int) $member->code);

            return $this->sendResponseNew([
                'member_code' => (int) $member->code,
            ], 'เปลี่ยนรหัสผ่านสำเร็จ');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError($e->validator->errors()->first() ?: 'ข้อมูลเปลี่ยนรหัสผ่านไม่ถูกต้อง', 422);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเปลี่ยนรหัสผ่านได้ในขณะนี้', 422);
        }
    }

    public function contributor(Request $request)
    {
        try {
            $member = $request->user();
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $memberRepository = app('Gametech\Member\Repositories\MemberRepository');
            $promotionRepository = app('Gametech\Promotion\Repositories\PromotionRepository');

            $member = $memberRepository->findOrFail($member->code);
            $affProfile = $memberRepository->getAff($member->code);
            $promotion = $promotionRepository->findOneWhere(['id' => 'pro_faststart']);

            $promotionLengthType = $promotion ? strtoupper((string) $promotion->length_type) : null;
            $bonusPercent = $promotion ? (float) $promotion->bonus_percent : null;
            $bonusPrice = $promotion ? (float) $promotion->bonus_price : null;
            $ruleDisplay = null;
            $ruleMoreMessage = null;

            if ($promotion) {
                $ruleDisplay = $promotionLengthType === 'PERCENT'
                    ? number_format((float) $bonusPercent, 2, '.', '') . ' %'
                    : number_format((float) $bonusPrice, 2, '.', '');

                $ruleMoreMessage = Lang::get(
                    'app.con.more',
                    ['field' => $ruleDisplay],
                    $this->requestLanguage($request)
                );
            }

            $promotionBonusIncome = (float) data_get(
                $affProfile,
                'payments_promotion_credit_bonus_sum',
                data_get($affProfile, 'payments_promotion_sum_credit_bonus', 0)
            );

            $downlines = $memberRepository
                ->without('bank')
                ->select(['code', 'upline_code', 'user_name', 'name', 'date_regis'])
                ->where('upline_code', $member->code)
                ->where('enable', 'Y')
                ->with(['payment_first' => function ($query) {
                    $query->select(['code', 'member_topup', 'value', 'date_approve', 'date_create']);
                }])
                ->orderByDesc('date_regis')
                ->get();

            $referrals = $downlines->map(function ($downline) {
                $firstDepositAt = $downline->payment_first->date_approve
                    ?? $downline->payment_first->date_create
                    ?? null;

                return [
                    'username' => (string) ($downline->user_name ?? ''),
                    'name' => (string) ($downline->name ?? ''),
                    'regis_date' => optional($downline->date_regis)->format('Y-m-d'),
                    'first_deposit_amount' => (float) ($downline->payment_first->value ?? 0),
                    'first_deposit_date' => $firstDepositAt ? $firstDepositAt->format('Y-m-d H:i:s') : null,
                ];
            })->values();

            $payload = [
                'summary' => [
                    'referred_members' => (int) data_get($affProfile, 'downs_count', 0),
                    'referral_code' => (string) ($member->referral_code ?? ''),
                    'referral_income' => (float) ($member->faststart ?? 0),
                    'promotion_bonus_income' => $promotionBonusIncome,
                    'promotion_bonus_count' => (int) data_get($affProfile, 'payments_promotion_count', 0),
                ],
                'rule' => [
                    'promotion_id' => 'pro_faststart',
                    'length_type' => $promotion ? (string) $promotion->length_type : null,
                    'bonus_percent' => $bonusPercent,
                    'bonus_price' => $bonusPrice,
                    'display_value' => $ruleDisplay,
                    'more_message' => $ruleMoreMessage,
                ],
                'referrals' => $referrals,
            ];

            return $this->sendResponseNew($payload, 'complete');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลแนะนำเพื่อนได้ในขณะนี้', 422);
        }
    }

    public function history(Request $request, ?string $type = null)
    {
        try {
            $member = $request->user();
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $historyType = strtolower((string) ($type ?: $request->query('type', 'deposit')));
            $dateStart = $request->query('date_start');
            $dateStop = $request->query('date_stop');

            $memberRepository = app('Gametech\Member\Repositories\MemberRepository');
            $memberCode = (int) $member->code;

            switch ($historyType) {
                case 'deposit':
                    $items = collect($memberRepository->loadBillType($memberCode, 'TOPUP', $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
                            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
                            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

                            return [
                                'id' => '#DP' . Str::of($item->code)->padLeft(8, 0),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                                'amount' => $item->amount,
                                'amount_request' => $item->amount_request,
                                'pro_name' => $item->pro_name,
                                'credit_bonus' => $item->credit_bonus,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_after,
                                'status' => $item->complete,
                                'image' => $image[$item->complete] ?? '',
                                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                                'method' => Lang::get('app.status.refill'),
                                'status_color' => $color[$item->complete] ?? '',
                                'status_display' => $status[$item->complete] ?? '',
                            ];
                        })
                        ->values();
                    break;

                case 'withdraw':
                    $items = collect($memberRepository->loadBillType($memberCode, 'WITHDRAW', $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
                            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel'), 'F' => Lang::get('app.status.cancel')];
                            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

                            return [
                                'id' => '#DP' . Str::of($item->code)->padLeft(8, 0),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                                'amount' => $item->amount,
                                'amount_request' => $item->amount_request,
                                'pro_name' => $item->pro_name,
                                'credit_bonus' => $item->credit_bonus,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_after,
                                'status' => $item->complete,
                                'image' => $image[$item->complete] ?? '',
                                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                                'method' => Lang::get('app.home.withdraw'),
                                'status_color' => $color[$item->complete] ?? '',
                                'status_display' => $status[$item->complete] ?? '',
                            ];
                        })
                        ->values();
                    break;

                case 'transfer':
                    $items = collect($memberRepository->loadBill($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $gameName = data_get($item, 'game.name', '');
                            $gameFile = data_get($item, 'game.filepic', '');

                            return [
                                'code' => $item->code,
                                'id' => '#BL' . Str::of($item->code)->padLeft(8, 0),
                                'promotion_name' => data_get($item, 'promotion.name_th', 'ไม่มี'),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                                'amount' => $item->amount,
                                'balance_before' => $item->balance_before,
                                'balance_after' => $item->balance_after,
                                'credit' => $item->credit,
                                'credit_bonus' => $item->credit_bonus,
                                'credit_balance' => $item->credit_balance,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_after,
                                'game_name' => $gameName,
                                'filepic' => $item->transfer_type == 1
                                    ? Storage::url('game_img/' . ltrim((string) $gameFile, '/'))
                                    : Storage::url('game_img/wallet.png'),
                                'transfer' => $item->transfer_type == 1 ? 'Wallet -> Game (โยกเข้าเกม)' : 'Wallet <- Game (โยกออกเกม)',
                                'status' => $item->transfer_type == 1 ? 'text-success' : 'text-danger',
                            ];
                        })
                        ->values();
                    break;

                case 'spin':
                    $items = collect($memberRepository->loadSpin($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

                            return [
                                'code' => $item->code,
                                'id' => '#SP' . Str::of($item->code)->padLeft(8, 0),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                                'amount' => $item->amount,
                                'image' => 'ic_success',
                                'transfer_type' => '',
                                'method' => Lang::get('app.home.wheels'),
                                'status' => 1,
                                'status_color' => $color[1],
                                'status_display' => $item->bonus_name,
                            ];
                        })
                        ->values();
                    break;

                case 'money':
                    $items = collect($memberRepository->loadMoneyTran($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $status = ['D' => 'รับโอน', 'W' => 'โอน'];
                            $color = ['D' => 'bg-info', 'W' => 'bg-success'];

                            return [
                                'code' => $item->code,
                                'id' => $item->remark,
                                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                                'amount' => $item->amount,
                                'status' => $item->credit_type,
                                'status_color' => $color[$item->credit_type] ?? '',
                                'status_display' => $status[$item->credit_type] ?? '',
                            ];
                        })
                        ->values();
                    break;

                case 'cashback':
                    $items = collect($memberRepository->loadCashbackNew($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

                            return [
                                'code' => $item->code,
                                'id' => 'ยอดเงินคืน จากการคำนวน',
                                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                                'amount' => $item->amount,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_balance,
                                'image' => 'ic_success',
                                'transfer_type' => '',
                                'method' => Lang::get('app.home.cashback'),
                                'status' => 1,
                                'status_color' => $color[1],
                                'status_display' => Lang::get('app.status.success'),
                            ];
                        })
                        ->values();
                    break;

                case 'memberic':
                    $items = collect($memberRepository->loadIC($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $color = ['0' => 'bg-info', '1' => 'bg-success', '2' => 'bg-danger'];

                            return [
                                'code' => $item->code,
                                'id' => 'ยอดเสียเพื่อน จากการคำนวน',
                                'date_create' => core()->formatDate($item->date_create, 'd/m/y H:i'),
                                'amount' => $item->amount,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_balance,
                                'image' => 'ic_success',
                                'transfer_type' => '',
                                'method' => Lang::get('app.home.ic'),
                                'status' => 1,
                                'status_color' => $color[1],
                                'status_display' => Lang::get('app.status.success'),
                            ];
                        })
                        ->values();
                    break;

                case 'bonus':
                    $items = collect($memberRepository->loadBonus($memberCode, $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
                            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
                            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];

                            return [
                                'id' => '#DP' . Str::of($item->code)->padLeft(8, 0),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                                'amount' => ((float) $item->credit_bonus > 0 ? $item->credit_bonus : $item->amount),
                                'pro_name' => $item->pro_name,
                                'credit_bonus' => $item->credit_bonus,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_after,
                                'status' => $item->complete,
                                'image' => $image[$item->complete] ?? '',
                                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                                'method' => $item->pro_name,
                                'status_color' => $color[$item->complete] ?? '',
                                'status_display' => $status[$item->complete] ?? '',
                            ];
                        })
                        ->values();
                    break;

                case 'other':
                    $items = collect($memberRepository->loadBillTypeArr($memberCode, ['ROLLBACK', 'SETWALLET'], $dateStart, $dateStop)->toArray())
                        ->map(function ($item) {
                            $item = (object) $item;
                            $image = ['N' => 'ic_fail', 'Y' => 'ic_success', 'R' => 'ic_fail'];
                            $status = ['N' => Lang::get('app.status.wait'), 'Y' => Lang::get('app.status.success'), 'R' => Lang::get('app.status.cancel')];
                            $color = ['N' => 'bg-info', 'Y' => 'bg-success', 'R' => 'bg-danger'];
                            $methodMap = [
                                'ROLLBACK' => Lang::get('app.status.rollback'),
                                'SETWALLET' => Lang::get('app.status.setwallet'),
                                'BONUS' => Lang::get('app.status.bonus'),
                            ];

                            return [
                                'id' => '#DP' . Str::of($item->code)->padLeft(8, 0),
                                'date_create' => core()->formatDate($item->date_create, 'd/m/Y H:i'),
                                'amount' => $item->amount,
                                'pro_name' => $item->pro_name,
                                'credit_bonus' => $item->credit_bonus,
                                'credit_before' => $item->credit_before,
                                'credit_after' => $item->credit_after,
                                'status' => $item->complete,
                                'image' => $image[$item->complete] ?? '',
                                'transfer_type' => ($item->transfer_type == 1 ? '+' : '-'),
                                'method' => $methodMap[$item->method] ?? $item->method,
                                'status_color' => $color[$item->complete] ?? '',
                                'status_display' => $status[$item->complete] ?? '',
                            ];
                        })
                        ->values();
                    break;

                default:
                    return $this->sendError('ไม่รองรับประเภทประวัติที่ร้องขอ', 422);
            }

            return $this->sendResponseNew([
                'type' => $historyType,
                'date_start' => $dateStart,
                'date_stop' => $dateStop,
                'items' => $items,
            ], 'complete');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลประวัติได้ในขณะนี้', 422);
        }
    }

    private function buildBalanceResponse(Request $request, bool $includeSpin)
    {
        try {
            $member = $request->user();
            if (! $member) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $config = collect(core()->getConfigData());
            $gameRepository = app('Gametech\Game\Repositories\GameUserRepository');
            $memberRepository = app('Gametech\Member\Repositories\MemberRepository');

            $system = [
                'point' => ($config['point_open'] ?? 'N') === 'Y',
                'diamond' => ($config['diamond_open'] ?? 'N') === 'Y',
                'notice' => $config['notice'] ?? null,
                'multi' => ($config['multigame_open'] ?? 'N') === 'Y',
                'wheel' => ($config['wheel_open'] ?? 'N') === 'Y',
            ];

            $game = core()->getGame();
            $gameUser = $gameRepository->findOneWhere([
                'member_code' => $member->code,
                'game_code' => $game->code,
            ]);

            $member = $memberRepository->findOrFail($member->code);

            $today = now()->toDateString();
            if (($config['seamless'] ?? 'N') === 'Y') {
                $withdrawToday = $memberRepository->sumWithdrawSeamless($member->code, $today)->withdraw_seamless_amount_sum;
            } else {
                $withdrawToday = $memberRepository->sumWithdraw($member->code, $today)->withdraw_amount_sum;
            }

            $withdraw = is_null($withdrawToday) ? 0 : $withdrawToday;
            $maxWithdraw = ((float) $member->maxwithdraw_day) === 0.0
                ? ($config['maxwithdraw_day'] ?? 0)
                : $member->maxwithdraw_day;
            $withdrawRemain = $maxWithdraw - $withdraw;

            $gameUserProCode = (int) ($gameUser->pro_code ?? 0);
            $gameUserAmountBalance = (float) ($gameUser->amount_balance ?? 0);
            $hasPromotionFlow = $gameUserProCode > 0 || $gameUserAmountBalance > 0;

            $profile = collect($member)->only([
                'balance',
                'point_deposit',
                'diamond',
                'balance_free',
                'credit',
                'user_name',
                'bonus',
                'cashback',
                'ic',
                'faststart',
                'pic_id',
            ])->toArray();

            $profile['getpro'] = $hasPromotionFlow;
            $profile['pro'] = ($config['wallet_withdraw_all'] ?? 'N') === 'Y' ? true : $hasPromotionFlow;
            $profile['pro_name'] = $gameUserProCode > 0 ? (data_get($gameUser, 'promotion.name_th', '')) : '';
            $profile['bank_code'] = $member->bank_code;
            $profile['name'] = (string) ($member->name ?? '');
            $profile['acc_no'] = (string) ($member->acc_no ?? '');
            $profile['tel'] = (string) ($member->tel ?? '');
            $profile['phone'] = (string) ($member->tel ?? '');
            $profile['pic_id'] = $member->pic_id ? $this->appendMediaCacheBust(asset('storage/' . ltrim((string) $member->pic_id, '/'))) : '';
            $profile['balance'] = $member->balance;
            $profile['diamond'] = (int) $member->diamond;
            $profile['amount_balance'] = data_get($gameUser, 'amount_balance', 0);
            $profile['withdraw_limit_amount'] = data_get($gameUser, 'withdraw_limit_amount', 0);
            $profile['winlost'] = 0;
            $profile['downline'] = $member->load('down')->down->count();
            $profile['maxwithdraw_day'] = $maxWithdraw;
            $profile['withdraw_min'] = $config['minwithdraw'] ?? 0;
            $profile['withdraw_max'] = $withdrawRemain;
            $profile['withdraw_sum_today'] = $withdraw;
            $profile['withdraw_remain_today'] = $withdrawRemain;
            $profile['lastupdate'] = now()->format('d/m/Y H:i:s');

            $promotionSelect = core()->getSelectPro();
            $promotion = [
                'select' => ! empty($promotionSelect),
                'name' => ! empty($promotionSelect) ? ($promotionSelect['name_th'] ?? '') : '',
                'min' => ! empty($promotionSelect) ? ($promotionSelect['amount_min'] ?? '') : '',
            ];

            $depositCount = core()->getBankTopupCountsNew();
            $deposit = [
                'bank' => $depositCount['bank'] ?? 0,
                'payment' => $depositCount['payment'] ?? 0,
                'tw' => $depositCount['tw'] ?? 0,
                'slip' => $depositCount['slip'] ?? 0,
                'sort' => [
                    'payment' => $depositCount['payment_min_sort'] ?? null,
                    'tw' => $depositCount['tw_min_sort'] ?? null,
                    'slip' => $depositCount['slip_min_sort'] ?? null,
                    'bank' => $depositCount['bank_min_sort'] ?? null,
                ],
            ];

            $payload = [
                'profile' => $profile,
                'promotion' => $promotion,
                'withdraw' => ($config['withdraw_status'] ?? 'N') === 'Y',
                'deposit' => $deposit,
                'system' => $system,
            ];

            if ($includeSpin) {
                $bankName = '';
                try {
                    $bank = app('Gametech\Payment\Repositories\BankRepository')
                        ->findOneByField('code', (int) ($member->bank_code ?? 0));
                    $bankName = (string) ($bank->name_th ?? $bank->name_en ?? $bank->name ?? '');
                    $bankImage = $this->resolveBankImage((string) ($bank->filepic ?? ''));
                    $payload['profile']['bank_image'] = $bankImage['path'];
                    $payload['profile']['bank_image_url'] = $bankImage['url'];
                } catch (\Throwable $e) {
                    $bankName = '';
                    $payload['profile']['bank_image'] = '';
                    $payload['profile']['bank_image_url'] = '';
                }

                $payload['profile']['bank_name'] = $bankName;
                $payload['spin'] = app('Gametech\Wallet\Http\Controllers\HomeController')->loadSpin();
            }

            return $this->sendResponseNew($payload, 'complete');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงยอดเงินคงเหลือได้ในขณะนี้', 422);
        }
    }

    /**
     * @return array{path:string,url:string}
     */
    private function resolveBankImage(string $filepic): array
    {
        $filepic = trim($filepic);
        if ($filepic === '') {
            return ['path' => '', 'url' => ''];
        }

        if (Str::startsWith($filepic, ['http://', 'https://'])) {
            $url = $this->appendMediaCacheBust($filepic);
            return ['path' => $url, 'url' => $url];
        }

        if (Str::startsWith($filepic, '/')) {
            $path = $this->appendMediaCacheBust($filepic);
            return ['path' => $path, 'url' => url($path)];
        }

        return $this->storageMediaUrls('bank_img/' . ltrim($filepic, '/'));
    }
}
