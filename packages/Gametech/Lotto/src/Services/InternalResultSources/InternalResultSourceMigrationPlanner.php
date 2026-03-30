<?php

namespace Gametech\Lotto\Services\InternalResultSources;

class InternalResultSourceMigrationPlanner
{
    /**
     * @return array<string,mixed>|null
     */
    public function plan(string $endpointUrl, string $internalBaseUrl): ?array
    {
        $url = trim($endpointUrl);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);

        $base = rtrim($internalBaseUrl, '/');
        if ($base === '') {
            return null;
        }

        if (str_contains($host, 'exphuay.com') && preg_match('#/backward/([^/]+)/__data\.json$#', $path, $matches) === 1) {
            $type = trim((string) ($matches[1] ?? ''));
            if ($type === '') {
                return null;
            }

            return [
                'source_key' => 'exphuay',
                'type' => $type,
                'target_endpoint_url' => $base . '/internal/lottery/results/exphuay/' . rawurlencode($type),
                'recommended_query_template' => [
                    'date' => '{{lookup_date}}',
                    'page' => isset($query['page']) ? (int) $query['page'] : 1,
                ],
            ];
        }

        if (str_contains($host, 'api.dowjones-midnight.com') && $path === '/result') {
            return [
                'source_key' => 'dowjones-midnight',
                'type' => 'dowjones-midnight',
                'target_endpoint_url' => $base . '/internal/lottery/results/dowjones-midnight',
                'recommended_query_template' => [
                    'date' => '{{lookup_date}}',
                ],
            ];
        }

        if (str_contains($host, 'api.dowjonesextra.com') && $path === '/result') {
            return [
                'source_key' => 'dowjones-extra',
                'type' => 'dowjones-extra',
                'target_endpoint_url' => $base . '/internal/lottery/results/dowjones-extra',
                'recommended_query_template' => [
                    'date' => '{{lookup_date}}',
                ],
            ];
        }

        return null;
    }
}

