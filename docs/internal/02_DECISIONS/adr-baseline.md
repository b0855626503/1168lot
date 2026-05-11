# ADR Baseline

อัปเดตล่าสุด: 2026-04-05

## จุดประสงค์

เอกสารนี้รวบรวม Architecture Decision Record ชุดแกนกลางที่ควรอ่านก่อนเริ่มงาน
เพื่อให้ agent และทีมงานเข้าใจข้อห้าม, boundary, source of truth, และ trade-off ของระบบโดยเร็ว

แนวทางใช้งาน:
- อ่านไฟล์นี้ก่อนถ้างานมีผลต่อ architecture หรือหลาย domain
- ใช้คู่กับ `decision-log.md`
- ถ้าตัดสินใจใหม่ระดับ architecture ให้เพิ่มใน `decision-log.md` ก่อน แล้วค่อยสรุปเข้ามาที่ไฟล์นี้เมื่อ decision นิ่งแล้ว

## ADR-001: FrontendApi Boundary

- สถานะ: Approved
- Context:
  - `FrontendApi` เป็น BFF สำหรับ frontend
  - package อย่าง Lotto/Wallet ต้องย้ายไปใช้ project อื่นได้โดยไม่พังจากการผูกกับ controller ภายนอก
- Decision:
  - controller ใน `packages/Gametech/FrontendApi` ห้ามเรียก controller ของ package อื่นโดยตรง
  - อนุญาตให้ reuse ผ่าน domain service, repository, query, model, และ service ภายใน `FrontendApi`
- Consequences:
  - โค้ดซ้ำบางส่วนใน `FrontendApi` ยอมรับได้ถ้าช่วยลด coupling
  - ต้องมี regression test เชิงสถาปัตยกรรมคุมข้อนี้

## ADR-002: Customer and Admin Realtime Must Be Separated

- สถานะ: Approved
- Context:
  - event ของทีมงานมี payload เฉพาะ admin UI เช่น badge/datatable metadata
  - ลูกค้าไม่ควร subscribe channel เดียวกับทีมงาน
- Decision:
  - ทีมงานใช้ `{APP_NAME}_events`
  - ฝั่งสมาชิกใช้ `{APP_NAME}_members` และ `{APP_NAME}_members.{member_code}`
  - `FrontendApi` ห้าม expose direct admin events ให้ frontend ลูกค้า
- Consequences:
  - frontend ต้องใช้ realtime config ของตัวเอง
  - event ใหม่ต้องคิดเรื่อง audience ให้ชัดตั้งแต่แรก

## ADR-003: Wallet Transactions Are the Financial Source of Truth

- สถานะ: Approved
- Context:
  - รายการเงินมีหลายประเภท เช่น ฝาก ถอน แทงหวย คืนเงิน ค่าแนะนำ cashback และ admin adjust
  - หน้า financial history ต้องอ่านได้จาก route เดียว
- Decision:
  - ใช้ `wallet_transactions` เป็น source หลักของประวัติการเงินรวม
  - enrich context เพิ่มเฉพาะตอนจำเป็น เช่น Lotto ticket info
- Consequences:
  - งานใหม่ที่กระทบยอดเงินควรเขียน transaction context ให้ครบ
  - ถ้าข้อมูลอยู่เฉพาะตาราง domain โดยไม่มี transaction จะ audit ย้อนหลังยาก

## ADR-004: Lotto Draw Lifecycle Is a Fixed State Machine

- สถานะ: Approved
- Context:
  - งวดหวยมีหลาย flow ทั้ง manual, auto, no-result, retry
  - ถ้า state machine ไม่ชัดจะเกิดสิทธิ์/ปุ่ม/validation ไม่ตรงกัน
- Decision:
  - lifecycle หลักของงวดคือ `draft -> open -> closed -> resulted`
  - การกระทำใน admin และ frontend ต้องอิง state นี้เป็นหลัก
  - `no_result` และ `refunded` เป็น result context ของงวด ไม่ใช่ state ใหม่ของ draw
- Consequences:
  - ปุ่มใน UI และ endpoint validation ต้องเช็ก state ตรงกัน
  - การเพิ่ม state ใหม่ต้องถือเป็น architecture change

## ADR-005: Ticket Cancellation and Refund Must Preserve Audit Context

- สถานะ: Approved
- Context:
  - การยกเลิกโพยมีทั้งสมาชิกยกเลิกเอง, ทีมงานยกเลิก, และระบบคืนเงินทั้งงวด
  - ถ้าเหลือแค่ `status=cancelled` จะ audit ย้อนหลังไม่ได้
