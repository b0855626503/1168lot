# Decision Log - Flows

อัปเดตล่าสุด: 2026-04-18

## Flow Decisions ที่ต้องเช็คบ่อย

- wallet ledger append-only semantics
- customer realtime channel separation
- lotto draw/settlement/auto-result constraints
- dashboard summary queue dedup strategy

## Lookup Pattern

- ค้นวันที่หรือ keyword ใน archive ก่อน แล้วคัดเฉพาะ decision ที่เกี่ยวข้องมาใช้งาน
