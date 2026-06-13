<?php

namespace Gametech\Lotto\Http\Controllers\Api;

use Gametech\Lotto\Http\Requests\Api\GetLotteryRequest;
use Gametech\Lotto\Services\CentralLotteryResultService;
use Illuminate\Http\JsonResponse;

class CentralLotteryResultController
{
    public function __construct(private CentralLotteryResultService $service) {}

    public function show(GetLotteryRequest $request): JsonResponse
    {
        $payload = $this->service->fetch(
            (string) $request->query('type', ''),
            (string) $request->query('date', '')
        );

        $statusCode = $this->resolveStatusCode($payload);

        return new JsonResponse($payload, $statusCode);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveStatusCode(array $payload): int
    {
        $errors = $payload['errors'] ?? [];
        if (! is_array($errors) || $errors === []) {
            return 200;
        }

        $firstError = $errors[0] ?? null;
        $code = is_array($firstError) ? (string) ($firstError['code'] ?? '') : '';

        return match ($code) {
            'UNSUPPORTED_TYPE', 'INVALID_DATE_FORMAT' => 422,
            'DRAW_DATE_NOT_FOUND' => 404,
            'NOT_READY', 'FETCH_FAILED', 'INVALID_RESPONSE', 'UPSTREAM_NOT_CONFIGURED' => 200,
            default => 502,
        };
    }
}
