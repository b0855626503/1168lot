<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class EnsureUserInCurrentGame
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->input('username');
        $type = $this->extractRequestType($request);
        $gameId = $this->extractGameId($request);
        $productId = $request->input('productId');

        if ($this->shouldSkipSessionCheck($type)) {
            $this->logDebug('CREDIT request detected — skipping session check', $userId, compact('type'));
            return $next($request);
        }

        $this->logDebug('Incoming request', $userId, [
            'input' => $request->all(),
        ]);

        $this->logDebug('Extracted values', $userId, compact('gameId', 'productId'));

// กำหนดตัวแปรควบคุม
        $skipGameCheck = $this->shouldSkipGameCheckByProduct($productId);
        $skipGameIdCheckForType = $type === 'DEBIT';
        $shouldRequireGameId = ! $skipGameCheck && ! $skipGameIdCheckForType;

// เช็คเฉพาะ productId เสมอ และ gameId ตามเงื่อนไข
        if (! $productId || ($shouldRequireGameId && ! $gameId)) {
            $this->logWarning('Missing required identifiers', $userId, [
                'gameId' => $gameId,
                'productId' => $productId,
                'shouldRequireGameId' => $shouldRequireGameId,
            ]);
            return $this->invalidResponse($request, 30002);
        }

        $redis = Redis::connection('game');
        $key = "user_game_status:{$userId}";
        $sessionRaw = $redis->get($key);

        if (! $sessionRaw) {
            $this->logWarning('Session not found', $userId, ['key' => $key]);
            return $this->invalidResponse($request, 30001);
        }

        $session = json_decode($sessionRaw, true);

        $this->logDebug('Compare session', $userId, [
            'currentSession' => $session,
            'requestGameId' => $gameId,
            'requestProductId' => $productId,
        ]);

        $skipGameCheck = $this->shouldSkipGameCheckByProduct($productId);
        $skipGameIdCheckForType = $type === 'DEBIT';

        if ($skipGameCheck || $skipGameIdCheckForType) {
            $this->logDebug('Skipping gameCode check due to config', $userId, compact('type', 'productId', 'skipGameCheck', 'skipGameIdCheckForType'));
        }

        $shouldCheckGameCode = ! $skipGameCheck && ! $skipGameIdCheckForType;
        $gameCodeMismatch = $shouldCheckGameCode && ($session['gameCode'] ?? null) !== $gameId;
        $productIdMismatch = ($session['productId'] ?? null) !== $productId;

        if ($gameCodeMismatch || $productIdMismatch) {
            $this->logWarning('Session mismatch', $userId, [
                'session_gameCode' => $session['gameCode'] ?? null,
                'session_productId' => $session['productId'] ?? null,
                'request_gameId' => $gameId,
                'request_productId' => $productId,
                'skipGameCheck' => $skipGameCheck,
                'skipGameIdCheckForType' => $skipGameIdCheckForType,
            ]);
            return $this->invalidResponse($request, 30001);
        }

        $this->logDebug('Session valid, refreshing TTL', $userId, compact('gameId', 'productId'));

        $session['last_active_at'] = now()->toDateTimeString();
        $redis->setex($key, 600, json_encode($session));

        return $next($request);
    }

    private function extractRequestType(Request $request): ?string
    {
        if ($request->has('type')) {
            return strtoupper($request->input('type'));
        }

        if ($request->has('txns') && is_array($request->input('txns'))) {
            return strtoupper($request->input('txns')[0]['status'] ?? '');
        }

        return null;
    }

    private function extractGameId(Request $request): ?string
    {
        if ($request->has('gameCode')) {
            return $request->input('gameCode');
        }

        if ($request->has('txns') && is_array($request->input('txns'))) {
            return $request->input('txns')[0]['gameCode'] ?? null;
        }

        return null;
    }

    private function shouldSkipSessionCheck(?string $type): bool
    {
        return $type === 'CREDIT';
    }

    private function shouldSkipGameCheckByProduct(?string $productId): bool
    {
        $skipGameCheckProducts = ['SBO','SEXY','BIGGAME','ALLBET','BETGAME','MICRO_LIVECASINO','PRETTY','WM'];
        return in_array(strtoupper($productId), $skipGameCheckProducts);
    }

    private function invalidResponse(Request $request, int $code)
    {
        return response()->json([
            'id' => $request->input('id'),
            'statusCode' => $code,
            'productId' => $request->input('productId'),
            'timestampMillis' => round(microtime(true) * 1000),
            'balance' => 0,
        ]);
    }

    private function logDebug(string $message, ?string $userId, array $context = []): void
    {
        if ($userId === 'boattester') {
            Log::info("[EnsureUserInCurrentGame] {$message}", array_merge(['userId' => $userId], $context));
        }
    }

    private function logWarning(string $message, ?string $userId, array $context = []): void
    {
        if ($userId === 'boattester') {
            Log::warning("[EnsureUserInCurrentGame] {$message}", array_merge(['userId' => $userId], $context));
        }
    }
}
