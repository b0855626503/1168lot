#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

SEMANTIC_SYNC_MODE="${SEMANTIC_SYNC_MODE:-$UNIFIED_SYNC_MODE}"
SEMANTIC_REPORT_PATH="${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}"

mkdir -p "$(dirname "$SEMANTIC_REPORT_PATH")"

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

docs_endpoints_file="$tmp_dir/docs_endpoints.txt"
memory_endpoints_file="$tmp_dir/memory_endpoints.txt"
docs_domains_file="$tmp_dir/docs_domains.txt"
memory_domains_file="$tmp_dir/memory_domains.txt"
docs_modules_file="$tmp_dir/docs_modules.txt"
memory_modules_file="$tmp_dir/memory_modules.txt"

rg -o '/api/v1[[:alnum:]_\-/{}]*' docs/public/api/frontend-v1/03-endpoints.md 2>/dev/null | sort -u > "$docs_endpoints_file" || :
rg --no-filename -o '/api/v1[[:alnum:]_\-/{}]*' memory/auth.md memory/payment.md memory/wallet.md memory/game.md 2>/dev/null | sort -u > "$memory_endpoints_file" || :

sed -n "s/^- \`\([^\`]*\)\`.*/\1/p" docs/internal/03_DOMAINS/index.md 2>/dev/null | sort -u > "$docs_domains_file" || :
find memory -maxdepth 1 -type f -name '*.md' -printf '%f\n' 2>/dev/null | sed -E 's/\.md$//' | grep -E '^(auth|payment|wallet|game)$' | sort -u > "$memory_domains_file" || :

rg --no-filename -o '`(app|packages|routes|config|database)/[^`]+`' docs/internal/03_DOMAINS/*.md 2>/dev/null | tr -d '`' | sort -u > "$docs_modules_file" || :
rg --no-filename -o '`(app|packages|routes|config|database)/[^`]+`' memory/auth.md memory/payment.md memory/wallet.md memory/game.md 2>/dev/null | tr -d '`' | sort -u > "$memory_modules_file" || :

php /dev/stdin "$docs_endpoints_file" "$memory_endpoints_file" "$docs_domains_file" "$memory_domains_file" "$docs_modules_file" "$memory_modules_file" "$SEMANTIC_REPORT_PATH" <<'PHP'
<?php
[$docsEpFile, $memEpFile, $docsDomainFile, $memDomainFile, $docsModuleFile, $memModuleFile, $reportPath] = array_slice($argv, 1);

$read = static function (string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $lines = array_filter(array_map('trim', file($path) ?: []));
    $lines = array_values(array_unique($lines));
    sort($lines);
    return $lines;
};

$diffs = static function (array $left, array $right): array {
    return array_values(array_diff($left, $right));
};

$docsEndpoints = $read($docsEpFile);
$memoryEndpoints = $read($memEpFile);
$docsDomains = $read($docsDomainFile);
$memoryDomains = $read($memDomainFile);
$docsModules = $read($docsModuleFile);
$memoryModules = $read($memModuleFile);

$report = [
    'generated_at' => gmdate('c'),
    'summary' => [
        'docs_endpoints' => count($docsEndpoints),
        'memory_endpoints' => count($memoryEndpoints),
        'docs_domains' => count($docsDomains),
        'memory_domains' => count($memoryDomains),
        'docs_modules' => count($docsModules),
        'memory_modules' => count($memoryModules),
    ],
    'mismatch' => [
        'doc_only_endpoints' => $diffs($docsEndpoints, $memoryEndpoints),
        'memory_only_endpoints' => $diffs($memoryEndpoints, $docsEndpoints),
        'doc_only_domains' => $diffs($docsDomains, $memoryDomains),
        'memory_only_domains' => $diffs($memoryDomains, $docsDomains),
        'doc_only_modules' => $diffs($docsModules, $memoryModules),
        'memory_only_modules' => $diffs($memoryModules, $docsModules),
    ],
];

$mismatchCount = 0;
foreach ($report['mismatch'] as $items) {
    $mismatchCount += count($items);
}
$report['summary']['total_mismatches'] = $mismatchCount;

file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
PHP

mismatch_count="$(php -r '$j=json_decode(file_get_contents($argv[1]), true); echo (int)($j["summary"]["total_mismatches"] ?? 0);' "$SEMANTIC_REPORT_PATH")"

if (( mismatch_count > 0 )); then
  if [[ "$SEMANTIC_SYNC_MODE" == "error" ]]; then
    log_error "semantic-sync" "semantic mismatch found: $mismatch_count (report: $SEMANTIC_REPORT_PATH)"
    exit 1
  fi

  log_warn "semantic-sync" "semantic mismatch found: $mismatch_count (report: $SEMANTIC_REPORT_PATH)"
  exit 0
fi

log_info "semantic-sync" "no semantic mismatch (report: $SEMANTIC_REPORT_PATH)"
