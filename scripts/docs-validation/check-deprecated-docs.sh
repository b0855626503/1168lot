#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

# Active route reference for frontend-v1 (update if the active file ever changes)
active_route_ref="07-route-reference.md"
dir="docs/public/api/frontend-v1"

errors=0

if [[ ! -d "$dir" ]]; then
  log_warn "$dir" "frontend-v1 directory not found, skipping deprecated-docs check"
  exit 0
fi

while IFS= read -r -d '' f; do
  filename="$(basename "$f")"

  # Skip the currently active route reference
  if [[ "$filename" == "$active_route_ref" ]]; then
    log_info "$f" "active route reference, skipping deprecation check"
    continue
  fi

  # Enforce DEPRECATED/ARCHIVED header on stale route-reference and endpoints files
  if [[ "$filename" == *route-reference* ]] || [[ "$filename" == *endpoints* ]]; then
    head_block="$(head -5 "$f")"
    if grep -qiE "DEPRECATED|ARCHIVED" <<<"$head_block"; then
      log_info "$f" "deprecated/archived marker found"
    else
      log_error "$f" "stale route-reference/endpoints file is missing a DEPRECATED or ARCHIVED header — add one or move to archive"
      errors=$((errors + 1))
    fi
  fi
done < <(find "$dir" -maxdepth 1 -name "*.md" -print0 | sort -z)

if (( errors > 0 )); then
  exit 1
fi
