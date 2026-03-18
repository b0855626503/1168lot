<?php
	
	namespace App\Http\Middleware;
	
	use Closure;
	use Illuminate\Support\Facades\RateLimiter;
	use Illuminate\Http\Request;
	
	class ThrottleRootPath
	{
		public function handle(Request $request, Closure $next)
		{
			// ดักเฉพาะ path '/' เท่านั้น
			if ($request->is('/')) {
				$key = 'root_path:' . $request->ip();
				$maxAttempts = 5; // จำกัด 10 ครั้ง/นาที
				$decaySeconds = 60;
				
				if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
					abort(429, 'Too Many Requests');
				}
				
				RateLimiter::hit($key, $decaySeconds);
			}
			
			return $next($request);
		}
	}
