<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GamelogWhere extends Command
{
    protected $signature = 'gamelog:where';
    protected $description = 'Show Redis connection details (host/port/db/prefix) and DB size for connection= g a m e l o g';

    public function handle()
    {
        $r = Redis::connection('gamelog');

        $host = $port = $db = $prefix = 'n/a';
        try {
            $client = $r->client();
            if ($client instanceof \Redis) {
                $host = $client->getHost();
                $port = $client->getPort();
                $db = $client->getDbNum();
                $prefix = (string)$client->getOption(\Redis::OPT_PREFIX);
            } elseif ($client instanceof \Predis\Client) {
                $params = $client->getConnection()->getParameters();
                $host = $params->host ?? 'n/a';
                $port = $params->port ?? 'n/a';
                $db = $params->database ?? 'n/a';
                $opt = $client->getOptions();
                $prefix = isset($opt->prefix) ? (string)$opt->prefix->getPrefix() : '';
            }
        } catch (\Throwable $e) {
            $this->error('Cannot introspect Redis client: ' . $e->getMessage());
        }

        $this->info("Redis gamelog => host={$host} port={$port} db={$db} prefix=[{$prefix}]");
        try {
            $info = $r->command('INFO');
            if (is_array($info) && isset($info['db' . $db])) {
                $this->line('Keyspace db' . $db . ': ' . $info['db' . $db]);
            }
        } catch (\Throwable $e) {
        }

        // ลองนับคีย์แบบคร่าว ๆ: game:log:* (ใส่ prefix เองกันพลาด)
        $pattern = $prefix . 'game:log:*';
        $cursor = '0';
        $count = 0;
        do {
            $res = $r->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 2000]);
            if (!$res) {
                $cursor = '0';
                $keys = [];
            } else {
                [$cursor, $keys] = $res;
            }
            $count += count($keys);
        } while ($cursor !== '0');

        $this->info("Approx keys matched '{$pattern}' = {$count}");
        return self::SUCCESS;
    }
}
