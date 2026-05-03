# Start Here (Agent Router)

## Startup Path

อ่านตามลำดับนี้:

1. ไฟล์นี้ — เสร็จแล้ว
2. `.codebase-memory/SUMMARY.md` — ถ้ามี
3. `docs/internal/01_SYSTEM/system_map.md` — แผนที่ domain/package/entrypoint สั้น
4. domain discovery/note ที่เกี่ยวข้องกับงาน 1 ไฟล์ (ดู `docs/internal/03_DOMAINS/`)
5. ทำ Code Discovery ด้วย rg/git grep/IDE/Octocode — ดู `docs/internal/00_RULES/code_discovery_protocol.md`

## Escalate เมื่อ Risk สูง

เปิด `docs/internal/01_SYSTEM/system-current-state/index.md` และ `docs/internal/02_DECISIONS/decision-log/index.md` เมื่อ:

- domain เสี่ยง: wallet / payment / lotto settlement / auto-result / permission / migration / realtime / auth
- task เปลี่ยน architecture หรือ contract
- code ไม่ตรง doc (report mismatch ก่อน implement เสมอ)

## Plan Context

- งาน active/pending แยก domain: `docs/04_PLANS/_current_work.md`
- Full plan index + history: `docs/04_PLANS/README.md`

## Policies & Rules

→ ดู `docs/internal/00_RULES/agent_rules.md` สำหรับกฎทั้งหมด

## กรณีพิเศษ

- งาน MCP / knowledge graph: เพิ่ม `docs/internal/01_SYSTEM/mcp_operating_guide.md`
- ตรวจ retrieval/index: `docs/internal/01_SYSTEM/retrieval_system_status.md`

## Hard Rules

- ห้ามเดาระบบ
- ห้ามใช้ chat history เป็น source of truth
- ถ้า code ไม่ตรง doc ให้รายงานก่อนแก้
- เปลี่ยน behavior แล้วต้องอัปเดต docs + memory + index
