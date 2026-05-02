> สถานะ: ACTIVE
> วันที่: 2026-05-02
> โดเมน/เรื่อง: lotto / yeekee
> แทนแผนเก่า: -
> อ้างอิง: BOA-193, BOA-194

# PR-01 Docs Contract Lock: Yeekee Shooting Flow Hardening

## Objective
ล็อกเอกสารสัญญางาน Yeekee Shooting Hardening ให้พร้อมสำหรับ PR ถัดไป โดย **PR-01 เป็น docs-only** และไม่เปลี่ยน runtime behavior

## Non-Negotiable Scope
- ห้ามแก้ migration/service/controller/routes/runtime code
- ห้ามทำ API activation
- ห้ามเปลี่ยน route behavior
- ห้ามเปลี่ยน result formula behavior

## Current Runtime Contract
อ้างอิงจากเอกสาร/contract ปัจจุบันใน repo ก่อน PR-01; PR-01 ไม่ยืนยันหรือเปลี่ยน runtime behavior

- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`

## Target Contract (Planned)
สัญญานี้เป็นเป้าหมายของ Yeekee hardening เท่านั้น และยังไม่ใช่ active runtime ใน PR-01

New target endpoints in PR-04:
- `GET /api/v1/lotto/yeekee/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{round}`

Existing endpoints to be hardened by PR-03/PR-04:
- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`

Status: `Target Contract / Planned`  
Implemented in: `PR-04`  
Do not treat as active runtime endpoint until `PR-04` is merged.

## Decision Lock
- Yeekee shooting = round-based only
- Position = per-round atomic counter
- Cooldown = per member per round, default 6 seconds
- Public shoot list = masked only
- Close round = freeze snapshot before result calculation
- Repo `/docs` = source of truth หลัง PR-01

## Documentation Guardrails
- หาก current docs กับ target contract ไม่ตรง ให้แยกหัวข้อ `Current Runtime Contract` กับ `Target Contract (Planned)` ชัดเจน
- ห้ามลบหรือเขียนทับ endpoint เดิมใน current runtime docs
- ห้ามเขียนข้อความที่ทำให้เข้าใจว่า target endpoint เปิดใช้งานแล้วใน PR-01

## Result-Proof Semantic Note (Doc Correction Only)
- `result-proof.formula_label` ต้องสะท้อน runtime preset จริง
- ห้ามใช้ `PRECOMMITTED_BASE64_MD5` เป็น canonical label ถ้า runtime ไม่ได้ใช้
- PR-01 ทำแค่ correction ด้านเอกสาร
- รายละเอียด result-proof ขั้นสุดท้ายให้ lock ใน PR-05 หลังตรวจ Yeekee result service/runtime path จริง

## PR Breakdown (Execution Order)
- PR-01 Docs & Contract Lock
- PR-02 Schema + Config + Backfill
- PR-03 Atomic Shoot Service + Cooldown
- PR-04 Frontend API
- PR-05 Snapshot Freeze + Result Integration
- PR-06 Admin Audit + Sensitive Permission
- PR-07 Hardening + Regression Gate

## Acceptance Criteria
- มีเอกสารแผนนี้ใน `docs/04_PLANS`
- `docs/04_PLANS/README.md` อัปเดตลิงก์แผนใหม่
- Public docs แยก `Current Runtime` กับ `Target Contract (Planned)` ชัดเจน
- Target contract ระบุ `Status: Target Contract / Planned`, `Implemented in: PR-04`, และคำเตือนว่าไม่ active
- Endpoint เดิมใน current runtime contract ยังอยู่ครบ ไม่ถูกลบ/เขียนทับ
- docs validation ผ่าน
