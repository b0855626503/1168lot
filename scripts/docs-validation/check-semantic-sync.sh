#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

SEMANTIC_SYNC_MODE="${SEMANTIC_SYNC_MODE:-$UNIFIED_SYNC_MODE}"
SEMANTIC_REPORT_PATH="${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}"
CRITICAL_DRIFT_MODE="${CRITICAL_DRIFT_MODE:-error}"
DOMAIN_COVERAGE_MODE="${DOMAIN_COVERAGE_MODE:-warn}"

mkdir -p "$(dirname "$SEMANTIC_REPORT_PATH")"

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

DOC_ENDPOINTS_ALL="$tmp_dir/doc_endpoints_all.txt"
MEM_ENDPOINTS_ALL="$tmp_dir/mem_endpoints_all.txt"
DOC_MODULES_ALL="$tmp_dir/doc_modules_all.txt"
MEM_MODULES_ALL="$tmp_dir/mem_modules_all.txt"

DOC_ENDPOINTS_CRITICAL="$tmp_dir/doc_endpoints_critical.txt"
DOC_ENDPOINTS_NONCRITICAL="$tmp_dir/doc_endpoints_noncritical.txt"
DOC_MODULES_CRITICAL="$tmp_dir/doc_modules_critical.txt"
DOC_MODULES_NONCRITICAL="$tmp_dir/doc_modules_noncritical.txt"

DOMAIN_KEYWORDS_FILE="$tmp_dir/domain_keywords.json"

MEMORY_FILES=(
  "memory/auth.md"
  "memory/payment.md"
  "memory/wallet.md"
  "memory/game.md"
)

# ----- Collect docs entities -----
rg --no-filename -o '/api/v1[[:alnum:]_\-/{}]*' docs/public/api/frontend-v1/07-route-reference.md 2>/dev/null | sort -u > "$DOC_ENDPOINTS_ALL" || :
rg --no-filename -o '`(app|packages|routes|config|database)/[^`]+`' docs/internal/03_DOMAINS/auth.md docs/internal/03_DOMAINS/payment.md docs/internal/03_DOMAINS/wallet.md docs/internal/03_DOMAINS/frontend_api.md 2>/dev/null | tr -d '`' | sort -u > "$DOC_MODULES_ALL" || :

# Critical endpoint scope: auth + wallet + payment + game
grep -E '^/api/v1/(auth/|member/(balance|history|history/\{type\}|profile|change-password|wallet-address)$|wallet/(transactions|claim|withdraw)$|deposit/(channels|loadbank)$|smkpay/|games/)' "$DOC_ENDPOINTS_ALL" | sort -u > "$DOC_ENDPOINTS_CRITICAL" || :
comm -23 "$DOC_ENDPOINTS_ALL" "$DOC_ENDPOINTS_CRITICAL" > "$DOC_ENDPOINTS_NONCRITICAL" || :

# Critical modules expected for retrieval-first in 4 domains
cat > "$DOC_MODULES_CRITICAL" <<'CRITMOD'
packages/Gametech/FrontendApi/src/Routes/api.php
packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/AuthController.php
packages/Gametech/FrontendApi/src/Http/Middleware/AuthenticateFrontendToken.php
config/auth.php
packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/DepositController.php
packages/Gametech/Payment/src/
packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php
packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WithdrawController.php
packages/Gametech/Wallet/src/
database/migrations/
packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/GameController.php
packages/Gametech/Game/src/
CRITMOD
sort -u -o "$DOC_MODULES_CRITICAL" "$DOC_MODULES_CRITICAL"
comm -23 "$DOC_MODULES_ALL" "$DOC_MODULES_CRITICAL" > "$DOC_MODULES_NONCRITICAL" || :

# ----- Collect memory entities -----
existing_memory_files=()
for f in "${MEMORY_FILES[@]}"; do
  if [[ -f "$f" ]]; then
    existing_memory_files+=("$f")
  fi
