<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScanIC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;
    public int $maxExceptions = 3;
    public int $retryAfter = 5;

    protected string $date; // YYYY-MM-DD

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        $date = $this->date;

        // กำหนดช่วงวันให้ repo ใช้ (ทั้ง start/end = วันเดียวกัน)
        $param = [
            'startDate' => $date,
            'endDate' => $date,
        ];

        Log::channel('cashback')->info('ScanIC:start', ['date' => $date, 'param' => $param]);

        // ดึงรายการผู้เล่นจาก GameUserRepository ด้วยเงื่อนไขที่คุณใช้อยู่เดิม
        $repo = app('Gametech\Game\Repositories\GameUserRepository');

        // NOTE: ในระบบเดิมคุณใช้ checkUserTurn(1, 0, $param)
        $lists = $repo->checkUserTurn(1, 0, $param);

        Log::channel('cashback')->info('ScanIC:list_summary', [
            'date' => $date,
            'count' => is_countable($lists['result'] ?? null) ? count($lists['result']) : null,
        ]);

        if (empty($lists['result']) || ! is_array($lists['result'])) {
            Log::channel('cashback')->warning('ScanIC:empty_or_invalid_result', ['date' => $date]);

            return;
        }

        // แตกเป็นงานย่อย: ต่อสมาชิก 1 คน/1 งาน
        foreach ($lists['result'] as $item) {
            // ส่งข้อมูลที่จำเป็นเท่านั้นเพื่อลด payload
            $username = $item['username'] ?? null;
            $winlose = $item['winLost'] ?? 0;
            $turn = $item['turn'] ?? 0;

            if (! $username) {
                continue;
            }

            GrantIC::dispatch($date, $username, $winlose, $turn)
                ->onQueue('default');
        }

        Log::channel('cashback')->info('ScanCashback:done', ['date' => $date]);
    }
}
