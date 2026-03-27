<?php

namespace Gametech\Lotto\Services\AutoResultV2\Config;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use InvalidArgumentException;

final class SelectionStageGuard
{
    public static function normalize(string $value): string
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, SelectionConfigData::allowedStages(), true)) {
            throw new InvalidArgumentException('selection_stage ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $references
     * @param array<string, array<int, string>> $availableFieldsByStage
     */
    public static function assertCompatibleReferences(string $selectionStage, array $references, array $availableFieldsByStage = []): void
    {
        $stage = self::normalize($selectionStage);

        $allowedStages = match ($stage) {
            SelectionConfigData::STAGE_PRE_MAPPING => ['fetch', 'parser'],
            SelectionConfigData::STAGE_POST_MAPPING => ['fetch', 'parser', 'mapping'],
            default => throw new InvalidArgumentException('selection_stage ไม่ถูกต้อง: ' . $selectionStage),
        };

        ConfigReferenceGuard::assertReferences($references, $allowedStages, $availableFieldsByStage, 'selection_stage=' . $stage);
    }
}
