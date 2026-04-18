# Frontend API V1 - Edge Cases

อัปเดตล่าสุด: 2026-04-18

## Compatibility Notes

- realtime event migration: prefer `member.activity.updated`, keep legacy fallback ชั่วคราว
- lotto latest draw selection ต้องข้าม `draft` ตาม policy
- wallet transaction history ต้องแสดงข้อมูลรวมจาก source เดียวกันอย่างสม่ำเสมอ

## Contract Safety

- เปลี่ยน route/controller behavior ต้องอัปเดต `03-endpoints.md` ทันที
- เปลี่ยน response field ต้องอัปเดต `01-overview.md` หรือ note เฉพาะ flow
