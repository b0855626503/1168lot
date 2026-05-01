# Plan Contract Validator

This guide defines how to validate implementation output against a locked plan contract using repository files only.

The validator is deterministic and uses:
- contract file
- git diff
- changed file contents
- optional test output file
- optional handoff file

It does not rely on chat history.

## Contract location

Create one file per issue:

- `docs/04_PLANS/contracts/BOA-191.yml` (copy then rename per issue)

Use `docs/04_PLANS/contracts/BOA-191.yml` as reference.

## Required contract sections

- `allowed_files`
- `forbidden_files`
- `required_terms`
- `forbidden_terms`
- `required_tests`
- `required_test_commands`
- `handoff_required_sections`

Optional strictness flags are supported in the same file.

## Run locally

```bash
php tools/plan-validator/validate.php \
  --issue=BOA-191 \
  --base=main \
  --head=HEAD \
  --contract=docs/04_PLANS/contracts/BOA-191.yml \
  --test-output=storage/plan-validator/BOA-191-test-output.txt \
  --handoff=storage/plan-validator/BOA-191-handoff.md \
  --json
```

Report output path:

- `storage/plan-validator/{ISSUE}-report.json`

## Status meaning

- `pass`: all checks passed
- `warn`: policy warnings only
- `fail`: contract violation found

Exit code:

- `pass` => `0`
- `warn` => `0` (or `1` with `--strict`)
- `fail` => `1`

## Handoff gate before final delivery

1. Update handoff file with all required sections.
2. Run validator with `--test-output` and `--handoff`.
3. Attach human summary and JSON report.
4. Do not hand off when status is `fail`.
