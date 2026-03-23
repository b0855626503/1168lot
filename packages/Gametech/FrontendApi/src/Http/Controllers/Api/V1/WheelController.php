<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\SpinController;
use Illuminate\Http\Request;

class WheelController extends BaseController
{
    public function list(Request $request)
    {
        try {
            $spins = app(SpinController::class)->loadSpin()->values();

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
            return $this->normalizeJsonResponseImages(
                app(SpinController::class)->store($request)
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถหมุนวงล้อได้ในขณะนี้', 422);
        }
    }

    public function history(Request $request)
    {
        try {
            return $this->sendResponse([
                'history' => app(SpinController::class)->loadHistory(),
            ], 'ดึงประวัติวงล้อสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงประวัติวงล้อได้ในขณะนี้', 422);
        }
    }
}
