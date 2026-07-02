# ADR Index By Domain

อัปเดตล่าสุด: 2026-04-05

ไฟล์นี้เป็นทางลัดสำหรับหา decision สำคัญตาม domain
ให้ใช้คู่กับ `adr-baseline.md` และ `decision-log.md`

## วิธีใช้

- ถ้างานข้ามหลาย domain: อ่าน `adr-baseline.md` ก่อน
- ถ้างานอยู่ใน domain เดียว: เปิดหัวข้อของ domain นั้นก่อน
- ถ้า decision ยังไม่พอ: ตามต่อใน `decision-log.md`

## FrontendApi

- `ADR-001 FrontendApi Boundary`
  - ห้ามเรียก controller ข้าม package
- `ADR-002 Customer and Admin Realtime Must Be Separated`
  - realtime contract ของลูกค้า/ทีมงานต้องแยกกัน
- `ADR-003 Wallet Transactions Are the Financial Source of Truth`
  - financial history รวมต้องยึด ledger กลาง
- `ADR-012 FrontendApi Owns Customer-Facing Game, Promotion, and Wheel Endpoints Natively`
  - route ลูกค้าต้อง map contract ใน `FrontendApi` เอง

เหมาะกับงาน:
- ทำ route ใหม่ใน `FrontendApi`
- refactor BFF
- ออกแบบ payload ให้ frontend
- แก้ flow `promotion/wheel/game`

## Lotto Core

- `ADR-004 Lotto Draw Lifecycle Is a Fixed State Machine`
- `ADR-005 Ticket Cancellation and Refund Must Preserve Audit Context`
- `ADR-009 Rollout Compatibility Is Mandatory for Schema Transitions`

เหมาะกับงาน:
- draw status / action flow
- cancel/refund/result policy
- migration ที่แตะ schema หวย

## Admin Lotto Operations

- `ADR-006 Admin Ticket Menu Shows Active Tickets Only`
- `ADR-007 Lotto Reports Use Immediate Filters and Grouped Market Select`
- `ADR-008 Profit-Loss Forecast Is a Market+Draw Vue Report`
- `ADR-011 Admin loadCnt Is the Single Aggregate Source`
  - badge เมนู Lotto ต้องยึด `loadCnt`

เหมาะกับงาน:
- เมนู `/lotto/tickets`
- badge/realtime ของทีมงาน
- report UI / DataTable / Vue report
- wiring หน้าใหม่ของ Lotto ใน admin

## Wallet / Financial Audit

- `ADR-003 Wallet Transactions Are the Financial Source of Truth`
- `ADR-005 Ticket Cancellation and Refund Must Preserve Audit Context`
- `ADR-009 Rollout Compatibility Is Mandatory for Schema Transitions`
- `ADR-013 Wallet Ledger Evolution Must Preserve Append-Only Audit Semantics`
  - flow ใหม่ต้องเหลือ ledger context เสมอ

เหมาะกับงาน:
- ประวัติการเงิน
- refund / ledger / audit
- reconcile ข้อมูลข้ามตาราง
- งานต่อจากแผน `wallet-ledger-implementation`

## Realtime / Notifications

- `ADR-002 Customer and Admin Realtime Must Be Separated`
- `ADR-006 Admin Ticket Menu Shows Active Tickets Only`

เหมาะกับงาน:
- broadcast event ใหม่
- channel policy
- badge / toast semantics

## Knowledge Graph / MCP

- `ADR-010 Knowledge Graph and ADR Memory Are Acceleration Layers, Not Source of Truth`

เหมาะกับงาน:
- ใช้ `boat_ask` สำหรับ code intelligence + memory
- สร้าง architecture memory
- สรุป decision ข้าม session

## ลำดับอ่านเร็วที่สุดตามชนิดงาน

1. งาน FrontendApi:
   - `adr-baseline.md` เฉพาะ `ADR-001` ถึง `ADR-003`, `ADR-012`
2. งาน Lotto policy:
   - `ADR-004` ถึง `ADR-006`
3. งานรายงาน Lotto:
   - `ADR-006` ถึง `ADR-008`, `ADR-011`
4. งาน schema rollout:
   - `ADR-005` และ `ADR-009`
5. งาน MCP / architecture:
   - `ADR-010`
6. งาน wallet/reconcile:
   - `ADR-003`, `ADR-013`, แล้วตามด้วย `2026-03-21_wallet-ledger-implementation.md`
