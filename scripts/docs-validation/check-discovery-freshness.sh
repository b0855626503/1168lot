#!/usr/bin/env bash
# Checks freshness of *_discovery.md files:
#   - warns if Last Verified > FRESHNESS_WARN_DAYS (default 14)
#   - errors if Last Verified > FRESHNESS_ERROR_DAYS AND domain is critical AND Stability != stable
#   - Stability: stable  → WARN only after FRESHNESS_ERROR_DAYS, never ERROR
#   - Stability: volatile (or unset) → default thresholds apply
#   - warns if discovery doc exceeds DISCOVERY_MAX_LINES lines (default 150)

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

# Domains where staleness is an ERROR after FRESHNESS_ERROR_DAYS
critical_domains=("lotto" "wallet")
FRESHNESS_WARN_DAYS="${FRESHNESS_WARN_DAYS:-14}"
FRESHNESS_ERROR_DAYS="${FRESHNESS_ERROR_DAYS:-30}"
DISCOVERY_MAX_LINES="${DISCOVERY_MAX_LINES:-150}"

errors=0

today_epoch=$(date -d "$(date +%Y-%m-%d)" +%s 2>/dev/null)
if [[ -z "$today_epoch" ]]; then
  log_warn "discovery-freshness" "could not determine today's date — skipping staleness check"
  exit 0
fi

while IFS= read -r -d '' f; do
  # --- Freshness check ---
  verified_line="$(grep -m1 'Last Verified:' "$f" 2>/dev/null || true)"
  if [[ -z "$verified_line" ]]; then
    log_warn "$f" "missing 'Last Verified:' freshness header"
  else
    verified_date="$(printf '%s' "$verified_line" | grep -oE '[0-9]{4}-[0-9]{2}-[0-9]{2}' | head -1 || true)"
    if [[ -z "$verified_date" ]]; then
      log_warn "$f" "could not parse date from 'Last Verified:' line"
    else
      verified_epoch=$(date -d "$verified_date" +%s 2>/dev/null || true)
      if [[ -z "$verified_epoch" ]]; then
        log_warn "$f" "could not compute age from Last Verified: $verified_date"
      else
        age_days=$(( (today_epoch - verified_epoch) / 86400 ))

        # Parse Stability field from the same metadata line or nearby
        stability="$(printf '%s' "$verified_line" | grep -oiE 'Stability:\s*(stable|volatile)' | grep -oiE '(stable|volatile)' | tr '[:upper:]' '[:lower:]' || true)"
        # Fallback: check following lines in metadata block (first 5 lines)
        if [[ -z "$stability" ]]; then
          stability="$(head -5 "$f" | grep -oiE 'Stability:\s*(stable|volatile)' | grep -oiE '(stable|volatile)' | tr '[:upper:]' '[:lower:]' | head -1 || true)"
        fi

        # Determine if critical domain
        is_critical=0
        for pat in "${critical_domains[@]}"; do
          if [[ "$f" == *"$pat"* ]]; then
            is_critical=1
            break
          fi
        done

        if [[ "$stability" == "stable" ]]; then
          # Stable docs: WARN only after FRESHNESS_ERROR_DAYS, never ERROR
          if (( age_days > FRESHNESS_ERROR_DAYS )); then
            log_warn "$f" "stable discovery doc may need review: Last Verified $verified_date ($age_days days ago, stable threshold=${FRESHNESS_ERROR_DAYS}d)"
          else
            log_info "$f" "freshness ok [stable]: Last Verified $verified_date ($age_days days ago)"
          fi
        elif (( is_critical == 1 && age_days > FRESHNESS_ERROR_DAYS )); then
          log_error "$f" "critical discovery doc is stale: Last Verified $verified_date ($age_days days ago, limit=${FRESHNESS_ERROR_DAYS}d) — update and bump Last Verified"
          errors=$((errors + 1))
        elif (( age_days > FRESHNESS_WARN_DAYS )); then
          log_warn "$f" "discovery doc may be stale: Last Verified $verified_date ($age_days days ago, warn=${FRESHNESS_WARN_DAYS}d) — verify entrypoints with rg and bump Last Verified"
        else
          log_info "$f" "freshness ok: Last Verified $verified_date ($age_days days ago)"
        fi
      fi
    fi
  fi

  # --- Size check ---
  lines=$(wc -l < "$f")
  if (( lines > DISCOVERY_MAX_LINES )); then
    log_warn "$f" "discovery doc is large ($lines lines > ${DISCOVERY_MAX_LINES} limit) — split or trim to keep it scannable"
  fi

done < <(find docs/internal/03_DOMAINS -maxdepth 1 -name "*-discovery.md" -print0 | sort -z)

if (( errors > 0 )); then
  exit 1
fi
