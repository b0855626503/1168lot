# Lotto Domain Note

อัปเดตล่าสุด: 2026-04-24

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
- การสร้างงวดอัตโนมัติของ market ใช้ schedule config ใหม่:
  - `draw_schedule_type` = `manual|weekly|monthly`
  - `draw_days` (1-7, จันทร์=1)
  - `draw_dates` (1-31)

## ข้อควรจำ

- เมนู `/lotto/tickets` ฝั่ง admin คือ active-only
- Lotto Navbar Config v1:
  - schema (`lotto_navbars`, `lotto_navbar_items`) อยู่ใน Lotto package เท่านั้น
  - publish model ต่อ `code` ต้องมี active+published ได้สูงสุด 1 row
  - `published_version` เป็น integer monotonic increment ต่อ `code`
- สมาชิกกดยกเลิกเองนับ daily quota แยกจาก admin/system cancel
- manual retry ของทีมงาน bypass auto scheduler retry gating ได้แล้ว
- internal result source `exphuay` มี request budget cap (`LOTTO_EXPHUAY_REQUEST_BUDGET_SECONDS`) เพื่อกัน fallback latency ยาวผิดปกติ
- runtime ของ command `lotto:generate-auto-draws` ใช้ resolver เดียว:
  - `manual` = ไม่สร้างงวดอัตโนมัติ
  - `weekly` ใช้ `dayOfWeekIso`
  - `monthly` ใช้ day-of-month
  - monthly วันที่ที่ไม่มีในเดือนนั้นจะถูก skip ตามธรรมชาติ (ไม่ throw)
- fallback boundary:
  - ถ้า schedule ใหม่หาย/null จะ fallback ไป `draw_mode` เดิม
  - ถ้า schedule ใหม่มีค่าแต่ invalid จะ skip (`invalid_schedule_config`) และไม่ fallback
- rollback safety:
  - ยังเก็บ `draw_mode` ไว้ (ยังไม่ลบในรอบนี้)
  - save path จะ map schedule กลับ legacy `draw_mode` แบบ best-effort (`daily|weekdays|wed_sat_sun|manual`)
- Frontend API `POST /api/v1/lotto/bet` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `lotto_dashboard_risk_snapshot` และ `lotto_result_fetch_logs` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `lotto_tickets` และ `lotto_ticket_items` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `member_lotto_market_policies` จะไม่เขียน audit ลงตาราง `logs`
- การเขียนข้อมูลลง `lotto_result_archives` และ `lotto_result_archive_logs` จะไม่เขียน audit ลงตาราง `logs`

## Result Archive (Read Model)

- `lotto_result_archives` เป็น dedicated read model สำหรับ public result API — ห้ามใช้แทน `lotto_draws`
- Archive identity: `unique(market_code, draw_date, draw_key)`
- `draw_key` เป็น stable public key (three_up, two_down, etc.) — map จาก `bet_type` ภายใน
- `result_set` = `array<string>` เท่านั้น — preserve leading zero
- Mirror: `lotto:mirror-result-archives` command + `MirrorDrawToArchiveJob` (afterCommit, queue `lotto`)
- Fill: `lotto:fill-missing-results` command สำหรับข้อมูลที่ขาดจาก external source
- Reconcile: `lotto:reconcile-result-archive` command — guard ด้วย `--market`, `--from`, `--to`, `--yes`
- FrontendApi: `GET /api/v1/lotto/results/{marketCode}` (public, no auth, throttle 60/min)
- ไม่รวมหวยยี่กี — archive เฉพาะหวยชุด

## Entry Points

- Lotto package: `packages/Gametech/Lotto/src/`
- Frontend lotto controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/LottoController.php`
- Admin routes/controllers: `packages/Gametech/Admin/src/`
- Draw/result jobs/services: `app/Jobs/`, `app/Services/`

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system-current-state/index.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision-log/index.md`
- active plans -> `docs/04_PLANS/README.md`
