<?php

namespace App\Listeners;

use App\Jobs\PersistRequestLog;
use App\Jobs\SendTelegramAlert;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogRequestDuration
{
    public function handle(RequestHandled $event): void
    {
        $request = $event->request;
        $response = $event->response;

        $host = $request->getHost();
        //        $isApiHost = Str::startsWith($host, 'api.') || Str::contains($host, 'api789.');
        $isApiPath = $request->is('api/*');

        if (! ($isApiPath)) {
            return;
        }

        $start = (float) $request->server('REQUEST_TIME_FLOAT', microtime(true));
        $duration = microtime(true) - $start;
        $status = (int) $response->getStatusCode();

        if ($status < 200 || $status >= 300 || $duration > 3.5) {
            $session = $request->all();

            // ดึง txid/roundId แบบปลอดภัย (อาจไม่มีหรือไม่เป็นอาร์เรย์)
            $txns = (array) data_get($session, 'txns', []);
            $txIds = array_values(array_filter((array) data_get($session, 'txns.*.id', [])));
            $roundIds = array_values(array_filter((array) data_get($session, 'txns.*.roundId', [])));

            $totals = $this->sumTxnAmounts($txns);

            // ดึง response content แบบกันกรณีพิเศษ
            $content = '';
            if (method_exists($response, 'getContent')) {
                try {
                    $content = $response->getContent();
                } catch (\Throwable $e) {
                    $content = '[non-buffered response]';
                }
            }

            $payload = [
                'trace_id' => (string) Str::uuid(),
                'url' => (string) $request->fullUrl(),
                'method' => (string) $request->method(),
                'headers' => $request->headers->all(),   // ไป mask ใน Job
                'body' => $session,                   // ไป mask/trim ใน Job
                'status' => $status,
                'response' => $content,                   // ไป limit ใน Job
                'duration' => round($duration, 3),
                'created_at' => now()->toISOString(),

                'txid' => $txIds,       // ส่งเป็น array ไป ให้ Job แปลง
                'roundId' => $roundIds,    // ส่งเป็น array ไป ให้ Job แปลง
                'betAmount' => $totals['sum']['betAmount'] ?? '0.00',
                'payAmount' => $totals['sum']['payAmount'] ?? '0.00',
                'amount' => $totals['sum']['amount'] ?? '0.00',

                'company' => (string) data_get($session, 'productId', ''),
                'game_user' => (string) data_get($session, 'username', ''),
                'ip' => (string) $request->ip(),
            ];

            // แนะให้แยกคิวเฉพาะงาน log เช่น 'logs' จะอ่านง่ายกว่า
            try {
                $this->dispatchPersistRequestLog($payload);

                if ($status >= 500 || $duration > 3.5) {
                    $msg = "ค่าย {$payload['company']} ID {$payload['game_user']} API {$payload['url']} ".
                        "Status {$payload['status']} Duration {$payload['duration']} วิ ".
                        'ID '.json_encode($payload['txid']).' RoundId '.json_encode($payload['roundId']).' '.
                        "BetAmount {$payload['betAmount']} PayAmount {$payload['payAmount']} Amount {$payload['amount']}";

                    $this->dispatchTelegramAlert($msg);
                }
            } catch (\Throwable $e) {
                Log::warning('request_duration_dispatch_failed', [
                    'url' => (string) $request->fullUrl(),
                    'status' => $status,
                    'duration' => round($duration, 3),
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchPersistRequestLog(array $payload): void
    {
        PersistRequestLog::dispatch($payload)->onQueue('default');
    }

    protected function dispatchTelegramAlert(string $message): void
    {
        SendTelegramAlert::dispatch('notify/send', $message)->onQueue('default');
    }

    /**
     * รวมยอดตัวเลขใน txns:
     * - รองรับ betAmount / payAmount / amount
     * - ใช้หน่วยสตางค์กัน float แล้วค่อย format กลับ
     * - ถ้ามีทั้ง betAmount และ payAmount จะคืน net = pay - bet
     */
    private function sumTxnAmounts(array $txns): array
    {
        if (empty($txns)) {
            return ['present' => [], 'sum' => [], 'net' => null, 'count' => 0];
        }

        $candidates = ['betAmount', 'payAmount', 'amount'];
        $first = (array) ($txns[0] ?? []);
        $present = array_values(array_filter($candidates, fn ($k) => array_key_exists($k, $first)));

        $toMinor = fn ($v) => (int) round(((float) $v) * 100);
        $fromMinor = fn (int $v) => number_format($v / 100, 2, '.', '');

        $sumMinor = array_fill_keys($present, 0);

        foreach ($txns as $t) {
            foreach ($present as $k) {
                $sumMinor[$k] += $toMinor(data_get($t, $k, 0));
            }
        }

        $sum = [];
        foreach ($sumMinor as $k => $minor) {
            $sum[$k] = $fromMinor($minor);
        }

        $net = null;
        if (in_array('betAmount', $present, true) && in_array('payAmount', $present, true)) {
            $net = $fromMinor($sumMinor['payAmount'] - $sumMinor['betAmount']);
        }

        return [
            'present' => $present,
            'sum' => $sum,
            'net' => $net,
            'count' => count($txns),
        ];
    }
}
