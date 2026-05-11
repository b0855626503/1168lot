# Lotto Check Output Contract (Source of Truth)

อัปเดตล่าสุด: 2026-03-29

## Purpose

เอกสารนี้กำหนดรูปแบบผลลัพธ์ของคำสั่ง:

- `check <url> <draw_date> <first_prize> <last_2_raw>`

ให้เป็นมาตรฐานเดียวกันทุก session และห้าม drift เป็นรูปแบบอื่น

## Scope

- ใช้กับงานที่ trigger ผ่าน skill `lotto-parser-config-generator`
- โดยเฉพาะเคส positional 4 args ซึ่งต้องเข้าโหมด `EXHAUSTIVE`

## Required Blocks (EXHAUSTIVE)

ผลลัพธ์ต้องมี code blocks ตามลำดับนี้เท่านั้น:

1. `PAGE JSON`
2. `PAGE DOM (CSS)`
3. `PAGE REGEX`
4. `ENDPOINT JSON`
5. `ENDPOINT DOM (CSS)`
6. `ENDPOINT REGEX`
7. `SELF_TEST SUMMARY`

ถ้า parser/branch ใดไม่ feasible:
- ต้องส่ง block ของ branch นั้นเป็น JSON error object
- ห้ามข้าม block

## Minimum Required Schema (Config Blocks)

ทุก config block (ทั้ง PAGE/ENDPOINT) ต้องมี top-level keys ชุดเดียวกัน:

- `request_headers_json` (array)
- `request_query_template_json` (array)
- `request_body_template_json` (array)
- `fetch_config_json` (object)
- `parser_config_json` (object)
- `mapping_config_json` (object)
- `selection_config_json` (object)
- `validation_config_json` (object)
- `readiness_config_json` (object)
- `retry_policy_json` (object)

นี่คือ top-level ขั้นต่ำที่ต้องมีเสมอ

- อนุญาตเพิ่ม field ย่อย (nested fields) ภายในแต่ละ object ได้
- แต่ห้ามเพิ่ม top-level key นอกเหนือจาก 10 keys นี้ เว้นแต่มีการอัปเดต doc + decision log ก่อน

## Canonical Field Shapes (Minimum Baseline)

ให้ยึดรูปแบบ field ภายในตามนี้เป็น baseline:

- `request_headers_json`: array ของ `{ "key": "...", "value": "..." }`
- `request_query_template_json`: array
- `request_body_template_json`: array
- `fetch_config_json.timeout_seconds`: number
- `fetch_config_json.headers`: array
- `fetch_config_json.query`: array
- `parser_config_json.version`: `2`
- `parser_config_json.mode`: `single_payload`
- `mapping_config_json.fields`: object
- `selection_config_json` ต้องมี:
  - `selection_stage`
  - `strategy`
  - `date_field`
  - `required_fields`
  - `meta`
- `validation_config_json` ต้องมี:
  - `required_fields`
  - `field_rules`
  - `expected_draw_date`
- `readiness_config_json` baseline:
  - `mode`: `by_target_date`
  - `expected_draw_date`: `{{expected_draw_date}}`
- `retry_policy_json` baseline:
  - `max_attempts`
  - `backoff_seconds` (array)

## Transform Lock

mapping transforms ให้รองรับรูปแบบที่ระบบใช้อยู่:

- string transform เช่น `trim`, `digits_only`, `right:2`
- object transform เช่น `{ "op": "date", "from": "d/m/Y", "to": "Y-m-d" }`

## Self-Test Minimum Contract

`SELF_TEST SUMMARY` ต้องมีอย่างน้อย:

- `raw_result`
- `transformed_result`
- `validation_result`
- `passed`
- `json_contract_checked`
- `json_contract_source`
- `json_contract_version`

## Validation Gate Before Send

ก่อนส่งคำตอบ `check` ทุกครั้ง ต้องผ่าน checklist นี้:

1. มีครบ 6 config blocks + 1 self-test summary block
2. ทุก config block มี top-level keys ครบตาม Minimum Required Schema
3. `request_headers_json/request_query_template_json/request_body_template_json` เป็น array ทั้งหมด
4. `mapping_config_json.fields` เป็น object
5. `selection_config_json` มี `date_field` และ `required_fields`
6. `validation_config_json.expected_draw_date.field` มีค่า
7. ถ้าไม่ครบข้อใดข้อหนึ่ง: regenerate ก่อนตอบทันที

## Extensibility Policy

เพื่อรองรับการขยายตาม runtime:

- เพิ่มได้: nested fields ที่ backward-compatible และระบบรองรับจริง
- เพิ่มได้: metadata เพิ่มเติมใน `fetch_config_json.*`, `selection_config_json.meta.*`, `validation_config_json.field_rules.*`, `readiness_config_json.*`, `retry_policy_json.*`
- ห้ามเพิ่ม: top-level key ใหม่โดยไม่อัปเดตเอกสารนี้และ `docs/internal/02_DECISIONS/decision-log.md`
