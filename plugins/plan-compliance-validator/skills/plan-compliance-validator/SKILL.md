---
name: plan-compliance-validator
description: Run deterministic implementation-vs-plan validation using contract files and git diff.
---

# plan_compliance.validate

Use this skill when you need to verify that implementation output matches the active plan contract.

## Command

```bash
bash plugins/plan-compliance-validator/scripts/plan_compliance_validate.sh \
  --issue=BOA-191 \
  --base=main \
  --head=HEAD \
  --contract=docs/04_PLANS/contracts/BOA-191.yml \
  --test-output=storage/plan-validator/BOA-191-test-output.txt \
  --handoff=storage/plan-validator/BOA-191-handoff.md \
  --json
```

## Behavior

- Reads contract from repository (`docs/04_PLANS/contracts/{ISSUE}.yml`)
- Reads actual git diff (`git diff --name-only base...head`)
- Emits deterministic JSON report + compact human summary
- Writes report file to `storage/plan-validator/{ISSUE}-report.json`
