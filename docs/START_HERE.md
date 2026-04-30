# จุดเริ่มต้นสำหรับ Agent

## Startup 60 วินาที

อ่านตามลำดับนี้เท่านั้น:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`

กรณีงาน MCP/knowledge graph ค่อยเพิ่ม:
- `docs/internal/01_SYSTEM/mcp_operating_guide.md`

## Memory First

ก่อนเปิด doc ใหญ่ ให้อ่าน:

- `.codebase-memory/SUMMARY.md`
- `memory/<domain>.md` ที่เกี่ยวข้อง

ถ้าต้องตรวจ retrieval/index:
- `docs/internal/01_SYSTEM/retrieval_system_status.md`

## อ่านต่อเฉพาะที่เกี่ยวข้อง

- `docs/internal/03_DOMAINS/frontend_api.md`
- `docs/internal/03_DOMAINS/wallet.md`
- `docs/internal/03_DOMAINS/lotto.md`
- `docs/internal/03_DOMAINS/admin_lotto.md`
- `docs/internal/03_DOMAINS/realtime.md`

## เปิดไฟล์ใหญ่เมื่อมีสัญญาณเสี่ยง

เปิด `system-current-state/index.md` หรือ `decision-log/index.md` เมื่อ:

- งานเปลี่ยน behavior จริง
- งานแตะ flow เสี่ยง (financial/auth/retry/queue/cron/schema)
- domain note ไม่พอหรือ code ไม่ตรง doc

## กติกาที่ห้ามพลาด

- ห้ามเดาระบบ
- ห้ามใช้ chat เป็น source of truth
- ถ้า code ไม่ตรง doc ให้รายงานก่อนแก้
- เปลี่ยน behavior แล้วต้องอัปเดต docs + memory + index
