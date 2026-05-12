# Requirements: Lotto Public Result Archive API

## Problem Statement

ระบบปัจจุบันไม่มี read model สำหรับ API สถิติผลหวยย้อนหลังที่เหมาะสม FrontendApi ต้อง query `lotto_draws` โดยตรงซึ่ง:
- `lotto_draws` เป็น write-side table สำหรับ draw lifecycle — schema ไม่ optimized สำหรับ public read
- ไม่มี normalization/formatting layer สำหรับ public consumption
- ขาด historical data สำหรับ markets ที่ผลออกนานแล้วแต่ไม่มีในระบบ
- ไม่มี throttling/cache สำหรับ public API

**เป้าหมาย:** สร้าง `lotto_result_archives` เป็น dedicated read model ให้ FrontendApi public endpoint อ่านเท่านั้น

## Acceptance Criteria

- [ ] `lotto_result_archives` และ `lotto_result_archive_logs` tables พร้อม schema ถูกต้อง
- [ ] Normalizer: mapping `lotto_draws` + bet settings → archive format (market_code, draw_date, draw_key, result_set)
- [ ] Archive Writer: idempotent write archive + append log, handle correction (draw re-resulted)
- [ ] Mirror Existing: Artisan command mirror resulted draws ทั้งหมดเข้า archive
- [ ] afterCommit Mirror Job: ทุกครั้งที่ draw เปลี่ยนเป็น resulted → dispatch job เข้า queue `lotto`
- [ ] Fill Missing: Artisan command ดึงข้อมูลที่ขาดจาก external source
- [ ] Reconcile: Artisan command ตรวจสอบ consistency ระหว่าง archive กับ source
- [ ] FrontendApi public endpoints สำหรับอ่านผลย้อนหลัง (GET endpoints)
- [ ] Throttle + Cache บน public endpoints
- [ ] Test suite ครอบคลุม happy path, edge cases, correction, retry
- [ ] Docs + Runbook สำหรับ operation

## Scope

### In Scope

- `lotto_result_archives` read model
- `lotto_result_archive_logs` audit trail
- Normalizer + Internal Draw Mapper + Checksum
- Archive Writer + Correction Handling
- Mirror Existing Resulted Draws Command
- afterCommit Mirror Job
- Fill Missing Command + External Fetcher
- Reconcile Command
- FrontendApi Public Endpoints (read-only)
- Throttle + Cache
- Tests + Regression Guards
- Docs + Runbook

### Out of Scope

- หวยยี่กี (Yeekee)
- Admin menu / ACL / retry UI
- Bearer token / external_api_clients table
- สร้าง queue ใหม่ (ใช้ queue `lotto` เท่านั้น)
- แก้ไข `lotto_draws` schema
- เรียก settlement service
- wallet transactions
- ticket status changes
- Admin dashboard integration

## Technical Constraints

### Hard Rules
1. ห้ามสร้าง/แก้ `lotto_draws` เพื่อเก็บ archive
2. ห้ามให้ FrontendApi query `lotto_draws` ตรง
3. ห้ามเรียก settlement service จาก archive flow
4. ห้ามแตะ wallet transactions
5. ห้ามแตะ ticket status
6. ห้ามรวมหวยยี่กี
7. ห้ามทำ Admin menu / ACL / retry UI
8. ห้ามทำ Bearer token / external client table
9. ห้ามสร้าง queue ใหม่ — ใช้ queue `lotto` เท่านั้น
10. ห้าม expose `raw_payload` ใน public API โดย default
11. ห้าม return API แบบ unbounded (ต้องมี pagination/limit)
12. ต้อง preserve leading zero ของเลขหวย (result number เป็น string เสมอ)
13. ทุก `result_set` ต้องเป็น `array<string>` เท่านั้น — ห้าม object, ห้าม nested key, ห้าม display metadata
14. mirror job fail ต้อง retry ได้ และห้ามทำให้ flow ออกผลหลัก rollback (afterCommit)

### Schema Requirements
- `lotto_result_archives` — archive identity: `market_code`, `draw_date`, `draw_key`
- `lotto_result_archive_logs` — append-only audit log
- Unique constraint: `unique(market_code, draw_date, draw_key)`

## Technology Stack

- **Backend**: Laravel 10 + PHP 8.2
- **Package**: `packages/Gametech/Lotto/src/`
- **BFF**: `packages/Gametech/FrontendApi/src/`
- **Database**: MySQL/MariaDB
- **Queue**: Horizon + Redis (queue: `lotto`)
- **Cache**: Redis
- **Testing**: PHPUnit 10
- **Frontend**: N/A (pure backend/API)

## Architecture Decisions

1. Mirror from resulted draws → archive ก่อน (internal data first)
2. Fill missing จาก external source เฉพาะข้อมูลที่ขาด (external fetcher second)
3. afterCommit dispatch mirror job ทุกครั้งที่ draw → resulted
4. FrontendApi public endpoint อ่านจาก `lotto_result_archives` เท่านั้น
5. ไม่มี token, external_api_clients, admin menu

## Dependencies

- `lotto_draws` (read only — existing data source)
- `lotto_market_bet_settings` (read only — bet type config)
- `lotto_draw_bet_settings` (read only — draw-specific bet config)
- `BetService` / `ResultApplier` / `DrawService` (existing lotto services — dispatch point for mirror job)
- External lottery result sources (for fill-missing)
- Queue `lotto` (Horizon/Redis)
- LottoServiceProvider (register commands, jobs)
