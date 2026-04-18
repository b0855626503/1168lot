#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

collect_changed_files() {
  local -n out_ref=$1
  UNIFIED_SCAN_MODE="working-tree"

  mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD 2>/dev/null || true)

  if (( ${#out_ref[@]} == 0 )) && [[ "${UNIFIED_SYNC_USE_LATEST_COMMIT}" == "1" ]]; then
    if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
      mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD~1 HEAD 2>/dev/null || true)
      if (( ${#out_ref[@]} > 0 )); then
        UNIFIED_SCAN_MODE="latest-commit"
      fi
    fi
  fi
}

is_code_trigger_change() {
  local path="$1"
  [[ "$path" == app/* || "$path" == bootstrap/* || "$path" == config/* || "$path" == database/* || "$path" == routes/* || "$path" == packages/* ]]
}

is_docs_sync_change() {
  local path="$1"
  [[ "$path" == docs/* && "$path" == *.md ]]
}

is_memory_sync_change() {
  local path="$1"
  [[ "$path" == .codebase-memory/* || "$path" == memory/* || "$path" == workspace/MEMORY.md ]]
}

is_octocode_sync_change() {
  local path="$1"
  [[ "$path" == .ai/mcp/* || "$path" == .octocode/* ]]
}

emit_issue() {
  local reason="$1"

  if [[ "$UNIFIED_SYNC_MODE" == "error" ]]; then
    log_error "unified-sync" "$reason"
  else
    log_warn "unified-sync" "$reason"
  fi
}

check_memory_coverage() {
  local missing=0
  local required=(
    "memory/auth.md"
    "memory/payment.md"
    "memory/wallet.md"
    "memory/game.md"
  )

  for f in "${required[@]}"; do
    if [[ ! -f "$f" ]]; then
      emit_issue "missing required memory coverage file: $f"
      missing=$((missing + 1))
    fi
  done

  if (( missing > 0 )); then
    return 1
  fi

  return 0
}

declare -a changed_files=()
UNIFIED_SCAN_MODE="working-tree"
collect_changed_files changed_files

if (( ${#changed_files[@]} == 0 )); then
  log_info "git" "no changed files found for unified sync check"
fi

log_info "git" "unified sync scan mode: $UNIFIED_SCAN_MODE"

has_code_trigger=0
has_docs_sync=0
has_memory_sync=0
has_octocode_sync=0

for f in "${changed_files[@]}"; do
  if is_code_trigger_change "$f"; then
    has_code_trigger=1
  fi

  if is_docs_sync_change "$f"; then
    has_docs_sync=1
  fi

  if is_memory_sync_change "$f"; then
    has_memory_sync=1
  fi

  if is_octocode_sync_change "$f"; then
    has_octocode_sync=1
  fi
done

issues=0
semantic_mismatch_count=0

if (( has_code_trigger == 1 )); then
  if (( has_docs_sync == 0 )); then
    emit_issue "code changed but docs (.md) were not updated"
    issues=$((issues + 1))
  fi

  if (( has_memory_sync == 0 )); then
    emit_issue "code changed but memory layer (.codebase-memory/memory) was not updated"
    issues=$((issues + 1))
  fi

  if (( has_octocode_sync == 0 )); then
    emit_issue "code changed but octocode index layer (.ai/mcp or .octocode) was not updated"
    issues=$((issues + 1))
  fi
fi

if ! check_memory_coverage; then
  issues=$((issues + 1))
fi

if ! bash "$SCRIPT_DIR/check-semantic-sync.sh"; then
  issues=$((issues + 1))
fi

if [[ -f "${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}" ]]; then
  semantic_mismatch_count="$(php -r '$j=json_decode(file_get_contents($argv[1]), true); echo (int)($j["summary"]["total_mismatches"] ?? 0);' "${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}")"
fi

if ! bash "$SCRIPT_DIR/check-octocode-index-sync.sh"; then
  issues=$((issues + 1))
fi

if (( issues > 0 )); then
  if [[ "$UNIFIED_SYNC_MODE" == "error" ]]; then
    exit 1
  fi

  log_info "unified-sync" "issues=$issues (mode=$UNIFIED_SYNC_MODE)"
  exit 0
fi

if (( semantic_mismatch_count > 0 )); then
  log_warn "unified-sync" "semantic drift remains: $semantic_mismatch_count (see ${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json})"
else
  log_info "unified-sync" "semantic-consistent + coverage-complete + index-synced"
fi
