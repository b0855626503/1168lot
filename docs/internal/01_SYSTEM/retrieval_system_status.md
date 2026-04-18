# Retrieval System Status

อัปเดตล่าสุด: 2026-04-18

ไฟล์นี้เป็นศูนย์กลางเดียวสำหรับตรวจสถานะ retrieval layer (docs + memory + octocode index)

## Layer Roles

| Layer | บทบาท | ระดับข้อมูล | หมายเหตุ |
|---|---|---|---|
| Markdown (`/docs`) | source-of-truth ของ policy/contract/decision | detail | authoritative |
| Memory (`.codebase-memory/`, `memory/`) | compressed semantic context สำหรับ startup/retrieval | summary | memory-first |
| Octocode (`.ai/mcp/index-build.json`) | machine-verifiable retrieval/index artifact | metadata | ใช้ยืนยัน freshness ของ index |

## Source of Truth

- truth หลัก: `/docs`
- memory และ index เป็น retrieval acceleration layer
- ถ้าไม่ตรงกัน ให้ถือว่า docs ถูกก่อน

## Evidence Index (Machine-Usable)

### Scripts

- unified consistency check: `scripts/docs-validation/check-unified-sync.sh`
- semantic consistency check: `scripts/docs-validation/check-semantic-sync.sh`
- code-doc drift check: `scripts/docs-validation/check-code-doc-drift.sh`
- octocode index verification: `scripts/docs-validation/check-octocode-index-sync.sh`
- retrieval entrypoint regression check: `scripts/docs-validation/check-retrieval-entrypoints.sh`
- full validation runner: `scripts/docs-validation/run.sh`
- rebuild index artifact: `scripts/docs-validation/rebuild-octocode-index-artifact.sh --changed-only`
- post-change helper: `scripts/docs-validation/post-change-sync.sh`
- retrieval metrics report generator: `scripts/docs-validation/generate-retrieval-metrics-report.sh`

### Artifacts / Reports

- semantic drift report: `.ai/mcp/semantic-drift-report.json`
- octocode index artifact: `.ai/mcp/index-build.json`
- retrieval metrics report: `.ai/mcp/retrieval-metrics-report.json` และ `.ai/mcp/retrieval-metrics-report.md`
- memory summary: `.codebase-memory/SUMMARY.md`
- required domain memory:
  - `memory/auth.md`
  - `memory/payment.md`
  - `memory/wallet.md`
  - `memory/game.md`

### Config

- `scripts/docs-validation/config.sh`
- `UNIFIED_SYNC_MODE=warn|error`
- `SEMANTIC_SYNC_MODE=warn|error`
- `INDEX_ARTIFACT_PATH=.ai/mcp/index-build.json` (default)
- `SEMANTIC_REPORT_PATH=.ai/mcp/semantic-drift-report.json` (default)

## Freshness / Sync Checks

1. Rebuild index artifact  
   `bash scripts/docs-validation/rebuild-octocode-index-artifact.sh --changed-only`
2. Validate semantic + unified consistency  
   `bash scripts/docs-validation/check-unified-sync.sh`
3. Validate full docs pipeline  
   `bash scripts/docs-validation/run.sh`

## Pass / Warn / Fail Conditions

- `check-semantic-sync.sh`
  - pass: mismatch = 0
  - warn/error: mismatch > 0 (ตาม `SEMANTIC_SYNC_MODE`)
- `check-octocode-index-sync.sh`
  - pass: มี `index-build.json`, โครงสร้างถูกต้อง, และ artifact sync กับ changed set
  - warn/error: artifact ขาด/เก่า/ไม่ครอบ changed files
- `check-unified-sync.sh`
  - pass: memory coverage ครบ + semantic check ผ่านเงื่อนไข + index check ผ่านเงื่อนไข
  - warn/error: inconsistency ตาม `UNIFIED_SYNC_MODE`

## Agent Retrieval Order

1. `.codebase-memory/SUMMARY.md`
2. `memory/<domain>.md`
3. docs เฉพาะ section ที่จำเป็น
4. ถ้าต้องตรวจ consistency/retrieval state ให้กลับมาไฟล์นี้ทันที
