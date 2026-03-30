# Lotto Internal Result Sources — Caller Inventory (SourceTruth-01)

อัปเดตล่าสุด: 2026-03-30  
สถานะ: WORKING INVENTORY (ต้องเติมจาก production evidence)

## วัตถุประสงค์

- ระบุว่า legacy behavior ใดมี caller จริงในระบบปัจจุบัน
- แยก in-repo caller กับ external caller (ที่ต้องยืนยันจาก ops/runtime evidence)
- ป้อนผลลัพธ์ให้ `PR-12` (compatibility) และ `PR-13` (migration/backfill)

## วิธีเก็บหลักฐานรอบนี้

- ค้นอ้างอิงใน repo ด้วย keyword:
  - `lottery-php`, `dowjones-midnight`, `dowjones-extra`, `exphuay`
  - `internal/lottery/results`
  - `endpoint_url`
  - `lotto:fetch-auto-results`
- ขอบเขตที่ค้น:
  - `app/`, `packages/`, `routes/`, `config/`, `docs/`

## Findings (In-Repo)

### 1) Caller ของ mini-project runtime เดิม

- ไม่พบโค้ดใน repo ที่เรียกไฟล์ `lottery.php`/`index.php` จาก zip โดยตรง
- ไม่พบ URL hardcoded ของ `api.dowjones-midnight.com` หรือ `api.dowjonesextra.com` ในโค้ดหลักตอนนี้

ผล: ยังไม่พบ in-repo caller โดยตรงของ runtime เดิม

### 2) Caller ฝั่งระบบหลักที่เกี่ยวกับ source endpoint

พบจุดอ้างอิง `endpoint_url` จำนวนมากใน flow ปัจจุบัน เช่น:

- model: `LottoResultSource.endpoint_url`
- admin forms: `markets/addedit.blade.php`, `result_sources/addedit.blade.php`
- services/pipeline:
  - `AutoResult/ResultRequestBuilder`
  - `AutoResultV2/*FetchConfig*`
  - `AutoResultV2/*PipelineRunner*`
- admin controllers:
  - `LottoResultSourceController`
  - `LottoDrawController` (manual retry/test via command)

ผล: caller หลักจริงตอนนี้คือ pipeline ที่ยิงตาม `endpoint_url` จาก source config

### 3) Scheduler/Command caller

- scheduler เรียก `lotto:fetch-auto-results --limit=100` ทุกนาที
- command นี้เป็น hot path ที่ใช้ source config จริง

ผล: migration/backfill ของ `endpoint_url` เป็น critical path

## Legacy Behavior Inventory (from zip) + Caller State

| legacy behavior | พบ caller ใน repo หรือไม่ | สถานะรอบนี้ |
|---|---|---|
| `type=list` ใน mini-project API | ไม่พบ | external-unknown |
| date input หลายรูปแบบ (`Y-m-d`,`d/m/Y`,`d-m-Y`) | ไม่พบ caller โดยตรง แต่เป็น behavior ใน zip | compat-candidate |
| path-style URL (`/lottery/{type}`) | ไม่พบ | drop-candidate (ถ้าไม่มี external caller) |
| CLI (`php lottery.php ...`) | ไม่พบ | drop-candidate (production path ใหม่ไม่ใช้) |
| exphuay `type/page/date` behavior | พบความต้องการเชิงแผนใน docs + source config path | keep-via-internal-endpoint |
| dowjones-midnight/extra by date | พบความต้องการเชิงแผนใน docs + source config path | keep-via-internal-endpoint |

## Working Recommendation (ยังไม่ final)

- keep:
  - internal endpoint 3 เส้นตาม contract freeze
  - date compatibility adapter (`Y-m-d`,`d/m/Y`,`d-m-Y`) สำหรับ integration safety
- drop/deprecate candidate:
  - legacy CLI runtime ของ mini projects
  - path-style URL behavior ของ mini projects
- require extra evidence ก่อน final decision:
  - external callers จริงที่อาจยังผูกกับ `type=list` หรือ path-style URL

## Evidence Gap ที่ต้องเติมก่อน Final Freeze

1. access logs/usage telemetry เพื่อยืนยัน external callers ของ legacy surfaces
2. รายชื่อ source config ปัจจุบันที่ชี้ endpoint เดิมจริง (เพื่อ feed PR-13)
3. รายการ market/source ที่มี business criticality สูง (ใช้กำหนด cutover order)

## Local Scan Evidence (2026-03-30)

- รัน command migration report:
  - `php artisan lotto:migrate-internal-result-endpoints --report-only`
- report:
  - `storage/app/lotto/internal_result_migration/migration_report_20260330_172843.json`
- พบ source ใน local DB = 2 รายการ และยังไม่เข้า pattern migration ของ 3 source เป้าหมาย

## Output ที่ส่งต่อ

- ส่งต่อ `PR-12`: ตาราง keep/drop/shim โดยมี caller-state backing
- ส่งต่อ `PR-13`: รายการ config migration priority จาก critical callers

เอกสารปลายทาง:

- `docs/internal/03_DOMAINS/lotto-internal-result-sources-compatibility-matrix.md`
- `docs/internal/03_DOMAINS/lotto-internal-result-sources-migration-backfill.md`
