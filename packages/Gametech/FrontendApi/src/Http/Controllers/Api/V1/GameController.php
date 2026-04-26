<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\API\Models\GameListProxy;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GameController extends BaseController
{
    private GameRepository $gameRepository;
    private GameUserRepository $gameUserRepository;
    private BankPaymentRepository $bankPaymentRepository;
    private GameTypeRepository $gameTypeRepository;
    private GameSeamlessRepository $gameSeamlessRepository;

    public function __construct(
        GameRepository $gameRepository,
        GameUserRepository $gameUserRepository,
        BankPaymentRepository $bankPaymentRepository,
        GameTypeRepository $gameTypeRepository,
        GameSeamlessRepository $gameSeamlessRepository
    ) {
        $this->gameRepository = $gameRepository;
        $this->gameUserRepository = $gameUserRepository;
        $this->bankPaymentRepository = $bankPaymentRepository;
        $this->gameTypeRepository = $gameTypeRepository;
        $this->gameSeamlessRepository = $gameSeamlessRepository;
    }

    public function types(): JsonResponse
    {
        try {
            $types = $this->gameTypeRepository
                ->findWhere(['enable' => 'Y', 'status_open' => 'Y'])
                ->map(function ($type) {
                    return [
                        'id' => strtolower((string) $type->id),
                        'name' => (string) $type->name,
                        'status_open' => (string) $type->status_open,
                    ];
                })
                ->values();

            return $this->sendResponse($types, 'ดึงประเภทเกมสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงประเภทเกมได้ในขณะนี้', 422);
        }
    }

    public function providers(string $type)
    {
        try {
            $providers = $this->gameSeamlessRepository
                ->orderBy('sort')
                ->findWhere([
                    'game_type' => strtoupper($type),
                    'status_open' => 'Y',
                    'enable' => 'Y',
                ])
                ->map(function ($item) {
                    $logo = $this->storageMediaUrls('game_img/'.strtolower((string) $item->filepic));

                    return [
                        'provider' => (string) $item->id,
                        'providerTier' => 'standard',
                        'providerName' => (string) $item->name,
                        'providerType' => (string) $item->game_type,
                        'logoURL' => $logo['url'],
                        'logoTransparentURL' => $logo['url'],
                        'status' => (string) $item->enable === 'Y' ? 'ACTIVE' : 'INACTIVE',
                        'detailStatus' => (string) $item->status_open === 'Y',
                    ];
                })
                ->values();

            return $this->sendResponse($providers, 'ดึงรายการค่ายเกมสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการค่ายเกมได้ในขณะนี้', 422);
        }
    }

    public function games(string $type, string $provider)
    {
        try {
            $providerModel = $this->gameSeamlessRepository->findOneByField('id', $provider);
            if (! $providerModel) {
                return $this->sendError('ไม่พบค่ายเกมที่ระบุ', 404);
            }

            $providerId = strtoupper($provider);
            $gameMethod = (string) ($providerModel->method ?? 'seamless');
            if ($gameMethod === '') {
                $gameMethod = 'seamless';
            }

            $this->warmGameListFromSource($providerId, $gameMethod);

            $gameList = GameListProxy::query()
                ->where('product', $providerId)
                ->where('enable', true)
                ->orderByDesc('click')
                ->orderByDesc('rank')
                ->get()
                ->map(function ($item) use ($providerModel, $providerId) {
                    $gameCode = (string) ($item['code'] ?? '');

                    return [
                        'id' => $gameCode,
                        'provider' => (string) ($item['product'] ?? ''),
                        'providerLogo' => [
                            'logoURL' => '',
                            'logoMobileURL' => '',
                            'logoTransparentURL' => '',
                        ],
                        'gameName' => (string) ($item['name'] ?? ''),
                        'gameCategory' => (string) $providerModel->method,
                        'gameType' => [(string) ($item['type'] ?? '')],
                        'image' => [
                            'vertical' => (string) ($item['img'] ?? ''),
                            'horizontal' => (string) ($item['img'] ?? ''),
                            'banner' => '',
                        ],
                        'loginURL' => route('frontend.api.v1.games.login.path', [
                            'game' => $providerId,
                            'code' => $gameCode,
                        ]),
                        'status' => ! empty($item['enable']) ? 'ACTIVE' : 'INACTIVE',
                    ];
                })
                ->values();

            return $this->sendResponse($gameList, 'ดึงรายการเกมสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการเกมได้ในขณะนี้', 422);
        }
    }

    private function warmGameListFromSource(string $providerId, string $method): void
    {
        try {
            $this->gameUserRepository->getGameList($providerId, $method);
        } catch (\Throwable $e) {
            Log::channel('api')->warning('frontend.game.list.warmup.failed', [
                'provider' => $providerId,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function login(Request $request)
    {
        try {
            $provider = (string) $request->input('id', '');
            $gameCode = (string) $request->input('game', '');

            return $this->loginGameRedirect($request, $provider, $gameCode);
        } catch (\Throwable $e) {
            Log::channel('api')->error('frontend.game.login.exception', [
                'path' => (string) $request->path(),
                'provider' => (string) $request->input('id', ''),
                'game_code' => (string) $request->input('game', ''),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('ไม่สามารถเข้าสู่เกมได้ในขณะนี้', 422);
        }
    }

    public function loginByPath(Request $request, string $game, string $code)
    {
        try {
            return $this->loginGameRedirect($request, $game, $code);
        } catch (\Throwable $e) {
            Log::channel('api')->error('frontend.game.login.path.exception', [
                'path' => (string) $request->path(),
                'provider' => $game,
                'game_code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('ไม่สามารถเข้าสู่เกมได้ในขณะนี้', 422);
        }
    }

    private function loginGameRedirect(Request $request, string $provider, string $gameCode): JsonResponse
    {
        $traceId = (string) Str::uuid();
        $request->attributes->set('frontend_game_login_trace_id', $traceId);

        $provider = strtoupper(trim($provider));
        $gameCode = trim($gameCode);
        if ($provider === '' || $gameCode === '') {
            return $this->sendError('กรุณาระบุ provider และ game code ให้ครบถ้วน', 422);
        }

        $user = $request->user();
        if (! $user) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        Log::channel('api')->info('frontend.game.login.start', [
            'trace_id' => $traceId,
            'member_code' => (int) $user->code,
            'user_name' => (string) ($user->user_name ?? ''),
            'provider' => $provider,
            'game_code' => $gameCode,
            'path' => (string) $request->path(),
        ]);

        $this->bankPaymentRepository
            ->where('member_topup', $user->code)
            ->where('pro_check', 'N')
            ->update([
                'pro_check' => 'Y',
                'user_update' => $user->name,
            ]);

        $gameMain = $this->gameRepository->findOneWhere([
            'enable' => 'Y',
            'status_open' => 'Y',
            'id' => 'seamless',
        ]);
        if (! $gameMain) {
            return $this->sendError('ไม่พบการตั้งค่าระบบเกม', 422);
        }

        $gameUser = $this->gameUserRepository->findOneWhere([
            'member_code' => $user->code,
            'game_code' => $gameMain->code,
        ]);

        if (! $gameUser) {
            $member = app('Gametech\Member\Repositories\MemberRepository')->find($user->code);
            $created = $this->gameUserRepository->addGameUser($gameMain->code, $member->code, [
                'username' => $member->user_name,
                'password' => $member->user_pass,
                'product_id' => $provider,
                'user_create' => $member->user_name,
            ]);

            if (($created['success'] ?? false) !== true) {
                return $this->sendError((string) ($created['msg'] ?? 'ไม่สามารถสร้างบัญชีเกมได้ในขณะนี้'), 422);
            }

            $gameUser = $created['data'] ?? null;
        }

        $result = $this->gameUserRepository->autoLoginSeamlessByGameUser($gameUser, $provider, $gameCode);
        Log::channel('api')->info('frontend.game.login.result', [
            'trace_id' => $traceId,
            'member_code' => (int) $user->code,
            'provider' => $provider,
            'game_code' => $gameCode,
            'success' => (bool) ($result['success'] ?? false),
            'msg' => (string) ($result['msg'] ?? ''),
            'has_url' => ! empty($result['url']),
            'api' => $result['api'] ?? null,
        ]);

        if (($result['success'] ?? false) !== true || empty($result['url'])) {
            return $this->sendError((string) ($result['msg'] ?? 'ไม่สามารถเข้าสู่เกมได้ในขณะนี้'), 422);
        }

        $gameItemName = GameListProxy::query()
            ->where('code', $gameCode)
            ->where('product', $provider)
            ->value('name');

        app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
            'ip' => request()->ip(),
            'credit_type' => 'D',
            'balance_before' => $user->balance,
            'balance_after' => $user->balance,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $user->balance,
            'credit_after' => $user->balance,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => 'System Auto',
            'user_update' => 'System Auto',
            'refer_code' => 0,
            'refer_table' => 'blank',
            'remark' => 'กดเข้าเกม ค่าย '.$provider.' เกม '.($gameItemName ?: $gameCode),
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $gameUser->amount_balance ?? 0,
            'withdraw_limit' => $gameUser->withdraw_limit ?? 0,
            'withdraw_limit_amount' => $gameUser->withdraw_limit_amount ?? 0,
            'method' => 'D',
            'member_code' => $user->code,
        ]);

        return $this->sendResponse([
            'url' => (string) $result['url'],
            'provider' => $provider,
            'code' => $gameCode,
        ], 'เข้าสู่เกมสำเร็จ');
    }
}
