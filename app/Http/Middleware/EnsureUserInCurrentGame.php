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
        $gameId = $this->normalizeGameCode($this->extractGameId($request));
        $productId = $this->normalizeProductId($request->input('productId'));
        $requestSessionToken = $this->extractSessionToken($request);

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

        $sessionGameCode = $this->normalizeGameCode($session['gameCode'] ?? null);
        $sessionProductId = $this->normalizeProductId($session['productId'] ?? null);
        $sessionToken = (string) ($session['sessionToken'] ?? '');
        $shouldCheckGameCode = ! $skipGameCheck && ! $skipGameIdCheckForType;
        $gameCodeMismatch = $shouldCheckGameCode && $sessionGameCode !== $gameId;
        $productIdMismatch = $sessionProductId !== $productId;
        $sessionTokenMismatch = $requestSessionToken !== null && $sessionToken !== '' && $sessionToken !== $requestSessionToken;

        if ($gameCodeMismatch || $productIdMismatch || $sessionTokenMismatch) {
            $this->logWarning('Session mismatch', $userId, [
                'session_gameCode' => $session['gameCode'] ?? null,
                'session_productId' => $session['productId'] ?? null,
                'session_sessionToken' => $sessionToken,
                'request_gameId' => $gameId,
                'request_productId' => $productId,
                'request_sessionToken' => $requestSessionToken,
                'skipGameCheck' => $skipGameCheck,
                'skipGameIdCheckForType' => $skipGameIdCheckForType,
                'sessionTokenMismatch' => $sessionTokenMismatch,
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

    private function extractSessionToken(Request $request): ?string
    {
        $token = $request->input('sessionToken', $request->input('token'));
        if (is_scalar($token)) {
            $normalized = trim((string) $token);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        $txns = $request->input('txns');
        if (is_array($txns) && isset($txns[0]) && is_array($txns[0])) {
            $txnToken = $txns[0]['sessionToken'] ?? ($txns[0]['token'] ?? null);
            if (is_scalar($txnToken)) {
                $normalized = trim((string) $txnToken);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        return null;
    }

    private function normalizeProductId($productId): ?string
    {
        if (! is_scalar($productId)) {
            return null;
        }

        $value = strtoupper(trim((string) $productId));

        return $value === '' ? null : $value;
    }

    private function normalizeGameCode($gameCode): ?string
    {
        if (! is_scalar($gameCode)) {
            return null;
        }

        $value = strtolower(trim((string) $gameCode));

        return $value === '' ? null : $value;
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
