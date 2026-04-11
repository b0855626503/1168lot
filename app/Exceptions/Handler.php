<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        ConnectionException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $e)
    {

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Session expired. Please try again.',
                ], 419);
            }

            return redirect()->route($this->resolveSessionExpiredRoute($request))
                ->with('error', 'Session expired. Please try again.');
        }

        return parent::render($request, $e);
    }

    private function resolveSessionExpiredRoute(Request $request): string
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return 'admin.session.index';
        }

        return Route::has('customer.session.index')
            ? 'customer.session.index'
            : 'admin.session.index';
    }

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
