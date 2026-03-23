<?php

namespace Gametech\FrontendApi\Http\Middleware;

use Closure;
use Gametech\FrontendApi\Services\FrontendTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateFrontendToken
{
    private FrontendTokenService $tokenService;

    public function __construct(FrontendTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return $this->unauthorized('ไม่พบ Bearer token');
        }

        $payload = $this->tokenService->decode($token);
        if (! $payload) {
            return $this->unauthorized('token ไม่ถูกต้องหรือหมดอายุ');
        }

        $member = $this->tokenService->resolveMember($token);
        if (! $member) {
            return $this->unauthorized('ไม่พบข้อมูลสมาชิก');
        }

        Auth::shouldUse('customer');
        Auth::guard('customer')->setUser($member);

        $request->attributes->set('frontend_api_token_payload', $payload);
        $request->setUserResolver(static function () use ($member) {
            return $member;
        });

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}

