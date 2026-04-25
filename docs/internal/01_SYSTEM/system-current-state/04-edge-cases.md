# System Current State - Edge Cases

อัปเดตล่าสุด: 2026-04-24

## High-Risk Areas

- backward compatibility ของ realtime events (`member.activity.updated` vs legacy)
- schema compatibility ระหว่าง migration rollout และ runtime read paths
- lotto auto-result fallback/retry chain และ failure telemetry
- lotto market draw schedule dual-read compatibility (`draw_schedule_*` + legacy `draw_mode`)
- dashboard cache/queue dedup race conditions

## Required Escalation

เมื่อแตะหัวข้อด้านบน ต้องตรวจเพิ่ม:
- `docs/internal/02_DECISIONS/decision-log/index.md`
- domain note ที่เกี่ยวข้อง
- migration/model diff ใน commit เดียวกัน

## Lotto Draw Schedule Runtime Boundary

- release นี้ยังไม่ลบ `lotto_markets.draw_mode`
- runtime generate auto draws:
  - ใช้ `draw_schedule_type/draw_days/draw_dates` เป็นหลัก
  - schedule ใหม่หาย/null เท่านั้นที่ fallback ไป `draw_mode`
  - schedule ใหม่มีค่าแต่ invalid ให้ skip (ไม่ fallback)
- monthly date ที่ไม่อยู่ในปฏิทินเดือนนั้นเป็น skip path ปกติ ไม่ถือว่า error
