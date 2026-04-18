# Domain Entrypoints Index

อัปเดตล่าสุด: 2026-04-18

เป้าหมาย: ให้ agent เลือกอ่านเฉพาะ domain ที่ตรงกับ task และเข้าจุดโค้ดจริงได้เร็ว

## Domains

- `frontend_api` -> `frontend_api.md`
- `wallet` -> `wallet.md`
- `payment` -> `payment.md`
- `auth` -> `auth.md`
- `lotto` -> `lotto.md`
- `admin_lotto` -> `admin_lotto.md`
- `realtime` -> `realtime.md`

## Fallback Rule

- ถ้า map domain ไม่ได้ ให้เริ่มจาก `docs/internal/01_SYSTEM/startup_digest.md`
- ถ้ายังไม่พอค่อย escalate ไป `system-current-state/index.md` และ `decision-log/index.md`
