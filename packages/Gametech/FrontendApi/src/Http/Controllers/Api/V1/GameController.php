<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\API\Models\GameListProxy;
use Gametech\Wallet\Http\Controllers\ProfileController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameController extends BaseController
{
    private GameTypeRepository $gameTypeRepository;
    private GameSeamlessRepository $gameSeamlessRepository;

    public function __construct(
        GameTypeRepository $gameTypeRepository,
        GameSeamlessRepository $gameSeamlessRepository
    )
    {
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
                    $logo = Storage::url('game_img/' . strtolower((string) $item->filepic) . '?v=' . date('ymd'));

                    return [
                        'provider' => (string) $item->id,
                        'providerTier' => 'standard',
                        'providerName' => (string) $item->name,
                        'providerType' => (string) $item->game_type,
                        'logoURL' => url($logo),
                        'logoTransparentURL' => url($logo),
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

            $gameList = GameListProxy::query()
                ->where('product', strtoupper($provider))
                ->where('enable', true)
                ->orderByDesc('click')
                ->orderByDesc('rank')
                ->get()
                ->map(function ($item) use ($providerModel) {
                    return [
                        'id' => (string) ($item['code'] ?? ''),
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
                        'status' => ! empty($item['enable']) ? 'ACTIVE' : 'INACTIVE',
                    ];
                })
                ->values();

            return $this->sendResponse($gameList, 'ดึงรายการเกมสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการเกมได้ในขณะนี้', 422);
        }
    }

    public function login(Request $request)
    {
        try {
            return app(ProfileController::class)->gameListLogin($request);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเข้าสู่เกมได้ในขณะนี้', 422);
        }
    }
}
