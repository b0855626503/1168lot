# System Current State

อัปเดตล่าสุด: 2026-03-27

## ภาพรวมระบบ

- Framework: Laravel 8
- Architecture: Modular (Konekt Concord)
- หลัก domain สำคัญ: Admin, Wallet, Payment, API, Lotto
- Lotto ใช้สถานะงวดหลัก: `draft -> open -> closed -> resulted`

## นโยบายแก้ไขงวดหวย (Admin)

- สถานะ `draft`: แก้ไขฟิลด์หลักของงวดได้
- สถานะ `open`: อนุญาตแก้ `draw_date` และ `close_at` (รวม metadata ที่ระบบ allowlist ไว้)
- สถานะ `resulted`: ไม่อนุญาตให้แก้ไข
- ฟิลด์เวลาในฟอร์ม admin รองรับเคสข้ามวัน:
  - ถ้า `close_at` < `open_at` จะตีความ `close_at` เป็นวันถัดไป
  - ถ้า `result_at` < `close_at` จะตีความ `result_at` เป็นวันถัดไป
  - ถ้าเวลาที่กรอกน้อยกว่าค่าอ้างอิง ระบบจะ normalize ไปวันถัดไปจนได้ลำดับเวลาที่ถูกต้อง
- เมนู `รายการหวย` รองรับเวลาอัตโนมัติแบบข้ามวันเช่นกัน (`auto_open_time > auto_close_time`)

## นโยบายสิทธิ์ปุ่มงวดหวย (Admin UI)

- ปุ่มในตาราง `draws` ถูกเช็กสิทธิ์รายปุ่มด้วย `bouncer()->hasPermission(...)`
- ผู้ใช้ `superadmin` ผ่านการตรวจสิทธิ์ทั้งหมดตามกลไก bouncer เดิม
- เคส `resulted` ยังคงแสดงปุ่ม `Logs` และ `Dry-run` ได้เมื่อมีสิทธิ์ที่เกี่ยวข้อง

## นโยบายการเรียงข้อมูลแหล่งผลอัตโนมัติ (Admin UI)

- ตาราง `/lotto/auto-result-sources` รองรับ interactive sorting จากการกดหัวคอลัมน์
- ระบบใช้ default initial sort เป็น `priority ASC` แล้วตามด้วย `id DESC`
- ห้าม lock ลำดับด้วย `orderBy(...)` ตายตัวใน query หลัก เพราะจะทำให้ผู้ใช้ sort คอลัมน์อื่นไม่ได้จริง

## นโยบาย Auto Result Manual Action (Admin UI)

- ปุ่ม `Retry` และ `Dry-run` ในหน้า `draws` เรียก `lotto:fetch-auto-results` แบบ synchronous (`Artisan::call`)
- เป้าหมายคือไม่ผูกกับ worker queue ใน production เพื่อให้คำสั่งถูกประมวลผลทันทีและเห็นผล/log ได้จริง
- ฝั่ง UI ของปุ่มดังกล่าวต้องแสดงข้อความ error จาก backend เมื่อคำสั่งล้มเหลว (ห้าม silent failure)

## โครงสร้างเอกสาร

- Internal docs: `docs/internal/*`
- Public docs: `docs/public/*`
- Archive: `docs/internal/05_ARCHIVE/*`

## เอกสารระบบหลักที่ใช้งานจริง

- กฎ agent: `docs/internal/00_RULES/agent_rules.md`
- บันทึกการตัดสินใจ: `docs/internal/02_DECISIONS/decision_log.md`
- แผนงาน: `docs/04_PLANS/`
- Domain docs: `docs/internal/03_DOMAINS/`

## เอกสาร system เสริม

- `docs/internal/01_SYSTEM/dev-workflow-phpstorm-wsl.md`
- `docs/internal/01_SYSTEM/lotto-system-handover-th.md`
- `docs/internal/01_SYSTEM/laravel-echo-nextjs-install.md`
