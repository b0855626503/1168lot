# Lotto Internal Result Sources — Rollout & Deprecation (PR-11)

อัปเดตล่าสุด: 2026-03-30  
สถานะ: READY

## Scope

- rollout internal result source endpoints
- migrate source configs ที่เคยชี้ upstream เดิม
- deprecate mini-project runtime จาก production path

## Preflight

1. ตรวจ `APP_URL` ให้ถูกต้อง (ใช้ build internal endpoint URL)
2. ตั้งค่าถ้าต้องการบังคับ auth:
   - `LOTTO_INTERNAL_RESULT_SHARED_KEY`
   - `LOTTO_INTERNAL_RESULT_SHARED_HEADER` (default `X-Lotto-Internal-Key`)
3. รัน test:
   - `./vendor/bin/phpunit tests/Feature/Lotto/InternalResultEndpointsTest.php`
   - `./vendor/bin/phpunit tests/Unit/Lotto/InternalResultSourceMigrationPlannerTest.php`

## Rollout Steps

1. deploy code ที่มี internal endpoints + migration command
2. dry-run migration:
   - `php artisan lotto:migrate-internal-result-endpoints --report-only`
3. review report แล้ว apply ทีละ batch:
   - `php artisan lotto:migrate-internal-result-endpoints --apply --source-id=...`
4. verify per source หลัง apply:
   - endpoint fetch success
   - auto-result scheduler run ไม่ fail
5. enable shared-key enforcement ใน production (ถ้าต้องการ lock caller)

## Deprecation Plan (Legacy mini projects)

- mini project CLI/runtime (`lottery.php`, `index.php`) ใช้เป็น reference only
- production integration path ต้องผ่าน:
  - service-first (`InternalResultService`) หรือ
  - internal endpoints `/internal/lottery/results/*`
- ห้ามเพิ่ม dependency ใหม่ที่ยิงไป mini project runtime โดยตรง

## Known Limitations

- ถ้า source parser ฝั่งปลายทางผูกกับ shape เดิมมาก อาจต้อง tune mapping config ราย source หลัง migration
- command migration เป็น deterministic mapping จาก URL pattern; endpoint แปลกนอก pattern ต้องจัดการมือ

## Migration Risks (ต้องเช็กก่อน apply)

1. parser path เดิมอาจชี้ root payload เก่า (`$.date`, `$.results.*`) และจะไม่ตรง canonical envelope ใหม่
2. source ที่ใช้ V2 ต้องเช็ก `fetch_config_json.query/headers` ให้ถูก ไม่ใช่อัปเดตเฉพาะ top-level fields
3. ถ้าเปิด shared-key middleware ต้องมี header ใน fetch config ก่อน cutover

## Traceability

1. zip evidence: behavior เดิมของ 3 mini projects
2. contract decision: lock canonical schema + date compatibility + deprecate CLI production path
3. implementation task: routes/service/middleware/migration command + docs
4. test evidence:
   - `InternalResultEndpointsTest`
   - `InternalResultSourceMigrationPlannerTest`
