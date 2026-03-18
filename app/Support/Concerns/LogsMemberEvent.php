<?php

namespace App\Support\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait LogsMemberEvent
{
    /**
     * ชื่อ table สำหรับบันทึก log (ตารางเดิม)
     */
    protected function memberLogTable(): string
    {
        return 'members_credit_log';
    }

    /**
     * connection ที่ใช้เขียน (ถ้าอยากแยกคิวต่อไป ค่อย override ที่คลาสผู้ใช้ trait)
     */
    protected function memberLogConnection(): ?string
    {
        return null; // null = default connection
    }

    /**
     * จะให้กลืน error (ไม่โยน) ไหมในเส้นไฟร้อน (แก้เป็น true ได้ที่คลาสที่ use trait)
     */
    protected function memberLogSilent(): bool
    {
        return false; // default เข้ม: error = throw (เหมือนเวอร์ชันเดิม)
    }

    /**
     * บันทึกเหตุการณ์กลาง (kind = LOG) ลง members_credit_log แบบเบาและไว
     *
     * @param  mixed        $memberOrCode  Model/array ที่มี key 'code' หรือเลข/สตริงของ member_code
     * @param  string       $remark        ข้อความเหตุการณ์ (สั้น ๆ)
     * @param  string|null  $referTable    ต้นทางอ้างอิง (ไม่ควรเป็น null)
     * @param  mixed|null   $referCode     รหัสอ้างอิง (ตัวเลข/สตริงก็ได้)
     * @param  array        $overrides     override ฟิลด์อื่น ๆ ตามต้องการ
     * @return mixed        true/id/Model แล้วแต่ writer; ถ้า silent และล้มเหลวอาจคืน false
     */
    protected function logMemberEvent(
        $memberOrCode,
        string $remark,
        string $referTable = null,
        $referCode = null,
        array $overrides = []
    ) {
        try {
            // 1) member identity
            [$memberCode, $memberUserName] = $this->extractMemberIdentity($memberOrCode);

            // 2) actor
            [$actorName, $actorCode] = $this->resolveActor();

            // 3) env
            $request = $this->getRequestSafely();
            $ip      = $request ? $request->ip() : '0.0.0.0';

            // 4) payload defaults (non-null, เบา, ไม่พึ่ง mutator/cast)
            $payload = [
                'kind'                   => 'LOG',
                'remark'                 => $this->clip((string) $remark, 500),
                'member_code'            => (int) $memberCode,
                'user_create'            => $this->clip($actorName, 100),
                'user_update'            => $this->clip($actorName, 100),
                'emp_code'               => (int) $actorCode,
                'ip'                     => $this->clip((string) $ip, 45),
                'auto'                   => 'Y',

                // ค่าทางการเงิน/เครดิต default เป็นศูนย์ทั้งหมด
                'credit_type'            => 'D',
                'game_code'              => 0,
                'gameuser_code'          => 0,
                'amount'                 => 0,
                'bonus'                  => 0,
                'total'                  => 0,
                'balance_before'         => 0,
                'balance_after'          => 0,
                'credit'                 => 0,
                'credit_bonus'           => 0,
                'credit_total'           => 0,
                'credit_before'          => 0,
                'credit_after'           => 0,

                'pro_code'               => 0,

                'bank_code'              => 0,

                // ป้องกัน null constraint
                'refer_table'            => $referTable ?? 'system',
                'refer_code'             => $referCode  ?? 0,


                'amount_balance'         => 0,
                'withdraw_limit'         => 0,
                'withdraw_limit_amount'  => 0,

                'user_name'              => $this->clip((string) ($memberUserName ?? ''), 150),
            ];

            // 5) overrides (เฉพาะฟิลด์ที่อนุญาต)
            if (!empty($overrides)) {
                $payload = array_replace($payload, $this->sanitizeOverrides($overrides));
            }

            // 6) เติมเวลา (เผื่อใช้คอลัมน์ date_create/date_update)
            $now = now();
            $payload += [
                'date_create' => $now,
                'date_update' => $now,
            ];

            // 7) เขียนแบบ "ไวสุด" ก่อน: Raw DB insert
            $written = $this->writeMemberLogViaDb($payload);
            if ($written !== null) {
                return $written; // true หรือ id แล้วแต่ driver/วิธี insert
            }

            // 8) fallback: Repository/Model (เพื่อเข้ากติกา observer ถ้ามี)
            $writer = $this->resolveMemberCreditLogWriter();
            if (is_array($writer) && isset($writer['type'])) {
                switch ($writer['type']) {
                    case 'repository':
                        return $writer['instance']->create($payload);

                    case 'model':
                        $class = $writer['class'];
                        /** @var \Illuminate\Database\Eloquent\Model $model */
                        return $class::query()->create($payload);
                }
            }

            // 9) ไม่เจอ writer ใดเลย
            throw new \RuntimeException('LogsMemberEvent: ไม่พบ writer สำหรับบันทึก log (db/repository/model)');
        } catch (\Throwable $e) {
            // โหมดเขี้ยว: โยนให้รู้เลย
            if (!$this->memberLogSilent()) {
                throw $e;
            }
            // โหมดเงียบ: log แล้วจบ
            Log::warning('LogsMemberEvent silent-fail', [
                'error' => $e->getMessage(),
                'class' => static::class,
            ]);
            return false;
        }
    }

