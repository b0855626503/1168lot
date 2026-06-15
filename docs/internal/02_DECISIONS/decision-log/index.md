# Decision Log (Index)

อัปเดตล่าสุด: 2026-06-15

ใช้เอกสารนี้เป็นจุดเข้า decision แบบ targeted แทนการเปิดไฟล์เดิมทั้งก้อน

## Sections

- [01-overview.md](./01-overview.md): วิธีใช้ decision log และ boundary
- [02-flows.md](./02-flows.md): decisions ที่กระทบ flow สำคัญ
- [03-endpoints.md](./03-endpoints.md): decisions ที่ล็อก API/contract
- [04-edge-cases.md](./04-edge-cases.md): fallback/compatibility/risk decisions

## Legacy Full Dump

- `docs/internal/05_ARCHIVE/monolith/decision-log.2026-04-18.md`

## Packages Without Separate Decision Records

แพ็คเกจต่อไปนี้เป็น utility/support ไม่มี decision record แยกเพราะ scope เล็กและถูกตัดสินใจใน context ของ domain หลัก:

| Package | Covered By | Reason |
|---------|-----------|--------|
| CenterOA | Admin | OA integration ตาม Admin pattern |
| LineOA | Member | Line notification ตาม Member flow |
| LogAdmin | Admin | Audit log ตาม Admin module |
| LogUser | Member | User activity log ตาม Member module |
| Lottobk | Lotto | Legacy backup ตาม Lotto domain |
| Marketing | Member | Marketing campaign ตาม Member domain |
| Reward | Member | Reward ตาม Member/Wallet domain |
| Sms | Member | SMS notification ตาม Member flow |

## Recent Decisions (2026)

- **WealthPay Payment Provider** (2026-06-15): เพิ่ม payment gateway ใหม่ integrate กับ Wealthwave Flex API ใช้ pattern hosted payment URL (คล้าย DeepPay) — ดู `docs/internal/03_DOMAINS/payment.md`
