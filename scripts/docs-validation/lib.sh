#!/usr/bin/env bash

set -u

log_info() {
  local path="${1:--}"
  local reason="${2:-}"
  printf '[INFO] %s - %s\n' "$path" "$reason"
}

log_warn() {
  local path="${1:--}"
  local reason="${2:-}"
  printf '[WARN] %s - %s\n' "$path" "$reason"
}

log_error() {
  local path="${1:--}"
  local reason="${2:-}"
  printf '[ERROR] %s - %s\n' "$path" "$reason"
}

repo_root() {
  git rev-parse --show-toplevel 2>/dev/null || pwd
}

plans_root() {
  if [[ -f docs/04_PLANS/README.md ]]; then
    printf 'docs/04_PLANS\n'
    return
  fi

  if [[ -f docs/internal/04_PLANS/README.md ]]; then
    printf 'docs/internal/04_PLANS\n'
    return
  fi

  # Default to new path for clearer errors.
  printf 'docs/04_PLANS\n'
}

is_docs_third_party_archive() {
  local p="$1"
  [[ "$p" == docs/internal/05_ARCHIVE/third-party/* ]]
}