    /**
     * พยายามเขียนผ่าน DB facade โดยตรง (เร็ว/เบา)
     * คืน true/lastInsertId หรือตามแต่ driver; ถ้าใช้ไม่ได้คืน null เพื่อให้ fallback ต่อ
     */
    protected function writeMemberLogViaDb(array $payload)
    {
        try {
            $conn = $this->memberLogConnection();
            $qb = $conn
                ? DB::connection($conn)->table($this->memberLogTable())
                : DB::table($this->memberLogTable());

            // บาง connection คืน bool, บางตัวคืน id (เช่น pdo->lastInsertId ใช้กับ insertGetId)
            // เราเลือกใช้ insert ธรรมดาเพื่อความกว้างของ driver
            $ok = $qb->insert($payload);
            return $ok; // true/false
        } catch (\Throwable $e) {
            // ให้ fallback ต่อไป repository/model
            Log::notice('LogsMemberEvent DB insert fallback', [
                'msg' => $e->getMessage(),
                'table' => $this->memberLogTable(),
                'conn' => $this->memberLogConnection(),
            ]);
            return null;
        }
    }

    /**
     * หา writer ถัดไป:
     * - property $memberCreditLogRepository ที่มีเมธอด create()
     * - container binds / class ชื่อที่ใช้จริง
     * - model class ตรง ๆ
     */
    protected function resolveMemberCreditLogWriter(): ?array
    {
        // 1) property บนคลาสที่ใช้ trait
        if (property_exists($this, 'memberCreditLogRepository')) {
            $repo = $this->memberCreditLogRepository ?? null;
            if ($repo && method_exists($repo, 'create')) {
                return ['type' => 'repository', 'instance' => $repo];
            }
        }

        // 2) container binds / class names
        $candidates = [
            'memberCreditLogRepository',
            'MemberCreditLogRepository',
            'gametech.member_credit_log.repository',
            \Gametech\Member\Repositories\MemberCreditLogRepository::class,
        ];

        foreach ($candidates as $key) {
            try {
                if (is_string($key) && app()->bound($key)) {
                    $repo = app($key);
                    if ($repo && method_exists($repo, 'create')) {
                        return ['type' => 'repository', 'instance' => $repo];
                    }
                } elseif (class_exists($key)) {
                    $repo = app($key);
                    if ($repo && method_exists($repo, 'create')) {
                        return ['type' => 'repository', 'instance' => $repo];
                    }
                }
            } catch (\Throwable $e) {
                // ข้ามแล้วลองตัวถัดไป
            }
        }

        // 3) fallback model
        $modelCandidates = [
            \Gametech\Member\Models\MemberCreditLog::class,
        ];
        foreach ($modelCandidates as $modelClass) {
            if (class_exists($modelClass) && method_exists($modelClass, 'query')) {
                return ['type' => 'model', 'class' => $modelClass];
            }
        }

        return null;
    }

