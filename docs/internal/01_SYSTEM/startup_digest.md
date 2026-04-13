# Startup Digest

อัปเดตล่าสุด: 2026-04-13

ไฟล์นี้เป็น startup core สำหรับ agent
เป้าหมายคือให้เริ่มงานด้วย token ต่ำที่สุด โดยยังไม่เสีย source-of-truth และ boundary สำคัญ

## Core Startup Set

ให้อ่านไฟล์เหล่านี้ทุกครั้งก่อนเริ่มงาน:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`
6. `docs/internal/01_SYSTEM/mcp_operating_guide.md` (เปิดเมื่อมีงาน MCP, knowledge graph, ADR memory)

## สิ่งที่ startup core ต้องจำ

- `/docs` คือ source of truth
- ห้ามใช้ chat history เป็นหลัก
- `FrontendApi` ห้ามเรียก controller ข้าม package
- ลูกค้าและทีมงานต้องแยก realtime channel
- `wallet_transactions` คือ financial source of truth
- `/lotto/tickets` ฝั่ง admin คือ active-only operations view
- งานใหม่ที่เปลี่ยน behavior ต้องอัปเดต doc

## วิธีอ่านต่อแบบประหยัด token

หลังอ่าน core startup แล้ว ให้เลือกอ่านเฉพาะ domain ที่เกี่ยวข้อง:

- งาน `FrontendApi` -> `docs/internal/03_DOMAINS/frontend_api.md`
- งาน `Wallet` / ledger / claim / financial history -> `docs/internal/03_DOMAINS/wallet.md`
- งาน `Lotto` policy / draw / cancel / result -> `docs/internal/03_DOMAINS/lotto.md`
- งาน `Admin Lotto` / report / badge / loadCnt -> `docs/internal/03_DOMAINS/admin_lotto.md`
- งาน `Realtime` -> `docs/internal/03_DOMAINS/realtime.md`
- งาน MCP / architecture memory / cross-session trace -> `docs/internal/01_SYSTEM/mcp_operating_guide.md`

## เมื่อไรต้องเปิดไฟล์ใหญ่

ให้เปิด `system_current_state.md` หรือ `decision_log.md` เพิ่มเฉพาะเมื่อ:

- งานจะเปลี่ยน behavior จริง
- งานแตะหลาย domain และ domain note ไม่พอ
- งานมี state machine / retry / queue / cron / pipeline
- งานมี schema rollout หรือ fallback compatibility
- พบว่า code กับ doc อาจไม่ตรงกัน
- เป็นงาน high-risk เช่น financial, settlement, refund, auth

## ลำดับ escalation ที่แนะนำ

1. อ่าน core startup
2. อ่าน domain note ที่เกี่ยวข้อง
3. อ่าน plan ที่ active ของ domain นั้น
4. ค่อยเปิด section ที่เกี่ยวข้องใน `system_current_state.md`
5. ค่อยเปิด decision ใน `decision_log.md` เฉพาะหัวข้อที่เกี่ยวข้อง

## เป้าหมาย

- งานเล็ก: ไม่ควรเสีย startup token เกินจำเป็น
- งานกลาง: อ่านเพิ่มเฉพาะ domain
- งานใหญ่: ค่อยขยายไป full docs ตาม risk
