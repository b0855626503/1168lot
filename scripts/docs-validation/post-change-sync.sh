#!/usr/bin/env bash

set -euo pipefail

# Optional helper to run after a code change or before commit.

bash scripts/docs-validation/rebuild-octocode-index-artifact.sh --changed-only
bash scripts/docs-validation/check-retrieval-entrypoints.sh
bash scripts/docs-validation/check-code-doc-drift.sh
bash scripts/docs-validation/check-semantic-sync.sh
bash scripts/docs-validation/check-octocode-index-sync.sh
bash scripts/docs-validation/check-unified-sync.sh
bash scripts/docs-validation/generate-retrieval-metrics-report.sh

# If local octocode index tooling exists in your environment, run it here.
# Example (environment-specific):
# octocode index --changed-only

printf '[INFO] post-change-sync: checks completed\n'
