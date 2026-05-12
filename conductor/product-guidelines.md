# Product Guidelines — 1168lot

## Voice and Tone

**Bilingual: Thai docs + English code**

- Documentation (docs/, memory/, .codebase-memory/): Thai with English for technical terms (class names, field names, method/function names, keywords)
- Code (PHP, JS, Blade, config): English — class names, variables, comments, commit messages
- Commit messages: Conventional Commits in English (e.g., `fix(lotto): ...`, `feat(wallet): ...`)

## Design Principles

1. **Code is truth — ground everything**
   - Never guess system behavior; always read code first
   - Use targeted lookup (`rg`, `system_map.md`, domain notes) before opening large files
   - If code and docs mismatch, report before changing behavior
   - Source of truth hierarchy: code → docs → memory → chat history

2. **Financial safety first**
   - `wallet_transactions` is the financial audit source of truth — append-only ledger
   - Never change member balance without a transaction context
   - High-risk domains (wallet, payment, settlement, auto-result) require escalation to full documentation
   - Every financial code path must be idempotent or have guardrails

3. **Minimal change surface**
   - Don't add features, refactors, or abstractions beyond the task
   - Three similar lines > premature abstraction
   - No half-finished implementations, no feature flags for unreleased work
   - Don't add error handling for scenarios that can't happen

4. **Extension over modification**
   - New markets, bet types, payment channels plug in without rewriting core
   - Package-based architecture: `packages/Gametech/` — each domain is an isolated package
   - FrontendApi BFF must not call other packages' controllers directly; use domain services/repositories
   - Queue workers, Horizon supervisors horizontally scalable per domain
