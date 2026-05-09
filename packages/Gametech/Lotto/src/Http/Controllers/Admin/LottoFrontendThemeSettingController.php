<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\Services\LottoFrontendThemeSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LottoFrontendThemeSettingController extends AppBaseController
{
    protected array $_config;

    public function __construct(private LottoFrontendThemeSettingService $service)
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(): View
    {
        return view($this->_config['view'], [
            'initialTheme' => $this->service->formatForAdminResponse(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = (array) $request->input('data', []);
        $user = auth()->user();
        $updatedBy = null;
        if (is_object($user) && isset($user->name)) {
            $updatedBy = (string) $user->name;
        }

        $setting = $this->service->updateTheme($payload, $updatedBy);

        return $this->sendResponse([
            'id' => (int) $setting->id,
            'version' => (int) $setting->version,
        ], 'บันทึก Frontend Theme สำเร็จ');
    }
}
