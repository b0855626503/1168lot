<?php

namespace Gametech\Lotto\Services\AutoResultV2\Config;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\MappingConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\ParserConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\ReadinessConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\ValidationConfigData;
use InvalidArgumentException;

final class SourcePipelineConfigCompiler
{
    public function compile(array $config): CompiledSourcePipelineData
    {
        $compiled = CompiledSourcePipelineData::fromArray($config);

        $this->validateStageReferences($compiled);

        return $compiled;
    }

    private function validateStageReferences(CompiledSourcePipelineData $compiled): void
    {
        $parserFields = $compiled->parser()->fieldNames();
        $mappingFields = $compiled->mapping()->fieldNames();
        $validationFields = $compiled->validation()->fieldNames();
        $readinessFields = $compiled->readiness()->fieldNames();

        $available = [
            'parser' => $parserFields,
            'mapping' => $mappingFields,
            'validation' => $validationFields,
            'readiness' => $readinessFields,
            'fetch' => [],
            'selection' => $compiled->selection()->fieldNames(),
        ];

        $this->assertMappingReferences($compiled->mapping(), $available);
        $this->assertSelectionReferences($compiled->selection(), $available);
        $this->assertValidationReferences($compiled->validation(), $available);
        $this->assertReadinessReferences($compiled->readiness(), $available);
    }

    /**
     * @param array<string, array<int, string>> $available
     */
    private function assertMappingReferences(MappingConfigData $mapping, array $available): void
    {
        ConfigReferenceGuard::assertReferences($mapping->references(), ['fetch', 'parser'], $available, 'mapping');
    }

    /**
     * @param array<string, array<int, string>> $available
     */
    private function assertSelectionReferences(SelectionConfigData $selection, array $available): void
    {
        SelectionStageGuard::assertCompatibleReferences($selection->selectionStage(), $selection->references(), $available);
        ConfigReferenceGuard::assertReferences(
            $selection->references(),
            $selection->selectionStage() === SelectionConfigData::STAGE_PRE_MAPPING
                ? ['fetch', 'parser']
                : ['fetch', 'parser', 'mapping'],
            $available,
            'selection'
        );

        if ($selection->dateField() !== null) {
            ConfigReferenceGuard::assertFieldReference($selection->dateField(), [], $available, 'selection.date_field');
        }
    }

    /**
     * @param array<string, array<int, string>> $available
     */
    private function assertValidationReferences(ValidationConfigData $validation, array $available): void
    {
        ConfigReferenceGuard::assertReferences($validation->references(), ['fetch', 'parser', 'mapping', 'selection'], $available, 'validation');
        foreach ($validation->requiredFields() as $field) {
            ConfigReferenceGuard::assertFieldReference($field, [], $available, 'validation.required_fields');
        }
    }

    /**
     * @param array<string, array<int, string>> $available
     */
    private function assertReadinessReferences(ReadinessConfigData $readiness, array $available): void
    {
        if (! $readiness->enabled()) {
            return;
        }

        ConfigReferenceGuard::assertReferences($readiness->requiredReferences(), ['fetch', 'parser', 'mapping', 'selection', 'validation'], $available, 'readiness');
        foreach ($readiness->minimumRequiredKeys() as $field) {
            ConfigReferenceGuard::assertFieldReference($field, [], $available, 'readiness.minimum_required_keys');
        }
    }
}
