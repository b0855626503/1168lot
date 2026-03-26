#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

plans_dir="$(plans_root)"
errors=0
warnings=0
active_count=0

if [[ ! -d "$plans_dir" ]]; then
  log_error "$plans_dir" "plans directory not found"
  exit 1
fi

mapfile -t plan_files < <(find "$plans_dir" -maxdepth 1 -type f -name '*.md' ! -name 'README.md' | sort)

if (( ${#plan_files[@]} == 0 )); then
  log_warn "$plans_dir" "no plan files found"
  exit 0
fi

for f in "${plan_files[@]}"; do
  head_block="$(sed -n '1,24p' "$f")"

  has_status=0
  has_date=0
  has_domain=0
  has_superseded=0

  if grep -Eq '^>\s*สถานะ:' <<<"$head_block"; then
    has_status=1
  fi
  if grep -Eq '^>\s*วันที่:' <<<"$head_block"; then
    has_date=1
  fi
  if grep -Eq '^>\s*โดเมน/เรื่อง:' <<<"$head_block"; then
    has_domain=1
  fi
  if grep -Eq '^>\s*แทนแผนเก่า:' <<<"$head_block"; then
    has_superseded=1
  fi

  if (( has_status == 0 )); then
    log_error "$f" "missing required header: สถานะ:"
    errors=$((errors + 1))
  fi
  if (( has_date == 0 )); then
    log_error "$f" "missing required header: วันที่:"
    errors=$((errors + 1))
  fi
  if (( has_domain == 0 )); then
    log_error "$f" "missing required header: โดเมน/เรื่อง:"
    errors=$((errors + 1))
  fi
  if (( has_superseded == 0 )); then
    log_error "$f" "missing required header: แทนแผนเก่า:"
    errors=$((errors + 1))
  fi

  if grep -Eq '^>\s*สถานะ:\s*ACTIVE\s*$' <<<"$head_block"; then
    active_count=$((active_count + 1))
    log_info "$f" "plan status is ACTIVE"
  fi
done

if (( active_count > 1 )); then
  log_error "$plans_dir" "ACTIVE plans must not exceed 1 (found: $active_count)"
  errors=$((errors + 1))
elif (( active_count == 0 )); then
  log_warn "$plans_dir" "no ACTIVE plan found"
  warnings=$((warnings + 1))
fi

if (( warnings > 0 )); then
  log_info "$plans_dir" "plan metadata warnings: $warnings"
fi

if (( errors > 0 )); then
  exit 1
fi