done

if (( ${#existing_memory_files[@]} > 0 )); then
  rg --no-filename -o '/api/v1[[:alnum:]_\-/{}]*' "${existing_memory_files[@]}" 2>/dev/null | sort -u > "$MEM_ENDPOINTS_ALL" || :
  rg --no-filename -o '`(app|packages|routes|config|database)/[^`]+`' "${existing_memory_files[@]}" 2>/dev/null | tr -d '`' | sort -u > "$MEM_MODULES_ALL" || :
else
  : > "$MEM_ENDPOINTS_ALL"
  : > "$MEM_MODULES_ALL"
fi

# ----- Critical flow keyword checks -----
cat > "$DOMAIN_KEYWORDS_FILE" <<'JSON'
{
  "auth": ["register", "login", "logout"],
  "payment": ["channel", "callback", "status"],
  "wallet": ["transactions", "claim", "withdraw"],
  "game": ["providers", "login", "games"]
}
JSON

php /dev/stdin "$DOC_ENDPOINTS_CRITICAL" "$DOC_ENDPOINTS_NONCRITICAL" "$MEM_ENDPOINTS_ALL" "$DOC_MODULES_CRITICAL" "$DOC_MODULES_NONCRITICAL" "$MEM_MODULES_ALL" "$DOMAIN_KEYWORDS_FILE" "$SEMANTIC_REPORT_PATH" <<'PHP'
<?php
[$docEpCriticalFile, $docEpNonCriticalFile, $memEpFile, $docModCriticalFile, $docModNonCriticalFile, $memModFile, $domainKeywordsFile, $reportPath] = array_slice($argv, 1);

$read = static function (string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $lines = array_filter(array_map('trim', file($path) ?: []));
    $lines = array_values(array_unique($lines));
    sort($lines);
    return $lines;
};

$diff = static function (array $left, array $right): array {
    return array_values(array_diff($left, $right));
};

$docCriticalEndpoints = $read($docEpCriticalFile);
$docNonCriticalEndpoints = $read($docEpNonCriticalFile);
$memEndpoints = $read($memEpFile);
$docCriticalModules = $read($docModCriticalFile);
$docNonCriticalModules = $read($docModNonCriticalFile);
$memModules = $read($memModFile);

$memoryFiles = [
    'auth' => 'memory/auth.md',
    'payment' => 'memory/payment.md',
    'wallet' => 'memory/wallet.md',
    'game' => 'memory/game.md',
];

$flowKeywords = json_decode((string) @file_get_contents($domainKeywordsFile), true);
if (!is_array($flowKeywords)) {
    $flowKeywords = [];
}

$criticalMissingFlowKeywords = [];
$domainsLowCoverage = [];

foreach ($memoryFiles as $domain => $path) {
    if (!is_file($path)) {
        $domainsLowCoverage[] = $domain;
        $criticalMissingFlowKeywords[$domain] = ['missing-file'];
        continue;
    }

    $content = mb_strtolower((string) file_get_contents($path));
    $missingKeywords = [];

    foreach (($flowKeywords[$domain] ?? []) as $keyword) {
        $kw = mb_strtolower((string) $keyword);
        if ($kw === '') {
            continue;
        }

        if (mb_strpos($content, $kw) === false) {
            $missingKeywords[] = $keyword;
        }
    }

    if (count($missingKeywords) > 0) {
        $domainsLowCoverage[] = $domain;
        $criticalMissingFlowKeywords[$domain] = $missingKeywords;
    }
}

$critical = [
    'doc_only_endpoints' => $diff($docCriticalEndpoints, $memEndpoints),
    'doc_only_modules' => $diff($docCriticalModules, $memModules),
    'missing_flow_keywords_by_domain' => $criticalMissingFlowKeywords,
];

$nonCritical = [
    'doc_only_endpoints' => $diff($docNonCriticalEndpoints, $memEndpoints),
    'memory_only_endpoints' => $diff($memEndpoints, array_values(array_unique(array_merge($docCriticalEndpoints, $docNonCriticalEndpoints)))),
    'doc_only_modules' => $diff($docNonCriticalModules, $memModules),
    'memory_only_modules' => $diff($memModules, array_values(array_unique(array_merge($docCriticalModules, $docNonCriticalModules)))),
];

$bucket = [
    'useful_soon' => [
        'endpoints' => [],
        'modules' => [],
    ],
    'docs_only_acceptable' => [
        'endpoints' => [],
        'modules' => [],
    ],
    'ignore_noise' => [
        'endpoints' => [],
        'modules' => [],
    ],
];

$endpointBucket = static function (string $endpoint): string {
    if (preg_match('#^/api/v1/member/loadbalance$#', $endpoint) === 1) {
        return 'useful_soon';
    }
    if (preg_match('#^/api/v1/(meta/|slides$|realtime/)#', $endpoint) === 1) {
        return 'useful_soon';
    }
    if (preg_match('#^/api/v1/(coupon/|promotion/|lotto/|wheel/)#', $endpoint) === 1) {
        return 'docs_only_acceptable';
    }
    return 'ignore_noise';
};

$moduleBucket = static function (string $module): string {
    if (preg_match('#FrontendApi/src/Http/Controllers/Api/V1/(Member|Realtime|Online|Slide)#', $module) === 1) {
        return 'useful_soon';
    }
    if (preg_match('#packages/Gametech/(Lotto|Promotion|Reward|Ui|API)/#', $module) === 1) {
        return 'docs_only_acceptable';
    }
    return 'ignore_noise';
};

foreach ($nonCritical['doc_only_endpoints'] as $endpoint) {
    $bucket[$endpointBucket($endpoint)]['endpoints'][] = $endpoint;
}
foreach ($nonCritical['memory_only_endpoints'] as $endpoint) {
    $bucket['ignore_noise']['endpoints'][] = $endpoint;
}
foreach ($nonCritical['doc_only_modules'] as $module) {
    $bucket[$moduleBucket($module)]['modules'][] = $module;
}
foreach ($nonCritical['memory_only_modules'] as $module) {
    $bucket['ignore_noise']['modules'][] = $module;
}

foreach ($bucket as $bucketName => $bucketParts) {
    foreach ($bucketParts as $part => $items) {
        $bucket[$bucketName][$part] = array_values(array_unique($items));
        sort($bucket[$bucketName][$part]);
    }
}

$gitCommitHits = static function (string $needle, ?string $since = null): int {
    $quotedNeedle = escapeshellarg($needle);
    $sinceArg = '';
    if ($since !== null && $since !== '') {
        $sinceArg = '--since=' . escapeshellarg($since);
    }

    $command = sprintf(
        'git log --oneline --no-merges %s -G%s -- docs/ memory/ app/ routes/ packages/ 2>/dev/null | wc -l',
        $sinceArg,
        $quotedNeedle
    );

    $output = shell_exec($command);
    if (!is_string($output)) {
        return 0;
    }

    return (int) trim($output);
};

$usefulSoonTracking = [
    'promotion_rule' => 'promote_if_total_hits>=3_and_recent_hits>=2',
    'recent_window' => '120 days',
    'items' => [],
];

foreach (($bucket['useful_soon']['endpoints'] ?? []) as $endpoint) {
    $totalHits = $gitCommitHits($endpoint, null);
    $recentHits = $gitCommitHits($endpoint, '120 days ago');

    $usefulSoonTracking['items'][] = [
        'entity_type' => 'endpoint',
        'entity' => $endpoint,
        'total_commit_hits' => $totalHits,
        'recent_commit_hits' => $recentHits,
        'promote_candidate' => ($totalHits >= 3 && $recentHits >= 2),
    ];
}

foreach (($bucket['useful_soon']['modules'] ?? []) as $module) {
    $totalHits = $gitCommitHits($module, null);
    $recentHits = $gitCommitHits($module, '120 days ago');

    $usefulSoonTracking['items'][] = [
        'entity_type' => 'module',
        'entity' => $module,
        'total_commit_hits' => $totalHits,
        'recent_commit_hits' => $recentHits,
        'promote_candidate' => ($totalHits >= 3 && $recentHits >= 2),
    ];
}

$usefulSoonTracking['promote_candidates'] = array_values(array_filter(
    $usefulSoonTracking['items'],
    static fn (array $item): bool => (bool) ($item['promote_candidate'] ?? false)
));

$criticalCount = count($critical['doc_only_endpoints']) + count($critical['doc_only_modules']);
foreach ($critical['missing_flow_keywords_by_domain'] as $items) {
    $criticalCount += count($items);
}

$nonCriticalCount = 0;
foreach ($nonCritical as $items) {
    $nonCriticalCount += count($items);
}

$report = [
    'generated_at' => gmdate('c'),
    'summary' => [
        'critical_mismatches' => $criticalCount,
        'non_critical_mismatches' => $nonCriticalCount,
        'total_mismatches' => $criticalCount + $nonCriticalCount,
        'critical_doc_endpoints' => count($docCriticalEndpoints),
        'memory_endpoints' => count($memEndpoints),
        'critical_doc_modules' => count($docCriticalModules),
        'memory_modules' => count($memModules),
        'domains_low_coverage' => array_values(array_unique($domainsLowCoverage)),
    ],
    'critical' => $critical,
    'non_critical' => $nonCritical,
    'non_critical_buckets' => $bucket,
    'useful_soon_tracking' => $usefulSoonTracking,
];

file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
PHP

critical_mismatch_count="$(php -r '$j=json_decode(file_get_contents($argv[1]), true); echo (int)($j["summary"]["critical_mismatches"] ?? 0);' "$SEMANTIC_REPORT_PATH")"
non_critical_mismatch_count="$(php -r '$j=json_decode(file_get_contents($argv[1]), true); echo (int)($j["summary"]["non_critical_mismatches"] ?? 0);' "$SEMANTIC_REPORT_PATH")"
total_mismatch_count=$((critical_mismatch_count + non_critical_mismatch_count))
domain_low_coverage_count="$(php -r '$j=json_decode(file_get_contents($argv[1]), true); echo (int)count($j["summary"]["domains_low_coverage"] ?? []);' "$SEMANTIC_REPORT_PATH")"

if (( domain_low_coverage_count > 0 )); then
  if [[ "$DOMAIN_COVERAGE_MODE" == "error" ]]; then
    log_error "semantic-sync" "domain coverage dropped: $domain_low_coverage_count domains (report: $SEMANTIC_REPORT_PATH)"
    exit 1
  fi

  log_warn "semantic-sync" "domain coverage dropped: $domain_low_coverage_count domains (report: $SEMANTIC_REPORT_PATH)"
fi

if (( critical_mismatch_count > 0 )); then
  if [[ "$CRITICAL_DRIFT_MODE" == "error" ]]; then
    log_error "semantic-sync" "critical semantic mismatch: $critical_mismatch_count (report: $SEMANTIC_REPORT_PATH)"
    exit 1
  fi

  log_warn "semantic-sync" "critical semantic mismatch: $critical_mismatch_count (non-critical=$non_critical_mismatch_count, report: $SEMANTIC_REPORT_PATH)"
  exit 0
fi

if (( non_critical_mismatch_count > 0 )); then
  log_warn "semantic-sync" "non-critical semantic mismatch: $non_critical_mismatch_count (report: $SEMANTIC_REPORT_PATH)"
  exit 0
fi

log_info "semantic-sync" "no semantic mismatch (report: $SEMANTIC_REPORT_PATH, total=$total_mismatch_count)"
