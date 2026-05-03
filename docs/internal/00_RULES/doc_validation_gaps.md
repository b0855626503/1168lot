# Doc Validation Gaps

สถานะของ validation checks — implemented vs pending

---

## Severity Tiers

| Level | ความหมาย | ผลใน CI |
|-------|----------|---------|
| `[INFO]` | ปกติ | ผ่าน |
| `[WARN]` | naming convention / heuristic hint | ผ่าน (track as debt) |
| `[ERROR]` | file หาย / link broken / policy violation | block |

**หมายเหตุ:** validation ใช้ `[ERROR]` = block เท่านั้น ยังไม่มี CRITICAL tier แยก
ถ้าต้องการ CRITICAL tier ให้เพิ่ม `log_critical()` ใน `lib.sh` และปรับ `run.sh`

---

## Implemented Checks (all wired in run.sh)

### check-deprecated-docs.sh ✅
- ตรวจ `docs/public/api/frontend-v1/` ว่าไฟล์ `*route-reference*` และ `*endpoints*`
  ที่ไม่ใช่ `07-route-reference.md` มี DEPRECATED/ARCHIVED header หรือไม่
- Severity: `[ERROR]`

### check-discovery-freshness.sh ✅
- ตรวจ `Last Verified:` ในทุก `*_discovery.md`
- WARN ถ้า > `FRESHNESS_WARN_DAYS` (default 14 วัน)
- ERROR ถ้า domain critical (lotto/wallet) **และ Stability ไม่ใช่ stable** และ > `FRESHNESS_ERROR_DAYS` (default 30 วัน)
- `Stability: stable` → WARN only หลัง `FRESHNESS_ERROR_DAYS` (30 วัน), ไม่มี ERROR
- `Stability: volatile` (หรือไม่ระบุ) → ใช้ threshold ปกติ
- WARN ถ้า doc เกิน `DISCOVERY_MAX_LINES` (default 150 บรรทัด)
- Config: `scripts/docs-validation/config.sh`

### code-doc-map.tsv — sync contract rules ✅
- `lotto_routes_sync`: Lotto routes → system_map.md / lotto_discovery.md
- `lotto_service_sync`: Lotto service → lotto_discovery.md / lotto.md
- `wallet_discovery_sync`: Wallet service → wallet_discovery.md / wallet.md
- `frontend_api_discovery_sync`: FrontendApi route/controller → frontend_api_discovery.md / 07-route-reference.md
- Severity: `[WARN]` (heuristic — human judgment required)

### check-service-discovery-sync.sh ✅ (generic fallback heuristic)
- ตรวจ: ถ้ามี `app/Services/*.php` หรือ `packages/Gametech/*/src/*Service.php` เปลี่ยน
  แต่ไม่มี `docs/internal/03_DOMAINS/*_discovery.md` ถูกแตะ
- Severity: `[WARN]` เท่านั้น — heuristic, ต้องใช้ human judgment
- จับ domain ใหม่ที่ยังไม่มีใน `code-doc-map.tsv`

### quick.sh ✅ (pre-commit fast path)
- รัน: `check-deprecated-docs.sh` + `check-discovery-freshness.sh`
- ใช้เวลา < 5 วินาที
- Setup: `bash scripts/docs-validation/quick.sh`
- ดู comment ในไฟล์สำหรับ git pre-commit hook setup
- **CI enforcement**: ควรรันใน CI pipeline ก่อน merge เสมอ (block PR ถ้า fail)
  ```yaml
  # GitHub Actions example
  - run: bash scripts/docs-validation/quick.sh
  ```

---

## Known Limitations

### Memory Files Cannot Be Auto-Validated
- Memory files อยู่นอก repo (`C:\Users\..\.claude\projects\...\memory\`)
- ไม่สามารถ include ใน `run.sh` หรือ git hooks ได้
- **ผลกระทบ**: memory drift ตรวจไม่ได้โดยอัตโนมัติ
- **Mitigation**: review memory files manually เมื่อมี major domain change
  หรือเมื่อ `project_context.md` / `doc_system.md` อาจ outdated

---

## Pending Validation Checks

### 1. Plans DONE/SUPERSEDED ยังไม่ sync กับ README
- warn/fail ถ้า plan file มี `สถานะ: DONE` แต่ไม่อยู่ใน README section DONE
- ปัจจุบัน `check-plan-metadata.sh` ตรวจ metadata ภายใน file เท่านั้น
- Proposed severity: `[ERROR]`

### 2. Startup policy conflict detection
- warn ถ้า `START_HERE.md`, `startup_digest.md`, `agent_rules.md` มี startup policy conflict
- Proposed severity: `[ERROR]`

### 3. Legacy WARN → tiered debt tracking
- 30 `[WARN]` ปัจจุบัน = legacy underscore filenames ทั้งหมด
- สามารถแยกเป็น "must fix before next release" ได้ถ้าต้องการ strict release gate

---

## Notes

- เพิ่ม script ใหม่ใน `scripts/docs-validation/`
- ต้องใช้ format `[INFO]`/`[WARN]`/`[ERROR]` จาก `lib.sh`
- เพิ่ม config variables ใหม่ใน `config.sh`
- wired ใน `run.sh` และ `quick.sh` ตาม scope ที่เหมาะสม
