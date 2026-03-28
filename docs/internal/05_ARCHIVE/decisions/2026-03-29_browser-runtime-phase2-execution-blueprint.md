# Browser Runtime Phase 2 — Execution Blueprint (Decision Complete)

## Context

รอบก่อนหน้าระบบ hardening orchestration/retry/contract/UI แล้ว แต่ `RenderedBrowserFetchDriver` ยังไม่ใช่ browser execution จริง (ยังเป็น HTTP + capture candidate) จึงยังรองรับเว็บ JS-delayed ได้ไม่ครบ

เป้าหมายรอบนี้: เพิ่ม browser runtime จริง โดยไม่ทำลาย flow เดิม, ไม่เปลี่ยน contract เกินจำเป็น, และไม่เพิ่มต้นทุนให้ source HTTP ปกติ

## Core Architecture Rules

1. แยก capability ชัด:
   - `http`
   - `rendered_browser_light` (ของเดิม)
   - `browser_runtime` (ใหม่)
2. Source ต้องประกาศ capability requirement แบบ declarative (ห้าม scheduler เดาเอง)
3. Browser runtime ใช้ network-first; DOM เป็น optional fallback
4. แยก layer ชัดเจน: fetch / capture / extraction / parser-mapping-validation

## Capability Policy (Locked)

ใช้ policy ระดับ source:
- `http_only`
- `prefer_browser_runtime`
- `require_browser_runtime`

### Fallback Semantics (Locked)

`prefer_browser_runtime` fallback ไป HTTP ได้เฉพาะ:
- `BROWSER_RUNTIME_UNAVAILABLE`
- `BROWSER_LAUNCH_FAILED`
- `BROWSER_EXECUTOR_TIMEOUT`
- `BROWSER_EXECUTOR_IO_ERROR`

ห้าม fallback ในเคส:
- `NO_NETWORK_MATCH` (กรณี source ตั้ง network capture เป็น logic หลัก)
- `DOM_SELECTOR_NOT_FOUND` (กรณี source ตั้ง browser path เป็น logic หลัก)
- `INVALID_CAPTURE_RULE`
- `INVALID_WAIT_STRATEGY`
- config/predicate invalid อื่น ๆ

## Runtime Transport (Locked)

เฟสแรกใช้:
- PHP queue job -> local Node process executor
- input JSON
- output JSON
- เก็บ `exit_code` + `stderr_summary`

ห้าม sync browser execution ใน request lifecycle

## Schema Governance (Locked)

- PHP เป็น owner ของ runtime output schema
- Node worker ต้อง emit ตาม schema version ที่ PHP กำหนด
- เปลี่ยน schema ต้อง bump version ชัดเจน

## Deterministic Capture Selection (Locked)

selection mode:
- `first`
- `best`
- `all`

`best` tie-break rule:
1. exact URL match > wildcard
2. exact method match > any
3. exact content-type match > generic
4. higher rule priority
5. latest response timestamp
6. ถ้ายังเสมอ -> reject ด้วย `CAPTURE_AMBIGUOUS_MATCH`

## DOM Fallback Policy (Locked)

- DOM fallback ใช้ได้เฉพาะ source ที่ config อนุญาต (`allow_dom_fallback=true`)
- driver ห้าม fallback DOM เองแบบ implicit
- trace ต้องระบุ `payload_origin` ชัด (`network_capture` / `dom_snapshot` / `http_response`)

## Artifact + Security Governance (Locked)

### Path / Naming

- path pattern:
  - `storage/lotto/browser-runtime/YYYY/MM/DD/source_{source_id}/draw_{draw_id}/run_{receipt_key}/`
- deterministic filenames:
  - `meta.json`
  - `network_summary.json`
  - `capture_{index}.json`
  - `screenshot_fail.png`
  - `console.log`

### Retention / Size

- retention default 7 วัน
- max artifact size ต่อ run = 5MB
- response/body preview truncate = 16KB ต่อ item

### Redaction

- ห้าม persist raw cookie / raw auth header / bearer token เต็ม
- mask query params ประเภท secret (`token`, `auth`, `key`, `signature`, ...)
- headers ที่เก็บต้องผ่าน allowlist/masklist

## Concurrency / Budget Policy (Locked)

ต้องมีตั้งแต่เฟสแรก:
- max concurrent browser jobs (global)
- per-source concurrency
- per-domain concurrency
- overall timeout cap
- artifact write cap ต่อ run

## Admin Test Fetch Policy (Locked)

- browser runtime test = async only
- UI ใช้ dispatch + polling status endpoint
- ห้ามมี sync browser execution ใน admin/test request lifecycle

## Rollout Policy (Locked)

- source เดิมทั้งหมด default = `http_only`
- browser runtime เปิดใช้แบบ opt-in per source
- เริ่มจาก whitelist source จำนวนน้อย
- มี global feature flag สำหรับปิด browser runtime ทั้งระบบ

## PR Scope

### PR-Next-01 Capability Model + Routing
- เพิ่ม capability policy ต่อ source
- route driver จาก config
- apply fallback/reject classifier ตาม lock

### PR-Next-02 Browser Runtime Worker
- เพิ่ม Node Playwright runtime worker (async queue)
- รองรับ launch/navigation/wait/intercept/capture/artifact

### PR-Next-03 Network Interception Contract
- ออกแบบ schema กลางของ capture payload
- deterministic selection policy (first/best/all)

### PR-Next-04 Wait Strategy / Readiness Policy
- เพิ่ม wait strategy แบบ config-driven
- timeout budget ราย phase

### PR-Next-05 Debug Artifact + Observability
- เพิ่ม artifact/debug summary ที่ operator ใช้งานได้จริง
- reason code มาตรฐาน

### PR-Next-06 Retry / Backoff / Budget
- policy แยกระหว่าง browser runtime กับ HTTP
- classifier retryable/non-retryable จาก reason code

### PR-Next-07 Admin/UI Capability Support
- เพิ่ม fields สำหรับ capability/wait/capture/timeout
- แสดง selected driver + payload origin + artifact refs

### PR-Next-08 Compatibility / Migration
- รักษา behavior source เดิม
- incremental rollout + rollback

### PR-Next-09 Test Coverage
- unit/integration/feature ครบ routing + runtime + observability

## Acceptance Criteria

1. source HTTP เดิมทำงานเหมือนเดิม
2. source require browser runtime ไม่ถูก route ไป HTTP
3. prefer fallback เฉพาะ error class allowlist
4. parser layer ใช้ payload แบบกลางได้โดยไม่รู้ implementation backend
5. admin test fetch เห็น root cause ชัดจาก reason code + artifact refs
