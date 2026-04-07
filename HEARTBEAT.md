# HEARTBEAT.md - Periodic Checks

## Daily Checks (ทุก 30 นาที)
- [x] ตรวจสอบ emails (ถ้ามี integration) - ไม่มี integration (07:40)
- [x] ตรวจสอบ calendar (ถ้ามี integration) - ไม่มี integration (07:40)
- [x] ตรวจสอบ project 1168lot status - Git มี changes, monitoring scripts ทำงานปกติ, API testing สำเร็จ (07:40)

## Project-Specific Checks
- [x] ตรวจสอบ withdraw fix ทำงานปกติไหม - จาก memory logs withdraw fix implementation ทำงานปกติ (07:40)
- [x] ตรวจสอบ memory usage (OpenClaw) - 9.2% memory (~751MB), system มี 5.8GB free (07:40)
- [x] ตรวจสอบ gateway status - ทำงานปกติบน port 18789 (07:40)

## Weekly Checks (ทุกอาทิตย์)
- [x] Clean up old memory files (>30 days) - ไม่มีไฟล์เก่า (07:40)
- [x] Backup workspace - backup สำเร็จแล้ววันนี้ (07:40)
- [x] Review MEMORY.md for important learnings - อัปเดตแล้ว (07:40)

## Notes
- ใช้ cron jobs สำหรับ定期チェック
- Update ไฟล์นี้เมื่อมี checks ใหม่เพิ่ม