#!/bin/bash
# verify-imports — check that all `use` statements in changed PHP files resolve to real classes
# Usage: bash scripts/dev/verify-imports.sh [file ...]
#        bash scripts/dev/verify-imports.sh --diff   (check unstaged changes)
#        bash scripts/dev/verify-imports.sh --all    (check entire codebase)

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ERRORS=0

# --- helpers ---
check_file() {
    local file="$1"
    local imports errors_in_file

    # Extract `use ...` statements (non-anon, fully qualified)
    imports=$(php -r "
        \$src = file_get_contents('$file');
        preg_match_all('/^use\s+(\\\\\\\S+)\\\\(\S+);/m', \$src, \$m);
        foreach (\$m[0] as \$i => \$full) {
            echo \$m[1][\$i] . '\\\\' . \$m[2][\$i] . PHP_EOL;
        }
    " 2>/dev/null)

    if [ -z "$imports" ]; then
        return
    fi

    errors_in_file=0
    while IFS= read -r fqcn; do
        [ -z "$fqcn" ] && continue

        # Skip framework / vendor classes — those are Composer's job
        if echo "$fqcn" | grep -qE '^(Illuminate|Carbon|Symfony|Psr|League|Spatie|Monolog|Doctrine|Guzzle|Ramsey|Opis|Intervention|Egulias|Brick|Predis|Redis|GuzzleHttp|Http\\|MongoDB|Elasticsearch)'; then
            continue
        fi

        # Convert FQCN to file path
        class_path=$(echo "$fqcn" | tr '\\' '/' | sed 's|^App/||')

        # Check in app/
        if echo "$fqcn" | grep -q '^App\\'; then
            if [ -f "$PROJECT_ROOT/app/${class_path}.php" ]; then
                continue
            fi
        fi

        # Check Gametech packages — resolve namespace to src/
        if echo "$fqcn" | grep -q '^Gametech\\'; then
            # Gametech\Payment\Libraries\FlashPay → packages/Gametech/Payment/src/Libraries/FlashPay.php
            pkg_path=$(echo "$fqcn" | sed 's|\\|/|g' | sed 's|^Gametech/|packages/Gametech/|' | sed 's|/\([^/]*\)$|/src/\1.php|')
            if [ -f "$PROJECT_ROOT/$pkg_path" ]; then
                continue
            fi
            # Try without /src/ (some packages have different structures)
            pkg_path=$(echo "$fqcn" | sed 's|\\|/|g' | sed 's|^Gametech/|packages/Gametech/|').php
            if [ -f "$PROJECT_ROOT/$pkg_path" ]; then
                continue
            fi
        fi

        echo "  ❌ $file: $fqcn — file not found"
        errors_in_file=$((errors_in_file + 1))
        ERRORS=$((ERRORS + 1))
    done <<< "$imports"

    if [ "$errors_in_file" -gt 0 ]; then
        return 1
    fi
}

# --- scan unstaged diff ---
scan_diff() {
    local files
    files=$(git -C "$PROJECT_ROOT" diff --name-only --diff-filter=ACMR -- '*.php' | grep -v 'vendor/' || true)
    if [ -z "$files" ]; then
        echo "[verify-imports] No changed PHP files"
        return
    fi
    echo "[verify-imports] Checking $(echo "$files" | wc -l) changed file(s)..."
    while IFS= read -r f; do
        check_file "$PROJECT_ROOT/$f" || true
    done <<< "$files"
}

# --- main ---
if [ $# -eq 0 ]; then
    echo "Usage: verify-imports.sh [--diff|--all|file.php ...]" >&2
    exit 1
fi

case "${1:-}" in
    --diff) scan_diff ;;
    --all)
        while IFS= read -r -d '' f; do
            check_file "$f" || true
        done < <(find "$PROJECT_ROOT/app" "$PROJECT_ROOT/packages" -name '*.php' -not -path '*/vendor/*' -print0)
        ;;
    *)
        for f in "$@"; do
            check_file "$f" || true
        done
        ;;
esac

if [ "$ERRORS" -gt 0 ]; then
    echo "[verify-imports] ❌ $ERRORS unresolved import(s) found"
    exit 1
else
    echo "[verify-imports] ✅ All imports resolve"
fi
