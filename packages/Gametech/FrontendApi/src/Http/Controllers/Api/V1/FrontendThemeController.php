<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Lotto\Services\LottoFrontendThemeSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FrontendThemeController extends BaseController
{
    public function __construct(private LottoFrontendThemeSettingService $service) {}

    public function show(): JsonResponse
    {
        $payload = Cache::remember(LottoFrontendThemeSettingService::CACHE_KEY, now()->addMinutes(15), function (): array {
            return $this->service->formatForPublicResponse();
        });

        return $this->sendResponse($payload, 'ดึง Frontend Theme สำเร็จ');
    }
}
