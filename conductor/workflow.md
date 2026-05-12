# Workflow — 1168lot

## TDD Policy

**Flexible — tests for complex logic**

Tests are required for:
- Financial logic (wallet transactions, settlement, payout)
- Complex business rules (draw lifecycle, permission checks, policy enforcement)
- API endpoints with non-trivial behavior
- Migration safety (schema changes, data backfills)

Tests are recommended but won't block:
- Simple CRUD additions
- UI-only changes
- Configuration updates

Run tests with: `php artisan test --compact --filter=testName`

## Commit Strategy

**Conventional Commits**

Format: `type(domain): description`

| Type | Usage |
|------|-------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `refactor` | Code restructuring without behavior change |
| `test` | Adding/updating tests |
| `chore` | Maintenance, config, dependencies |

Common domains: `lotto`, `wallet`, `payment`, `frontend-api`, `admin`, `dashboard`

Examples:
- `fix(lotto): remove checkbox, show static block label`
- `feat(wallet): add cashback refill transaction log`
- `docs: rename underscore filenames to dash naming`

## Code Review Policy

- Required for all non-trivial changes
- PR review workflow via GitHub
- Self-audit before requesting review: verify tests pass, Pint formatting, no dead config

## Verification Checkpoints

**After each phase completion**

- Phase A (Core storage): Migration + Models review
- Phase B (Core logic): Services + Commands review
- Phase C (API integration): FrontendApi controllers + routes review
- Phase D (External): Fill + Reconcile commands review
- Phase E (Quality): Tests + Docs review

Use `/conductor:implement` for phased execution with checkpoint pauses.

## Task Lifecycle

```
pending → in_progress → completed
                           ↓ (if blocked)
                        blocked → in_progress
```

Tasks created via `/conductor:new-track` with spec → plan → implement workflow.
