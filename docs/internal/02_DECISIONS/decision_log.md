# Decision Log

## 2026-03-28 — Increase Draw Row Tint Contrast + Middle Alignment (APPROVED)

- ปรับสีพื้นหลังแถวในหน้า `lotto/draws` ให้เข้มขึ้นจากเดิม เพื่อแยกสถานะ (`draft/open/closed/resulted`) ได้ชัดเจนขึ้น
- บังคับการจัดแนวข้อมูลในตารางให้เป็นแนวตั้งกึ่งกลาง (`vertical-align: middle`) และจัดข้อความส่วนข้อมูลให้อยู่กึ่งกลาง

## 2026-03-28 — Draw Status Toggle via Status Cell + Remove Open Action Button (APPROVED)

- หน้า `lotto/draws` เอาปุ่ม `เปิดรับ/ปิดรับ` ออกจากคอลัมน์ `ดำเนินการ`
- ช่อง `สถานะ` สำหรับงวดที่เป็น `open/closed` ถูกปรับเป็น clickable badge เพื่อสลับสถานะได้ตรงจากคอลัมน์สถานะ
- ก่อนสลับสถานะต้องแสดง popup ยืนยันทุกครั้ง
- การคลิกสลับสถานะยังคงดักสิทธิ์ตาม ACL เดิม:
  - `open -> closed` ต้องมีสิทธิ์ `lotto_draws.close`
  - `closed -> open` ต้องมีสิทธิ์ `lotto_draws.open`

## 2026-03-28 — Soft Row Tint by Draw Status on Admin Draws Table (APPROVED)

- หน้า `lotto/draws` เพิ่มสีพื้นหลังแถวแบบโทนอ่อนตามสถานะงวดเพื่ออ่านสถานะได้เร็วขึ้น
- mapping สี:
  - `draft` เทาอ่อน
  - `open` เขียวอ่อน
  - `closed` เหลืองอ่อน
  - `resulted` ฟ้าอ่อน
- ใช้การ tint ที่ฝั่ง DataTable render (ไม่เปลี่ยน domain data/schema)
- หมายเหตุ: ความเข้มสีถูกปรับเพิ่มภายหลังใน decision `Increase Draw Row Tint Contrast + Middle Alignment`

## 2026-03-28 — Lotto Markets Inline Auto Result Sources Management (APPROVED)

- เพิ่มปุ่ม `Auto` ในเมนู `lotto/markets` (วางหลังปุ่ม `แก้ไข`) และดักสิทธิ์ด้วย ACL `lotto_settings.auto_result_sources`
- ปุ่ม `Auto` เปิด modal เพื่อจัดการ `Auto Result Sources` ของตลาดนั้นโดยตรง (filter lock ตาม `market_id`)
- เพิ่มความสามารถทดสอบจากหน้าแก้ไข source:
  - เลือก `draw_date` แล้วกด dry-run โดย resolve draw จาก `market_id + draw_date` (ไม่ต้องไปกดจากเมนูงวดหวย)
  - มีปุ่มดู logs ของผลทดสอบจากวันที่ที่เลือกใน modal เดียวกัน
- เพิ่มคอลัมน์ในเมนู `lotto/markets` หลัง `ลิงก์ออกผล` เพื่อแสดงสถานะว่า market นี้ผูก Auto Result Source แล้วหรือยัง

## 2026-03-28 — Hide Dry-run/Logs Actions on Admin Lotto Draws (APPROVED)

- ปรับหน้าเมนู `lotto/draws` (action column) ให้ซ่อนปุ่ม `Dry-run` และ `Logs`
- คงปุ่ม action อื่นไว้ตามสิทธิ์เดิม (เช่น edit/open/close/settle/retry)

## 2026-03-28 — Frontend Lotto Critical Path Endpoints (`/api/frontend`) (APPROVED)

- เพิ่ม endpoint public สำหรับ frontend หน้าแทงโดยตรงที่ `/api/frontend/lotto/markets/{marketId}/betting-context`
- payload ต้องรวม market/current draw/blocked numbers/limits/number exposure/version/server_time ในเส้นเดียว
- เพิ่ม endpoint ผลย้อนหลัง:
  - `GET /api/frontend/lotto/markets/{marketId}/results`
  - `GET /api/frontend/lotto/markets/{marketId}/draws/{drawId}/result`
