# Decision Log - Edge Cases

อัปเดตล่าสุด: 2026-04-19

## Edge-Case Decision Themes

- compatibility fallback ระหว่าง event เก่า/ใหม่
- auto-result retry exhaustion และ source fallback
- schema rollout safety และ runtime guardrails
- lotto navbar fallback decisions:
  - locale fallback: requested -> `th` -> `en` -> `key`
  - default code (`mobile_bottom_nav`) ถูก unpublish/ไม่พบ ต้องตอบ `404` แบบคงที่

## Escalation

ถ้า task แตะหัวข้อข้างบน ให้เปิด archive decision เฉพาะช่วงวันที่ที่เกี่ยวข้อง
