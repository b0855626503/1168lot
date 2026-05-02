# BOA Marketing Phase 5 Rollout Checklist

วันที่อัปเดต: 2026-05-02

## Pre-deploy

- [ ] รัน migration บนทุก environment เป้าหมาย
- [ ] ตรวจ `php artisan migrate:status` ให้ไม่มี migration ตกค้าง
- [ ] รัน `php artisan config:clear`
- [ ] รัน `php artisan config:cache`
- [ ] ตรวจ env บน clone site:
  - [ ] `LOTTERY_RESULT_CLONE_AUTO_PULL_ENABLED`
  - [ ] `LOTTERY_RESULT_CLONE_AUTO_PULL_GROUP_IDS`
  - [ ] `LOTTERY_RESULT_CLONE_AUTO_PULL_DELAY_MINUTES`
- [ ] ยืนยัน scheduler ทำงาน (`php artisan schedule:list` + process supervisor/systemd)
- [ ] ยืนยัน queue worker ทำงาน (กรณี auto-result flow ใช้ queue)

## Functional verification

- [ ] ตรวจ endpoint dashboard campaign:
  - [ ] `GET /marketing_campaign/{campaign}/dashboard/summary`
  - [ ] route name `admin.marketing_campaign.dashboard.summary`
- [ ] ตรวจ frontend ส่ง `click_id` และ `visitor_id` จาก Next.js ตาม flow จริง
- [ ] ตรวจ conversion หลังสมัครสำเร็จ:
  - [ ] `POST /api/v1/auth/register`
  - [ ] `POST /api/v1/auth/register-with-username`
- [ ] ตรวจ clone mode auto pull:
  - [ ] ทำงานเฉพาะ Thai group IDs ที่ config
  - [ ] ไม่แตะกลุ่มที่ไม่ได้ config
- [ ] ตรวจ primary mode ไม่ได้รับผลกระทบ (คำสั่ง clone auto pull ต้อง skip)

## Post-deploy monitoring

- [ ] ตรวจ log `marketing/clicks`, `confirm`, `submitted` ไม่มี 500 ใหม่
- [ ] ตรวจ dashboard campaign ไม่แสดง fallback bonus เป็น 0 ตลอดเมื่อมีข้อมูลจริง
- [ ] ตรวจ alert/error rate ของ auto pull command หลังเปิดใช้งาน

