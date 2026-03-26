<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class ContactChannelController extends BaseController
{
    public function list(): JsonResponse
    {
        try {
            $rows = app('Gametech\Core\Repositories\ContactChannelRepository')
                ->orderBy('sort')
                ->findWhere(['enable' => 'Y'])
                ->map(function ($item): array {
                    return [
                        'code' => (int) ($item->code ?? 0),
                        'type' => (string) ($item->type ?? ''),
                        'label' => (string) ($item->label ?? ''),
                        'link' => (string) ($item->link ?? ''),
                        'sort' => (int) ($item->sort ?? 0),
                    ];
                })
                ->values()
                ->all();

            return $this->sendResponse([
                'contact_channels' => $rows,
            ], 'ดึงข้อมูลช่องทางติดต่อสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงข้อมูลช่องทางติดต่อได้ในขณะนี้', 422);
        }
    }
}

