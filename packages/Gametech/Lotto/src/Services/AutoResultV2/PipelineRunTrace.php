<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Trace\PipelineTraceNormalizer;

final class PipelineRunTrace
{
    public const SHADOW_COMPARE_MATCH = 'MATCH';
    public const SHADOW_COMPARE_MISMATCH = 'MISMATCH';
    public const SHADOW_COMPARE_ERROR = 'ERROR';
    public const SHADOW_COMPARE_SKIPPED = 'SKIPPED';

    private array $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromArray(array $data): self
    {
        $normalized = PipelineTraceNormalizer::normalize($data);

        return new self($normalized);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function runId(): string
    {
        return (string) $this->data['run_id'];
    }

    public function pipelineVersion(): string
    {
        return (string) $this->data['pipeline_version'];
    }

    public function pipelineStage(): string
    {
        return (string) $this->data['pipeline_stage'];
    }

    public function shadowCompareStatus(): string
    {
        return (string) ($this->data['shadow_compare_status'] ?? self::SHADOW_COMPARE_SKIPPED);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedShadowCompareStatuses(): array
    {
        return [
            self::SHADOW_COMPARE_MATCH,
            self::SHADOW_COMPARE_MISMATCH,
            self::SHADOW_COMPARE_ERROR,
            self::SHADOW_COMPARE_SKIPPED,
        ];
    }
}
