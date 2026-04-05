> สถานะ: PENDING
> วันที่: 2026-03-31
> โดเมน/เรื่อง: Lotto / Group Package System (Pre-Implementation Readiness)
> แทนแผนเก่า: -

# Lotto Group Package System — Readiness Plan

## Summary

แผนนี้ใช้สำหรับ lock สเปกใน Linear ให้ครบระดับ implementable ก่อนเริ่มลงมือเขียนโค้ดจริง โดยเน้น 4 เรื่องหลัก:

1. helper API contract (`select-package`, `selected-package`)
2. helper boundary (non-authoritative, ห้าม bypass betting validation)
3. error contract + HTTP status mapping กลาง
4. snapshot ownership และ minimum fields ของ `ticket_item`

หลังผ่าน gate นี้เท่านั้น จึงเริ่ม implementation ตาม dependency chain ได้

## Decision Locks

### 1) Helper API Response + Idempotency

- `POST /groups/{groupId}/select-package`
  - success: `HTTP 200`
  - response: `data { group_id, package_id, selected: true }`
  - ยิงซ้ำ package เดิม: `HTTP 200` response shape เดิม (idempotent)
- `GET /groups/{groupId}/selected-package`
  - มีการเลือกแล้ว: `HTTP 200` + top-level `selected: true`
  - payload `data` ต้องมีอย่างน้อย:
    - `group_id`
    - `package_id`
    - `name`
    - `image`
    - `bet_settings[] { bet_type, payout, discount_percent }`
  - ยังไม่เลือก: `HTTP 200` + `data: null` + `selected: false`

### 2) Helper Boundary + Mismatch Policy

- helper APIs เป็น flow-assist เท่านั้น
- helper state ต้องไม่เป็น authoritative server-side state
- submit bet ต้อง validate จาก `package_id` ใน request เท่านั้น
- ถ้า helper state ไม่ตรงกับ package_id ใน bet request ให้ยึดค่าใน bet request เสมอ

### 3) Error Contract (Business Code + HTTP)

- `PACKAGE_REQUIRED` -> `HTTP 400`
- `PACKAGE_NOT_IN_GROUP` -> `HTTP 400`
- `PACKAGE_INACTIVE` -> `HTTP 409`
- `BET_TYPE_NOT_CONFIGURED` -> `HTTP 422`

ต้องใช้ mapping เดียวกันทุกจุดใน BOA-55/56/57 และ validation path ที่เกี่ยวข้อง

### 4) Snapshot Ownership (Authoritative Point)

- เก็บ authoritative snapshot ที่ `ticket_item` เท่านั้น
- minimum fields:
  - `package_id`
  - `package_name`
  - `bet_type`
  - `payout`
  - `discount_percent`
  - `calculated_values_at_bet_time`
- `calculated_values_at_bet_time` อย่างน้อยต้องมี:
  - `bet_amount`
  - `discount_amount`
  - `net_amount`
  - `payout_amount`

## Linear Dependency Chain (Execution Order)

- `BOA-46` -> blocks `BOA-47`, `BOA-48`
- `BOA-47`, `BOA-48` -> block `BOA-49`
- `BOA-49` -> blocks `BOA-55`, `BOA-56`, `BOA-57`
- `BOA-50`, `BOA-51` -> block `BOA-52`
- `BOA-52`, `BOA-53`, `BOA-54`, `BOA-55`, `BOA-56`, `BOA-57` -> block `BOA-58`
- ทุกงานด้านบน -> block `BOA-59`

## Test Plan

- Contract tests: business error code + HTTP status ต้องตรง mapping
- Betting validation tests:
  - ไม่มี `package_id` -> reject
  - package คนละ group -> reject
  - package inactive -> reject
  - ไม่มี bet_type config ใน package -> reject
- Helper behavior tests:
  - helper state ห้าม bypass betting validation
  - select-package ยิงซ้ำต้อง idempotent
- Snapshot tests:
  - `ticket_item` เก็บครบ minimum fields
  - snapshot ต้องไม่เปลี่ยนย้อนหลังเมื่อ package ถูกแก้

## Definition of Done

- Linear issues ที่เกี่ยวข้องถูก harden ครบตาม decision locks
- dependency relations ถูกตั้งครบตาม chain
- plan นี้ถูกลงใน `docs/04_PLANS` และถูกชี้เป็น `ACTIVE`
- API contract ถูกล็อกก่อนเริ่มโค้ด
- implementation ต้องเดินตาม dependency chain เท่านั้น

## Assumptions

- helper selected state เป็น non-authoritative เสมอ
- ไม่มี persistent selected package model ระดับ member/group
- เฟสนี้ยังไม่ทำ package versioning (ใช้ snapshot ตอนแทง)
