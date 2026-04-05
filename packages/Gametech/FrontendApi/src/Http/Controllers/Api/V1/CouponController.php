<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\FrontendApi\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CouponController extends BaseController
{
    public function __construct(
        private CouponService $couponService
    ) {
    }

    public function redeem(Request $request): JsonResponse
    {
        try {
            $member = $request->user();
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $validated = validator($request->all(), [
                'coupon' => ['required', 'string', 'max:255'],
            ])->validate();

            $item = $this->couponService->redeemCode(
                $member,
                (string) $validated['coupon'],
                (string) $request->ip(),
                $this->requestLanguage($request)
            );

            return $this->sendResponseNew([
                'item' => $item,
            ], 'รับคูปองสำเร็จ');
        } catch (ValidationException $e) {
            return $this->sendError($e->validator->errors()->first() ?: 'ข้อมูลคูปองไม่ถูกต้อง', 422);
        } catch (RuntimeException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถรับคูปองได้ในขณะนี้', 422);
        }
    }

    public function myCoupons(Request $request): JsonResponse
    {
        try {
            $member = $request->user();
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $items = $this->couponService->listPendingBonuses($member);

            return $this->sendResponseNew([
                'items' => $items,
                'summary' => [
                    'count' => count($items),
                ],
            ], 'ดึงรายการคูปองสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการคูปองได้ในขณะนี้', 422);
        }
    }

    public function claim(Request $request, string $code): JsonResponse
    {
        try {
            $member = $request->user();
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $item = $this->couponService->claimBonus(
                $member,
                $code,
                (string) $request->ip(),
                $this->requestLanguage($request)
            );

            return $this->sendResponseNew([
                'item' => $item,
            ], 'รับโบนัสจากคูปองสำเร็จ');
        } catch (RuntimeException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถรับโบนัสจากคูปองได้ในขณะนี้', 422);
        }
    }
}
