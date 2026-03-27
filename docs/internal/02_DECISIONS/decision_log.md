# Decision Log

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
