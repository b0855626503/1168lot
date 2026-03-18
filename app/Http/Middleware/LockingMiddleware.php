<?php

declare(strict_types=1);

/*
 * This file is part of Alt Three Locker.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Middleware;

use AltThree\Locker\Exceptions\UnableToAcquireLockException;
use AltThree\Locker\Http\Exception\LockedHttpException;
use AltThree\Locker\Locker;
use Closure;
use Illuminate\Http\Request;

/**
 * This is the locking middleware class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class LockingMiddleware
{
    /**
     * The locker instance.
     *
     * @var Locker
     */
    protected $locker;

    /**
     * The URIs that should be excluded.
     *
     * @var string[]
     */
    protected $except = [];

    /**
     * Create a new locking middleware instance.
     *
     * @param Locker $locker
     *
     * @return void
     */
    public function __construct(Locker $locker)
    {
        $this->locker = $locker;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     *@throws \AltThree\Locker\Http\Exception\LockedHttpException
     *
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true) || $this->shouldPassThrough($request)) {
            return $next($request);
        }

        $function = function () use ($request, $next) {
            return $next($request);
        };

        $name = 'session-'.$request->session()->getId();

        try {
            $response = $this->locker->execute($function, $name, 1);
        } catch (UnableToAcquireLockException $e) {
            throw new LockedHttpException('Unable to acquire lock.', $e, $e->getCode());
        }

        return $response;
    }

    /**
     * Determine if the request has a URI that should pass through.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function shouldPassThrough(Request $request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
