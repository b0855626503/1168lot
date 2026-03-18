<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GamelogProbe extends Command
{
    protected $signature = 'gamelog:probe {user} {--limit=50}';
    protected $description = 'Probe Redis keys for a user under game:log:{user}:*:addon:* (handle prefix)';

    public function handle()
    {
        $user = (string)$this->argument('user');
        $limit = (int)$this->option('limit');

        $conn = Redis::connection('gamelog');

        // ดึง prefix (ถ้ามี)
        $prefix = '';
        try {
            $client = $conn->client();
            if ($client instanceof \Redis) {
                $prefix = (string)$client->getOption(\Redis::OPT_PREFIX);
            } elseif ($client instanceof \Predis\Client) {
                $opt = $client->getOptions();
                if (isset($opt->prefix)) {
                    $prefix = (string)$opt->prefix->getPrefix();
                }
            }
        } catch (\Throwable $e) {
        }

        $this->info('Redis prefix = [' . $prefix . ']');

        $patterns = [
            // แบบไม่ใส่ prefix (ให้ไดรเวอร์เติมเอง)
            "game:log:{$user}:*:addon:*",
            // แบบใส่ prefix เอง (กันกรณีไดรเวอร์ไม่เติมให้กับ MATCH)
            $prefix . "game:log:{$user}:*:addon:*",
        ];

        foreach ($patterns as $i => $pattern) {
            $this->line(PHP_EOL . "SCAN pattern #" . ($i + 1) . ": {$pattern}");
            $cursor = '0';
            $found = 0;
            $samples = [];

            do {
                $res = $conn->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 1000]);
                if (!$res) {
                    $cursor = '0';
                    $keys = [];
                } else {
                    [$cursor, $keys] = $res;
                }

                foreach ($keys as $k) {
                    $found++;
                    if (count($samples) < $limit) $samples[] = $k;
                }
            } while ($cursor !== '0');

            $this->info("Total keys found = {$found}");
            foreach ($samples as $k) $this->line(" - {$k}");
        }

        return self::SUCCESS;
    }
}
