<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Lotto\Services\InternalResultSources\InternalResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalResultController
{
    public function __construct(private InternalResultService $service)
    {
    }

    public function exphuay(string $type, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->service->fetch('exphuay', [
            'type' => $type,
            'date' => (string) $request->query('date', ''),
            'page' => $page,
        ]);

        return new JsonResponse($result, 200);
    }

    public function dowjonesMidnight(Request $request): JsonResponse
    {
        $result = $this->service->fetch('dowjones-midnight', [
            'date' => (string) $request->query('date', ''),
        ]);

        return new JsonResponse($result, 200);
    }

    public function dowjonesExtra(Request $request): JsonResponse
    {
        $result = $this->service->fetch('dowjones-extra', [
            'date' => (string) $request->query('date', ''),
        ]);

        return new JsonResponse($result, 200);
    }
}