    /**
     * ดึง code / user_name จาก object|array|scalar
     */
    protected function extractMemberIdentity($memberOrCode): array
    {
        $code = null;
        $user = null;

        if (is_object($memberOrCode)) {
            $code = $memberOrCode->code ?? null;
            $user = $memberOrCode->user_name ?? null;
        } elseif (is_array($memberOrCode)) {
            $code = $memberOrCode['code'] ?? null;
            $user = $memberOrCode['user_name'] ?? null;
        } else {
            $code = $memberOrCode;
        }

        if ($code === null || $code === '') {
            throw new \InvalidArgumentException('LogsMemberEvent: ต้องระบุ member_code หรือ object/array ที่มี key "code"');
        }

        return [$code, $user];
    }

    /**
     * ระบุตัวตนผู้ก่อเหตุการณ์ (actor)
     */
    protected function resolveActor(): array
    {
        if (method_exists($this, 'user')) {
            try {
                $u = $this->user();
                if ($u) {
                    return [
                        $u->user_name ?? ($u->name ?? 'System'),
                        (int) ($u->code ?? 0),
                    ];
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        try {
            /** @var Authenticatable|null $auth */
            $auth = auth()->user();
            if ($auth) {
                $name = $auth->user_name ?? ($auth->name ?? 'System');
                $code = (int) ($auth->code ?? 0);
                return [$name, $code];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return ['System', 0];
    }

    protected function getRequestSafely(): ?Request
    {
        try { return request(); } catch (\Throwable $e) { return null; }
    }

    /**
     * ทำความสะอาด overrides เบื้องต้น ป้องกัน null/ชน type
     */
    protected function sanitizeOverrides(array $overrides): array
    {
        $out = $overrides;

        // กัน refer_table null (ชน constraint)
        if (array_key_exists('refer_table', $out) && ($out['refer_table'] === null || $out['refer_table'] === '')) {
            unset($out['refer_table']); // คง default ‘system’
        }
        if (array_key_exists('refer_code', $out) && $out['refer_code'] === null) {
            $out['refer_code'] = 0;
        }

        // เลขที่คาดว่าเป็น int
        foreach ([
                     'member_code','emp_code','game_code','gameuser_code',
                     'amount','bonus','total',
                     'balance_before','balance_after',
                     'credit','credit_bonus','credit_total','credit_before','credit_after',
                     'pro_code','bank_code',
                     'amount_balance','withdraw_limit','withdraw_limit_amount',
                     'refer_code',
                 ] as $k) {
            if (array_key_exists($k, $out) && $out[$k] !== null && $out[$k] !== '') {
                $out[$k] = is_numeric($out[$k]) ? 0 + $out[$k] : $out[$k];
            }
        }

        // clip string ยาว ๆ ที่เสี่ยงชน schema
        foreach ([
                     'remark' => 500,
                     'user_create' => 100,
                     'user_update' => 100,
                     'user_name'   => 150,
                     'ip'          => 45,
                     'pro_name'    => 150,
                     'refer_table' => 50,
                     'kind'        => 20,
                     'kind_extra'  => 50,
                     'credit_type' => 1,
                     'auto'        => 1,
                 ] as $k => $len) {
            if (array_key_exists($k, $out) && $out[$k] !== null) {
                $out[$k] = $this->clip((string) $out[$k], $len);
            }
        }

        return $out;
    }

    protected function clip(string $s, int $max): string
    {
        return mb_strimwidth($s, 0, $max, '', 'UTF-8');
    }
}
