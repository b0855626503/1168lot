#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

errors=0
plans_dir="$(plans_root)"

required_dirs=(
  "docs/internal/00_RULES"
  "docs/internal/01_SYSTEM"
  "docs/internal/02_DECISIONS"
  "docs/internal/03_DOMAINS"
  "docs/internal/05_ARCHIVE"
  "docs/public/api"
  "docs/public/integration"
  "$plans_dir"
)

for d in "${required_dirs[@]}"; do
  if [[ ! -d "$d" ]]; then
    log_error "$d" "missing required directory"
    errors=$((errors + 1))
  else
    log_info "$d" "required directory exists"
  fi
done

if (( errors > 0 )); then
  exit 1
fi
