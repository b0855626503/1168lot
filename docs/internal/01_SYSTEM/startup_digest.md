# Startup Digest

อัปเดตล่าสุด: 2026-04-13

ไฟล์นี้คือ startup core สำหรับ agent
เป้าหมาย: เริ่มงานเร็ว ใช้ token ต่ำ และไม่เสีย source-of-truth

## Core Startup Set

ให้อ่านไฟล์เหล่านี้ทุกครั้งก่อนเริ่มงาน:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`
6. `docs/internal/01_SYSTEM/mcp_operating_guide.md` (เปิดเมื่อมีงาน MCP, knowledge graph, ADR memory)

## Memory First (บังคับ)

ให้อ่าน memory layer ก่อนเสมอ:

- `.codebase-memory/SUMMARY.md`
- `memory/auth.md`, `memory/payment.md`, `memory/wallet.md`, `memory/game.md` (เลือกเฉพาะ domain ที่เกี่ยวข้อง)

ถ้าต้องตรวจสถานะ retrieval/memory/index ให้เปิด:
- `docs/internal/01_SYSTEM/retrieval_system_status.md`

ถ้า memory ไม่พอ ค่อยเปิด docs ตามลำดับด้านล่าง

## Domain On-Demand

- `FrontendApi` -> `docs/internal/03_DOMAINS/frontend_api.md`
- `Wallet` -> `docs/internal/03_DOMAINS/wallet.md`
- `Lotto` -> `docs/internal/03_DOMAINS/lotto.md`
- `Admin Lotto` -> `docs/internal/03_DOMAINS/admin_lotto.md`
- `Realtime` -> `docs/internal/03_DOMAINS/realtime.md`

## Escalation (เฉพาะจำเป็น)

เปิด `system-current-state/index.md` หรือ `decision-log/index.md` เมื่อ:

- งานเปลี่ยน behavior จริง
- งานแตะ flow เสี่ยง: financial/auth/retry/queue/cron/schema
- domain note ไม่พอ หรือ code อาจไม่ตรง doc

## ลำดับ escalation ที่แนะนำ

1. อ่าน core startup
2. อ่าน memory ของ domain
3. อ่าน plan ที่ active ของ domain นั้น
4. อ่าน domain note ที่เกี่ยวข้อง
5. ค่อยเปิดไฟล์ใหญ่เฉพาะ section ที่จำเป็น

## Targeted Lookup Playbook

1. แปลง task เป็น keyword ที่ค้นหาได้จริง (route name, class, method, event, table)
2. รัน `rg` แบบเจาะ path ที่เกี่ยวข้องก่อน (เช่นเฉพาะ package/domain)
3. อ่านเฉพาะ block โค้ดที่เกี่ยวข้องด้วย `sed -n start,end`
4. เก็บหลักฐานเป็น path + function ที่แตะ behavior จริง
5. หยุดอ่านทันทีเมื่อได้ context เพียงพอ ไม่เปิดไฟล์เพิ่มโดยไม่จำเป็น

## สิ่งที่ต้องจำตลอด

- `/docs` คือ source of truth
- ห้ามใช้ chat history เป็นหลัก
- ถ้า code ไม่ตรง doc ให้ report ก่อน
- งานที่เปลี่ยน behavior ต้องอัปเดต doc/memory/index ให้ครบ
