<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticateLottoInternalResults
{
    public function handle(Request $request, Closure $next)
    {
        $sharedKey = (string) config('lotto_auto_result.internal_result_sources.shared_key', '');
        if ($sharedKey === '') {
            return $next($request);
        }

        $headerName = (string) config('lotto_auto_result.internal_result_sources.header_name', 'X-Lotto-Internal-Key');
        $providedKey = (string) $request->header($headerName, '');
        if ($providedKey === '') {
            $providedKey = (string) $request->query('internal_key', '');
        }

        if (! hash_equals($sharedKey, $providedKey)) {
            return new JsonResponse([
                'success' => false,
                'source' => null,
                'type' => null,
                'draw_date' => null,
                'raw_result' => [],
                'normalized_result' => [
                    'first_prize' => null,
                    'top_3' => null,
                    'top_2' => null,
                    'bottom_2' => null,
                    'digit_4' => null,
                    'digit_5' => null,
                ],
                'meta' => [
                    'remote_url' => null,
                    'request_params' => [],
                    'fetched_at' => now()->toIso8601String(),
                    'latency_ms' => 0,
                ],
                'errors' => [
                    [
                        'code' => 'UNAUTHORIZED_INTERNAL_REQUEST',
                        'message' => 'Unauthorized internal request.',
                    ],
                ],
            ], 401);
        }

        return $next($request);
    }
}

