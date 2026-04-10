# จุดเริ่มต้นสำหรับ Agent

## 📌 Startup แบบประหยัด token

ให้อ่านแค่ core startup set นี้ทุกครั้งก่อน:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`

---

## 🧠 อ่านต่อแบบ on-demand

หลังจากนั้นให้อ่านเฉพาะ domain ที่เกี่ยวข้อง:

- `docs/internal/03_DOMAINS/frontend_api.md`
- `docs/internal/03_DOMAINS/wallet.md`
- `docs/internal/03_DOMAINS/lotto.md`
- `docs/internal/03_DOMAINS/admin_lotto.md`
- `docs/internal/03_DOMAINS/realtime.md`

## 🧭 ค่อยเปิดไฟล์ใหญ่เมื่อจำเป็น

เปิด `system_current_state.md` หรือ `decision_log.md` เพิ่มเฉพาะเมื่อ:

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
2. อ่าน domain note ที่เกี่ยวข้อง
3. ตรวจ plan ที่ active
4. ค่อยอ่าน full docs เฉพาะจุดที่จำเป็น
5. อัปเดต doc ทุกครั้งที่มีการเปลี่ยน behavior

---

## ❌ ห้าม

* ห้ามใช้ข้อมูลจาก chat เป็นหลัก
* ห้ามแก้ logic โดยไม่อัปเดต doc
* ห้ามข้ามขั้นตอน

---

## 🎯 เป้าหมาย

Agent ต้องสามารถ:

* เริ่มงานได้เร็ว
* ใช้ token ต่ำลง
* ยังรักษา source-of-truth และ decision boundary ได้ครบ

โดยไม่ต้องอ่านแชตย้อนหลัง
