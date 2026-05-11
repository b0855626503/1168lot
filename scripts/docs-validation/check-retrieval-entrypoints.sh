#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

target="docs/internal/01_SYSTEM/retrieval-system-status.md"
files=(
  "docs/START-HERE.md"
  "docs/internal/00_RULES/agent-rules.md"
  "docs/internal/01_SYSTEM/startup-digest.md"
  "docs/internal/01_SYSTEM/mcp-operating-guide.md"
)

errors=0
for f in "${files[@]}"; do
  if [[ ! -f "$f" ]]; then
    log_error "$f" "entrypoint file missing"
    errors=$((errors + 1))
    continue
  fi

  if rg -q "$target" "$f"; then
    log_info "$f" "retrieval status pointer found"
  else
    log_error "$f" "missing retrieval status pointer: $target"
    errors=$((errors + 1))
  fi
done

if (( errors > 0 )); then
  exit 1
fi
