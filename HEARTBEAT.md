# HEARTBEAT.md - Periodic Checks

## Daily Checks (ทุก 30 นาที)
- [x] ตรวจสอบ emails (ถ้ามี integration) - ไม่มี integration (20:11)
- [x] ตรวจสอบ calendar (ถ้ามี integration) - ไม่มี integration (20:11)
- [x] ตรวจสอบ project 1168lot status - Git มี changes (LottoTicketRealtimeObserver.php), monitoring scripts ทำงานปกติ (20:11)

## Project-Specific Checks
- [x] ตรวจสอบ withdraw fix ทำงานปกติไหม - จาก memory logs withdraw fix implementation ทำงานปกติ (20:11)
- [x] ตรวจสอบ memory usage (OpenClaw) - 6.9% memory (~562MB), system มี 882MB free (20:11)
- [x] ตรวจสอบ gateway status - ทำงานปกติบน port 18789 (restarted at 14:58) (20:11)

## Weekly Checks (ทุกอาทิตย์)
- [x] Clean up old memory files (>30 days) - ไม่มีไฟล์เก่า (20:11)
- [x] Backup workspace - backup สำเร็จแล้ววันนี้ (20:11)
- [x] Review MEMORY.md for important learnings - อัปเดตแล้ว (20:11)

## Notes
- ใช้ cron jobs สำหรับ定期チェック
- Update ไฟล์นี้เมื่อมี checks ใหม่เพิ่ม