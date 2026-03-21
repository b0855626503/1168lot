# สรุป Audit Query และ Index (21 มี.ค. 2026)

## แนวทางการปรับปรุง Index (สรุปคร่าว)

- ตรวจสอบ index เดิมในแต่ละ table
- Drop index ที่ซ้ำซ้อนหรือไม่เหมาะสม
- เพิ่ม composite index ใหม่ที่เหมาะกับ pattern query ของแต่ละ route
- สร้าง migration แยกไฟล์สำหรับแต่ละ table

---

### 1. bank_payment
- ลบ index เดิมที่ไม่จำเป็น (account_code, tx_hash, deposit_status)
- เพิ่ม composite index: (enable, status, date_create, value)

### 2. withdraws_seamless
- เพิ่ม composite index: (enable, status, code, date_create)

### 3. payments_waiting
- เพิ่ม composite index: (enable, confirm, date_create)

### 4. members
- เพิ่ม composite index: (enable, confirm, code, date_create)

### 5. banks_account
- เพิ่ม composite index: (bank_type, enable)

### 6. promotions
- เพิ่ม composite index: (code, enable, active)

### 7. check_case
- เพิ่ม composite index: (date_create, bank_code, status)

---

## หมายเหตุ
- การ drop/replace index เดิมจะช่วยลดภาระ index ซ้ำซ้อน ทำให้ query เร็วขึ้นและระบบไม่ช้า
- สามารถ rollback index เดิมได้ใน migration (down)
- หากพบ query pattern ใหม่ในอนาคต ควรปรับ index เพิ่มเติม

---

## รายชื่อไฟล์ migration ที่สร้าง
- 2026_03_21_000001_update_index_on_bank_payment_table.php
- 2026_03_21_000002_update_index_on_withdraws_seamless_table.php
- 2026_03_21_000003_update_index_on_payments_waiting_table.php
- 2026_03_21_000004_update_index_on_members_table.php
- 2026_03_21_000005_update_index_on_banks_account_table.php
- 2026_03_21_000006_update_index_on_promotions_table.php
- 2026_03_21_000007_update_index_on_check_case_table.php

---

**สรุป:** การปรับ index ตามนี้จะช่วยลด query ซ้ำซ้อนและเพิ่มประสิทธิภาพการค้นหาในแต่ละ route admin ได้อย่างมีนัยสำคัญ

---

## เครื่องมือ Audit Runtime (เพิ่มใหม่)

เพิ่มคำสั่งสำหรับเก็บ query รายเมนู/route, ตรวจ query ซ้ำ, และตรวจ EXPLAIN + index จริงจากฐานข้อมูล:

```bash
php artisan admin:query-audit \
  --top-routes=12 \
  --top-queries=3 \
  --min-dup=1 \
  --only-admin=1 \
  --output=docs/ADMIN_QUERY_AUDIT_2026_RUNTIME.md
```

ผลลัพธ์จะถูกสร้างที่:
- `docs/ADMIN_QUERY_AUDIT_2026_RUNTIME.md`

โดยรายงานจะมี:
- Top routes ที่ใช้ SQL สูง/ซ้ำสูง
- รายการ query ซ้ำราย route พร้อม repeat count และเวลารวม
- `EXPLAIN FORMAT=JSON` ของ query ตัวอย่าง
- index จริงของตารางที่ query นั้นใช้งาน (อ่านจาก `information_schema.statistics`)

---

## การปรับ Query รอบล่าสุด (Phase 2/3)

### 1) Dashboard `admin.home.loadcnt`
- ปรับจากหลาย query แยก (`bank_in_today`, `bank_in`, `bank_out`) เป็น 1 SQL ที่ประกอบด้วย subquery นับผลลัพธ์
- เปลี่ยนเงื่อนไขวันที่จาก `whereDate` เป็นช่วง datetime เพื่อรองรับ index ดีขึ้น
- จุดแก้: `packages/Gametech/Admin/src/Http/Controllers/DashboardController.php`

ผลตรวจ `EXPLAIN FORMAT=JSON` (บน MySQL จริง):
- subquery `bank_in_today` ใช้ key `idx_enable_status_date_value` (range, using_index)
- subquery `bank_in` ใช้ key `idx_enable_status_date_value` (range, using_index)
- subquery `bank_out` ใช้ key `idx_bp_date_create` (range)

### 2) Withdraw DataTables (ลด query ซ้ำเงื่อนไขเดิม)
- ลดเงื่อนไข `status=0` ซ้ำใน `in_wait` เพราะฐาน query ถูกจำกัดสถานะ pending อยู่แล้ว
- จุดแก้:
  - `packages/Gametech/Admin/src/DataTables/WithdrawDataTable.php`
  - `packages/Gametech/Admin/src/DataTables/WithdrawSeamlessDataTable.php`
  - `packages/Gametech/Admin/src/DataTables/WithdrawSeamlessFreeDataTable.php`
