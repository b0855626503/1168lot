<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\PromotionController as WalletPromotionController;
use Illuminate\Http\Request;

class PromotionController extends BaseController
{
    public function list(Request $request)
    {
        try {
            return $this->normalizeJsonResponseImages(
                app(WalletPromotionController::class)->loadPromotion()
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการโปรโมชันได้ในขณะนี้', 422);
        }
    }

    public function select(Request $request)
    {
        try {
            $request->validate([
                'promotion' => 'required',
            ]);

            return $this->normalizeJsonResponseImages(
                app(WalletPromotionController::class)->selectPromotion($request)
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเลือกโปรโมชันได้ในขณะนี้', 422);
        }
    }

    public function deselect(Request $request)
    {
        try {
            return $this->normalizeJsonResponseImages(
                app(WalletPromotionController::class)->deselectPromotion($request)
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถยกเลิกโปรโมชันได้ในขณะนี้', 422);
        }
    }
}
