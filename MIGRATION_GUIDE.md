# Migration Guide

This package is designed to be dropped into the repository and adopted immediately.

---

## Target structure

```text
/
├── AGENTS.md
├── CLAUDE.md
├── README.md
├── docs/
│   ├── START_HERE.md
│   ├── README.md
│   ├── 04_PLANS/README.md
│   └── internal/
│       ├── 00_RULES/
│       │   ├── agent_rules.md
│       │   ├── accelerator_policy.md
│       │   └── subagent_patterns.md
│       ├── 01_SYSTEM/startup_digest.md
│       ├── 02_DECISIONS/
│       │   ├── adr_baseline.md
│       │   └── adr_index_by_domain.md
│       └── 04_EXECUTION/
│           ├── task_tiering.md
│           ├── token_optimization_playbook.md
│           ├── investigation_checklists.md
│           └── change_execution_contract.md
└── workspace/
    ├── README.md
    ├── SOUL.md
    ├── USER.md
    ├── MEMORY.md
    └── TOOLS.md
```

---

## File move map

Move these if they currently live at repository root:

- `SOUL.md` -> `workspace/SOUL.md`
- `USER.md` -> `workspace/USER.md`
- `MEMORY.md` -> `workspace/MEMORY.md`
- `TOOLS.md` -> `workspace/TOOLS.md`

Keep repository/system docs in `docs/`.

---

## Mandatory cleanup after moving

### Remove or rewrite duplicate content
After adopting this package, do not keep older duplicated startup instructions in multiple places.

Specifically:
- `README.md` stays thin
- `AGENTS.md` stays a router
- `CLAUDE.md` stays thin
- startup truth lives in `docs/START_HERE.md`
- operating rules live in `docs/internal/00_RULES/agent_rules.md`

---

## Conflict resolution policy

If older files say different things:
1. current implementation facts
2. `docs/internal/01_SYSTEM/system_current_state.md`
3. ADR / decision docs
4. active plan docs
5. accelerator output
6. workspace memory files last

Workspace memory must never override repository source of truth.
