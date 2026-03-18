<?php

namespace App\Helpers;

use Gametech\API\Models\GameLogProxy;
use Illuminate\Support\Facades\Redis;

class GameLogService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection('gamelog'); // ใช้ connection ชื่อ 'game'
    }

    /**
     * บันทึก log และเพิ่ม id ลง Redis Set สำหรับ roundId
     */
    public function createGameLogWithRedisSet(array $data)
    {
        $log = GameLogProxy::create($data);

        // เก็บ _id ของ log ลง Redis Set ของ roundId
        if (!empty($data['con_2']) && !empty($data['con_1'])) {
            $roundId = $data['con_2'];
            $txnId = $data['con_1'];

            // 1. เก็บ _id ลง Redis Set สำหรับ roundId
            $this->redis->sadd("round_logs:{$roundId}", (string)$log->_id);

            // 2. เก็บ mapping txnId -> logId
            $this->redis->hset("round_log_index:{$roundId}", $txnId, (string)$log->_id);
        }

        return $log;
    }

    /**
     * อัพเดต field con_4 ของ log ทั้งหมดในรอบนั้น
     */
    public function updateCon4ByRound($roundId, $status, $settleId)
    {
        $logIds = $this->getLogIdsByRound($roundId);
        if (empty($logIds)) {
            return false; // ไม่มี log ที่จะ update
        }

        $con4Value = $status . '_' . $settleId;

        // อัพเดต batch ทีละ 500 (ปรับได้ตามเหมาะสม)
        $chunks = array_chunk($logIds, 500);

        foreach ($chunks as $chunk) {
            GameLogProxy::whereIn('_id', $chunk)
                ->update(['con_4' => $con4Value]);
        }

        return true;
    }

    /**
     * ดึง log ids จาก Redis หรือ MongoDB fallback
     */
    public function getLogIdsByRound($roundId): array
    {
        $logIds = $this->redis->smembers("round_logs:{$roundId}");

        if (empty($logIds)) {
            // fallback query MongoDB
            $logs = GameLogProxy::where('con_2', $roundId)->get();
            $logIds = $logs->pluck('_id')->map(function ($id) {
                return (string)$id;
            })->toArray();

            // เก็บกลับ Redis เพื่อรอบถัดไป
            if (!empty($logIds)) {
                $this->redis->sadd("round_logs:{$roundId}", ...$logIds);
            }
        }

        return $logIds;
    }
}
