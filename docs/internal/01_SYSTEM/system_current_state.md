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
- รองรับส่ง `expected_draw_date` จาก admin action ไปยัง pipeline เพื่อใช้ strict context validation

## นโยบาย Auto Result Parser/Selector v2

- parser v2 เป็น `context-aware` และแยกบทบาทชัดเจน:
  - `parser` = extract candidate + raw fields เท่านั้น
  - `selector` = เลือกหรือ reject candidate
  - `mapper` = normalize/transform chain
  - `validator` = validate canonical output + expected context
- strategy default สำหรับ source v2 คือ `strict_single_match` (โดยเฉพาะ HTML/text)
- reject แบบ strict เมื่อ:
  - ไม่มี candidate ตรง `expected_draw_date`
  - มีหลาย candidate ตรง `expected_draw_date`
  - candidate ที่ถูกเลือกไม่ผ่าน required fields
  - candidate ที่ถูกเลือกไม่ตรง `expected_draw_date`
  - tie score ในกรณี strategy ที่อิง score
- transform chain อยู่ใน `mapping_config_json` เป็นหลัก (`trim`, `digits_only`, `left:n`, `right:n`, `{op:"date"...}`)
- debug runtime เก็บใน `lotto_result_fetch_logs.selection_debug_json` เท่านั้น (ไม่เก็บใน master state)

## นโยบาย Lotto Result Pipeline v2 (Config-Driven)

- รองรับ pipeline เวอร์ชันต่อ source ผ่าน `pipeline_version`:
  - `LEGACY`
  - `V2_SHADOW`
  - `V2_CUTOVER`
- รองรับ `fetch_strategy` แบบ fixed set:
  - `JSON_HTTP`, `HTML_HTTP`, `RENDERED_BROWSER`, `EMBEDDED_JSON`, `MANUAL_INPUT`
- รองรับ `selection_stage` แบบ fixed set:
  - `PRE_MAPPING`, `POST_MAPPING`
- มี shadow compare status แบบ fixed set:
  - `MATCH`, `MISMATCH`, `ERROR`, `SKIPPED`
- เพิ่ม config storage ฝั่ง source:
  - `fetch_config_json`, `selection_config_json`, `readiness_config_json`
  - flags: `supports_partial`, `requires_browser`, `shadow_enabled`, `cutover_enabled`
- เพิ่ม structured trace/error ใน fetch logs:
  - `trace_json`, `error_code`, `error_stage`
  - `legacy_result_json`, `v2_result_json`, `shadow_diff_json`, `shadow_compare_status`
- เพิ่ม revision history สำหรับ source config:
  - ตาราง `lotto_result_source_revisions` เก็บ `changed_by`, `reason`, `config_hash` และ snapshot ต่อ revision
- เพิ่ม admin actions ใหม่ในเมนู `/lotto/auto-result-sources`:
  - preview config, validate config, validate cutover
- ฟอร์ม admin ของ `auto-result-sources` ใช้โหมด V2-only:
  - derive ค่า legacy-required fields (`endpoint_url`, `http_method`, `parser_type`, `fetch_strategy`, `selection_stage`) จาก JSON config อัตโนมัติก่อน preview/validate/save
  - ลดความสับสนจากการกรอกค่า legacy และ v2 ซ้ำซ้อน
- policy ของ `validate cutover`:
  - `local/testing`: บังคับ fixture gate ต่อ source (สำหรับ regression test)
  - `production`: ใช้ live validation จาก `endpoint_url` จริงผ่าน pipeline runner (ไม่บังคับให้ผู้ใช้ admin สร้างไฟล์ fixture)
  - ถ้าไม่ส่ง `expected_draw_date` และ fail ด้วย `NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE` ระบบจะ retry live validation อีกครั้งโดยไม่ผูก expected date เพื่อลด false-negative
- `RenderedBrowserFetchDriver` ถูกออกแบบเป็น async worker/runtime path เท่านั้น:
  - main fetch path ห้าม block รอ browser execution แบบ synchronous

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
