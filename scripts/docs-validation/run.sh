#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$(mktemp)"

checks=(
  "check-required-files.sh"
  "check-structure.sh"
  "check-naming.sh"
  "check-plans-readme.sh"
  "check-plan-metadata.sh"
  "check-domain-entrypoints.sh"
  "check-retrieval-entrypoints.sh"
  "check-doc-links.sh"
  "check-monolith-size.sh"
  "check-code-doc-drift.sh"
  "check-semantic-sync.sh"
  "check-unified-sync.sh"
  "generate-retrieval-metrics-report.sh"
)

for check in "${checks[@]}"; do
  echo "[INFO] scripts/docs-validation/$check - running"
  if ! bash "$SCRIPT_DIR/$check" | tee -a "$LOG_FILE"; then
    # Keep running remaining checks to show all issues.
    :
  fi
done

error_count=$(grep -c '^\[ERROR\]' "$LOG_FILE" || true)
warn_count=$(grep -c '^\[WARN\]' "$LOG_FILE" || true)

printf '[INFO] scripts/docs-validation/run.sh - summary: errors=%s warnings=%s\n' "$error_count" "$warn_count"

if (( error_count > 0 )); then
  echo '[ERROR] scripts/docs-validation/run.sh - docs validation failed'
  rm -f "$LOG_FILE"
  exit 1
fi

echo '[INFO] scripts/docs-validation/run.sh - docs validation passed'
rm -f "$LOG_FILE"
