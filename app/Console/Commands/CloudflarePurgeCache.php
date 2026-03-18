<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudflarePurgeCache extends Command
{
    protected $signature = 'cloudflare:purge {domain}';

    protected $description = 'Purge Cloudflare cache for specified domain';

    // ใส่ API Token ของ Cloudflare ที่มีสิทธิ์ zone.cache_purge
    protected $apiToken;

    public function __construct()
    {
        parent::__construct();
        $this->apiToken = 'Sn0Fsb8nqKVC94u0jpkhBurKC2-Ka88y-Eq0B_g7'; // ดึงจาก config/services.php
    }

    public function handle()
    {
        $domain = $this->argument('domain');

        // 1. ดึง Zone ID ของโดเมน
        $zoneResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->get('https://api.cloudflare.com/client/v4/zones', [
            'name' => $domain,
            'status' => 'active',
        ]);

        if (!$zoneResponse->ok()) {
            $this->error('Failed to fetch zone info: ' . $zoneResponse->body());

            return 1;
        }

        $zones = $zoneResponse->json('result');

        if (empty($zones)) {
            $this->error("Zone for domain '{$domain}' not found.");

            return 1;
        }

        $zoneId = $zones[0]['id'];
        $this->info("Found Zone ID: {$zoneId}");

        // 2. สั่ง Purge Cache ทั้งหมด
        $purgeResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
            'purge_everything' => true,
        ]);

        if ($purgeResponse->ok() && $purgeResponse->json('success')) {
            $this->info("Cache purged successfully for domain {$domain}");

            return 0;
        } else {
            $this->error('Failed to purge cache: ' . $purgeResponse->body());

            return 1;
        }
    }
}
