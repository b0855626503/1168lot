# ADR-002: Lotto Public Result Archive API

วันที่: 2026-05-12
สถานะ: Accepted
PR: [#84](https://github.com/b0855626503/1168lot/pull/84)

## Context

ต้องการ public API สำหรับ historical lottery results ที่:
- อ่านเร็ว ไม่กระทบ table หลัก `lotto_draws`
- ไม่ต้อง auth (public endpoint)
- รองรับ pagination และ cache
- แก้ไข/เพิ่มผลย้อนหลังได้ โดยไม่กระทบ source of truth

## Decision

สร้าง `lotto_result_archives` เป็น dedicated read model แยกจาก `lotto_draws`

### Core Architecture

1. **Mirror first, fill later** — Internal draws mirror ก่อน external gap-fill
2. **result_set = array only** — No objects; checksum via versioned SHA-256 JSON
3. **Idempotent 3-branch writer** — create / skip (same hash) / correct (different hash)
4. **Pagination at draw_date level** — `SELECT DISTINCT draw_date` → paginate → `WHERE draw_date IN (...)`
5. **afterCommit dispatch** — Mirror job หลัง draw transaction commit เท่านั้น

### Public API

```
GET /api/v1/lotto/results/{marketCode}?from_date=&to_date=&page=&per_page=
GET /api/v1/lotto/results/{marketCode}/{drawDate}
GET /api/v1/lotto/results/{marketCode}/{drawDate}/{drawKey}
```

### Commands

```
lotto:mirror-result-archives    — backfill จาก draws ที่มีอยู่
lotto:fill-missing-results      — เติมช่องว่างจาก external source
lotto:reconcile-result-archive  — ตรวจสอบ/แก้ไข consistency
```

## Rationale

- **Read model แยก** — archive อ่านเร็ว ไม่ล็อค `lotto_draws`
- **Idempotent** — รันซ้ำได้ปลอดภัย ด้วย hash-based skip
- **External never overwrites internal** — `allowExternalOverwrite` flag ป้องกัน external ทับ internal
- **Versioned cache key** — `v{version}` invalidate ทุกครั้งที่เขียน โดยไม่ต้องไล่ลบทีละ key
- **Strict date validation** — `date_format:Y-m-d` + round-trip check ทุก public endpoint
- **No yeekee** — `LotteryMarket::RESULT_MODE_YEEKEE` ถูก exclude ทุก flow

## Consequences

### Positive

- API public อ่านเร็ว ไม่กระทบ draw table
- Idempotent — replay-safe
- Audit log ติดตามทุกการเปลี่ยนแปลง (`source_info_json`, `changed_keys`)
- External fetch canonicalize `draw_key` ผ่าน `RAW_TO_CANONICAL` map

### Negative

- ต้อง maintain mirror job + 3 commands
- มีความซ้ำซ้อนของข้อมูลระหว่าง `lotto_draws` และ `lotto_result_archives`
- Cache invalidation ต่อ market code แบบ version-based อาจ invalidate บ่อยถ้าเขียนถี่

### Mitigations

- `lockForUpdate()` ป้องกัน race ใน MySQL
- Correction update `source_draw_id` + `source_type` เพื่อ track provenance
- Unknown external `draw_key` ถูก skip + log ไม่ persist ดิบ
- Reconcile batch preload draws ลด N+1

## Hard Constraints

- ห้ามแตะ `lotto_draws` writes
- FrontendApi reads จาก `lotto_result_archives` เท่านั้น
- ห้ามเรียก settlement service จาก archive flow
- ใช้ queue `lotto` เท่านั้น ไม่สร้าง queue ใหม่
- ไม่มี admin UI / token / external_api_clients table
- ไม่ expose `raw_payload` ใน public API
- Leading zero ใน result numbers ต้อง preserve (string)

## Related

- [ADR-001](lotto-market-content-storage.md) — Content storage pattern
- BOA-247 — Parent Linear issue
- `docs/internal/03_DOMAINS/lotto.md` — Lotto domain docs
