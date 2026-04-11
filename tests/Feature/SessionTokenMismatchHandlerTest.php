<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class SessionTokenMismatchHandlerTest extends TestCase
{
    public function test_it_returns_json_for_ajax_token_mismatch_requests(): void
    {
        $request = Request::create('/admin/dashboard', 'POST', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setRouteResolver(fn (): Route => new Route('POST', 'admin/dashboard', []));

        $response = app(Handler::class)->render($request, new TokenMismatchException);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Session expired. Please try again.',
        ], $response->getData(true));
    }

    public function test_it_redirects_admin_requests_to_admin_login_when_token_mismatch_happens(): void
    {
        $request = Request::create('/admin/dashboard', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setRouteResolver(
            fn (): Route => (new Route('POST', 'admin/dashboard', []))->name('admin.dashboard.index')
        );

        $response = app(Handler::class)->render($request, new TokenMismatchException);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('admin.session.index'), $response->getTargetUrl());
        $this->assertSame('Session expired. Please try again.', $response->getSession()->get('error'));
    }
}
