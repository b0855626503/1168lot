<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersistRequestLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [5, 30];
    public int $timeout = 10;

    public function __construct(public array $data) {}

    public function handle(): void
    {
        // 1) mask header/body ที่อาจมี PII/secret
        $headers = $this->data['headers'] ?? [];
        unset($headers['authorization'], $headers['cookie']);

        $body = $this->data['body'] ?? [];
        data_set($body, 'password', '***');
        data_set($body, 'token', '***');

        // 2) บังคับ response เป็นสตริง + limit ความยาว
        $response = $this->stringify($this->data['response'] ?? '');
        if (strlen($response) > 2000) {
            $response = substr($response, 0, 2000) . '...';
        }

        // 3) ทำ trace_id ถ้าไม่ได้ส่งมา
        $traceId = (string) ($this->data['trace_id'] ?? '');
        if ($traceId === '') {
            $traceId = $this->makeTraceId([
                $this->data['url'] ?? '',
                $this->data['method'] ?? '',
                json_encode($this->data['txid'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                json_encode($this->data['roundId'] ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                $this->data['company'] ?? '',
            ]);
        }

        // 4) normalize ฟิลด์เสี่ยง (อาร์เรย์/อ็อบเจกต์) -> string/JSON
        $txid    = $this->data['txid']    ?? '';
        $roundId = $this->data['roundId'] ?? '';

        $row = [
            'trace_id'   => $traceId,
            'url'        => (string) ($this->data['url'] ?? ''),
            'method'     => (string) ($this->data['method'] ?? ''),
            'headers'    => json_encode($headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'body'       => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'     => $this->scalarOrNull($this->data['status'] ?? null),
            'response'   => $response,
            'duration'   => $this->numericOrNull($this->data['duration'] ?? null),
            'txid'       => is_array($txid) ? json_encode($txid, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $txid,
            'roundId'    => is_array($roundId) ? json_encode($roundId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $roundId,
            'company'    => (string) ($this->data['company'] ?? ''),
            'game_user'  => (string) ($this->data['game_user'] ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 5) upsert: กำหนดคอลัมน์ที่จะอัปเดต (ไม่แตะ created_at)
        DB::table('failed_requests')->upsert(
            [$row],
            ['trace_id'],
            [
                'url','method','headers','body','status','response',
                'duration','txid','roundId','company','game_user','updated_at'
            ]
        );
    }

    private function stringify(mixed $v): string
    {
        if (is_string($v)) return $v;
        if (is_numeric($v)) return (string) $v;
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_array($v) || is_object($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return '';
    }

    private function scalarOrNull(mixed $v): ?string
    {
        return (is_scalar($v) || $v === null) ? ($v === null ? null : (string) $v) : null;
    }

    private function numericOrNull(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    private function makeTraceId(array $parts): string
    {
        $salt = config('app.key') ?: 'trace_salt';
        return hash_hmac('sha1', implode('|', $parts), $salt);
    }
}
