#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib.sh"

errors=0
warnings=0
plans_dir="$(plans_root)"

mapfile -t md_files < <(git ls-files '*.md')

plan_style=""
plan_underscore_count=0
plan_dash_count=0
root_allowlist=(
  "AGENTS.md"
  "README.md"
  "CLAUDE.md"
  "FILE_POLICY.md"
  "MIGRATION_GUIDE.md"
)

for f in "${md_files[@]}"; do
  bn="$(basename "$f")"

  if [[ "$f" == "$plans_dir"/* && "$bn" != "README.md" ]]; then
    if [[ "$bn" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}_[a-z0-9-]+\.md$ ]]; then
      plan_underscore_count=$((plan_underscore_count + 1))
    elif [[ "$bn" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}-[a-z0-9-]+\.md$ ]]; then
      plan_dash_count=$((plan_dash_count + 1))
    fi
  fi
done

if (( plan_underscore_count > 0 && plan_dash_count > 0 )); then
  log_error "$plans_dir" "mixed plan naming styles detected (underscore and dash after date)"
  errors=$((errors + 1))
elif (( plan_dash_count > 0 )); then
  plan_style="dash"
else
  plan_style="underscore"
fi

for f in "${md_files[@]}"; do
  bn="$(basename "$f")"

  # Placement rules
  is_allowlisted_root="false"
  for allowed in "${root_allowlist[@]}"; do
    if [[ "$f" == "$allowed" ]]; then
      is_allowlisted_root="true"
      break
    fi
  done

  if [[ "$f" != docs/* && "$is_allowlisted_root" != "true" ]]; then
    if [[ "$f" == plugins/*/skills/*/SKILL.md ]]; then
      log_warn "$f" "plugin skill markdown is allowlisted"
      warnings=$((warnings + 1))
      continue
    fi

    if [[ "$f" == .github/* ]]; then
      log_warn "$f" "markdown under .github is temporarily allowlisted"
      warnings=$((warnings + 1))
      continue
    fi

    if [[ "$f" == memory/* ]]; then
      log_warn "$f" "markdown under memory is temporarily allowlisted"
      warnings=$((warnings + 1))
      continue
    fi

    if [[ "$f" == workspace/* ]]; then
      log_warn "$f" "markdown under workspace is temporarily allowlisted"
      warnings=$((warnings + 1))
      continue
    fi

    log_error "$f" "markdown file outside allowed locations (allowed: AGENTS.md, README.md, docs/*)"
    errors=$((errors + 1))
    continue
  fi

  # Skip non-doc markdown naming checks.
  if [[ "$f" != docs/* ]]; then
    continue
  fi

  # Skip archived third-party docs.
  if is_docs_third_party_archive "$f"; then
    continue
  fi

  # Allowed uppercase filename exceptions.
  if [[ "$bn" == "README.md" || "$bn" == "START_HERE.md" ]]; then
    :
  else
    if [[ "$bn" =~ [A-Z] ]]; then
      log_error "$f" "filename must be lowercase"
      errors=$((errors + 1))
    fi
  fi

  if [[ "$bn" =~ [[:space:]] ]]; then
    log_error "$f" "filename must not contain spaces"
    errors=$((errors + 1))
  fi

  lower_bn="$(printf '%s' "$bn" | tr '[:upper:]' '[:lower:]')"
  if [[ "$lower_bn" =~ (final|latest|new|v2-final) ]]; then
    log_error "$f" "forbidden naming keyword detected (final/latest/new/v2-final)"
    errors=$((errors + 1))
  fi

  # Plan-specific naming checks.
  if [[ "$f" == "$plans_dir"/* ]]; then
    # README.md = index file; _*.md = system/tracker files (e.g. _current_work.md). Both exempt.
    if [[ "$bn" == "README.md" || "$bn" == _*.md ]]; then
      continue
    fi

    if [[ "$plan_style" == "underscore" ]]; then
      if [[ ! "$bn" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}_[a-z0-9-]+\.md$ ]]; then
        log_error "$f" "plan filename must match YYYY-MM-DD_topic-name.md (or prefix with _ for system files)"
        errors=$((errors + 1))
      fi
    else
      if [[ ! "$bn" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}-[a-z0-9-]+\.md$ ]]; then
        log_error "$f" "plan filename must match YYYY-MM-DD-topic-name.md (or prefix with _ for system files)"
        errors=$((errors + 1))
      fi
    fi

    continue
  fi

  # Legacy underscore support in docs paths (warning-only for this phase).
  if [[ "$bn" == *_* ]]; then
    log_warn "$f" "legacy underscore filename allowed temporarily; migrate to dash in future phase"
    warnings=$((warnings + 1))
  fi

done

if (( warnings > 0 )); then
  log_info "docs" "naming warnings: $warnings"
fi

if (( errors > 0 )); then
  exit 1
fi