- Decision:
  - ต้องเก็บ `cancelled_at`, `cancelled_by`, `refund_amount`, `reason`
  - ผู้ยกเลิกและสาเหตุใช้ `wallet_transactions(ref_type=LOTTO_CANCEL)` เป็น source หลักและ fallback จาก ticket schema เมื่อจำเป็น
  - daily cancel quota นับเฉพาะ self-cancel ของสมาชิก
- Consequences:
  - frontend และ admin report ต้องแสดง cancel context ได้ครบ
  - ช่วง rollout ที่ schema ยังไม่ครบต้องมี fallback compatibility

## ADR-006: Admin Ticket Menu Shows Active Tickets Only

- สถานะ: Approved
- Context:
  - เมนู `/lotto/tickets` ใช้สำหรับงานปฏิบัติการ текущий ไม่ใช่ history รวมทั้งหมด
  - ถ้าเอา `cancelled/resulted` มาปน จะอ่าน badge และ workload ผิด
- Decision:
  - DataTable หลักของ `/lotto/tickets` แสดงเฉพาะ `status=active`
  - badge `lotto_tickets` ต้องนับเฉพาะ active tickets จาก `DashboardController@loadCnt`
  - realtime total ของ `lotto.ticket.list.changed` ต้องมีความหมายเดียวกับ badge นี้
- Consequences:
  - history และ audit ต้องไปดู report/menu อื่น
  - ทุก producer ของ badge/toast ต้องรักษา semantic active-only ให้ตรงกัน

## ADR-007: Lotto Reports Use Immediate Filters and Grouped Market Select

- สถานะ: Approved
- Context:
  - รายงาน Lotto หลายหน้ามี filter ตลาดเหมือนกัน และผู้ใช้ต้องสลับหวยบ่อย
- Decision:
  - filter `market_id` ใช้ grouped `select2` ตามกลุ่มหวย พร้อม logo/icon เมื่อมี
  - filter bar ใช้ immediate apply, ไม่ใช้ปุ่ม `ค้นหา`
- Consequences:
  - controller ของ report ต้องส่ง market options แบบ grouped มาตรฐานเดียวกัน
  - หน้า reset filter ต้อง sync UI state ของ select2 เสมอ

## ADR-008: Profit-Loss Forecast Is a Market+Draw Vue Report

- สถานะ: Approved
- Context:
  - หน้า forecast เดิมแบบ aggregate แถวเดียวไม่ตอบโจทย์การดูความเสี่ยงทั้งงวด
- Decision:
  - ใช้ Vue single-page
  - บังคับเลือก `market_id` และ `draw_id` ก่อนโหลดข้อมูล
  - ส่วนบนเป็น summary matrix ตาม bet type
  - ส่วนล่างเป็น exposure รายหมายเลข
- Consequences:
  - route แยกเป็น page / draw-options / loaddata
  - report นี้เป็น analysis screen ไม่ใช่ DataTable ธรรมดา

## ADR-009: Rollout Compatibility Is Mandatory for Schema Transitions

- สถานะ: Approved
- Context:
  - production หลาย environment อาจ migrate ไม่พร้อมกัน
  - งาน Lotto มี field audit ใหม่เพิ่มต่อเนื่อง
- Decision:
  - ถ้าเพิ่ม schema ที่จำเป็นต่อ flow จริง ต้องมี fallback ชั่วคราวเมื่อทำได้
  - ตัวอย่างเช่น `lotto_tickets.reason` fallback ไป `wallet_transactions.meta.reason`
- Consequences:
  - code ต้องเช็ก schema/table ก่อนอ่านหรือเขียนในช่วง rollout
  - หลัง rollout นิ่งแล้วควรลบ fallback ที่ไม่จำเป็นออก

## ADR-010: Knowledge Graph and ADR Memory Are Acceleration Layers, Not Source of Truth

- สถานะ: Approved
- Context:
  - `codebase-memory-mcp` ช่วย query โค้ดข้าม session ได้เร็วมาก
  - แต่ repo นี้กำหนดว่า `/docs` คือ source of truth
- Decision:
  - ใช้ MCP knowledge graph เป็น acceleration layer สำหรับ search, trace, architecture memory, และ ADR memory
  - ห้ามใช้ MCP memory แทนเอกสารใน `/docs`
  - เมื่อมี architecture decision ใหม่ ต้องเขียนลง docs ก่อนหรือพร้อมกัน แล้วค่อย sync เข้า MCP
- Consequences:
  - งานวิเคราะห์ codebase จะเร็วขึ้นมาก
  - ถ้า docs กับ memory ไม่ตรง ให้ยึด docs ก่อนเสมอ

## ADR-011: Admin `loadCnt` Is the Single Aggregate Source for Lotto Menu Badges

