#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

required=(
  "docs/internal/03_DOMAINS/frontend_api.md"
  "docs/internal/03_DOMAINS/wallet.md"
  "docs/internal/03_DOMAINS/payment.md"
  "docs/internal/03_DOMAINS/auth.md"
  "docs/internal/03_DOMAINS/lotto.md"
  "docs/internal/03_DOMAINS/admin_lotto.md"
  "docs/internal/03_DOMAINS/realtime.md"
)

errors=0
for f in "${required[@]}"; do
  if [[ ! -f "$f" ]]; then
    log_error "$f" "missing required domain entrypoint doc"
    errors=$((errors + 1))
    continue
  fi

  if ! rg -q "Entry Points|จุดเข้าโค้ด|Quick Map" "$f"; then
    log_warn "$f" "no clear entrypoint section found (recommended: Entry Points)"
  else
    log_info "$f" "domain entrypoint section found"
  fi
done

if (( errors > 0 )); then
  exit 1
fi
