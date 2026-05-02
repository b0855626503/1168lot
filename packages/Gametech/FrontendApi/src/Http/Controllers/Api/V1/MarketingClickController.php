<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\FrontendApi\Http\Requests\TrackMarketingClickRequest;
use Gametech\Marketing\Services\MarketingClickTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketingClickController extends BaseController
{
    public function __construct(
        private readonly MarketingClickTrackingService $trackingService
    ) {}

    public function track(TrackMarketingClickRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload = array_intersect_key($payload, array_flip([
            'registration_code',
            'visitor_id',
            'session_id',
            'landing_url',
            'referrer',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]));

        $result = $this->trackingService->trackClick($request, $payload);

        if (! $result['success']) {
            return $this->sendError((string) ($result['message'] ?? 'Invalid request'), 422);
        }

        return $this->sendResponseNew($result['data'], 'บันทึก click สำเร็จ');
    }

    public function confirm(Request $request, string $clickId): JsonResponse
    {
        $visitorId = $request->input('visitor_id');

        try {
            $success = $this->trackingService->confirmClick($clickId, $visitorId);

            if (! $success) {
                return $this->sendError('ไม่พบ click ดังกล่าว', 404);
            }

            return $this->sendSuccess('ยืนยัน click สำเร็จ');
        } catch (\Throwable $e) {
            Log::warning('MarketingClickController: confirm failed', [
                'click_id' => $clickId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('ไม่สามารถยืนยัน click ได้ในขณะนี้', 422);
        }
    }

    public function submitted(Request $request, string $clickId): JsonResponse
    {
        $visitorId = $request->input('visitor_id');

        try {
            $success = $this->trackingService->markSubmitted($clickId, $visitorId);

            if (! $success) {
                return $this->sendError('ไม่พบ click ดังกล่าว', 404);
            }

            return $this->sendSuccess('บันทึกการ submit สำเร็จ');
        } catch (\Throwable $e) {
            Log::warning('MarketingClickController: submitted failed', [
                'click_id' => $clickId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('ไม่สามารถบันทึกการ submit ได้ในขณะนี้', 422);
        }
    }
}
