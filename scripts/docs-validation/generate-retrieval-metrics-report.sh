#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

report_json="${RETRIEVAL_METRICS_REPORT_JSON:-.ai/mcp/retrieval-metrics-report.json}"
report_md="${RETRIEVAL_METRICS_REPORT_MD:-.ai/mcp/retrieval-metrics-report.md}"
semantic_report="${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}"
before_report=".ai/mcp/semantic-drift-report.before-improvement.json"

mkdir -p "$(dirname "$report_json")"
mkdir -p "$(dirname "$report_md")"

count_words() {
  local total=0
  for file in "$@"; do
    if [[ -f "$file" ]]; then
      count=$(wc -w < "$file")
      total=$((total + count))
    fi
  done
  printf '%s' "$total"
}

legacy_pack_words=$(count_words \
  "docs/internal/05_ARCHIVE/monolith/system_current_state.2026-04-18.md" \
  "docs/internal/05_ARCHIVE/monolith/decision_log.2026-04-18.md" \
  "docs/public/api/archive/api-frontend-v1.2026-04-18.md")

current_pack_words=$(count_words \
  ".codebase-memory/SUMMARY.md" \
  "memory/auth.md" \
  "memory/payment.md" \
  "memory/wallet.md" \
  "memory/game.md" \
  "docs/internal/01_SYSTEM/retrieval_system_status.md")

legacy_token_estimate=$((legacy_pack_words * 13 / 10))
current_token_estimate=$((current_pack_words * 13 / 10))

memory_first_pointer_count=$(rg -l "memory" docs/START_HERE.md docs/internal/01_SYSTEM/startup_digest.md docs/internal/00_RULES/agent_rules.md 2>/dev/null | wc -l)
docs_first_pointer_count=0

critical_now=0
non_critical_now=0
total_now=0
domains_low="[]"
useful_soon_count=0
docs_only_acceptable_count=0
ignore_noise_count=0

if [[ -f "$semantic_report" ]]; then
  critical_now=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo (int)($j["summary"]["critical_mismatches"] ?? 0);' "$semantic_report")
  non_critical_now=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo (int)($j["summary"]["non_critical_mismatches"] ?? 0);' "$semantic_report")
  total_now=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo (int)($j["summary"]["total_mismatches"] ?? 0);' "$semantic_report")
  domains_low=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo json_encode($j["summary"]["domains_low_coverage"] ?? [], JSON_UNESCAPED_SLASHES);' "$semantic_report")

  useful_soon_count=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); $b=$j["non_critical_buckets"]["useful_soon"] ?? []; echo (int)(count($b["endpoints"] ?? []) + count($b["modules"] ?? []));' "$semantic_report")
  docs_only_acceptable_count=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); $b=$j["non_critical_buckets"]["docs_only_acceptable"] ?? []; echo (int)(count($b["endpoints"] ?? []) + count($b["modules"] ?? []));' "$semantic_report")
  ignore_noise_count=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); $b=$j["non_critical_buckets"]["ignore_noise"] ?? []; echo (int)(count($b["endpoints"] ?? []) + count($b["modules"] ?? []));' "$semantic_report")
fi

before_total=0
if [[ -f "$before_report" ]]; then
  before_total=$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo (int)($j["summary"]["total_mismatches"] ?? 0);' "$before_report")
fi

drift_delta=$((before_total - total_now))

hops_before=4
hops_after=2
files_opened_before=4
files_opened_after=2

METRIC_HOPS_BEFORE="$hops_before" \
METRIC_HOPS_AFTER="$hops_after" \
METRIC_FILES_BEFORE="$files_opened_before" \
METRIC_FILES_AFTER="$files_opened_after" \
METRIC_MEMORY_FIRST_COUNT="$memory_first_pointer_count" \
METRIC_DOCS_FIRST_COUNT="$docs_first_pointer_count" \
METRIC_LEGACY_WORDS="$legacy_pack_words" \
METRIC_CURRENT_WORDS="$current_pack_words" \
METRIC_LEGACY_TOKENS="$legacy_token_estimate" \
METRIC_CURRENT_TOKENS="$current_token_estimate" \
METRIC_DRIFT_BEFORE="$before_total" \
METRIC_DRIFT_AFTER="$total_now" \
METRIC_DRIFT_DELTA="$drift_delta" \
METRIC_CRITICAL_AFTER="$critical_now" \
METRIC_NON_CRITICAL_AFTER="$non_critical_now" \
METRIC_DOMAINS_LOW="$domains_low" \
METRIC_BUCKET_USEFUL_SOON="$useful_soon_count" \
METRIC_BUCKET_DOCS_ONLY="$docs_only_acceptable_count" \
METRIC_BUCKET_IGNORE_NOISE="$ignore_noise_count" \
php /dev/stdin "$report_json" "$report_md" <<'PHP'
<?php
[$jsonPath, $mdPath] = array_slice($argv, 1);
$env = $_ENV + $_SERVER;

