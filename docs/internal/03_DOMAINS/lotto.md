# Lotto Domain Note

อัปเดตล่าสุด: 2026-04-06

## ใช้อ่านเมื่อ

- แตะ draw lifecycle
- แตะ result / no-result / refund
- แตะ ticket cancel / result policy
- แตะ auto-result / retry / scheduler / parser

## กติกาหลัก

- draw lifecycle หลัก: `draft -> open -> closed -> resulted`
- `no_result` และ `refunded` เป็น result context ไม่ใช่ draw state ใหม่
- cancel/refund ต้องเก็บ audit context ให้ครบ
- งาน auto-result เป็น high-risk; ถ้างานแตะ retry/backoff/exhausted ให้เปิด full docs เพิ่ม
- market สามารถตั้ง `auto_refund_on_no_result` เพื่อให้ no-result คืนเงินทั้งงวดอัตโนมัติได้

## ข้อควรจำ

- เมนู `/lotto/tickets` ฝั่ง admin คือ active-only
- สมาชิกกดยกเลิกเองนับ daily quota แยกจาก admin/system cancel
- manual retry ของทีมงาน bypass auto scheduler retry gating ได้แล้ว
- internal result source `exphuay` มี request budget cap (`LOTTO_EXPHUAY_REQUEST_BUDGET_SECONDS`) เพื่อกัน fallback latency ยาวผิดปกติ
- Frontend API `POST /api/v1/lotto/bet` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `lotto_dashboard_risk_snapshot` และ `lotto_result_fetch_logs` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `lotto_tickets` และ `lotto_ticket_items` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `member_lotto_market_policies` จะไม่เขียน audit ลงตาราง `logs`

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision_log.md`
- active plans -> `docs/04_PLANS/README.md`
