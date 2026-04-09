# 1168lot

Laravel + Vue SPA + Lotto + Wallet + Payment + Admin

---

## Entry Points

### Code agent / AI coding assistant
Start at:

- `docs/START_HERE.md`

### Developer reading repository behavior
Start at:

- `docs/internal/01_SYSTEM/system_current_state.md`

### Workspace assistant / memory / non-code assistant work
Start at:

- `workspace/README.md`

---

## Source of Truth Layers

### Repository / product / architecture / behavior
- `docs/`

### Assistant / memory / user preference / local environment
- `workspace/`

Do not mix them.

---

## Hard Rules

- Never change system behavior without updating docs.
- If code and docs mismatch, report mismatch before changing behavior.
- Internal docs stay in `docs/internal`.
- Public docs stay in `docs/public`.

---

## Why this repository is structured this way

This repository supports two valid operating modes:

1. **Code agent / coding assistant**
   - optimized for low-token startup
   - optimized for architecture-safe changes
   - avoids loading unrelated assistant memory

2. **Workspace / general AI assistant**
   - may need memory, user preferences, workflow context
   - must not override repository truth

The structure is designed to keep both modes strong without polluting each other.