- เป้าหมาย: ลดการเรียกหลายเส้นใน critical path ของหน้าแทงและทำให้ผลย้อนหลังมี contract ชัดเจนแบบ pagination-friendly

## 2026-03-28 — Frontend API v1 Game List Warmup Before Proxy Read (APPROVED)

- สำหรับ endpoint `GET /api/v1/games/{type}/{provider}` ให้ trigger provider `gamelist` ก่อนทุกครั้ง
- หลัง warmup แล้วให้คืนผลจาก `GameListProxy` เป็นหลักตาม contract v1 เดิม
- วัตถุประสงค์: ลดเคสข้อมูลค่ายเกมใน v1 ไม่อัปเดต/ไม่ครบเมื่อ cache/proxy ยังไม่ทัน sync

## 2026-03-27 — Document Standardization (LOCKED)

- รวมเอกสารเข้าโครงสร้างมาตรฐานเดียวภายใต้ `docs/`
- แยก internal/public ชัดเจน
- ย้ายเอกสารกระจัดกระจายจาก root/.github/packages/public/vendor เข้า `docs/internal`
- ตั้ง source-of-truth หลัก:
  - `docs/internal/00_RULES/agent_rules.md`
  - `docs/internal/01_SYSTEM/system_current_state.md`
  - `docs/internal/02_DECISIONS/decision_log.md`
- เอกสารซ้ำและเวอร์ชันเก่าถูกย้ายไป `docs/internal/05_ARCHIVE/`

## 2026-03-27 — Lotto Draw Lifecycle Hardening (LOCKED)

- ล็อก `open/close` ให้รับ `source` แบบ explicit (`scheduled|manual`)
- ล็อก settle idempotency แบบ reject เมื่อ `status=resulted`
- ล็อก `result_at` ให้ใช้ server time ใน service เท่านั้น
- เพิ่มฟิลด์ audit transition ของ draw (`opened_at`, `closed_at`, `open_mode`, `close_mode`)

## 2026-03-27 — Open Draw Date Editable (APPROVED)

- อนุญาตให้แก้ `draw_date` ได้ในหน้าแก้ไขงวด เมื่อสถานะงวดเป็น `open`
- คงหลัก allowlist ของ update ไว้ โดยเพิ่ม `draw_date` เข้า allowlist ของสถานะ `open`
- ฝั่ง UI และ backend ต้องสอดคล้องกัน (เปิด field + validate/persist ได้จริง)

## 2026-03-27 — Draw Actions Permission Gate (APPROVED)

- เพิ่มการเช็กสิทธิ์รายปุ่มในหน้า `draws` action column ผ่าน `bouncer()->hasPermission(...)`
- map ACL key ตาม action (`edit/open/close/settle/dry-run/retry/logs`)
- กำหนดให้สถานะ `resulted` ยังแสดงปุ่ม `Logs` และ `Dry-run` ได้เมื่อมีสิทธิ์
- ยืนยันว่า `superadmin` ผ่านทุกสิทธิ์ตาม bouncer behavior เดิม

## 2026-03-27 — Resulted Dry-run Visibility (APPROVED)

- เพิ่มการแสดงปุ่ม `Dry-run` ในสถานะงวด `resulted` เมื่อผู้ใช้มีสิทธิ์ `lotto_draws.auto_result_test_fetch`
- ปรับ command `lotto:fetch-auto-results` ให้ manual dry-run แบบระบุ `draw_id` รองรับสถานะ `closed` และ `resulted`

## 2026-03-27 — Auto Result Sources Table Sorting (APPROVED)

- ยกเลิกการ lock ลำดับข้อมูลด้วย `orderBy(priority,id)` ตายตัวใน query ของ DataTable
- กำหนด default initial sort ที่ฝั่ง DataTables แทน (`priority ASC`, `id DESC`)
- เป้าหมายคือให้ผู้ใช้กด sort คอลัมน์อื่นได้จริงตามพฤติกรรมตารางมาตรฐาน

