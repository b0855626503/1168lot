# System Current State - Edge Cases

อัปเดตล่าสุด: 2026-04-18

## High-Risk Areas

- backward compatibility ของ realtime events (`member.activity.updated` vs legacy)
- schema compatibility ระหว่าง migration rollout และ runtime read paths
- lotto auto-result fallback/retry chain และ failure telemetry
- dashboard cache/queue dedup race conditions

## Required Escalation

เมื่อแตะหัวข้อด้านบน ต้องตรวจเพิ่ม:
- `docs/internal/02_DECISIONS/decision-log/index.md`
- domain note ที่เกี่ยวข้อง
- migration/model diff ใน commit เดียวกัน
