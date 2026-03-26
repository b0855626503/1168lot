#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

plans_dir="$(plans_root)"
readme="$plans_dir/README.md"

if [[ ! -f "$readme" ]]; then
  log_error "$readme" "plans README is required"
  exit 1
fi

log_info "$readme" "plans README exists"
