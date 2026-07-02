# Startup Digest

> **Optional digest. Not a startup source of truth.**
> Use only after `docs/START-HERE.md` routes you here, or when you need the extended context below.
> Startup path authority: `docs/START-HERE.md`

อัปเดตล่าสุด: 2026-05-03

---

## Memory Layer (fast path, optional)

ถ้าต้องการ context เร็วก่อนเปิด docs:

- `boat_ask(question="system overview + latest context")`
- `memory/auth.md`, `memory/payment.md`, `memory/wallet.md`, `memory/game.md` (เลือกเฉพาะ domain ที่เกี่ยวข้อง)

ถ้าต้องตรวจสถานะ retrieval/memory/index ให้เปิด:
- `docs/internal/01_SYSTEM/retrieval-system-status.md`

ถ้า memory ไม่พอ ค่อยเปิด docs ตามลำดับด้านล่าง

## Domain On-Demand

- `FrontendApi` → `docs/internal/03_DOMAINS/frontend-api.md` + `docs/internal/03_DOMAINS/frontend-api-discovery.md`
- `Wallet` → `docs/internal/03_DOMAINS/wallet.md` + `docs/internal/03_DOMAINS/wallet-discovery.md`
- `Lotto` → `docs/internal/03_DOMAINS/lotto.md` + `docs/internal/03_DOMAINS/lotto-discovery.md`
- `Admin Lotto` → `docs/internal/03_DOMAINS/admin-lotto.md`
- `Realtime` → `docs/internal/03_DOMAINS/realtime.md`

## Escalation (เฉพาะจำเป็น)

เปิด `system-current-state/index.md` หรือ `decision-log/index.md` เมื่อ:

- งานเปลี่ยน behavior จริง
- งานแตะ flow เสี่ยง: financial / auth / retry / queue / cron / schema
- domain note ไม่พอ หรือ code อาจไม่ตรง doc

## Targeted Lookup Playbook

1. แปลง task เป็น keyword ที่ค้นหาได้จริง (route name, class, method, event, table)
2. รัน `rg` แบบเจาะ path ที่เกี่ยวข้องก่อน (เช่นเฉพาะ package/domain)
3. อ่านเฉพาะ block โค้ดที่เกี่ยวข้องด้วย `sed -n start,end`
4. เก็บหลักฐานเป็น path + function ที่แตะ behavior จริง
5. หยุดอ่านทันทีเมื่อได้ context เพียงพอ ไม่เปิดไฟล์เพิ่มโดยไม่จำเป็น

## สิ่งที่ต้องจำตลอด

- `/docs` คือ source of truth
- ห้ามใช้ chat history เป็นหลัก
- ถ้า code ไม่ตรง doc ให้ report ก่อน
- งานที่เปลี่ยน behavior ต้องอัปเดต doc/memory/index ให้ครบ
