> สถานะ: DONE
> วันที่: 2026-03-28
> โดเมน/เรื่อง: Lotto / Auto Result Browser Runtime Phase 2
> แทนแผนเก่า: -

# Summary

ยกระดับจาก `RENDERED_BROWSER` แบบเบา (HTTP + capture candidate) ไปเป็น `browser_runtime` จริงด้วย Playwright Node Worker โดยคงโครงสร้าง `Async + Retry` และไม่เพิ่มต้นทุนให้ source เดิมที่เป็น HTTP

# Locked Decisions

1. Runtime baseline: `Playwright Node Worker`
2. Capability policy: `http_only | prefer_browser_runtime | require_browser_runtime`
3. Transport (phase แรก): PHP queue job เรียก local Node process, input/output เป็น JSON, เก็บ `exit_code` + `stderr_summary`
4. Schema owner: PHP เป็น runtime result schema authority, Node ต้อง emit ตาม schema version ที่ PHP กำหนด
5. Fallback (เฉพาะ `prefer_browser_runtime`) อนุญาตเฉพาะ allowlist:
   - `BROWSER_RUNTIME_UNAVAILABLE`
   - `BROWSER_LAUNCH_FAILED`
   - `BROWSER_EXECUTOR_TIMEOUT`
   - `BROWSER_EXECUTOR_IO_ERROR`
6. ห้าม fallback ไป HTTP ในเคส:
   - `NO_NETWORK_MATCH` (เมื่อ source ตั้ง network capture เป็น logic หลัก)
   - `DOM_SELECTOR_NOT_FOUND` (เมื่อ source ตั้ง browser path เป็น logic หลัก)
   - config/predicate/capture rule invalid
7. DOM fallback เป็น optional capability เท่านั้น (`allow_dom_fallback=true`) และ trace ต้องบอก `payload_origin` ชัด
8. Admin browser test = async only (dispatch + status/polling), ห้าม sync browser execution ใน request lifecycle
9. Artifact persistence ใช้ `Store Path + Summary` พร้อม redaction/truncation/retention
10. Rollout policy:
   - source เดิมทั้งหมด default = `http_only`
   - browser runtime เปิดใช้แบบ opt-in per source + whitelist
   - มี global feature flag ปิด browser runtime ได้ทั้งระบบ

# Implementation Scope

## PR-Next-01 Capability Model + Routing

- เพิ่ม fetch capability policy ระดับ source (`http_only|prefer_browser_runtime|require_browser_runtime`)
- route driver จาก declarative config เท่านั้น (ห้ามเดาจาก parser)
- classifier fallback/reject ต้อง deterministic ตาม locked decisions

## PR-Next-02 Browser Runtime Worker

- เพิ่ม Node executor ที่ทำงานผ่าน queue job แบบ async
- รองรับ browser launch, navigation, readiness wait, network interception, response capture
- แยก execution path จาก HTTP driver ชัดเจน

## PR-Next-03 Network Interception Contract

- นิยาม contract กลางของ capture result:
  - request/response metadata
  - capture timestamp
  - final URL
  - artifact refs
- selection mode: `first|best|all`
- `best` ต้อง deterministic tie-break:
  1) exact URL > wildcard  
  2) exact method > any  
  3) exact content-type > generic  
  4) rule priority สูงกว่า  
  5) latest response  
  6) ถ้ายัง tie ให้ reject `CAPTURE_AMBIGUOUS_MATCH`

## PR-Next-04 Wait Strategy + Timeout Budget

- wait strategy config per source:
  - selector, network idle, specific request seen, DOM ready, fixed short delay, optional JS predicate
- timeout budget แยก phase:
  - navigation/readiness/capture/overall

## PR-Next-05 Artifact + Observability + Redaction

- path deterministic:
  - `storage/lotto/browser-runtime/YYYY/MM/DD/source_{source_id}/draw_{draw_id}/run_{receipt_key}/`
- filename มาตรฐาน:
  - `meta.json`, `network_summary.json`, `capture_{index}.json`, `screenshot_fail.png`, `console.log`
- policy:
  - retention default 7 วัน
  - max artifact ต่อ run = 5MB
  - preview truncate = 16KB ต่อรายการ
- redaction:
  - ห้าม persist raw cookie/auth header/token
  - mask query secrets (token/auth/key/signature)
  - เก็บ headers ผ่าน allowlist/masklist

## PR-Next-06 Concurrency / Budget / Retry

- แยก budget จาก HTTP pipeline:
  - global browser concurrency
  - per-source concurrency
  - per-domain concurrency
  - overall runtime timeout cap
  - artifact write cap ต่อ run
- retryable/non-retryable classifier อิง reason code แบบ explicit

## PR-Next-07 Admin UI/Test UX

- เพิ่ม config UI สำหรับ capability policy + runtime config + capture rules + timeout budget
- async test fetch ต้องแสดง:
  - selected driver, reason code, phase timing, payload origin, selected capture, artifact refs

## PR-Next-08 Compatibility / Rollout

- migration default: source เดิม `http_only`
- rollout แบบ whitelist + opt-in
- rollback ผ่าน global feature flag

## PR-Next-09 Test Coverage

- Unit: routing matrix, fallback classifier, selection tie-break, redaction
- Integration: queue -> node -> cache/trace -> pipeline
- Feature: admin dispatch/status async flow และ debug visibility

## PR-Next-10 Operational Retention Cleanup

- เพิ่ม scheduled cleanup สำหรับ browser-runtime artifacts ตาม `retention_days`
- เพิ่ม artisan command แบบปลอดภัยรองรับ dry-run และสรุปจำนวนโฟลเดอร์ที่ลบ
- ยืนยันว่า cleanup เป็น non hot-path และไม่กระทบ fetch/pipeline path หลัก

## PR-Next-11 CI Guardrail (AutoResultV2)

- เพิ่ม GitHub Actions workflow สำหรับรัน `tests/Unit/Lotto/AutoResultV2` ในทุก PR
- workflow ต้อง fail-fast เมื่อ regression เกิดขึ้นใน runtime policy/trace contract
- เก็บ test log + junit artifact ทุกครั้งเพื่อใช้ debug หลังงาน CI ล้มเหลว

## PR-Next-12 Incident Runbook (Reason Codes)

- เพิ่ม runbook สำหรับ on-call triage โดย map reason code -> first response
- เพิ่ม rollback decision tree (global enable flag / whitelist source)
- เพิ่ม evidence checklist สำหรับ post-incident closure

# Acceptance Criteria

1. Source `http_only` behavior ไม่เปลี่ยน
2. Source `require_browser_runtime` ไม่ถูกยิงด้วย HTTP driver
3. `prefer_browser_runtime` fallback ทำงานเฉพาะ allowlist class
4. Parser layer consume payload ได้โดยไม่ผูกกับ implementation browser
5. Admin test fetch ใช้ async only และอ่าน root cause ได้จาก reason code + artifact refs
