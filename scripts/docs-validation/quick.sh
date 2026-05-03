#!/usr/bin/env bash
# Quick docs validation — critical checks only (fast, < 5s).
# Intended for pre-commit hooks and local fast feedback.
#
# Full suite: bash scripts/docs-validation/run.sh
#
# To install as a git pre-commit hook:
#   echo '#!/usr/bin/env bash' > .git/hooks/pre-commit
#   echo 'bash scripts/docs-validation/quick.sh' >> .git/hooks/pre-commit
#   chmod +x .git/hooks/pre-commit

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$(mktemp)"

quick_checks=(
  "check-deprecated-docs.sh"
  "check-discovery-freshness.sh"
)

for check in "${quick_checks[@]}"; do
  echo "[INFO] scripts/docs-validation/$check - running"
  if ! bash "$SCRIPT_DIR/$check" | tee -a "$LOG_FILE"; then
    :
  fi
done

error_count=$(grep -c '^\[ERROR\]' "$LOG_FILE" || true)
warn_count=$(grep -c '^\[WARN\]' "$LOG_FILE" || true)

printf '[INFO] quick.sh - summary: errors=%s warnings=%s\n' "$error_count" "$warn_count"

if (( error_count > 0 )); then
  echo '[ERROR] quick.sh - critical docs check failed'
  rm -f "$LOG_FILE"
  exit 1
fi

echo '[INFO] quick.sh - critical docs check passed'
rm -f "$LOG_FILE"