$get = static function (string $key, $default = 0) use ($env) {
    if (!array_key_exists($key, $env)) {
        return $default;
    }
    return $env[$key];
};

$data = [
    'generated_at' => gmdate('c'),
    'retrieval_metrics' => [
        'estimated_hops_before' => (int) $get('METRIC_HOPS_BEFORE'),
        'estimated_hops_after' => (int) $get('METRIC_HOPS_AFTER'),
        'estimated_files_opened_before' => (int) $get('METRIC_FILES_BEFORE'),
        'estimated_files_opened_after' => (int) $get('METRIC_FILES_AFTER'),
        'memory_first_pointer_count' => (int) $get('METRIC_MEMORY_FIRST_COUNT'),
        'docs_first_pointer_count' => (int) $get('METRIC_DOCS_FIRST_COUNT'),
    ],
    'token_estimate' => [
        'legacy_pack_words' => (int) $get('METRIC_LEGACY_WORDS'),
        'current_pack_words' => (int) $get('METRIC_CURRENT_WORDS'),
        'legacy_token_estimate' => (int) $get('METRIC_LEGACY_TOKENS'),
        'current_token_estimate' => (int) $get('METRIC_CURRENT_TOKENS'),
        'estimated_token_reduction' => (int) $get('METRIC_LEGACY_TOKENS') - (int) $get('METRIC_CURRENT_TOKENS'),
    ],
    'drift' => [
        'before_total' => (int) $get('METRIC_DRIFT_BEFORE'),
        'after_total' => (int) $get('METRIC_DRIFT_AFTER'),
        'delta' => (int) $get('METRIC_DRIFT_DELTA'),
        'critical_after' => (int) $get('METRIC_CRITICAL_AFTER'),
        'non_critical_after' => (int) $get('METRIC_NON_CRITICAL_AFTER'),
        'domains_low_coverage' => json_decode((string) $get('METRIC_DOMAINS_LOW', '[]'), true) ?: [],
    ],
    'non_critical_buckets' => [
        'useful_soon' => (int) $get('METRIC_BUCKET_USEFUL_SOON'),
        'docs_only_acceptable' => (int) $get('METRIC_BUCKET_DOCS_ONLY'),
        'ignore_noise' => (int) $get('METRIC_BUCKET_IGNORE_NOISE'),
    ],
];

file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$md = [];
$md[] = '# Retrieval Metrics Report';
$md[] = '';
$md[] = 'อัปเดตล่าสุด: ' . gmdate('Y-m-d H:i:s') . ' UTC';
$md[] = '';
$md[] = '## Startup Cost (Estimated)';
$md[] = '';
$md[] = '- words: legacy=' . $data['token_estimate']['legacy_pack_words'] . ' -> current=' . $data['token_estimate']['current_pack_words'];
$md[] = '- tokens~: legacy=' . $data['token_estimate']['legacy_token_estimate'] . ' -> current=' . $data['token_estimate']['current_token_estimate'];
$md[] = '- reduction~: ' . $data['token_estimate']['estimated_token_reduction'];
$md[] = '';
$md[] = '## Retrieval Efficiency (Estimated)';
$md[] = '';
$md[] = '- hops: before=' . $data['retrieval_metrics']['estimated_hops_before'] . ' -> after=' . $data['retrieval_metrics']['estimated_hops_after'];
$md[] = '- files opened/task: before=' . $data['retrieval_metrics']['estimated_files_opened_before'] . ' -> after=' . $data['retrieval_metrics']['estimated_files_opened_after'];
$md[] = '- memory-first pointers=' . $data['retrieval_metrics']['memory_first_pointer_count'] . ', docs-first pointers=' . $data['retrieval_metrics']['docs_first_pointer_count'];
$md[] = '';
$md[] = '## Drift Stability';
$md[] = '';
$md[] = '- total drift: before=' . $data['drift']['before_total'] . ' -> after=' . $data['drift']['after_total'] . ' (delta=' . $data['drift']['delta'] . ')';
$md[] = '- critical=' . $data['drift']['critical_after'] . ', non-critical=' . $data['drift']['non_critical_after'];
$md[] = '- domains_low_coverage=' . json_encode($data['drift']['domains_low_coverage'], JSON_UNESCAPED_SLASHES);
$md[] = '';
$md[] = '## Non-Critical Buckets';
$md[] = '';
$md[] = '- useful soon: ' . $data['non_critical_buckets']['useful_soon'];
$md[] = '- docs-only acceptable: ' . $data['non_critical_buckets']['docs_only_acceptable'];
$md[] = '- ignore/noise: ' . $data['non_critical_buckets']['ignore_noise'];

file_put_contents($mdPath, implode("\n", $md) . "\n");
PHP

log_info "retrieval-metrics" "report generated: $report_json"
log_info "retrieval-metrics" "report generated: $report_md"
