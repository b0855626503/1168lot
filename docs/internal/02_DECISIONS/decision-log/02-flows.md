# Decision Log - Flows

อัปเดตล่าสุด: 2026-04-25

## Flow Decisions ที่ต้องเช็คบ่อย

- wallet ledger append-only semantics
- customer realtime channel separation
- lotto draw/settlement/auto-result constraints
- dashboard summary queue dedup strategy
- 2026-04-25: Lotto shared member feed `.public.activity.updated` ต้องแนบ `message` พร้อมแสดงผลกับ activity ที่ลูกค้าเห็น

## Lookup Pattern

- ค้นวันที่หรือ keyword ใน archive ก่อน แล้วคัดเฉพาะ decision ที่เกี่ยวข้องมาใช้งาน
