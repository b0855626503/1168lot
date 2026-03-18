<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncDomainExpiryCommand extends Command
{
    protected $signature = 'domain:sync-expiry
        {--force : เช็คใหม่แม้เคย checked แล้ว}
        {--only-sync : sync โดเมนจาก ENV ลง DB อย่างเดียว ไม่เช็ค expiry}
        {--only-check : เช็ค expiry อย่างเดียว (ไม่ sync ENV)}
        {--no-whois : ปิด fallback WHOIS ใช้ RDAP อย่างเดียว}
        {--raw-limit=4096 : จำกัดขนาด raw ที่บันทึกลง DB (byte)}
        {--sleep=0 : หน่วงเวลาระหว่างเช็คแต่ละโดเมน (วินาที) ป้องกัน rate limit}';

    protected $description = 'Sync domains from ENV into DB and fill domain expiration date (RDAP -> WHOIS fallback).';

    public function handle(): int
    {
        $force    = (bool) $this->option('force');
        $onlySync = (bool) $this->option('only-sync');
        $onlyCheck = (bool) $this->option('only-check');
        $noWhois  = (bool) $this->option('no-whois');
        $rawLimit = (int) $this->option('raw-limit');
        $sleepSec = (int) $this->option('sleep');

        if ($rawLimit < 256) {
            $rawLimit = 256;
        }

        if (!$onlyCheck) {
            $this->syncDomainsFromEnv();
        }

        if ($onlySync) {
            $this->info('Done (sync only).');
            return self::SUCCESS;
        }

        $query = DB::table('domain_expiries');

        // requirement: เช็คเฉพาะที่ยังไม่มีค่า expires_at
        $query->whereNull('expires_at');

        // กันยิงซ้ำ ถ้าไม่ force
        if (!$force) {
            $query->whereNull('checked_at');
        }

        $rows = $query->orderBy('role')->orderBy('domain')->get();

        if ($rows->isEmpty()) {
            $this->info('No domains to check (expires_at already filled or already checked).');
            return self::SUCCESS;
        }

        $this->info('Checking domain expiry for '.$rows->count().' record(s)...');

        foreach ($rows as $row) {
            $domain = (string) $row->domain;
            $role   = (string) $row->role;

            $this->line(" - [$role] $domain");

            $checkedAt = Carbon::now();
            $result = $this->resolveExpiry($domain, $noWhois);

            $payload = [
                'checked_at' => $checkedAt->toDateTimeString(),
                'status'     => $result['status'] ?? null,
                'source'     => $result['source'] ?? null,
                'raw'        => $this->truncateRaw($result['raw'] ?? null, $rawLimit),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];

            if (!empty($result['expires_at'])) {
                $payload['expires_at'] = Carbon::parse($result['expires_at'])->toDateTimeString();
            }

            DB::table('domain_expiries')
                ->where('id', (int) $row->id)
                ->update($payload);

            if (!empty($result['expires_at'])) {
                $this->info("   expires_at: ".$payload['expires_at']." (".$payload['source'].")");
            } else {
                $this->warn("   no expires_at (status: ".$payload['status'].", source: ".$payload['source'].")");
            }

            if ($sleepSec > 0) {
                sleep($sleepSec);
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * Sync ENV domains into domain_expiries table (upsert).
     */
    protected function syncDomainsFromEnv(): void
    {
        $public = $this->normalizeDomain(env('APP_USER_DOMAIN_URL'))
            ?: $this->normalizeDomain(env('APP_DOMAIN_URL')); // legacy fallback

        $admin  = $this->normalizeDomain(env('APP_ADMIN_DOMAIN_URL'));

        $pairs = [];

        if ($public) {
            $pairs[] = ['role' => 'public', 'domain' => $public];
        }

        if ($admin) {
            $pairs[] = ['role' => 'admin', 'domain' => $admin];
        }

        if (empty($pairs)) {
            $this->warn('No domain found in ENV (APP_USER_DOMAIN_URL / APP_DOMAIN_URL / APP_ADMIN_DOMAIN_URL).');
            return;
        }

        foreach ($pairs as $p) {
            // upsert แบบไม่ทับค่า expires_at ที่อุตส่าห์หาได้แล้ว
            $existing = DB::table('domain_expiries')
                ->where('role', $p['role'])
                ->where('domain', $p['domain'])
                ->first();

            if (!$existing) {
                DB::table('domain_expiries')->insert([
                    'role'       => $p['role'],
                    'domain'     => $p['domain'],
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]);
                $this->info("Synced new domain: [{$p['role']}] {$p['domain']}");
            } else {
                $this->line("Already exists: [{$p['role']}] {$p['domain']}");
            }
        }
    }

    /**
     * Resolve expiry date using RDAP, fallback WHOIS.
     *
     * @return array{expires_at?:string|null,status:string,source:string,raw?:string|null}
     */
    protected function resolveExpiry(string $domain, bool $noWhois = false): array
    {
        // basic sanity
        if (!$this->isValidDomain($domain)) {
            return [
                'expires_at' => null,
                'status' => 'invalid_domain',
                'source' => 'local',
                'raw' => null,
            ];
        }

        // 1) RDAP
        $rdap = $this->resolveByRdap($domain);
        if (!empty($rdap['expires_at'])) {
            return $rdap;
        }

        // ถ้า RDAP ได้ raw แต่ไม่มี expires_at ก็ยังส่งกลับ status ให้เห็นภาพ
        if ($noWhois) {
            return $rdap ?: [
                'expires_at' => null,
                'status' => 'no_data',
                'source' => 'rdap',
                'raw' => null,
            ];
        }

        // 2) WHOIS fallback (requires `whois` binary)
        $whois = $this->resolveByWhois($domain);
        if (!empty($whois['expires_at'])) {
            return $whois;
        }

        // ถ้า WHOIS ก็ไม่ได้ ให้คืนค่าจาก RDAP (ถ้ามี) เพื่อ status/debug
        if (!empty($rdap)) {
            return $rdap;
        }

        return $whois ?: [
            'expires_at' => null,
            'status' => 'no_data',
            'source' => 'whois',
            'raw' => null,
        ];
    }

    protected function resolveByRdap(string $domain): array
    {
        // RDAP bootstrap: for .com/.net typically works via Verisign endpoint
        // But we call IANA RDAP which redirects/points to correct server often.
        // Many servers accept: https://rdap.org/domain/{domain}
        // We’ll use rdap.org for convenience.
        try {
            $url = 'https://rdap.org/domain/'.rawurlencode($domain);

            $resp = Http::timeout(15)
                ->acceptJson()
                ->get($url);

            $raw = $resp->body();

            if (!$resp->successful()) {
                $status = $resp->status() === 429 ? 'rate_limited' : 'error';
                return [
                    'expires_at' => null,
                    'status' => $status,
                    'source' => 'rdap',
                    'raw' => $raw ?: null,
                ];
            }

            $json = $resp->json();
            $expiresAt = null;

            // RDAP events example: [{ "eventAction": "expiration", "eventDate": "..." }, ...]
            if (is_array($json) && !empty($json['events']) && is_array($json['events'])) {
                foreach ($json['events'] as $ev) {
                    if (!is_array($ev)) {
                        continue;
                    }
                    $action = strtolower((string) ($ev['eventAction'] ?? ''));
                    if ($action === 'expiration') {
                        $expiresAt = (string) ($ev['eventDate'] ?? '');
                        break;
                    }
                }
            }

            if ($expiresAt) {
                return [
                    'expires_at' => $expiresAt,
                    'status' => 'ok',
                    'source' => 'rdap',
                    'raw' => $raw ?: null,
                ];
            }

            return [
                'expires_at' => null,
                'status' => 'no_expiry_in_response',
                'source' => 'rdap',
                'raw' => $raw ?: null,
            ];
        } catch (\Throwable $e) {
            return [
                'expires_at' => null,
                'status' => 'error',
                'source' => 'rdap',
                'raw' => $e->getMessage(),
            ];
        }
    }

    protected function resolveByWhois(string $domain): array
    {
        // check whois binary exists
        $whoisPath = trim((string) @shell_exec('command -v whois 2>/dev/null'));
        if ($whoisPath === '') {
            return [
                'expires_at' => null,
                'status' => 'whois_not_available',
                'source' => 'whois',
                'raw' => null,
            ];
        }

        try {
            $cmd = 'whois '.escapeshellarg($domain).' 2>/dev/null';
            $raw = (string) shell_exec($cmd);

            if (trim($raw) === '') {
                return [
                    'expires_at' => null,
                    'status' => 'no_data',
                    'source' => 'whois',
                    'raw' => null,
                ];
            }

            $expiresAt = $this->parseWhoisExpiry($raw);

            if ($expiresAt) {
                return [
                    'expires_at' => $expiresAt,
                    'status' => 'ok',
                    'source' => 'whois',
                    'raw' => $raw,
                ];
            }

            // บาง whois ไม่มีวันหมดอายุให้
            return [
                'expires_at' => null,
                'status' => 'no_expiry_in_response',
                'source' => 'whois',
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            return [
                'expires_at' => null,
                'status' => 'error',
                'source' => 'whois',
                'raw' => $e->getMessage(),
            ];
        }
    }

    protected function parseWhoisExpiry(string $raw): ?string
    {
        // Common keys:
        // - Registry Expiry Date: 2026-01-01T00:00:00Z
        // - Registrar Registration Expiration Date: ...
        // - Expiration Date: ...
        $patterns = [
            '/Registry Expiry Date:\s*(.+)/i',
            '/Registrar Registration Expiration Date:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/paid-till:\s*(.+)/i',
        ];

        foreach ($patterns as $pat) {
            if (preg_match($pat, $raw, $m)) {
                $val = trim((string) ($m[1] ?? ''));
                $val = preg_replace('/\s+/', ' ', $val);

                // attempt parse with Carbon
                try {
                    return Carbon::parse($val)->toDateTimeString();
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        return null;
    }

    protected function normalizeDomain($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // ถ้าเผลอใส่ scheme/path มา ให้ดึง host
        if (str_contains($value, '://') || str_contains($value, '/')) {
            $host = parse_url($value, PHP_URL_HOST);
            if ($host) {
                $value = (string) $host;
            } else {
                // ถ้า parse_url ไม่ได้ ให้ตัด path แบบหยาบ
                $value = explode('/', $value)[0] ?? $value;
            }
        }

        // ตัด port ถ้ามี
        $value = preg_replace('/:\d+$/', '', $value);

        $value = strtolower(trim($value, " \t\n\r\0\x0B."));

        return $value !== '' ? $value : null;
    }

    protected function isValidDomain(string $domain): bool
    {
        // simple validation; allow subdomains
        if (strlen($domain) > 253) {
            return false;
        }

        // filter_var domain validation is limited; we do pragmatic check
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain);
    }

    protected function truncateRaw(?string $raw, int $limit): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = (string) $raw;
        if (strlen($raw) <= $limit) {
            return $raw;
        }
        return substr($raw, 0, $limit);
    }
}
