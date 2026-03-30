> สถานะ: DONE
> วันที่: 2026-03-30
> โดเมน/เรื่อง: Lotto / Internal Result Sources Integration
> แทนแผนเก่า: -
> อ้างอิง Linear Project: `Lotto Internal Result Sources Integration` (https://linear.app/boatjunior/project/lotto-internal-result-sources-integration-57b7c22b0945)

# Summary

รวม `lottery-php`, `dowjones-midnight`, `dowjones-extra` เข้าระบบหลักให้เป็น service-first integration โดยยึด behavior ที่ต้องคงจริงตาม caller และ lock canonical schema ให้ชัดเจนก่อนลง implementation

baseline compatibility:

- คง API ที่จำเป็นต่อ integration เดิม
- รองรับ input date format: `Y-m-d`, `d/m/Y`, `d-m-Y`
- output `draw_date` บังคับ `Y-m-d`
- ไม่ใช้ CLI runtime เดิมเป็น production path (ใช้เป็น reference/transition เท่านั้น)

# Locked Interfaces

- Internal endpoints หลัก:
  - `GET /internal/lottery/results/exphuay/{type}?date=YYYY-MM-DD&page=1`
  - `GET /internal/lottery/results/dowjones-midnight?date=YYYY-MM-DD`
  - `GET /internal/lottery/results/dowjones-extra?date=YYYY-MM-DD`
- Canonical response ต้องคง key หลักครบเสมอ:
  - `success`, `source`, `type`, `draw_date`, `raw_result`, `normalized_result`, `meta`, `errors`
  - `normalized_result` ต้องมี key คงที่ (ค่าที่ derive ไม่ได้ให้ `null`)
  - `errors` ต้องเป็น array เสมอ
- Dowjones supplemental fields (`start_spin`, `show_result`, `now`, `update`) ต้องถูก lock ownership ชัดเจนว่าอยู่ `meta`/`raw_result`/drop ที่ชั้น schema policy เท่านั้น

# Implementation Scope (Linear Mapping)

## Core Track

- `BOA-18` = PR-01 Discovery & Contract Freeze
- `BOA-20` = PR-02 Driver Contract
- `BOA-19` = PR-03 HTTP Fetch Base Layer
- `BOA-21` = PR-04 Exphuay Driver
- `BOA-22` = PR-05 Dowjones Midnight Driver
- `BOA-23` = PR-06 Dowjones Extra Driver
- `BOA-24` = PR-07 Resolver + Controller + Routes
- `BOA-25` = PR-08 Auth/Middleware/Rate Limit (`BOA-29` เป็น duplicate)
- `BOA-26` = PR-09 Tests
- `BOA-27` = PR-10 Integration Hooks
- `BOA-28` = PR-11 Docs/Rollout/Deprecation

## Gap-Closure Track (ตามแผนนี้)

- `BOA-42` = PR-12 Compatibility & Legacy Contract Matrix
- `BOA-43` = PR-13 Source Config Migration/Backfill to Internal Endpoints
- `BOA-44` = PR-14 Canonical Extra-Fields Policy (Dowjones Metadata)
- `BOA-45` = SourceTruth-01 Inventory real callers and freeze source-of-truth

# Dependency Lock

- `PR-01 (BOA-18)` -> `PR-12 (BOA-42)` -> (`PR-07 (BOA-24)` และ `PR-09 (BOA-26)`) -> `PR-13 (BOA-43)` -> `PR-11 (BOA-28)`
- `PR-14 (BOA-44)` ต้องปิดก่อน finalize contract docs (`PR-11`)
- `SourceTruth-01 (BOA-45)` ต้อง feed decision เข้า `PR-12` และ `PR-13`

# Traceability Contract (บังคับใช้กับทุก issue ใน project)

ทุก issue ต้องมีส่วน `Traceability` ใน description โดยอย่างน้อยมี:

1. `zip evidence`
2. `contract decision`
3. `implementation task`
4. `test evidence`

# Acceptance Criteria

1. `docs/04_PLANS/README.md` มี ACTIVE เพียงไฟล์นี้
2. มี issue กลุ่ม PR-12/13/14 ครบและ dependency ตาม lock flow
3. ไม่มี duplicate เชิงความหมายที่ไม่ผูก relation (`duplicateOf`/parent/related)
4. มี compatibility matrix ครบ 3 source พร้อม keep/drop/shim decision
5. มี migration checklist ราย source และมี post-migration verification evidence
