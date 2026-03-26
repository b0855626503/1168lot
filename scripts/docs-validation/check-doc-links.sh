#!/usr/bin/env bash

set -u

python3 - <<'PY'
import os
import re
import sys
from pathlib import Path

repo = Path('.').resolve()
docs_root = repo / 'docs'
if not docs_root.exists():
    print('[ERROR] docs - docs directory not found')
    sys.exit(1)

md_files = sorted(docs_root.rglob('*.md'))

link_pattern = re.compile(r'\[[^\]]*\]\(([^)]+)\)')
code_path_pattern = re.compile(r'`((?:docs/|\./|\.\./)[^`\s]+)`')

errors = 0


def normalize_target(raw: str) -> str:
    t = raw.strip()
    if t.startswith('<') and t.endswith('>'):
        t = t[1:-1].strip()
    # Remove optional title part in markdown link: path "title"
    if ' ' in t and not t.startswith(('http://', 'https://', 'mailto:')):
        t = t.split()[0]
    # Remove query / fragment
    t = t.split('#', 1)[0]
    t = t.split('?', 1)[0]
    return t


def should_ignore(target: str) -> bool:
    if not target:
        return True
    if target.startswith(('http://', 'https://', 'mailto:', '#')):
        return True
    if target.startswith('javascript:'):
        return True
    if '<' in target or '>' in target or '*' in target:
        return True
    return False


def resolve_target(file_path: Path, target: str):
    if target.startswith('docs/'):
        return (repo / target).resolve()
    if target.startswith('./') or target.startswith('../'):
        return (file_path.parent / target).resolve()
    return None


for md_file in md_files:
    rel_file = md_file.relative_to(repo).as_posix()

    in_code = False
    try:
        lines = md_file.read_text(encoding='utf-8').splitlines()
    except Exception as exc:
        print(f'[ERROR] {rel_file} - failed to read file: {exc}')
        errors += 1
        continue

    for idx, line in enumerate(lines, start=1):
        stripped = line.strip()
        if stripped.startswith('```'):
            in_code = not in_code
            continue
        if in_code:
            continue

        targets = []
        for m in link_pattern.finditer(line):
            targets.append((m.group(1), 'markdown-link'))
        for m in code_path_pattern.finditer(line):
            targets.append((m.group(1), 'inline-code-path'))

        for raw_target, source_kind in targets:
            target = normalize_target(raw_target)
            if should_ignore(target):
                continue

            resolved = resolve_target(md_file, target)
            if resolved is None:
                continue

            if not resolved.exists():
                path_reason = f'broken {source_kind} target "{target}" at line {idx}'
                print(f'[ERROR] {rel_file} - {path_reason}')
                errors += 1

if errors > 0:
    sys.exit(1)

print('[INFO] docs - internal docs/ and relative link check passed')
PY
