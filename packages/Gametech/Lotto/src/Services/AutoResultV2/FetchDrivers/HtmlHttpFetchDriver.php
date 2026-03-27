<?php

namespace Gametech\Lotto\Services\AutoResultV2\FetchDrivers;

class HtmlHttpFetchDriver
{
    public function __construct(
        private ?JsonHttpFetchDriver $httpDriver = null
    ) {
        $this->httpDriver = $this->httpDriver ?: new JsonHttpFetchDriver();
    }

    /**
     * @param array<string,mixed> $fetchConfig
     * @return array<string,mixed>
     */
    public function fetch(array $fetchConfig): array
    {
        $result = $this->httpDriver->fetch($fetchConfig);
        if (! isset($result['response_content_type']) || $result['response_content_type'] === null) {
            $result['response_content_type'] = 'text/html';
        }

        return $result;
    }
}
