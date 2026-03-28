# System Current State

อัปเดตล่าสุด: 2026-03-29

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
- ปัจจุบันหน้าเมนู `lotto/draws` ซ่อนปุ่ม `Dry-run` และ `Logs` ออกจาก action column
- ปุ่ม `เปิดรับ/ปิดรับ` ถูกถอดออกจากคอลัมน์ `ดำเนินการ` และย้ายพฤติกรรมสลับสถานะไปที่ช่อง `สถานะ` แทน
- ปุ่ม `ออกผล` (สถานะ `closed`) เปิด modal ขนาดเล็กให้เลือกโหมด `Manual` หรือ `Auto`
  - `Manual` = เปิดฟอร์มกรอกผลและคำนวณรางวัลด้วยมือ
  - `Auto` = เรียก flow เดียวกับ `Retry` (`lotto_draws.auto_result_manual_retry`)
- ปุ่ม `Retry` แยกใน action column ถูกยุบเข้าในโหมด `Auto` ของ modal `ออกผล`
- ช่อง `สถานะ` ของงวด `open/closed` แสดงเป็นปุ่ม (button) เพื่อสลับ `เปิดรับ <-> ปิดรับ` ได้เมื่อผู้ใช้มีสิทธิ์ที่เกี่ยวข้อง (`lotto_draws.open`/`lotto_draws.close`) และต้องยืนยันผ่าน popup ก่อนทุกครั้ง
- สถานะที่กดไม่ได้ (`draft`/`resulted` หรือรายการที่ไม่มีสิทธิ์) แสดงเป็นข้อความตกแต่งสีพร้อมไอคอน (ไม่ใช้ badge เดิม)
- ตาราง `lotto/draws` แสดงสีพื้นหลังแยกตามสถานะงวดด้วยโทนที่เห็นชัดขึ้น:
  - `draft` = เทาอ่อน
  - `open` = เขียวอ่อน
  - `closed` = เหลืองอ่อน
  - `resulted` = ฟ้าอ่อน
- ตาราง `lotto/draws` จัดข้อมูลใน cell เป็นแนวตั้งกึ่งกลาง (`vertical-align: middle`) และจัดข้อความกึ่งกลางในส่วนข้อมูลแถว
- หน้า `lotto/draws` รองรับ filter เพิ่มเติมด้วย `สถานะ` (`draft/open/closed/resulted`) ร่วมกับ group/market/draw_date

## นโยบายการเรียงข้อมูลแหล่งผลอัตโนมัติ (Admin UI)

- ตาราง `/lotto/auto-result-sources` รองรับ interactive sorting จากการกดหัวคอลัมน์
- ระบบใช้ default initial sort เป็น `priority ASC` แล้วตามด้วย `id DESC`
- ห้าม lock ลำดับด้วย `orderBy(...)` ตายตัวใน query หลัก เพราะจะทำให้ผู้ใช้ sort คอลัมน์อื่นไม่ได้จริง

## นโยบายหน้าเลขอั้น (Admin `/lotto/number-blocks`)

- ตารางมีคอลัมน์ checkbox เป็นคอลัมน์แรกสุดสำหรับเลือกหลายรายการ
- หน้า index รองรับ filter ด้วย:
  - `งวดหวย` (`draw_id`)
  - `ประเภทเดิมพัน` (`bet_type`)
  - `ค้นหาเลข` (`number_search`)
- เปิด DataTables global searching (`searching=true`) และยังรองรับ filter ด้านบนผ่าน preXhr
- รองรับลบหลายรายการจาก checkbox ที่เลือกผ่านปุ่ม `ลบที่เลือก`
- คอลัมน์ `จัดการ` มีปุ่ม `แก้ไข` และ `ลบ` รายรายการ

## นโยบาย Auto Result Manual Action (Admin UI)

- ปุ่ม `Retry` และ `Dry-run` ในหน้า `draws` เรียก `lotto:fetch-auto-results` แบบ synchronous (`Artisan::call`)
- เป้าหมายคือไม่ผูกกับ worker queue ใน production เพื่อให้คำสั่งถูกประมวลผลทันทีและเห็นผล/log ได้จริง
- ฝั่ง UI ของปุ่มดังกล่าวต้องแสดงข้อความ error จาก backend เมื่อคำสั่งล้มเหลว (ห้าม silent failure)
- รองรับส่ง `expected_draw_date` จาก admin action ไปยัง pipeline เพื่อใช้ strict context validation

## นโยบาย Frontend API v1 Game List

- endpoint `GET /api/v1/games/{type}/{provider}` จะ trigger provider `gamelist` ก่อนทุกครั้ง
- จากนั้นระบบจะอ่านและคืนข้อมูลจาก `GameListProxy` เป็นหลัก (คง response contract v1 เดิม)

## นโยบาย Frontend Lotto Critical Path API

- เพิ่ม public routes ชุด `/api/frontend/lotto/*` สำหรับหน้าแทงและผลย้อนหลังโดยตรง:
  - `GET /api/frontend/lotto/markets/{marketId}/betting-context`
  - `GET /api/frontend/lotto/markets/{marketId}/results`
  - `GET /api/frontend/lotto/markets/{marketId}/draws/{drawId}/result`
