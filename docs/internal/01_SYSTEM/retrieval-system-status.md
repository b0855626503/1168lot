# Retrieval System Status

อัปเดตล่าสุด: 2026-07-02

ไฟล์นี้เป็นศูนย์กลางเดียวสำหรับตรวจสถานะ retrieval layer (docs + memory + boat)

## Layer Roles

| Layer | บทบาท | ระดับข้อมูล | หมายเหตุ |
|---|---|---|---|
| Markdown (`/docs`) | source-of-truth ของ policy/contract/decision | detail | authoritative |
| Memory (`memory/`) | compressed semantic context สำหรับ startup/retrieval | summary | memory-first |
| Boat MCP | code intelligence + memory + graph query | metadata/semantic | ใช้ `boat_ask` เป็นหลัก |

## Source of Truth

- truth หลัก: `/docs`
- memory และ Boat เป็น retrieval acceleration layer
- ถ้าไม่ตรงกัน ให้ถือว่า docs ถูกก่อน

## Evidence Index (Machine-Usable)

### Scripts

- unified consistency check: `scripts/docs-validation/check-unified-sync.sh`
- semantic consistency check: `scripts/docs-validation/check-semantic-sync.sh`
- code-doc drift check: `scripts/docs-validation/check-code-doc-drift.sh`
- retrieval entrypoint regression check: `scripts/docs-validation/check-retrieval-entrypoints.sh`
- full validation runner: `scripts/docs-validation/run.sh`

### Artifacts / Reports

- semantic drift report: `.ai/mcp/semantic-drift-report.json`
- required domain memory:
  - `memory/auth.md`
  - `memory/payment.md`
  - `memory/wallet.md`
  - `memory/game.md`

### Config

- `scripts/docs-validation/config.sh`
- `UNIFIED_SYNC_MODE=warn|error`
- `SEMANTIC_SYNC_MODE=warn|error`
- `SEMANTIC_REPORT_PATH=.ai/mcp/semantic-drift-report.json` (default)

## Freshness / Sync Checks

1. Validate semantic + unified consistency
   `bash scripts/docs-validation/check-unified-sync.sh`
2. Validate full docs pipeline
   `bash scripts/docs-validation/run.sh`

## Pass / Warn / Fail Conditions

- `check-semantic-sync.sh`
  - pass: mismatch = 0
  - warn/error: mismatch > 0 (ตาม `SEMANTIC_SYNC_MODE`)
- `check-unified-sync.sh`
  - pass: memory coverage ครบ + semantic check ผ่านเงื่อนไข
  - warn/error: inconsistency ตาม `UNIFIED_SYNC_MODE`

## Agent Retrieval Order

1. `boat_ask` — quick code intelligence + memory query
2. `memory/<domain>.md`
3. docs เฉพาะ section ที่จำเป็น
4. ถ้าต้องตรวจ consistency/retrieval state ให้กลับมาไฟล์นี้ทันที
