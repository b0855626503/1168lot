<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchExpalertBackfillDataCommand extends Command
{
    protected $signature = 'lotto:backfill-expalert-fetch
        {--slug= : Fetch a single lottery slug}
        {--delay=300 : Delay in ms between API calls}
        {--out-dir= : Output directory (default: storage/app/lotto/expalert-backfill)}';

    protected $description = 'Fetch ALL backward data pages from api.expalert.cc and save as JSON files';

    public function handle(): int
    {
        $apiKey = (string) config('lotto_auto_result.internal_result_sources.expalert.api_key', (string) env('EXPHUAY_API_KEY', ''));
        $delayMs = max(0, (int) $this->option('delay'));
        $targetSlug = $this->option('slug') ? trim((string) $this->option('slug')) : null;

        $baseDir = $this->option('out-dir')
            ? (string) $this->option('out-dir')
            : storage_path('app/lotto/expalert-backfill');

        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // 1. Fetch lotto list
        $this->info('Fetching lotto list from expalert...');
        $slugs = $this->fetchLottoList($apiKey);

        if ($slugs === null) {
            $this->error('Failed to fetch lotto list.');

            return self::FAILURE;
        }

        if ($targetSlug !== null) {
            if (! in_array($targetSlug, $slugs, true)) {
                $this->warn("Slug '{$targetSlug}' not in lotto list. Attempting anyway...");
                $slugs = [$targetSlug];
            } else {
                $slugs = [$targetSlug];
            }
        }

        // 2. Fetch ALL pages of backward data for each slug
        $manifest = [
            'fetched_at' => Carbon::now()->toIso8601String(),
            'total_slugs' => count($slugs),
            'slugs' => [],
        ];

        $totalEntries = 0;
        $totalErrors = 0;

        foreach ($slugs as $idx => $slug) {
            $slug = trim((string) $slug);
            if ($slug === '') {
                continue;
            }

            $apiTotal = 0;
            $entries = $this->fetchAllBackwardPages($slug, $apiKey, $apiTotal, $delayMs);

            if ($entries === null) {
                $this->warn("  [{$idx}/".count($slugs)."] {$slug}: API error, skipping");
                $manifest['slugs'][] = ['slug' => $slug, 'count' => 0, 'total' => 0, 'error' => true];
                $totalErrors++;

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }

                continue;
            }

            $count = count($entries);
            $filePath = $baseDir.'/'.$slug.'.json';

            file_put_contents(
                $filePath,
                json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );

            $manifest['slugs'][] = [
                'slug' => $slug,
                'count' => $count,
                'total_api' => $apiTotal,
                'file' => basename($filePath),
            ];
            $totalEntries += $count;

            $this->info("  [{$idx}/".count($slugs)."] {$slug}: {$count}/{$apiTotal} entries → ".basename($filePath));

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        // 3. Write manifest
        $manifest['total_entries'] = $totalEntries;
        $manifest['errors'] = $totalErrors;

        file_put_contents(
            $baseDir.'/_manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->info("Done. {$totalEntries} entries from ".count($slugs).' slugs.');
        $this->info("Output: {$baseDir}/");
        if ($totalErrors > 0) {
            $this->warn("{$totalErrors} slugs had errors.");
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]|null
     */
    private function fetchLottoList(string $apiKey): ?array
    {
        $headers = [];
        if ($apiKey !== '') {
            $headers['x-api-key'] = $apiKey;
        }

        $response = Http::timeout(15)
            ->withHeaders($headers)
            ->get('https://api.expalert.cc/data/lotto-list');

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return null;
        }

        $slugs = [];
        foreach ($data as $item) {
            $fn = (string) ($item['fn'] ?? '');
            if ($fn !== '') {
                $slugs[] = $fn;
            }
        }

        return $slugs;
    }

    /**
     * Fetch ALL pages of backward data for a slug.
     *
     * @param  int  $apiTotal  (out) Total count reported by the API
     * @param  int  $delayMs  Delay between page requests
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchAllBackwardPages(string $slug, string $apiKey, int &$apiTotal, int $delayMs): ?array
    {
        $headers = [];
        if ($apiKey !== '') {
            $headers['x-api-key'] = $apiKey;
        }

        $allEntries = [];
        $page = 1;
        $apiTotal = 0;

        while (true) {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->get('https://api.expalert.cc/data/backward/'.rawurlencode($slug).'?page='.$page);

            if (! $response->successful()) {
                if ($page === 1) {
                    return null; // First page failed — real error
                }

                break; // Subsequent page failed — assume end of data
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                if ($page === 1) {
                    return null;
                }

                break;
            }

            // Capture total count from first page
            if ($page === 1) {
                $apiTotal = (int) ($payload['all'] ?? 0);
            }

            $data = $payload['data'] ?? [];
            if (! is_array($data) || empty($data)) {
                break; // No more data
            }

            $allEntries = array_merge($allEntries, $data);

            // If we fetched less than a full page, we're done
            if (count($data) < 30) {
                break;
            }

            $page++;

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return $allEntries;
    }
}
