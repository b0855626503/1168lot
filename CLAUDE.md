# CLAUDE.md

This repository is configured so Claude Code can start with minimal token cost.

## Start

Read:

- `docs/START_HERE.md`

Do not load workspace memory/persona files for normal coding tasks.

---

## Source of Truth

- Repository/system truth: `docs/`
- Workspace memory/persona: `workspace/`

---

## Hard Rules

- Never change logic without updating docs.
- If code and docs mismatch, report it before changing behavior.
- Use the startup path from `docs/START_HERE.md`.
- Open large files only when task scope or risk justifies it.

---

## Minimal command reference

```bash
composer install && npm install
composer run dev
composer run test
npm run dev
php artisan migrate
./vendor/bin/phpunit
./vendor/bin/pint
bash scripts/docs-validation/run.sh
```

---

## Architecture reminders

- `FrontendApi` is the customer-facing BFF and must not call other packages' controllers directly.
- `wallet_transactions` is the financial audit source of truth.
- Lotto lifecycle is a fixed state machine.
- Admin/customer realtime channels must remain separated.

For actual decision details, use the docs pointed to by `docs/START_HERE.md`.
