# Admin Query After Runtime (2026-03-21)

## Scope
- วัดผลหลังปรับ query dedup และ seamless table switch
- โฟกัสเมนูหลักที่เคยมี query ซ้ำ: `admin.home.loadcnt`, `admin.withdraw_seamless.index`

## Baseline (จาก log audit ก่อนแก้)
อ้างอิง: `docs/internal/05_ARCHIVE/decisions/admin-query-audit-2026-runtime-admin.md`

- `admin.home.loadcnt`
  - SQL Count: `72`
  - Unique SQL: `8`
  - Duplicate SQL: `64`
- `admin.withdraw_seamless.index`
  - SQL Count: `8`
  - Unique SQL: `5`
  - Duplicate SQL: `3`

## After (runtime profile ในโค้ดปัจจุบัน)
วันที่ทดสอบ: `2026-03-21 11:40-11:44 Asia/Bangkok`

1. `DashboardController::loadCnt()`
- query_count = `5`
- ไม่พบ duplicate SQL ในรอบทดสอบ

2. `WithdrawDataTable` (make true)
- query_count = `3`
- duplicate SQL = `0`

3. `WithdrawSeamlessDataTable` (make true)
- query_count = `3`
- duplicate SQL = `0`

4. `WithdrawSeamlessFreeDataTable` (make true)
- query_count = `3`
- duplicate SQL = `0`

## Index Verification (DB จริง)
ตรวจผ่าน `information_schema.statistics` บน DB `1168lot_wallet`

- `withdraws_free`
  - `idx_wdf_status_enable_create` (`status, enable, date_create`)
  - `idx_wdf_status_enable_approve` (`status, enable, date_approve`)
- `withdraws_seamless_free`
  - `idx_wdsf_status_enable_create` (`status, enable, date_create`)
  - `idx_wdsf_status_enable_approve` (`status, enable, date_approve`)

## Notes
- route-audit จาก slow log ยังสะท้อนทราฟฟิกเดิมบางส่วน จึงใช้ runtime profile เสริมเพื่อยืนยันผลหลังแก้โค้ด
- จุดที่เปลี่ยนยังคงชื่อ flow เดิม (`WithdrawSeamless*`) และเปลี่ยนเฉพาะ source table ตาม `config->seamless`
