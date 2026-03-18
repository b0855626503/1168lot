<?php

namespace App\Http\Middleware;

use App\Helpers\TelegramFailedBot;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class LogFailedRequests
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true); // เริ่มจับเวลา

        try {
            $response = $next($request);

            $duration = microtime(true) - $start;

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || $duration > 3) {
                // response ไม่ใช่ 2xx หรือช้าเกิน 3 วินาที
                $this->logRequest($request, $response->getStatusCode(), $response->getContent(), $duration);
            }

            return $response;
        } catch (Throwable $e) {
            $duration = microtime(true) - $start;

            $status = $e instanceof ConnectionException ? 408 : 500;

            $this->logRequest($request, $status, $e->getMessage(), $duration);

            return response()->json([
                'error' => $status === 408 ? 'Request Timeout' : 'Internal Server Error',
            ], $status);
        }
    }

    protected function logRequest(Request $request, int $status, $responseContent, float $duration)
    {
        $session = $request->all();

        $data = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => json_encode($request->headers->all()),
            'body' => json_encode($request->all()),
            'status' => $status,
            'response' => is_string($responseContent) ? $responseContent : json_encode($responseContent),
            'duration' => round($duration, 3), // วินาที
            'created_at' => now(),
            'company' => $session['productId'] ?? '',
            'game_user' => $session['username'] ?? '',
        ];

        try {
            $response = DB::table('failed_requests')->insert($data);
            if ($response) {
                $msg = 'ค่าย '.$data['company'].' ID '.$data['game_user'].' API '.$data['url'].' Status '.$data['status'].' Duration '.$data['duration'];
                TelegramFailedBot::Send('notify/send', $msg);
            }
        } catch (\Exception $e) {
            \Log::error('DB insert failed in LogFailedRequests', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}
