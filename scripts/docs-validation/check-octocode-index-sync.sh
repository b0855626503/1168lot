#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

INDEX_ARTIFACT_PATH="${INDEX_ARTIFACT_PATH:-.ai/mcp/index-build.json}"

collect_changed_files() {
  local -n out_ref=$1
  INDEX_SCAN_MODE="working-tree"

  mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD 2>/dev/null || true)

  if (( ${#out_ref[@]} == 0 )) && [[ "${UNIFIED_SYNC_USE_LATEST_COMMIT}" == "1" ]]; then
    if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
      mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD~1 HEAD 2>/dev/null || true)
      if (( ${#out_ref[@]} > 0 )); then
        INDEX_SCAN_MODE="latest-commit"
      fi
    fi
  fi
}

is_code_trigger_change() {
  local path="$1"
  [[ "$path" == app/* || "$path" == bootstrap/* || "$path" == config/* || "$path" == database/* || "$path" == routes/* || "$path" == packages/* ]]
}

emit_issue() {
  local reason="$1"

  if [[ "$UNIFIED_SYNC_MODE" == "error" ]]; then
    log_error "octocode-index" "$reason"
  else
    log_warn "octocode-index" "$reason"
  fi
}

declare -a changed_files=()
INDEX_SCAN_MODE="working-tree"
collect_changed_files changed_files

if (( ${#changed_files[@]} == 0 )); then
  log_info "octocode-index" "no changed files for index verification"
  exit 0
fi

has_code_trigger=0
changed_code_files=()
for f in "${changed_files[@]}"; do
  if is_code_trigger_change "$f"; then
    has_code_trigger=1
    changed_code_files+=("$f")
  fi
done

if (( has_code_trigger == 0 )); then
  log_info "octocode-index" "no behavior/structure code changes detected"
  exit 0
fi

issues=0

if [[ ! -f "$INDEX_ARTIFACT_PATH" ]]; then
  emit_issue "missing index artifact: $INDEX_ARTIFACT_PATH"
  issues=$((issues + 1))
else
  valid_json="$(php -r '$j=@json_decode(file_get_contents($argv[1]),true); if (is_array($j) && !empty($j["built_at"]) && !empty($j["commit_hash"]) && isset($j["indexed_files"]) && is_array($j["indexed_files"])) { echo "1"; } else { echo "0"; }' "$INDEX_ARTIFACT_PATH")"
  if [[ "$valid_json" != "1" ]]; then
    emit_issue "invalid index artifact structure: $INDEX_ARTIFACT_PATH"
    issues=$((issues + 1))
  fi
fi

artifact_touched=0
for f in "${changed_files[@]}"; do
  if [[ "$f" == "$INDEX_ARTIFACT_PATH" ]]; then
    artifact_touched=1
    break
  fi
done

if (( artifact_touched == 0 )); then
  emit_issue "code changed but $INDEX_ARTIFACT_PATH was not rebuilt in this change set"
  issues=$((issues + 1))
fi

if [[ -f "$INDEX_ARTIFACT_PATH" ]]; then
  indexed_files_blob="$(php -r '$j=json_decode(file_get_contents($argv[1]),true); foreach (($j["indexed_files"] ?? []) as $f) { echo $f, PHP_EOL; }' "$INDEX_ARTIFACT_PATH")"

  missing_count=0
  for cf in "${changed_code_files[@]}"; do
    if ! grep -Fxq "$cf" <<<"$indexed_files_blob"; then
      missing_count=$((missing_count + 1))
    fi
  done

  if (( missing_count > 0 )); then
    emit_issue "index artifact missing $missing_count changed code files"
    issues=$((issues + 1))
  fi
fi

if (( issues > 0 )); then
  if [[ "$UNIFIED_SYNC_MODE" == "error" ]]; then
    exit 1
  fi
  log_info "octocode-index" "issues=$issues (mode=$UNIFIED_SYNC_MODE, scan=$INDEX_SCAN_MODE)"
  exit 0
fi

log_info "octocode-index" "index artifact is verifiably synced"
