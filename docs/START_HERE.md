# จุดเริ่มต้นสำหรับ Agent

## 📌 Startup แบบประหยัด token

ให้อ่านแค่ core startup set นี้ทุกครั้งก่อน:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`
6. `docs/internal/01_SYSTEM/mcp_operating_guide.md` (เมื่อมีงาน MCP / architecture memory / cross-session context)

ก่อนเปิด docs domain ให้เปิด memory layer ที่เกี่ยวข้องก่อน:

- `.codebase-memory/SUMMARY.md`
- `memory/auth.md` / `memory/payment.md` / `memory/wallet.md` / `memory/game.md`

---

## 🧠 อ่านต่อแบบ on-demand

หลังจากนั้นให้อ่านเฉพาะ domain ที่เกี่ยวข้อง:

- `docs/internal/03_DOMAINS/frontend_api.md`
- `docs/internal/03_DOMAINS/wallet.md`
- `docs/internal/03_DOMAINS/lotto.md`
- `docs/internal/03_DOMAINS/admin_lotto.md`
- `docs/internal/03_DOMAINS/realtime.md`

## ⚡ วิธีหาโค้ดให้เร็วที่สุด (Targeted Lookup)

1. ระบุสิ่งที่กำลังแก้ให้ชัดก่อน: endpoint / command / class / function / event
2. ค้นหาเฉพาะ path ที่เกี่ยวข้อง ไม่ค้นทั้ง repo ตั้งแต่แรก
3. อ่านเฉพาะช่วงบรรทัดที่จำเป็นต่อ task
4. ชี้ path/module/function ที่ใช้งานจริงก่อนตัดสินใจแก้
5. ถ้ายังไม่พอค่อยขยาย scope ทีละขั้น

ตัวอย่าง path เริ่มต้นที่ใช้บ่อย:
- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`
- `packages/Gametech/Lotto/src/`
- `packages/Gametech/Wallet/src/`
- `app/`, `routes/`, `config/`

## 🧭 ค่อยเปิดไฟล์ใหญ่เมื่อจำเป็น

เปิด `system-current-state/index.md` หรือ `decision-log/index.md` เพิ่มเฉพาะเมื่อ:

- งานจะเปลี่ยน behavior จริง
- งานมี state machine / retry / queue / cron / pipeline
- งานแตะ schema rollout / fallback compatibility
- งานเป็น financial / settlement / auth / high-risk
- พบว่า code อาจไม่ตรง doc
- domain note ยังไม่พอ

---

## ⚠️ กติกาสำคัญ

* ห้ามเดาระบบ
* ต้องยึดเอกสารเป็นหลัก
* ถ้า code ไม่ตรง doc → report ก่อน

---

## 🔧 วิธีทำงาน

1. อ่าน core startup
2. อ่าน memory ของ domain ที่เกี่ยวข้อง
3. อ่าน domain note ที่เกี่ยวข้อง
4. ตรวจ plan ที่ active
5. ค่อยอ่าน full docs เฉพาะจุดที่จำเป็น
6. อัปเดต doc + memory + index ทุกครั้งที่มีการเปลี่ยน behavior

---

## ❌ ห้าม

* ห้ามใช้ข้อมูลจาก chat เป็นหลัก
* ห้ามแก้ logic โดยไม่อัปเดต doc
* ห้ามข้ามขั้นตอน
* ห้ามเปิดไฟล์จำนวนมากพร้อมกันโดยไม่มีเหตุผลจาก task
* ห้าม scan ทั้งระบบก่อนทำ targeted lookup
* ห้ามเปิด doc ใหญ่ก่อนโดยยังไม่อ่าน memory layer

---

## 🎯 เป้าหมาย

Agent ต้องสามารถ:

* เริ่มงานได้เร็ว
* ใช้ token ต่ำลง
* ยังรักษา source-of-truth และ decision boundary ได้ครบ

โดยไม่ต้องอ่านแชตย้อนหลัง
