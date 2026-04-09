# File Policy

This policy explains where each type of knowledge must live.

---

## Repository docs (`docs/`)

Use for:
- architecture
- domain logic
- lifecycle
- validation
- permissions
- route behavior
- queues / cron / retries / pipelines
- rollout policy
- financial semantics
- audit rules
- public API docs
- execution patterns for repository work

---

## Workspace docs (`workspace/`)

Use for:
- assistant tone/personality
- user preferences
- long-term assistant memory
- local environment notes
- device/setup notes
- MCP/Boost machine-specific setup

---

## Entry files

### `README.md`
Human-readable top-level orientation

### `AGENTS.md`
Tiny router for agents

### `CLAUDE.md`
Thin Claude-specific pointer

### `docs/START_HERE.md`
Single startup entry point for repository work

---

## Anti-patterns

Do not:
- duplicate startup guidance across many files
- place architecture rules in workspace memory
- place user preference in repository docs
- keep obsolete docs active without archiving
- let plan docs conflict silently with current-state docs
- turn accelerator output into source-of-truth
