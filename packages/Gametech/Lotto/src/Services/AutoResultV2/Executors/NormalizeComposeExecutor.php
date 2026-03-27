<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\Compose\ResultComposer;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\MappingConfigData;

class NormalizeComposeExecutor
{
    public function __construct(
        private ?ResultComposer $composer = null
    ) {
        $this->composer = $this->composer ?: new ResultComposer();
    }

    /**
     * @param array<string,mixed> $selectedCandidate
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function execute(array $selectedCandidate, MappingConfigData $mappingConfig, array $context = []): array
    {
        return $this->composer->compose($selectedCandidate, ['mapping_config_json' => $mappingConfig->toArray()], $context);
    }
}
