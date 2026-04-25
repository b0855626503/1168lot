# Decision Log - Flows

อัปเดตล่าสุด: 2026-04-25

## Flow Decisions ที่ต้องเช็คบ่อย

- wallet ledger append-only semantics
- 2026-04-25: customer Frontend API ต้องยอมรับ active token ล่าสุดต่อ member เพียงตัวเดียว เพื่อกันใช้งานหลายเครื่องพร้อมกัน
- customer realtime channel separation
- lotto draw/settlement/auto-result constraints
- dashboard summary queue dedup strategy

## Lookup Pattern

- ค้นวันที่หรือ keyword ใน archive ก่อน แล้วคัดเฉพาะ decision ที่เกี่ยวข้องมาใช้งาน
