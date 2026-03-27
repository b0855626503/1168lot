<?php

namespace Gametech\Lotto\Services\AutoResultV2\Config;

use InvalidArgumentException;

final class ConfigReferenceGuard
{
    /**
     * @param array<int, string> $allowedStages
     * @param array<string, array<int, string>> $availableFieldsByStage
     */
    public static function assertReferences(
        array $references,
        array $allowedStages = [],
        array $availableFieldsByStage = [],
        string $context = 'config'
    ): void {
        foreach ($references as $reference) {
            self::assertReference((string) $reference, $allowedStages, $availableFieldsByStage, $context);
        }
    }

    /**
     * @param array<int, string> $allowedStages
     * @param array<string, array<int, string>> $availableFieldsByStage
     */
    public static function assertReference(
        string $reference,
        array $allowedStages = [],
        array $availableFieldsByStage = [],
        string $context = 'config'
    ): void {
        $reference = trim($reference);
        if ($reference === '') {
            throw new InvalidArgumentException($context . ': reference ว่าง');
        }

        $parts = explode('.', $reference, 2);
        $stage = strtolower(trim((string) ($parts[0] ?? '')));
        $fieldPath = trim((string) ($parts[1] ?? ''));

        if (count($parts) === 1) {
            self::assertFieldReference($reference, $allowedStages, $availableFieldsByStage, $context);

            return;
        }

        if ($stage === '' || $fieldPath === '') {
            throw new InvalidArgumentException($context . ': reference ไม่ถูกต้อง: ' . $reference);
        }

        $knownStages = $allowedStages !== [] ? $allowedStages : array_keys($availableFieldsByStage);
        if ($knownStages !== [] && ! in_array($stage, $knownStages, true)) {
            throw new InvalidArgumentException($context . ': stage reference ไม่อนุญาต: ' . $reference);
        }

        self::assertFieldPath($fieldPath, $context, $reference);

        if ($availableFieldsByStage !== []) {
            $stageFields = $availableFieldsByStage[$stage] ?? [];
            $rootField = explode('.', $fieldPath, 2)[0] ?? '';

            if ($stageFields !== [] && ! in_array($rootField, $stageFields, true)) {
                throw new InvalidArgumentException($context . ': unknown field reference: ' . $reference);
            }
        }
    }

    /**
     * @param array<int, string> $allowedStages
     * @param array<string, array<int, string>> $availableFieldsByStage
     */
    public static function assertFieldReference(
        string $reference,
        array $allowedStages = [],
        array $availableFieldsByStage = [],
        string $context = 'config'
    ): void {
        $reference = trim($reference);
        if ($reference === '') {
            throw new InvalidArgumentException($context . ': field reference ว่าง');
        }

        if (str_contains($reference, '.')) {
            self::assertReference($reference, $allowedStages, $availableFieldsByStage, $context);

            return;
        }

        self::assertFieldPath($reference, $context, $reference);

        if ($availableFieldsByStage === []) {
            return;
        }

        $available = [];
        $stagesToInspect = $allowedStages !== [] ? $allowedStages : array_keys($availableFieldsByStage);
        foreach ($stagesToInspect as $stageName) {
            foreach ($availableFieldsByStage[$stageName] ?? [] as $field) {
                $available[] = $field;
            }
        }

        if ($available !== [] && ! in_array($reference, $available, true)) {
            throw new InvalidArgumentException($context . ': unknown field reference: ' . $reference);
        }
    }

    /**
     * @param array<int, string> $references
     * @param array<int, string> $allowedStages
     */
    public static function assertStageReferences(array $references, array $allowedStages, string $context = 'config'): void
    {
        foreach ($references as $reference) {
            self::assertStageReference((string) $reference, $allowedStages, $context);
        }
    }

    /**
     * @param array<int, string> $allowedStages
     */
    public static function assertStageReference(string $reference, array $allowedStages, string $context = 'config'): void
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new InvalidArgumentException($context . ': stage reference ว่าง');
        }

        if (! str_contains($reference, '.')) {
            throw new InvalidArgumentException($context . ': stage reference ต้องมี prefix stage: ' . $reference);
        }

        $stage = strtolower((string) explode('.', $reference, 2)[0]);
        if (! in_array($stage, $allowedStages, true)) {
            throw new InvalidArgumentException($context . ': stage reference ไม่อนุญาต: ' . $reference);
        }

        self::assertFieldPath((string) explode('.', $reference, 2)[1], $context, $reference);
    }

    private static function assertFieldPath(string $fieldPath, string $context, string $reference): void
    {
        $fieldPath = trim($fieldPath);
        if ($fieldPath === '') {
            throw new InvalidArgumentException($context . ': reference ไม่ถูกต้อง: ' . $reference);
        }

        $segments = explode('.', $fieldPath);
        foreach ($segments as $segment) {
            if ($segment === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $segment)) {
                throw new InvalidArgumentException($context . ': field reference ไม่ถูกต้อง: ' . $reference);
            }
        }
    }
}
