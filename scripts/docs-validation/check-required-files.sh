#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
source "$SCRIPT_DIR/lib.sh"

errors=0
plans_dir="$(plans_root)"

required_files=(
  "AGENTS.md"
  "README.md"
  "docs/START-HERE.md"
  "docs/README.md"
  "docs/internal/00_RULES/agent-rules.md"
  "docs/internal/01_SYSTEM/system-current-state.md"
  "docs/internal/02_DECISIONS/decision-log.md"
  "$plans_dir/README.md"
)

for f in "${required_files[@]}"; do
  if [[ ! -f "$f" ]]; then
    log_error "$f" "missing required file"
    errors=$((errors + 1))
  else
    log_info "$f" "required file exists"
  fi
done

if (( errors > 0 )); then
  exit 1
fi
