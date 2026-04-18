#!/usr/bin/env bash

set -euo pipefail

ARTIFACT_PATH="${INDEX_ARTIFACT_PATH:-.ai/mcp/index-build.json}"
MODE="full"
if [[ "${1:-}" == "--changed-only" ]]; then
  MODE="changed-only"
fi

mkdir -p "$(dirname "$ARTIFACT_PATH")"

if [[ "$MODE" == "changed-only" ]]; then
  mapfile -t files < <(git diff --name-only --diff-filter=ACMR HEAD 2>/dev/null || true)
else
  mapfile -t files < <(find app bootstrap config database routes packages -type f 2>/dev/null | sort)
fi

commit_hash=""
if git rev-parse --verify HEAD >/dev/null 2>&1; then
  commit_hash="$(git rev-parse HEAD)"
fi

files_list_file="$(mktemp)"
trap 'rm -f "$files_list_file"' EXIT
printf '%s\n' "${files[@]:-}" > "$files_list_file"

php /dev/stdin "$ARTIFACT_PATH" "$MODE" "$commit_hash" "$files_list_file" <<'PHP'
<?php
[$artifactPath, $mode, $commitHash, $filesListFile] = array_slice($argv, 1);
$files = [];
if (is_file($filesListFile)) {
    $lines = file($filesListFile) ?: [];
    $files = array_values(array_unique(array_filter(array_map('trim', $lines))));
}
sort($files);

$payload = [
    'built_at' => gmdate('c'),
    'commit_hash' => $commitHash,
    'mode' => $mode,
    'indexed_files' => $files,
];

file_put_contents($artifactPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
PHP

printf '[INFO] index artifact rebuilt: %s (%s)\n' "$ARTIFACT_PATH" "$MODE"