## 2026-03-27 — Auto Result Dry-run Sync Execution (APPROVED)

- เปลี่ยน endpoint admin `Dry-run` ให้รัน `lotto:fetch-auto-results` แบบ synchronous แทน queue dispatch
- เหตุผล: production อาจไม่มี worker queue ทำให้ขึ้นข้อความว่าส่งคำสั่งแล้วแต่ไม่เกิดการประมวลผลจริง
- กำหนดให้ UI แสดง error message จาก backend เมื่อ dry-run/retry ล้มเหลว เพื่อลด silent failure

## 2026-03-27 — Draw Window Overnight Normalization (APPROVED)

- ในฟอร์ม admin `draws/addedit` ให้รองรับการกรอกเวลาข้ามวันโดยไม่ต้องเปลี่ยนวันที่เองทุกครั้ง
- ถ้า `close_at` น้อยกว่า `open_at` ให้ normalize `close_at` เป็นวันถัดไป
- ถ้า `result_at` น้อยกว่า `close_at` ให้ normalize `result_at` เป็นวันถัดไป
- ถ้าเวลาที่กรอกน้อยกว่าค่าอ้างอิง ระบบให้ normalize ไปวันถัดไปจนได้ลำดับเวลาที่ถูกต้อง
- เมนู `รายการหวย` ใช้กติกาเวลาเดียวกัน และ command `lotto:generate-auto-draws` ต้องคำนวณข้ามวันให้ตรงกับ config

## 2026-03-27 — Auto Result Parser v2 Strict Context (APPROVED)

- เพิ่ม parser pipeline v2 แบบ candidate/record-scoped เพื่อกัน cross-block mismatch
- ล็อกความรับผิดชอบ layer:
  - parser = extract candidate/raw fields
  - selector = choose/reject candidate
  - mapper = transform chain
  - validator = canonical validation + expected context
- default strategy ของ v2 คือ `strict_single_match` และไม่ fallback แบบเงียบเมื่อ ambiguous
- score-based strategy เป็น opt-in เท่านั้น และต้อง reject เมื่อ tie
- เพิ่ม runtime debug field `selection_debug_json` ใน `lotto_result_fetch_logs` (execution metadata)
- รองรับส่ง `expected_draw_date` จาก command/admin action เข้า pipeline โดยตรง

## 2026-03-27 — Auto Result Skip When Source Config Missing (APPROVED)

- ใน command `lotto:fetch-auto-results` โหมด auto sweep ให้เช็กก่อนว่า market นั้นมี source config ใน `lotto_result_sources` หรือยัง
- ถ้ายังไม่มีให้ `skip` โดยไม่เรียก pipeline, ไม่เพิ่ม retry attempts, และไม่ปล่อยให้วิ่งจน `EXHAUSTED`
- ใช้เพื่อกันเคส noise alert ประเภท exhausted จาก draw ที่ยังไม่ได้ onboard source

## 2026-03-27 — Lotto Result Pipeline v2 Enum/Trace/Shadow Governance (APPROVED)

- เพิ่ม fixed enum/value sets สำหรับ pipeline orchestration:
  - `pipeline_version`: `LEGACY|V2_SHADOW|V2_CUTOVER`
  - `fetch_strategy`: `JSON_HTTP|HTML_HTTP|RENDERED_BROWSER|EMBEDDED_JSON|MANUAL_INPUT`
  - `selection_stage`: `PRE_MAPPING|POST_MAPPING`
  - `shadow_compare_status`: `MATCH|MISMATCH|ERROR|SKIPPED`
- เพิ่ม schema/config storage สำหรับ source v2:
  - `fetch_config_json`, `selection_config_json`, `readiness_config_json`
  - flags: `supports_partial`, `requires_browser`, `shadow_enabled`, `cutover_enabled`
- เพิ่ม structured trace/error storage ใน `lotto_result_fetch_logs`:
  - `trace_json`, `error_code`, `error_stage`
  - `legacy_result_json`, `v2_result_json`, `shadow_diff_json`, `shadow_compare_status`
- เพิ่ม source revision table `lotto_result_source_revisions` พร้อม metadata:
  - `changed_by`, `reason`, `config_hash`
