<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Core\Repositories\SlideRepository;
use Illuminate\Http\JsonResponse;

class SlideController extends BaseController
{
    private SlideRepository $slideRepository;

    public function __construct(SlideRepository $slideRepository)
    {
        $this->slideRepository = $slideRepository;
    }

    public function list(): JsonResponse
    {
        try {
            $slides = $this->slideRepository
                ->orderBy('sort')
                ->findWhere(['enable' => 'Y'])
                ->map(function ($slide) {
                    $row = $slide->toArray();

                    $filepic = (string) ($row['filepic'] ?? '');
                    $fullImageUrl = '';
                    if ($filepic !== '') {
                        $media = $this->storageMediaUrls('slide_img/' . ltrim($filepic, '/'));
                        $fullImageUrl = $media['url'];
                    }

                    // Keep payload shape flexible for frontend while ensuring image fields are absolute URLs.
                    $row['filepic'] = $fullImageUrl;
                    $row['image'] = $fullImageUrl;

                    return $row;
                })
                ->values();

            return $this->sendResponse($slides, 'ดึงรายการสไลด์สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการสไลด์ได้ในขณะนี้', 422);
        }
    }
}
