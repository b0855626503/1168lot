<?php

declare(strict_types=1);

namespace Gametech\Game\Services;

use Throwable;

/**
 * Service สำหรับดึงเครดิต Agent ของเกม (Seamless)
 * - คืนค่าแบบ Blade-friendly: credits.game / credits.gamefree
 * - ตัดสินใจเรียก SeamlessRepository ตาม config จาก core()->getConfigData()
 */
final class GameCreditService
{
    /**
     * ดึงเครดิตสำหรับนำไปแสดงใน Blade ได้ทันที
     *
     * @param  bool  $debug  ส่งต่อไปยัง SeamlessRepository (ถ้า repo รองรับ)
     * @return array{
     *   enabled: bool,
     *   freecredit_open: bool,
     *   success: bool,
     *   credits: array{game: float, gamefree: float},
     *   fetched_methods: array<int, string>,
     *   message: string
     * }
     */
    public function getAgentCreditsForBlade(bool $debug = false): array
    {
        $base = [
            'enabled'         => false,
            'freecredit_open' => false,
            'success'         => true,
            'credits'         => [
                'game'     => 0.0,
                'gamefree' => 0.0,
            ],
            'fetched_methods' => [],
            'message'         => '',
        ];

        try {
            $config = core()->getConfigData();
        } catch (Throwable $e) {
            // ถ้าอ่าน config ไม่ได้ ถือว่าไม่ทำงานและคืนค่า 0 ทั้งคู่
            $base['success'] = false;
            $base['message'] = 'Cannot read core config data.';
            return $base;
        }

        $seamlessFlag = (string) ($config->seamless ?? '');
        $freeFlag     = (string) ($config->freecredit_open ?? 'N');

        // ถ้าไม่ใช่ seamless ก็ "ไม่ต้องทำงาน" ตามที่กำหนด
        if ($seamlessFlag !== 'Y') {
            $base['enabled'] = false;
            $base['freecredit_open'] = ($freeFlag === 'Y');
            $base['success'] = true;
            $base['message'] = 'Seamless is disabled. Skip fetching agent credit.';
            return $base;
        }

        $base['enabled'] = true;
        $base['freecredit_open'] = ($freeFlag === 'Y');

        // เรียก method=game เสมอ เมื่อ seamless เปิด
        $gameResp = $this->callSeamlessGetAgentCredit('game', $debug);
        $base['fetched_methods'][] = 'game';
        $base['credits']['game'] = (float) ($gameResp['credit'] ?? 0);

        $overallSuccess = (bool) ($gameResp['success'] ?? false);

        // ถ้าเปิด freecredit ให้เรียกเพิ่ม method=gamefree
        if ($base['freecredit_open'] === true) {
            $freeResp = $this->callSeamlessGetAgentCredit('gamefree', $debug);
            $base['fetched_methods'][] = 'gamefree';
            $base['credits']['gamefree'] = (float) ($freeResp['credit'] ?? 0);

            $overallSuccess = $overallSuccess && (bool) ($freeResp['success'] ?? false);
        }

        $base['success'] = $overallSuccess;

        if ($base['success'] !== true) {
            $base['message'] = 'Fetching agent credit failed (one or more methods).';
        } else {
            $base['message'] = 'OK';
        }

        return $base;
    }

    /**
     * เรียก SeamlessRepository::getAgentCredit() ตาม pattern ที่คุณใช้งานอยู่
     *
     * @param  string  $method  'game'|'gamefree'
     * @param  bool    $debug
     * @return array{success: bool, credit: float}
     */
    private function callSeamlessGetAgentCredit(string $method, bool $debug): array
    {
        try {
            /** @var object $repo */
            $repo = app('Gametech\Game\Repositories\Games\SeamlessRepository', [
                'method' => $method,
                'debug'  => $debug,
            ]);

            // คาดหวังว่า repo คืน ['success'=>bool,'credit'=>...]
            $resp = $repo->getAgentCredit();

            return [
                'success' => (bool) ($resp['success'] ?? false),
                'credit'  => (float) ($resp['credit'] ?? 0),
            ];
        } catch (Throwable) {
            return [
                'success' => false,
                'credit'  => 0.0,
            ];
        }
    }
}
