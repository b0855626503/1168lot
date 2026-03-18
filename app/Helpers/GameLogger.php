<?php

namespace App\Helpers;

use App\Services\RedisGameLogService;
use Gametech\API\Models\GameLogProxy;
use Illuminate\Support\Facades\Log;

class GameLogger
{
    /**
     * Log ไปที่ Redis และ Mongo (เลือกได้)
     *
     * @return void|null
     */
    public static function log(array $data, bool $writeToMongo = false, int $ttl = 60): void
    {
        if (!self::isValidData($data)) {
            Log::error('Invalid data for key generation', compact('data'));
        }

        $company = $data['company'] ?? 'unknown';
        $gameUser = $data['game_user'] ?? 'unknown';
        $method = $data['method'] ?? 'UNKNOWN';
        $id = $data['con_1'] ?? uniqid();

        // เขียนลง Redis
        RedisGameLogService::put($company, $gameUser, $method, $id, $data, $ttl);

        // เขียนลง Mongo ถ้าต้องการ
        if ($writeToMongo) {
            GameLogProxy::create($data);
        }
    }

    /**
     * ตรวจสอบความถูกต้องของข้อมูล
     */
    private static function isValidData(array $data): bool
    {
        return is_array($data) &&
            array_keys($data) !== range(0, count($data) - 1) &&
            isset($data['company'], $data['game_user'], $data['method']);
    }

    /**
     * Log และคืนค่า ID ที่สร้างขึ้น
     */
    public static function logReturnId(array $data, bool $writeToMongo = false, int $ttl = 60): ?string
    {
        if (!self::isValidData($data)) {
            Log::error('Invalid data for key generation', compact('data'));

            return null;
        }

        $company = $data['company'] ?? 'unknown';
        $gameUser = $data['game_user'] ?? 'unknown';
        $method = $data['method'] ?? 'UNKNOWN';
        $id = $data['con_1'] ?? uniqid();

        RedisGameLogService::put($company, $gameUser, $method, $id, $data, $ttl);

        if ($writeToMongo) {
            GameLogProxy::create($data);
        }

        return implode(':', [$company, $gameUser, $method, $id]);
    }

    /**
     * อัปเดต log ตาม key
     */
    public static function updateByKey(string $key, array $fields): void
    {
        if (empty($key) || !is_string($key)) {
            Log::error('Invalid key passed to updateByKey', ['key' => $key]);

            return;
        }

        $parts = explode(':', $key);
        if (count($parts) !== 4) {
            Log::error('Invalid key format for updateByKey', ['key' => $key]);

            return;
        }

        [$company, $gameUser, $method, $id] = $parts;
        self::update($company, $gameUser, $method, $id, $fields);
    }

    /**
     * อัปเดต field แบบ sync ทั้ง Redis และ Mongo
     */
    public static function update(string $company, string $gameUser, string $method, string $id, array $fields): void
    {
        foreach ($fields as $field => $value) {
            RedisGameLogService::updateField($company, $gameUser, $method, $id, $field, $value);
        }

        GameLogProxy::where([
            'company' => $company,
            'game_user' => $gameUser,
            'method' => $method,
            'con_1' => $id,
        ])->update($fields);
    }

    /**
     * สร้าง key จากข้อมูล
     */
    public static function buildKeyFromData(array $data): ?string
    {
        if (!self::isValidData($data)) {
            Log::error('Invalid data for key generation, must be associative array', ['data' => $data]);

            return null;
        }

        $data['con_1'] = $data['con_1'] ?? uniqid('__fallback__');

        return "log:{$data['company']}:{$data['game_user']}:{$data['method']}:{$data['con_1']}";
    }
}
