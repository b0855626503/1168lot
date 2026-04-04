# Lotto Auto Result Integration (Merged Plan)

> สถานะ: DONE
> วันที่: 2026-03-27
> โดเมน/เรื่อง: Lotto Auto Result
> แทนแผนเก่า: -

## Goal
สร้างระบบ Auto Result สำหรับ Lotto แบบแยก layer ชัดเจน (schema -> resolver -> builder -> fetcher -> parser -> mapper/validator -> apply -> orchestration -> retry -> admin tooling -> hardening) โดยไม่กระทบ flow manual เดิม และยังคง backward compatibility

## Locked Principles
- merged plan นี้คือ source of truth หลัก
- execution tracker ใช้เป็น implementation memory เท่านั้น
- ห้ามเปลี่ยน interface เดิมนอก scope ที่ล็อก
- preserve manual/public settlement flow
- state transition สำคัญต้อง explicit และ idempotent
- time source ใช้ server now() เท่านั้น

## PR Breakdown (PR-01 .. PR-11)

### PR-01 Schema Foundation
- เพิ่มตาราง `lotto_result_sources`
- เพิ่มตาราง `lotto_result_fetch_logs`
- เพิ่มฟิลด์ fetch/result hash บน `lotto_draws`
- เพิ่ม index ที่จำเป็น
- ห้ามเปลี่ยน behavior runtime

### PR-02 Result Source Resolver
- resolve source ด้วย `market_id`, `is_active`, effective window, priority ASC
- ไม่พบ source: return null + clear draw snapshot
- บันทึก draw-level source snapshot

### PR-03 Request Builder + Lookup Date Engine
- รองรับโหมด: `ROUND_DATE`, `ROUND_DATE_MINUS_DAYS`, `ROUND_DATE_PLUS_DAYS`, `RESULT_AT_DATE`
- render URL/query/header/body จาก template
- unknown placeholder: fail-fast ด้วย exception เฉพาะ

### PR-04 Fetcher
- ยิง HTTP ด้วย timeout + duration
- classify failure เป็น `HTTP_ERROR`
- write fetch log ทุก execution
- ยังไม่ parse ใน PR นี้

### PR-05 Parser Engine
- รองรับ `JSON_PATH`, `CSS_SELECTOR`, `REGEX`
- output เป็น extracted tree (raw) เท่านั้น
- `NOT_READY` vs `PARSE_ERROR` ต้องแยกตาม taxonomy

### PR-06 Mapper + Validator
- mapper: extracted payload -> logical map
- validator contract:
  - `first_prize` (5-6 หลัก)
  - `last_2_digits` (2 หลัก)
- invalid => `VALIDATION_ERROR`

### PR-07 Apply Result
- ใช้ `SettlementService->settleDraw()`
- ถ้า draw เป็น RESULTED แล้วและ hash ใหม่ไม่เท่าของเดิม => `CONFLICT`, ห้าม overwrite
- `result_fetch_status` เป็น canonical auto-result state

### PR-08 Fetch Orchestration Command
- command: `lotto:fetch-auto-results`
- flow: resolver -> builder -> fetch -> parse -> map -> validate -> apply
- eligibility: closed, due result_at, not resulted
- `NO_SOURCE` ต้อง explicit
- ถ้า draw ใด throw unhandled exception ระหว่าง orchestration:
  - command ต้อง log draw-level exception แล้ว continue draw ถัดไป
  - command ต้อง persist แถวใน `lotto_result_fetch_logs` ด้วย
  - ห้าม abort ทั้ง batch

### PR-09 Retry + Backoff
- retry policy:
  - ทุก 1 นาที x 15
  - ทุก 5 นาที x 12
- `NOT_READY` = retryable
- payload ที่ match งวดแล้วแต่ผลหลักยังว่าง/ไม่ครบ (เช่น upstream ส่ง `results: []`) ต้องถูกจัดเป็น `NOT_READY`
- `TEMPLATE_ERROR` = non-retryable by scheduler
- exceeded => `EXHAUSTED`

### PR-10 Admin Tooling
- manage result sources
- async test fetch (dry-run only, no apply)
- preview parsed/normalized
- view logs
- manual retry
- ต้องมี ACL และสามารถทดสอบได้โดยไม่ deploy

### PR-11 Hardening
- exhausted alert: structured log + async telegram + spam guard per draw
- metrics:
  - `success_rate` (APPLIED only)
  - `retry_count` (execution-level attempt_no > 1)
- optional rate limit: per-source per-minute
- `RATE_LIMITED` ต้อง log-level เสมอ และ draw-level ห้ามทับ terminal states
- unhandled exception หลัง increment attempt:
  - ต้องไม่หายไปใน app log อย่างเดียว
  - ต้องมี DB trace ใน `lotto_result_fetch_logs` และ draw fetch fields ทุกครั้ง

## Status Taxonomy (Locked)
- `NO_SOURCE`
- `HTTP_ERROR`
- `NOT_READY`
- `PARSE_ERROR`
- `VALIDATION_ERROR`
- `TEMPLATE_ERROR`
- `CONFLICT`
- `RATE_LIMITED`
- `EXHAUSTED`
- `APPLIED`

## Appendix / Archive Notes
- แผนนี้ merge จากข้อกำหนดที่กระจายอยู่ในบทสนทนาและเอกสาร plan เดิม เพื่อทำให้ทีมมี SoT เดียว
- PR สามารถส่งแยกได้ แต่ต้องไม่ข้าม scope ของกันและกัน
- ถ้ามี conflict กับ tracker ให้ยึดไฟล์นี้ก่อนเสมอ
