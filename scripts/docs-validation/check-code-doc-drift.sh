#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

map_file="$SCRIPT_DIR/code-doc-map.tsv"
if [[ ! -f "$map_file" ]]; then
  log_error "$map_file" "drift mapping file not found"
  exit 1
fi

collect_changed_files() {
  local -n out_ref=$1
  DRIFT_SCAN_MODE="working-tree"

  mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD 2>/dev/null || true)

  if (( ${#out_ref[@]} == 0 )) && [[ "${DRIFT_USE_LATEST_COMMIT}" == "1" ]]; then
    if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
      mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD~1 HEAD 2>/dev/null || true)
      if (( ${#out_ref[@]} > 0 )); then
        DRIFT_SCAN_MODE="latest-commit"
      fi
    fi
  fi
}

declare -a changed_files=()
DRIFT_SCAN_MODE="working-tree"
collect_changed_files changed_files

if (( ${#changed_files[@]} == 0 )); then
  log_info "git" "no changed files found (working tree and latest commit)"
  exit 0
fi

log_info "git" "drift scan mode: $DRIFT_SCAN_MODE"

changed_blob="$(printf '%s\n' "${changed_files[@]}")"
errors=0
warnings=0

while IFS=$'\t' read -r rule_id code_regex doc_csv message; do
  [[ -z "${rule_id:-}" ]] && continue
  [[ "${rule_id:0:1}" == "#" ]] && continue

  matched_code=0
  while IFS= read -r f; do
    if [[ "$f" =~ $code_regex ]]; then
      matched_code=1
      break
    fi
  done <<<"$changed_blob"

  (( matched_code == 0 )) && continue

  doc_touched=0
  IFS=',' read -ra required_docs <<<"$doc_csv"
  for doc in "${required_docs[@]}"; do
    d="$(echo "$doc" | xargs)"
    [[ -z "$d" ]] && continue
    if grep -Fxq "$d" <<<"$changed_blob"; then
      doc_touched=1
      break
    fi
  done

  if (( doc_touched == 1 )); then
    log_info "$rule_id" "code-doc mapping satisfied"
    continue
  fi

  detail="$message (rule=$rule_id)"
  if [[ "$DRIFT_MODE" == "error" ]]; then
    log_error "$rule_id" "$detail"
    errors=$((errors + 1))
  else
    log_warn "$rule_id" "$detail"
    warnings=$((warnings + 1))
  fi

done < "$map_file"

if (( warnings > 0 )); then
  log_info "drift" "warnings: $warnings (mode=$DRIFT_MODE)"
fi

if (( errors > 0 )); then
  exit 1
fi
