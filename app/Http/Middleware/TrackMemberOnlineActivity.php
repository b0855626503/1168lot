<?php

namespace App\Http\Middleware;

use App\Services\Online\MemberOnlineService;
use Closure;
use Illuminate\Http\Request;

class TrackMemberOnlineActivity
{
    public function __construct(private MemberOnlineService $memberOnlineService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $member = auth()->guard('customer')->user();

        if ($member && isset($member->code)) {
            $this->memberOnlineService->touch((int) $member->code);
        }

        return $next($request);
    }
}
