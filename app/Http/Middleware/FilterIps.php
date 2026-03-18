<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FilterIps
{
	protected $whitelist = [];
	
	public function __construct()
	{
		// ดึง config ips ทั้งหมด
		$config = config('filterips');
		
		// รวม allow_ips ของทุกหมวดเป็น array เดียว
		$this->whitelist = [];
		
		foreach ($config as $category) {
			if (isset($category['allow_ips']) && is_array($category['allow_ips'])) {
				$this->whitelist = array_merge($this->whitelist, $category['allow_ips']);
			}
		}
		
		// เอา IP ซ้ำออก
		$this->whitelist = array_unique($this->whitelist);
	}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
	public function handle(Request $request, Closure $next)
	{
		if (in_array($request->ip(), $this->whitelist)) {
			return $next($request);
		}
		
		abort(403, 'Your IP is not allowed.');
	}
}
