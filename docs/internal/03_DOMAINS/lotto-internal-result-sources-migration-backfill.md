# Lotto Internal Result Sources — Source Config Migration/Backfill (PR-13)

อัปเดตล่าสุด: 2026-03-30  
สถานะ: EXECUTION READY

## เป้าหมาย

ย้าย `lotto_result_sources.endpoint_url` จาก upstream เดิม -> internal endpoints ใหม่แบบตรวจสอบได้

## Command

- dry-run/report:
  - `php artisan lotto:migrate-internal-result-endpoints --report-only`
- apply:
  - `php artisan lotto:migrate-internal-result-endpoints --apply`
- apply เฉพาะบาง source:
  - `php artisan lotto:migrate-internal-result-endpoints --apply --source-id=12 --source-id=15`

report จะถูกเขียนที่:

- `storage/app/lotto/internal_result_migration/migration_report_*.json`

## Mapping Rules

| old endpoint pattern | new endpoint |
|---|---|
| `https://exphuay.com/backward/{type}/__data.json` | `{APP_URL}/internal/lottery/results/exphuay/{type}` |
| `https://api.dowjones-midnight.com/result` | `{APP_URL}/internal/lottery/results/dowjones-midnight` |
| `https://api.dowjonesextra.com/result` | `{APP_URL}/internal/lottery/results/dowjones-extra` |

additional policy:

- merge `request_query_template_json` ให้มี `date={{lookup_date}}` ถ้ายังไม่มี
- exphuay แนะนำ `page=1` ถ้ายังไม่ถูกกำหนด
- ถ้ามี `fetch_config_json` ให้ sync:
  - `endpoint_url`
  - `request.url`
  - `request.query` และ `query`
- ถ้าตั้ง shared key (`LOTTO_INTERNAL_RESULT_SHARED_KEY`) ให้ inject header ไปทั้ง:
  - `fetch_config_json.request.headers`
  - `fetch_config_json.headers`

## Verification Checklist (post-migration)

1. มีรายชื่อ source ที่ migrate ครบใน report (`migrated[]`)
2. random sample อย่างน้อย 1 source ต่อ pattern:
   - test ผ่าน endpoint ใหม่ได้ (`success=true`)
3. scheduler path ไม่แตก:
   - `lotto:fetch-auto-results` ยังวิ่งได้
4. ไม่มี source critical ที่ endpoint หาย (`UNSUPPORTED_OR_ALREADY_INTERNAL` ต้อง review)

## Current Local Evidence (2026-03-30)

- รันแล้ว:
  - `php artisan lotto:migrate-internal-result-endpoints --report-only`
- report:
  - `storage/app/lotto/internal_result_migration/migration_report_20260330_173011.json`
- ผล:
  - scanned = 2
  - migratable = 0
  - skipped = 2 (`UNSUPPORTED_OR_ALREADY_INTERNAL`)

สรุป:

- local environment ยังไม่พบ source ที่เข้า pattern migration ของ zip 3 ชุด
- production/staging ต้องรัน command ซ้ำเพื่อยืนยันรายการจริงก่อน cutover

## Rollback

- ใช้ report JSON ล่าสุดย้อนค่า `old_endpoint_url` กลับตาม source id
- apply rollback ทีละ source ที่ fail ก่อน

## Traceability

1. zip evidence: endpoint patterns จากทั้ง 3 zip
2. contract decision: migrate to internal endpoints โดยไม่ใช้ CLI runtime เดิม
3. implementation task:
   - `MigrateInternalResultEndpointsCommand`
   - `InternalResultSourceMigrationPlanner`
4. test evidence:
   - `tests/Unit/Lotto/InternalResultSourceMigrationPlannerTest.php`