- สถานะ: Approved
- Context:
  - เมนู Lotto ฝั่ง admin หลายหน้าเคยอัปเดต badge จาก query/page-local logic คนละแบบ
  - ถ้าแต่ละเมนูนับเองจะเกิด semantic ไม่ตรงกัน โดยเฉพาะเมนู `รายการโพย`
- Decision:
  - badge และ aggregate count ของเมนู Lotto ฝั่ง admin ต้องยึด `DashboardController@loadCnt` เป็น source กลาง
  - หน้า Lotto ทุกหน้าต้อง trigger `loadCnt` ตอนเข้าเมนู ไม่ว่าหน้านั้นจะเป็น DataTable หรือ Vue/custom page
  - page-local payload เช่น DataTable response มีได้ แต่ห้ามนิยาม badge semantic แข่งกับ `loadCnt`
- Consequences:
  - การเปลี่ยนกติกานับ badge ต้องแก้ที่ `loadCnt` ก่อน
  - หน้าใหม่ของ Lotto ต้องคิดเรื่อง `loadCnt` ตั้งแต่ตอน wiring หน้า ไม่ใช่ค่อยเติมทีหลัง

## ADR-012: FrontendApi Owns Customer-Facing Game, Promotion, and Wheel Endpoints Natively

- สถานะ: Approved
- Context:
  - route ฝั่ง frontend สำหรับ `promotion`, `wheel`, และ flow เกม เป็น BFF contract สำหรับลูกค้า
  - ถ้า `FrontendApi` delegate ไป controller ของ package domain อื่น จะผูก lifecycle และ payload contract เข้ากับ package ภายในมากเกินไป
- Decision:
  - route ลูกค้าใน `FrontendApi` ต้อง implement ใน controller/service ของ `FrontendApi` เอง
  - อนุญาตให้ reuse domain service, repository, query, model, และ transaction/service logic ของ package อื่นโดยตรง
  - ห้ามผูก route contract ของลูกค้าเข้ากับ controller ภายในของ `Wallet`, `Promotion`, `Game`, หรือ package อื่น
- Consequences:
  - `FrontendApi` เป็นที่รวม customer contract และ localization/response mapping
  - การแยก package domain ไปใช้ project อื่นจะทำได้ง่ายขึ้น เพราะไม่ต้องแบก controller dependency จาก BFF

## ADR-013: Wallet Ledger Evolution Must Preserve Append-Only Audit Semantics

- สถานะ: Approved
- Context:
  - งาน wallet ยังมีทั้ง behavior ปัจจุบันและแผน `wallet-ledger-implementation` ที่จะขยายต่อ
  - ถ้า flow ใหม่เขียนยอดเงินโดยไม่มี transaction context จะทำให้ประวัติการเงินรวมและการ audit ย้อนหลังไม่นิ่ง
- Decision:
  - ทุกงานใหม่ที่กระทบ main wallet ต้องถือว่า `wallet_transactions` คือ audit ledger หลัก
  - ห้ามออกแบบ flow ใหม่ที่เปลี่ยน `members.balance` โดยไม่เหลือ transaction row สำหรับอธิบายการเปลี่ยนยอด
  - ระหว่างที่ `WalletMutationService` ยังไม่ถูก rollout ครบทุก flow ให้รักษา append-only semantics ของ `wallet_transactions` และเติม context (`ref_type`, `ref_id`, `group_code`, `meta`) ให้ครบที่สุด
- Consequences:
  - งาน frontend history, refund audit, และ reconcile จะต่อยอดได้จาก ledger กลางชุดเดียว
  - งาน wallet รอบถัดไปต้องดูทั้ง ADR นี้และแผน `2026-03-21_wallet-ledger-implementation.md` ควบกัน

## ลำดับการอ่านที่แนะนำ

1. `ADR-001 FrontendApi Boundary`
2. `ADR-002 Customer/Admin Realtime Separation`
3. `ADR-003 Wallet Transactions Source of Truth`
4. `ADR-004 Lotto Draw Lifecycle`
5. `ADR-005 Ticket Cancellation Audit Context`
6. `ADR-011 Admin loadCnt Aggregate Source`
7. `ADR-013 Wallet Ledger Audit Semantics`

## เกณฑ์ว่าเรื่องไหน “คุ้ม” ที่จะทำ ADR เพิ่ม

- มีผลข้ามมากกว่า 1 domain
- มีข้อห้ามถาวรหรือ boundary ที่ไม่ควรถูกฝ่าฝืน
- ถ้าลืมแล้วจะทำให้ refactor ผิดทาง
- มี rollout/schema trade-off ที่ต้องจำ
- มีโอกาสถูกถามซ้ำหรือถกซ้ำบ่อย
