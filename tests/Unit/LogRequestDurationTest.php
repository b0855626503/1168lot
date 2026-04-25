<?php

namespace Tests\Unit;

use App\Listeners\LogRequestDuration;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogRequestDurationTest extends TestCase
{
    public function test_listener_logs_warning_when_queue_dispatch_fails(): void
    {
        Log::spy();

        $listener = new class extends LogRequestDuration
        {
            protected function dispatchPersistRequestLog(array $payload): void
            {
                throw new \RuntimeException('redis auth failed');
            }
        };

        $request = Request::create('/api/v1/test', 'POST', [
            'productId' => 'pg',
            'username' => 'user01',
            'txns' => [
                [
                    'id' => 'tx-1',
                    'roundId' => 'round-1',
                    'betAmount' => 10,
                    'payAmount' => 20,
                    'amount' => 5,
                ],
            ],
        ]);
        $request->server->set('REQUEST_TIME_FLOAT', microtime(true) - 4.2);

        $response = new Response(['ok' => true], 500);

        $listener->handle(new RequestHandled($request, $response));

        Log::shouldHaveReceived('warning')
            ->with('request_duration_dispatch_failed', \Mockery::on(function (array $context): bool {
                return $context['status'] === 500
                    && $context['url'] === 'http://localhost/api/v1/test'
                    && $context['exception_class'] === \RuntimeException::class
                    && $context['exception_message'] === 'redis auth failed'
                    && $context['duration'] >= 4.1;
            }))
            ->once();
    }
}