- บังคับ trace normalization ก่อน persist (minimum required keys + shape normalization)
- บังคับ deterministic mismatch policy ใน shadow compare โดยเทียบ canonical outcome set เท่านั้น
- บังคับ `RenderedBrowserFetchDriver` เป็น async worker/runtime path เท่านั้น (ไม่ block main fetch path)
- เพิ่ม admin preview/validate config และ validate cutover ก่อนเปิด cutover

## 2026-03-27 — Cutover Validation Production Readiness (APPROVED)

- ปรับ `validate cutover` ให้เหมาะกับ production:
  - `production` ใช้ live validation โดยรัน pipeline กับ `endpoint_url` จริง
  - ไม่บังคับให้ผู้ใช้ admin จัดการไฟล์ fixture เอง
  - เพิ่ม fallback deterministic: หากไม่ส่ง `expected_draw_date` และเจอ `NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE` ให้ retry live validate แบบไม่ผูก expected date 1 ครั้ง
- คง fixture gate ไว้เฉพาะ `local/testing` เพื่อรองรับ regression test ของทีมพัฒนา
- ตอนบันทึก source ที่เปิด `cutover_enabled=true`:
  - production ไม่บล็อกด้วย fixture gate
  - local/testing ยังบล็อกจนกว่าจะมี fixture ตาม source

## 2026-03-27 — Auto Result Source Form V2-Only Mode (APPROVED)

- ลดความสับสนจากการมี field legacy+v2 ซ้ำซ้อนในฟอร์มเดียว
- ฟอร์ม `admin/lotto/auto-result-sources` แสดงและเน้นการตั้งค่าแบบ V2 config เป็นหลัก
- ก่อน `preview/validate/save` ระบบจะ derive ค่า field ที่ backend legacy ยังต้องใช้จาก JSON config อัตโนมัติ:
  - `endpoint_url`, `http_method`, `parser_type`, `fetch_strategy`, `selection_stage`
- ตั้ง default ฝั่งฟอร์มเป็น `pipeline_version=V2_CUTOVER` เพื่อให้ flow การใช้งานสอดคล้องกับ runtime ใหม่

## 2026-03-27 — Auto Result Latest-Only Runtime (APPROVED)

- ปรับ runtime ให้ใช้ V2 cutover path เท่านั้น (`latest-only`)
- ปิดการใช้งาน shadow/legacy path ใน `AutoResultPipelineService`
- นโยบายตรวจวันงวดยังคง strict (ห้าม fallback ข้าม expected_draw_date)

## 2026-03-27 — Auto Result Form Single JSON Input UX (APPROVED)

- ฟอร์ม `admin/lotto/auto-result-sources` ให้ผู้ใช้กรอก config หลักผ่าน `Pipeline Config JSON` ช่องเดียว
- ช่อง JSON ย่อย (fetch/parser/mapping/selection/validation/readiness/retry/headers/query/body) ถูกซ่อนจากหน้า form หลัก
- ก่อน preview/validate/save ระบบยัง split/derive ไป field ย่อยอัตโนมัติเพื่อคง backend contract เดิม
- เพิ่มแท็บ `Quick Setup` สำหรับ generate config อัตโนมัติจาก input สั้น ๆ และมี preset สำเร็จรูป
- ยกเลิกแท็บ `Pipeline` ในหน้า UI เพื่อลด field ซ้ำซ้อนและลด cognitive load ของผู้ใช้
- ปรับ label/tab/action เป็นภาษาไทย และจัด layout ของ `ตั้งค่าด่วน` เป็น 2 คอลัมน์สมมาตร พร้อมปรับปุ่ม action ให้มองเห็นชัดขึ้น
- ย้ายฟิลด์แก้ไขหลักทั้งหมดไปที่ `ตั้งค่าด่วน` และให้แท็บ `ทั่วไป` เป็น read-only summary
- โหมดแก้ไข source (`update`) ถูกล็อกไม่ให้เปลี่ยน `market_id` ทั้งใน UI และ backend เพื่อกันย้าย source ข้ามตลาดโดยไม่ตั้งใจ
