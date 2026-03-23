<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Wallet\Http\Controllers\SlipController;
use Illuminate\Http\Request;

class DepositController extends BaseController
{
    public function channels(Request $request)
    {
        try {
            $deposit = core()->getBankTopupCountsNew();

            return $this->sendResponse([
                'deposit' => [
                    'bank' => (int) ($deposit['bank'] ?? 0),
                    'payment' => (int) ($deposit['payment'] ?? 0),
                    'tw' => (int) ($deposit['tw'] ?? 0),
                    'slip' => (int) ($deposit['slip'] ?? 0),
                    'sort' => [
                        'payment' => $deposit['payment_min_sort'] ?? null,
                        'tw' => $deposit['tw_min_sort'] ?? null,
                        'slip' => $deposit['slip_min_sort'] ?? null,
                        'bank' => $deposit['bank_min_sort'] ?? null,
                    ],
                ],
            ], 'ดึงช่องทางเติมเงินสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงช่องทางเติมเงินได้ในขณะนี้', 422);
        }
    }

    public function loadBank(Request $request)
    {
        try {
            $validated = validator($request->all(), [
                'method' => ['required', 'in:bank,payment,tw,slip'],
            ])->validate();

            $request->merge(['method' => $validated['method']]);

            return $this->normalizeJsonResponseImages(
                app(SlipController::class)->loadBank($request)
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลบัญชีเติมเงินได้ในขณะนี้', 422);
        }
    }
}
