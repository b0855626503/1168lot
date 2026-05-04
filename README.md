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

# Payment MCP Generator V3 for 1168lot

V3 = full interactive flow สำหรับสร้าง payment provider ใหม่แบบปลอดภัย:

- อ่านเอกสาร API จาก URL / local file / pasted text
- detect capabilities: deposit, withdraw, callback, balance, customer/account
- ถ้า capability ขาด จะถามยืนยันก่อนเสมอ
- สร้าง plan + manifest + validation report
- generate ไฟล์ provider ตาม pattern `smkpay`
- default เป็น dry-run
- write จริงต้องใช้ `--mode=write_files`

## Install

แตก zip ไปทับ repo แล้วรัน:

```bash
composer dump-autoload
php artisan config:clear
```

ถ้า package auto-discovery ไม่เจอ command ให้ register command เองใน Console Kernel / service provider ของโปรเจกต์

## One-shot usage

```bash
php artisan payment:provider-generate \
  --provider=boat_pay \
  --doc-url="https://example.com/api-doc" \
  --mode=dry_run
```

หรือใช้ไฟล์เอกสาร:

```bash
php artisan payment:provider-generate \
  --provider=boat_pay \
  --doc-file=storage/app/docs/boatpay.md \
  --mode=dry_run
```

ถ้าต้องเขียนไฟล์จริง:

```bash
php artisan payment:provider-generate \
  --provider=boat_pay \
  --doc-file=storage/app/docs/boatpay.md \
  --mode=write_files
```

## Output

- `storage/app/mcp/payment-providers/{provider}/manifest.json`
- `storage/app/mcp/payment-providers/{provider}/plan.json`
- `storage/app/mcp/payment-providers/{provider}/validation.json`
- generated files หรือ dry-run preview

## Important

V3 ตั้งใจให้ agent/Codex ใช้ต่อ:
- ถ้า API doc ไม่มี withdraw → ถามว่าจะ skip/stub/abort
- ถ้า API doc ไม่มี callback → ถามว่าจะ polling/manual/abort
- ถ้า signature ไม่ชัด → หยุดให้เติมข้อมูล
