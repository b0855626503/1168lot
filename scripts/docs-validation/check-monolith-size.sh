#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"
source "$SCRIPT_DIR/config.sh"

errors=0
warns=0

while IFS= read -r file; do
  [[ -z "$file" ]] && continue

  if is_docs_third_party_archive "$file"; then
    continue
  fi

  if [[ "$file" == docs/internal/05_ARCHIVE/* || "$file" == docs/public/api/archive/* ]]; then
    continue
  fi

  if [[ "$file" == docs/public/api/frontend-v1/07-route-reference.md ]]; then
    # Public runtime docs intentionally keep full reference in one page.
    continue
  fi

  if [[ "$file" == docs/public/llms-full-th.md ]]; then
    # Vendor API doc (WealthWave) — single file for AI agent paste-ability.
    continue
  fi

  lines=$(wc -l < "$file")
  if (( lines > DOC_MONOLITH_MAX_LINES )); then
    log_error "$file" "exceeds monolith threshold ($lines > $DOC_MONOLITH_MAX_LINES). Split to index + chapters"
    errors=$((errors + 1))
  fi
done < <(find docs -type f -name '*.md' | sort)

if (( warns > 0 )); then
  log_info "docs" "monolith warnings: $warns"
fi

if (( errors > 0 )); then
  exit 1
fi
