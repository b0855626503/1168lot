<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
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
        try {
            return $this->normalizeJsonResponseImages(
                app(HomeController::class)->loadCreditMin()
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงยอดเงินคงเหลือได้ในขณะนี้', 422);
        }
    }

    public function loadBalance(Request $request)
    {
        return $this->buildBalanceResponse($request, false);
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
            $profile['pic_id'] = $member->pic_id ? asset('storage/' . $member->pic_id) : '';
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
            return ['path' => $filepic, 'url' => $filepic];
        }

        $path = Str::startsWith($filepic, '/')
            ? $filepic
            : Storage::url('bank_img/' . ltrim($filepic, '/'));

        return [
            'path' => $path,
            'url' => url($path),
        ];
    }
}
