#!/usr/bin/env bash

# Drift mode: warn|error
DRIFT_MODE="${DRIFT_MODE:-warn}"

# If enabled, drift check will use latest commit when working tree has no diff.
DRIFT_USE_LATEST_COMMIT="${DRIFT_USE_LATEST_COMMIT:-1}"

# Maximum line count allowed for active (non-archive) markdown docs.
DOC_MONOLITH_MAX_LINES="${DOC_MONOLITH_MAX_LINES:-1200}"

# Unified sync mode: warn|error
UNIFIED_SYNC_MODE="${UNIFIED_SYNC_MODE:-warn}"

# If enabled, unified sync check will use latest commit when working tree has no diff.
UNIFIED_SYNC_USE_LATEST_COMMIT="${UNIFIED_SYNC_USE_LATEST_COMMIT:-1}"

# Semantic sync mode: warn|error
SEMANTIC_SYNC_MODE="${SEMANTIC_SYNC_MODE:-$UNIFIED_SYNC_MODE}"
SEMANTIC_REPORT_PATH="${SEMANTIC_REPORT_PATH:-.ai/mcp/semantic-drift-report.json}"
CRITICAL_DRIFT_MODE="${CRITICAL_DRIFT_MODE:-error}"
DOMAIN_COVERAGE_MODE="${DOMAIN_COVERAGE_MODE:-warn}"

# Machine-verifiable octocode index artifact.
INDEX_ARTIFACT_PATH="${INDEX_ARTIFACT_PATH:-.ai/mcp/index-build.json}"

# Retrieval metrics artifacts.
RETRIEVAL_METRICS_REPORT_JSON="${RETRIEVAL_METRICS_REPORT_JSON:-.ai/mcp/retrieval-metrics-report.json}"
RETRIEVAL_METRICS_REPORT_MD="${RETRIEVAL_METRICS_REPORT_MD:-.ai/mcp/retrieval-metrics-report.md}"