- `betting-context` คืนข้อมูลรวมสำหรับหน้าแทงในเส้นเดียว: market/draw/blocked numbers/limits/exposure/version/server_time
- `results` รองรับ `limit` และ `page` เพื่อให้ frontend ทำ pagination ได้

## นโยบาย UI รายการหวย (Admin `/lotto/markets`)

- เพิ่มปุ่ม `Auto` ต่อแถว (หลังปุ่ม `แก้ไข`) เพื่อเปิด modal จัดการ `Auto Result Sources` ของตลาดนั้นแบบ inline
- ปุ่ม `Auto` ถูกดักสิทธิ์ด้วย ACL `lotto_settings.auto_result_sources`
- ปุ่ม `Auto` ของหน้า `lotto/markets` เปิด modal ในหน้าเดิมแบบ native (ไม่ใช้ iframe) และดึงข้อมูลผ่าน API:
  - `GET /lotto/auto-result-sources/list?market_id={id}`
  - `POST /lotto/auto-result-sources/loaddata|create|update|edit`
- ตาราง `รายการหวย` เพิ่มคอลัมน์สถานะผูก source (`ผูกแล้ว` / `ยังไม่ผูก`) ถัดจากคอลัมน์ `ลิงก์ออกผล`
- ใน modal แก้ไข source มีโหมดทดสอบตามวันที่:
  - เลือก `draw_date` แล้วกด dry-run โดย resolve draw จาก `market_id + draw_date`
  - ดู fetch logs ของวันทดสอบได้ใน modal เดียวกัน
- คอลัมน์ `สถานะ` ในตาราง `lotto/markets` แสดงเป็นปุ่ม icon-only (`check/times`) และกดสลับ `is_enabled` ได้โดยตรง (มี confirm)
- คอลัมน์ `จัดการ` ของตาราง `lotto/markets` คงปุ่ม `แก้ไข` และ `Auto`
- ปุ่ม `ลบ` ของ `Auto Result Source` แสดงเฉพาะใน modal รายการ source ของ market นั้น และเรียก endpoint `POST /lotto/auto-result-sources/delete`
- ตารางใน modal `Auto Result Sources`:
  - คอลัมน์ `สถานะ` ถูกวางไว้ก่อน `จัดการ` และแสดงเป็นปุ่ม icon-only สำหรับกดสลับสถานะ
  - คอลัมน์ `จัดการ` แสดงเฉพาะปุ่ม `แก้ไข/ลบ` แบบมาตรฐาน (`btn-info`/`btn-danger`) พร้อม icon+ข้อความ
  - หัว modal แสดงชื่อรายการหวยร่วมกับ `market id`

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
  - มีแท็บ `Quick Setup` สำหรับผู้ใช้ทั่วไป กรอก URL + path หลัก แล้วระบบ generate pipeline JSON อัตโนมัติ
  - ฟิลด์ที่แก้ได้จริงถูกรวมไว้ที่แท็บ `Quick Setup` (เช่น market/priority/timeout/lookup/offset/effective)
  - แท็บ `ทั่วไป` ถูกปรับเป็นมุมมองสรุปแบบ read-only
  - มีช่องหลัก `Pipeline Config JSON (Single Source of Truth)` สำหรับตั้งค่ารวม
  - ตัดแท็บ `Pipeline` ออกจากหน้าแก้ไข เพื่อไม่ให้สับสนกับแท็บตั้งค่าหลัก
  - ปรับ label/tab/action ใน modal เป็นภาษาไทยและจัด layout 2 คอลัมน์ในโหมดตั้งค่าด่วนเพื่อให้อ่านง่ายขึ้น
  - ระบบ sync/แตกค่าไป field ย่อยอัตโนมัติก่อน preview/validate/save
  - ซ่อนช่อง JSON ย่อยจากหน้า form หลักเพื่อลดการกรอกซ้ำและลดความสับสนของผู้ใช้
  - derive ค่า legacy-required fields (`endpoint_url`, `http_method`, `parser_type`, `fetch_strategy`, `selection_stage`) จาก JSON config อัตโนมัติก่อน preview/validate/save
  - ลดความสับสนจากการกรอกค่า legacy และ v2 ซ้ำซ้อน
- runtime `AutoResultPipelineService` ใช้ V2 cutover path เท่านั้น (latest-only) และไม่วิ่ง legacy/shadow path แล้ว
- policy ของ `validate cutover`:
  - `local/testing`: บังคับ fixture gate ต่อ source (สำหรับ regression test)
  - `production`: ใช้ live validation จาก `endpoint_url` จริงผ่าน pipeline runner (ไม่บังคับให้ผู้ใช้ admin สร้างไฟล์ fixture)
  - strict date-check ตาม `expected_draw_date` (ไม่ fallback แบบข้ามเงื่อนไขวันที่)
- `RenderedBrowserFetchDriver` ถูกออกแบบเป็น async worker/runtime path เท่านั้น:
  - main fetch path ห้าม block รอ browser execution แบบ synchronous

## นโยบาย Browser Worker (Auto Result JS-delayed Sources)

