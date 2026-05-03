#!/usr/bin/env bash
# Generic fallback heuristic: any Service.php change should have at least one *_discovery.md touched.
# This catches new domains not yet explicitly mapped in code-doc-map.tsv.
# Severity: WARN only — heuristic, requires human judgment.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

collect_changed_files() {
  local -n out_ref=$1
  mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD 2>/dev/null || true)
  if (( ${#out_ref[@]} == 0 )); then
    if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
      mapfile -t out_ref < <(git diff --name-only --diff-filter=ACMR HEAD~1 HEAD 2>/dev/null || true)
    fi
  fi
}

declare -a changed_files=()
collect_changed_files changed_files

if (( ${#changed_files[@]} == 0 )); then
  log_info "service-discovery-sync" "no changed files"
  exit 0
fi

changed_blob="$(printf '%s\n' "${changed_files[@]}")"

# Check: any Service.php changed under app/ or packages/?
services_changed="$(printf '%s' "$changed_blob" | grep -E '(^app/Services/|^packages/Gametech/[^/]+/src/.*Service\.php)' || true)"

if [[ -z "$services_changed" ]]; then
  log_info "service-discovery-sync" "no service files changed"
  exit 0
fi

# Check: any *_discovery.md touched?
discovery_touched="$(printf '%s' "$changed_blob" | grep -E '^docs/internal/03_DOMAINS/.*_discovery\.md$' || true)"

if [[ -n "$discovery_touched" ]]; then
  log_info "service-discovery-sync" "service changed and a discovery doc was updated — ok"
  exit 0
fi

log_warn "service-discovery-sync" "service file(s) changed but no docs/internal/03_DOMAINS/*_discovery.md was updated — verify if flow changed and bump Last Verified if so"

# WARN only: heuristic, not a hard block
exit 0
