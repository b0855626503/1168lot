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
- PR-06 Admin Audit + Sensitive Permission ← **กำลังทำ**
- PR-07 Hardening + Regression Gate

---

## PR-06 Admin Audit + Permission Contract

> Status: IN PROGRESS — BOA-199
> Branch: `codex/boa-199-pr-06-admin-audit-permission`

### Objective

เพิ่ม admin view สำหรับตรวจสอบ Yeekee shoots และ snapshot ย้อนหลัง
พร้อม permission แยกสำหรับ sensitive data

### Scope

**ทำ:**
- Admin controller `YeekeeAuditController` (read-only, ไม่แตะ service logic)
- ACL permission ใหม่ 2 รายการ (ดูหัวข้อ Permission Contract)
- Admin routes (GET only)
- Blade view สำหรับ audit UI
- Feature tests ครอบ permission enforcement และ JSON endpoints

**ห้ามทำ:**
- ห้ามแก้ `YeekeeShootService`
- ห้ามแก้ `YeekeeResultEngineService` (เว้นแต่ read-only helper)
- ห้ามเพิ่ม migration
- ห้ามเปลี่ยน frontend API contract
- ห้ามทำ PR-07 regression gate ใน PR นี้

### Permission Contract

| ACL Key | ชื่อ | หมายเหตุ |
|---|---|---|
| `lotto.yeekee.audit.view` | ดู Yeekee Audit (masked) | เข้าถึง endpoint audit ได้ |
| `lotto.yeekee.audit.view_sensitive` | ดู Yeekee Audit แบบ Sensitive | เห็นเลขเต็ม, snapshot, proof/raw fields |

- ขาด `lotto.yeekee.audit.view` → 403
- มี `lotto.yeekee.audit.view` แต่ไม่มี `lotto.yeekee.audit.view_sensitive` → ได้ข้อมูล masked + redacted

### Admin Endpoint Contract (read-only)

| Route Name | Path | Permission Required |
|---|---|---|
| `admin.lotto.yeekee.audit.rounds` | `GET /lotto/yeekee/audit/rounds` | `lotto.yeekee.audit.view` |
| `admin.lotto.yeekee.audit.show` | `GET /lotto/yeekee/rounds/{roundId}/audit` | `lotto.yeekee.audit.view` (+ `view_sensitive` for full payload) |

### Snapshot Data Contract

`admin.lotto.yeekee.audit.show` ส่ง `shoot_snapshot_json` ดิบจาก `yeekee_rounds.shoot_snapshot_json` เมื่อมีสิทธิ์ `lotto.yeekee.audit.view_sensitive`
โดย canonical structure คือ (ตาม PR-05):

```json
{
  "version": 1,
  "round_id": ...,
  "lotto_draw_id": ...,
  "market_id": ...,
  "round_no": ...,
  "round_date": "...",
  "shoot_open_at": "...",
  "shoot_close_at": "...",
  "shoot_closed_at": "...",
  "shoot_count": ...,
  "last_shoot_position": ...,
  "shoots": [...]
}
```

`snapshot_hash` = sha256 ของ payload ข้างบน (ตาม PR-05)

### Data Exposed per Endpoint

**admin.lotto.yeekee.audit.rounds**:
- ไม่รวม `shoot_snapshot_json` ดิบ
- แสดง `has_snapshot: bool` และ metadata รอบที่จำเป็น

**admin.lotto.yeekee.audit.show**:
- ผู้มี `lotto.yeekee.audit.view`: ได้ข้อมูลยิงแบบ masked + round summary
- ผู้มี `lotto.yeekee.audit.view_sensitive`: ได้ข้อมูล sensitive เพิ่ม (เลขเต็ม, snapshot, proof/raw fields)

### Files Changed

| ไฟล์ | ประเภท |
|---|---|
| `packages/Gametech/Lotto/src/Http/Controllers/Admin/YeekeeAuditController.php` | ใหม่ |
| `packages/Gametech/Lotto/src/Config/acl.php` | แก้ไข (เพิ่ม ACL) |
| `packages/Gametech/Lotto/src/Routes/admin.php` | แก้ไข (เพิ่ม routes) |
| `packages/Gametech/Lotto/src/Models/YeekeeRound.php` | แก้ไข (เพิ่ม `market()` relationship) |
| `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/draws/yeekee_audit_modal.blade.php` | ใหม่ |
| `tests/Feature/Lotto/YeekeeAuditControllerTest.php` | ใหม่ |
| `docs/04_PLANS/2026-05-02_yeekee-shooting-flow-hardening.md` | แก้ไข (เพิ่ม PR-06 contract) |

## Acceptance Criteria
- มีเอกสารแผนนี้ใน `docs/04_PLANS`
- `docs/04_PLANS/README.md` อัปเดตลิงก์แผนใหม่
- Public docs แยก `Current Runtime` กับ `Target Contract (Planned)` ชัดเจน
- Target contract ระบุ `Status: Target Contract / Planned`, `Implemented in: PR-04`, และคำเตือนว่าไม่ active
- Endpoint เดิมใน current runtime contract ยังอยู่ครบ ไม่ถูกลบ/เขียนทับ
- docs validation ผ่าน
