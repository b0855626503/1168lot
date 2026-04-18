# Docs Validation Status

อัปเดตล่าสุด: 2026-04-18

## ตรวจได้แล้ว (Phase 1)
- Required files
- Docs structure
- Markdown placement/naming (pragmatic)
- Plans README presence

## ตรวจได้แล้ว (Phase 2)
- Plan metadata header (สถานะ/วันที่/โดเมน-เรื่อง/แทนแผนเก่า)
- ACTIVE count policy (per-domain: ACTIVE > 1 ต่อ domain = fail, ACTIVE = 0 = warn)
- Broken internal doc paths (เฉพาะ `docs/` และ relative links)

## ตรวจได้แล้ว (Phase 3)
- Code ↔ Doc drift detection (scan working tree / latest commit ได้)
- Drift severity config (`DRIFT_MODE=warn|error`)
- Domain entrypoint presence (`auth/payment/wallet/...`)
- Monolith size policy สำหรับ active docs (index+chapter enforcement)

## ตรวจได้แล้ว (Phase 3.1)
- Unified sync check: docs + memory + octocode index layer
- Inconsistency state detection (invalid state) พร้อมโหมด `warn|error`
- helper script สำหรับ post-change sync validation

## ตรวจได้แล้ว (Phase 3.2)
- Semantic sync validation (entity-level: endpoint/domain/module)
- Semantic drift report artifact: `.ai/mcp/semantic-drift-report.json`
- Octocode index verification ด้วย machine-verifiable artifact (`.ai/mcp/index-build.json`)
- Memory coverage policy ต่อ domain (`auth/payment/wallet/game`)

## พฤติกรรมการรัน
- ใช้ `bash scripts/docs-validation/run.sh`
- ถ้ามี `[ERROR]` อย่างน้อย 1 รายการ: exit 1
- `[WARN]` ไม่ทำให้ fail
- มี summary จำนวน errors/warnings ตอนท้าย

## TODO (Phase 4)
- เพิ่ม semantic drift checks ระดับ field/response contract ต่อ endpoint
- เพิ่ม domain-specific drift map สำหรับ Lotto draw/settlement lifecycle
- เพิ่ม check ว่า chapter index links ครบทุก section ที่บังคับ
- เพิ่ม auto integration กับเครื่องมือ index rebuild จริงของ octocode ใน CI environment

## หมายเหตุ pragmatic
- ใช้ `docs/04_PLANS` เป็น source of truth ของ plans (ถ้ามี)
- ไฟล์ underscore เดิมใน docs ยัง allow แบบ warning และจะ migrate ในเฟสถัดไป
- `.github/*.md` อยู่ใน allowlist ชั่วคราวสำหรับ workflow/documentation ของ repository
