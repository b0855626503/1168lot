# Decision Log

## 2026-03-27 — Document Standardization (LOCKED)

- รวมเอกสารเข้าโครงสร้างมาตรฐานเดียวภายใต้ `docs/`
- แยก internal/public ชัดเจน
- ย้ายเอกสารกระจัดกระจายจาก root/.github/packages/public/vendor เข้า `docs/internal`
- ตั้ง source-of-truth หลัก:
  - `docs/internal/00_RULES/agent_rules.md`
  - `docs/internal/01_SYSTEM/system_current_state.md`
  - `docs/internal/02_DECISIONS/decision_log.md`
- เอกสารซ้ำและเวอร์ชันเก่าถูกย้ายไป `docs/internal/05_ARCHIVE/`

## 2026-03-27 — Lotto Draw Lifecycle Hardening (LOCKED)

- ล็อก `open/close` ให้รับ `source` แบบ explicit (`scheduled|manual`)
- ล็อก settle idempotency แบบ reject เมื่อ `status=resulted`
- ล็อก `result_at` ให้ใช้ server time ใน service เท่านั้น
- เพิ่มฟิลด์ audit transition ของ draw (`opened_at`, `closed_at`, `open_mode`, `close_mode`)
