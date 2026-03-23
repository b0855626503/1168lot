<?php

namespace Gametech\Lotto\Support;

use InvalidArgumentException;

final class DrawStatusFlow
{
    /**
     * @return array<int, string>
     */
    public static function allowedStatuses(): array
    {
        return ['draft', 'open', 'closed'];
    }

    /**
     * @return array<int, string>
     */
    public static function transitionSteps(string $currentStatus, string $targetStatus): array
    {
        $current = trim($currentStatus);
        $target = trim($targetStatus);

        if ($target === 'resulted') {
            throw new InvalidArgumentException('การประกาศผลต้องใช้ปุ่มประกาศผลเท่านั้น');
        }

        if (! in_array($current, self::allowedStatuses(), true) || ! in_array($target, self::allowedStatuses(), true)) {
            throw new InvalidArgumentException('สถานะงวดไม่ถูกต้อง');
        }

        if ($current === $target) {
            return [];
        }

        return match ($current . '->' . $target) {
            'draft->open' => ['open'],
            'draft->closed' => ['open', 'close'],
            'open->closed' => ['close'],
            'closed->open' => ['open'],
            default => throw new InvalidArgumentException('ไม่สามารถเปลี่ยนสถานะงวดตามลำดับนี้ได้'),
        };
    }
}

