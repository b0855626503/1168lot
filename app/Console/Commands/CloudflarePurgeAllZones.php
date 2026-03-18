<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudflarePurgeAllZones extends Command
{
    protected $signature = 'cloudflare:purge:all';
    protected $description = 'Purge Cloudflare cache for all zones accessible by the API token (with pagination)';

    public function handle()
    {
        $token = 'Sn0Fsb8nqKVC94u0jpkhBurKC2-Ka88y-Eq0B_g7';
        $perPage = 50; // จำนวน zone ต่อหน้า (max 50)
        $page = 1;
        $zones = [];

        $this->info('📡 Fetching all zones accessible by this token...');

        do {
            $response = Http::withToken($token)
                ->get('https://api.cloudflare.com/client/v4/zones', [
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

            if (!$response->successful()) {
                $this->error("❌ Failed to fetch zones on page {$page}:");
                $this->line($response->body());
                return 1;
            }

            $result = $response->json();

            $zones = array_merge($zones, $result['result'] ?? []);

            $totalPages = $result['result_info']['total_pages'] ?? 1;

            $this->info("➡️ Fetched page {$page} of {$totalPages}");

            $page++;

        } while ($page <= $totalPages);

        if (empty($zones)) {
            $this->warn('⚠️ No zones found for this token.');
            return 0;
        }

        foreach ($zones as $zone) {
            $zoneId = $zone['id'];
            $zoneName = $zone['name'];

            $this->line("🚀 Purging cache for zone: {$zoneName}");

            $purgeResponse = Http::withToken($token)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'purge_everything' => true,
                ]);

            if ($purgeResponse->successful()) {
                $this->info("✅ Purged cache for {$zoneName}");
            } else {
                $this->error("❌ Failed to purge {$zoneName}:");
                $this->line($purgeResponse->body());
            }
        }

        return 0;
    }
}
