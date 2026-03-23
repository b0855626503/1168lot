<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\HomeController;
use Illuminate\Http\Request;

class MemberController extends BaseController
{
    public function profile(Request $request)
    {
        try {
            return app(HomeController::class)->loadProfile();
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลสมาชิกได้ในขณะนี้', 422);
        }
    }

    public function balance(Request $request)
    {
        try {
            return app(HomeController::class)->loadCreditMin();
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงยอดเงินคงเหลือได้ในขณะนี้', 422);
        }
    }

    public function loadBalance(Request $request)
    {
        try {
            return app(HomeController::class)->loadCredit();
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงยอดเงินคงเหลือได้ในขณะนี้', 422);
        }
    }
}
