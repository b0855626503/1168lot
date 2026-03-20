# รายงานตรวจสอบไฟล์ตามเอกสาร Gametech Lotto System (v1)

เอกสารนี้จัดทำจากข้อมูลที่ผู้ใช้ส่งมาในบทสนทนา และจากสถานะเครื่องมือใน session นี้

## ขอบเขตการตรวจสอบ

- ตรวจเทียบรายการที่ต้องมีตามเอกสาร Lotto v1
- อ้างอิงไฟล์ที่ระบุ:
  - `structure.txt`
  - `routes.txt`
  - `schema.sql`
- อ้างอิงโครงสร้างเป้าหมาย package:
  - `packages/Gametech/Lotto`

## สถานะการเข้าถึงไฟล์ในรอบนี้

- เครื่องมืออ่านไฟล์ (`view`, `glob`) ไม่สามารถเข้าถึงพาธ UNC/WSL ในสภาพแวดล้อมนี้ได้
- เครื่องมือ shell (`powershell`) ใช้งานไม่ได้ เนื่องจากไม่มี `pwsh.exe`
- จึง **ไม่สามารถยืนยันเชิงเนื้อหาไฟล์จริง** ได้ในรอบนี้

## สิ่งที่พอระบุได้จากข้อมูล context ของ session

- มีการแสดงชื่อไฟล์ใน root โครงการว่า:
  - `structure.txt`
  - `routes.txt`
  - `schema.sql`
- มีสัญญาณว่ามีพาธเกี่ยวกับ Lotto แสดงใน context:
  - `packages\Gametech\Lotto\src\`

> หมายเหตุ: ข้อมูลข้างต้นเป็นการอ้างอิงจาก session context เท่านั้น ยังไม่ใช่การยืนยันจากการอ่านไฟล์จริง

## เช็กลิสต์ไฟล์/โครงสร้างที่ควรมีตามเอกสาร Lotto v1

### 1) โครงสร้าง package

- `packages/Gametech/Lotto/src/Models`
- `packages/Gametech/Lotto/src/Http/Controllers`
- `packages/Gametech/Lotto/src/Http/Requests`
- `packages/Gametech/Lotto/src/Services/BetService.php`
- `packages/Gametech/Lotto/src/Services/ExposureService.php`
- `packages/Gametech/Lotto/src/Services/DrawService.php`
- `packages/Gametech/Lotto/src/Repositories`
- `packages/Gametech/Lotto/src/DataTables`
- `packages/Gametech/Lotto/src/Providers`
- `packages/Gametech/Lotto/src/Database/Migrations`
- `packages/Gametech/Lotto/src/Database/Seeders`
- `packages/Gametech/Lotto/src/Routes`
- `packages/Gametech/Lotto/src/Resources`
- `packages/Gametech/Lotto/src/Config`

### 2) Route แยกใน package

- `packages/Gametech/Lotto/src/Routes/admin.php`
- `packages/Gametech/Lotto/src/Routes/api.php`

### 3) ตารางหลักที่ต้องมี (migration/schema)

- `lotto_groups`
- `lotto_markets`
- `lotto_market_bet_settings`
- `lotto_rate_plans`
- `lotto_rate_plan_items`
- `lotto_draws`
- `lotto_draw_bet_settings`
- `lotto_number_exposures` (ควรมี unique: `draw_id, bet_type, number`)
- `lotto_number_blocks`
- `lotto_tickets`
- `lotto_ticket_items`
- `member_lotto_permissions`

## Gap Analysis (เชิงเอกสาร/สถาปัตยกรรม)

### มีแล้ว (จากเอกสารที่ให้มา)

- นิยามโดเมนหลักครบ (Group, Market, Draw, Exposure, Ticket)
- กำหนด Bet Type แบบ fixed enum ชัดเจน
- กติกา transaction/lock ของ exposure ชัดเจน
- ลำดับ validation ตอนแทงครบ
- Phase plan พัฒนาเป็นขั้นชัดเจน

### ยังต้องยืนยันจากไฟล์จริง

- การมีอยู่ของโครงสร้าง package ทั้งหมดตามหัวข้อ 1)
- การมีอยู่ของ route admin/api ใน package Lotto
- การมีอยู่ของ migration/schema ของตาราง lotto ทั้งหมด
- การบังคับใช้ rule สำคัญในโค้ดจริง (snapshot draw, atomic exposure, fixed bet type)

### ข้อเสนอแนะการยืนยันรอบถัดไป

- ให้รันการตรวจบน environment ที่อ่านพาธโครงการได้จริง แล้วตรวจตามคำสั่ง:
  - ค้นหาไฟล์โครงสร้าง Lotto
  - ตรวจ route ใน `src/Routes`
  - ตรวจ migration/schema ตาราง `lotto_*`
  - ตรวจ service: `BetService`, `ExposureService`, `DrawService`
- ยืนยัน 3 จุดก่อน dev ตามเอกสาร:
  - logic วิ่งบน/ล่าง
  - format เลข (string/int)
  - result format รายตลาดหวย

## สรุป

เอกสาร requirement ของ Lotto v1 มีความครบถ้วนและเป็นฐานออกแบบที่ดี แต่ในรอบนี้ยังไม่สามารถยืนยันสถานะไฟล์จริงได้จากข้อจำกัดการเข้าถึงพาธในเครื่องมือ จึงจัดทำเอกสารตรวจสอบเชิงรายการเพื่อใช้เป็น checklist สำหรับ verify ในรอบถัดไป