- ใช้ deterministic `receipt_key` จาก normalized config + stable context (`source_id`, `draw_id`, `endpoint_url`, `strategy`, `parser_type`, `expected_draw_date`)
- ตัด volatile runtime fields ออกจาก receipt hashing เพื่อให้ input เดิมได้ key เดิมเสมอ
- dispatch gate ใช้ atomic lock (`SETNX + TTL`) ต่อ `receipt_key` เพื่อกัน dispatch ซ้ำ
- cache payload มาตรฐาน key `lotto:auto-result:browser-fetch:{receipt_key}`:
  - `status`: `success|failed|app_shell_only`
  - `response_body`
  - `selected_endpoint`
  - `error_code`
  - `meta` (`duration_ms`, `content_type`, `captured_count`, etc.)
- fetch selection priority แบบ strict:
  1. captured endpoint JSON (valid)
  2. rendered HTML
  3. `APP_SHELL_ONLY`
- retry policy:
  - retry เฉพาะ deferred/network-class failures
  - `APP_SHELL_ONLY` = terminal reject (no retry)
- หน้า admin auto source มี Browser Worker config fields และ async test API:
  - `wait_for_selector`, `wait_until`, `timeout_ms`, `capture_url_patterns`, `block_resource_types`
  - serialize/deserialize ผ่าน `fetch_config_json.meta.browser_worker`

## นโยบาย Browser Runtime Phase 2 (Locked)

- Runtime baseline:
  - ใช้ `Playwright Node Worker` เป็น browser executor หลัก
  - transport เฟสแรก: PHP queue job เรียก local Node process (JSON in/out + exit/stderr summary)
- Capability policy ต่อ source:
  - `http_only`
  - `prefer_browser_runtime`
  - `require_browser_runtime`
- Fallback policy (`prefer_browser_runtime`) แบบ allowlist เท่านั้น:
  - `BROWSER_RUNTIME_UNAVAILABLE`
  - `BROWSER_LAUNCH_FAILED`
  - `BROWSER_EXECUTOR_TIMEOUT`
  - `BROWSER_EXECUTOR_IO_ERROR`
- Non-fallback policy:
  - `NO_NETWORK_MATCH` (เมื่อ source declare network path เป็นหลัก)
  - `DOM_SELECTOR_NOT_FOUND` (เมื่อ source declare browser path เป็นหลัก)
  - invalid capture/wait/predicate config
- Schema governance:
  - PHP เป็น owner ของ runtime output schema
  - Node worker ต้อง emit ตาม version ที่ PHP กำหนด (ห้าม ad hoc shape)
- Selection determinism:
  - `selection_mode=best` ใช้ deterministic tie-break
  - tie ไม่แตกต้อง reject ด้วย `CAPTURE_AMBIGUOUS_MATCH`
- DOM fallback:
  - ใช้ได้เฉพาะ source ที่ `allow_dom_fallback=true`
  - trace ต้องระบุ `payload_origin` ชัด
- Admin/test fetch ของ browser runtime:
  - async only (dispatch + polling status)
  - ห้าม sync browser execution ใน request lifecycle
- Artifact governance:
  - deterministic storage path + filename convention
  - redaction policy สำหรับ secret/cookie/auth/token
  - truncation/retention/size cap ต่อ run
  - มี command cleanup ตาม retention policy: `lotto:cleanup-browser-runtime-artifacts` (รองรับ `--days`, `--dry-run`)
  - scheduler รัน cleanup รายวันเวลา `03:55` (non hot-path, `withoutOverlapping`)
- Rollout:
  - source เดิม default = `http_only`
  - browser runtime เปิดใช้แบบ opt-in + whitelist
  - มี global feature flag ปิดระบบ browser runtime ได้ทั้งระบบ
- Resource budget:
  - global/per-source/per-domain concurrency caps
  - overall timeout cap
  - artifact write cap ต่อ run
- Source capability/rollout config (ใช้งานจริงผ่าน `fetch_config_json.meta.runtime`):
  - `fetch_capability`: `http_only|prefer_browser_runtime|require_browser_runtime`
  - `allow_dom_fallback`: เปิด/ปิด DOM fallback ราย source
  - รองรับ `http_fallback_strategy` สำหรับ route แบบ `http_only`
- Admin Auto Source form รองรับตั้งค่า:
  - capability policy
  - DOM fallback
  - browser `selection_mode` (`first|best|all`)
- Browser test status/dispatch payload แสดง debug เพิ่ม:
  - `selected_driver`
  - `phase_timing`
  - `payload_origin`
  - `selected_capture`
  - `artifact_refs`
- มี CI guardrail สำหรับ AutoResultV2:
  - GitHub Actions workflow `lotto-autoresultv2-unit` รัน `tests/Unit/Lotto/AutoResultV2`
  - อัปโหลด artifact (`autoresultv2-unit.log`, `junit-autoresultv2.xml`) ทุกครั้ง
- มี incident runbook สำหรับ on-call:
  - `docs/internal/03_DOMAINS/lotto-browser-runtime-incident-runbook.md`
  - ครอบคลุม reason code triage + rollback decision tree + evidence checklist

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
