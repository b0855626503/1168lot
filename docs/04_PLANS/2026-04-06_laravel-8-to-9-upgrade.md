> สถานะ: ACTIVE
> วันที่: 2026-04-06
> โดเมน/เรื่อง: Platform Upgrade / Laravel 8 to 10 Alignment
> แทนแผนเก่า: -
> อ้างอิง Linear Project: `Laravel 8 to 9 Upgrade` (https://linear.app/boatjunior/project/laravel-8-to-9-upgrade-ec0fee770706)

# Laravel 8 to 10 Upgrade

## Summary

แผนนี้เป็น execution plan สำหรับการอัปเกรดระบบจาก Laravel 8 ไป Laravel 10 บน PHP 8.2 โดยยึดกติกา:

1. `/docs` ใน repo เป็น source of truth
2. Linear เป็น execution mirror เท่านั้น
3. behavior parity ยังเป็น default และทุก intended drift ต้องถูกบันทึกใน docs ก่อน deploy

ผลลัพธ์ของแผนนี้คือให้ execution scope, dependency chain, package replacement decisions, และ gate สำหรับโปรเจกต์อัปเกรดถูกล็อกไว้ใน repo ก่อน แล้วค่อยสะท้อนลง Linear โดยไม่มี source-of-truth ซ้อนกัน

## Decision Locks

### 1) Docs First

- plan ใน repo คือ authoritative plan ของงานนี้
- ถ้า Linear project/document ไม่ตรงกับ repo plan ให้แก้ Linear ตาม repo
- ห้ามใช้ข้อความใน Linear แทน repo plan

### 2) Linear Mirror Only

- Linear ใช้สำหรับ execution tracking, issue breakdown, checklist gate, และ status reporting
- ห้ามเพิ่ม policy หลักใน Linear โดยไม่มีข้อความเดียวกันใน repo plan ก่อน
- checklist ใน Linear ต้องเป็น execution gate ของ plan นี้ ไม่ใช่ source-of-truth แยก

### 3) Docs First, Then Code

- implementation ทำได้เมื่อ repo plan กับ Linear mirror aligned แล้ว
- ทุก code/dependency/runtime change ต้องย้อนกลับมาอัปเดต docs ให้ทันในรอบเดียวกัน
- ห้ามปล่อย code path ใหม่ที่ไม่มี decision หรือ current-state note รองรับเมื่อ behavior เปลี่ยนจริง

### 4) No Execution Before Repo Plan Is Aligned

- ห้ามเริ่ม issue implementation ของโปรเจกต์นี้จนกว่า repo plan, plans index, และ Linear mirror จะสอดคล้องกัน
- ถ้ามี scope ใหม่หรือ intended behavior drift ต้องอัปเดต repo plan ก่อนเสมอ

### 5) Behavior Parity Is Default

- default assumption ของโปรเจกต์นี้คือ behavior parity
- ถ้าจำเป็นต้องยอมรับ behavior drift ต้องมีการบันทึกใน docs อย่าง explicit ก่อนเริ่ม implement
- การที่ระบบ boot ได้หรือ deploy ได้ ไม่ถือว่าผ่าน ถ้า behavior เปลี่ยนโดยไม่ได้อนุมัติ

## Execution Phases

### Phase 1: Baseline Inventory + PHP 8.2 Gap Analysis

- รวบรวม baseline runtime ปัจจุบันของ web, CLI, worker, scheduler, Composer, extensions, และ operational dependencies
- ระบุ environment gaps เทียบ target = Laravel 10 + PHP 8.2
- ล็อก critical business flows และ operational risks ที่ห้าม regression
- Linear mapping:
  - `BOA-68`

### Phase 2: Compatibility Matrix + Package Action Plan

- สร้าง compatibility matrix สำหรับทุก package และ dependency สำคัญ
- จัดประเภท dependency ว่า compatible, upgrade, replace, remove, หรือ blocker
- ล็อก action plan สำหรับ framework/package path โดยไม่เดา version
- Linear mapping:
  - `BOA-69`

### Phase 3: Branch, CI, Staging, Monitoring Readiness

- เตรียม upgrade branch, CI บน PHP 8.2, staging readiness, backup/restore, และ monitoring baseline
- ยืนยันว่า execution environment พร้อมก่อนแตะ framework upgrade จริง
- Linear mapping:
  - `BOA-70`

### Phase 4: Framework / Package Upgrade + Remediation

- ปรับ composer constraints ให้ target Laravel 10 + PHP 8.2 ชัดเจน
- อัป first-party packages ให้ตรง compatibility matrix
- resolve third-party conflicts/replacements
- remediation สำหรับ application code, integration, deprecations, และ runtime warnings
- `codedge/laravel-selfupdater` ถูกอนุมัติให้ถอดออกได้ถ้าเป็น blocker ของ Laravel 10 path โดยต้องบันทึก route/behavior change ใน docs ให้ครบ
- package ที่ abandoned หรือไม่ได้ใช้งานจริงสามารถ remove/replace ได้ ถ้า compatibility matrix ยืนยันว่าไม่เป็น runtime requirement ของระบบ
- local broadcast stack สามารถ replace `beyondcode/laravel-websockets` ด้วย `laravel/reverb` ได้ ถ้ายังคง Pusher-compatible auth/channel/event contract เดิมไว้
- `BOA-79` เป็น remediation follow-up หลัง `BOA-71` และ `BOA-72` เพื่อไม่ให้ deprecation/runtime warning ถูกตีความว่าเป็นงานลอยก่อน upgraded surface พร้อม
- Linear mapping:
  - `BOA-71`
  - `BOA-76`
  - `BOA-77`
  - `BOA-78`
  - `BOA-72`
  - `BOA-79`

### Phase 5: Regression + Behavior Parity Validation

- รัน automated และ manual regression บน staging PHP 8.2
- ยืนยัน parity ของ admin/team panel, Frontend API, queue, และ broadcast
- ใช้ critical flows เดิมเป็น gate ไม่ใช่เพียง smoke test
- Linear mapping:
  - `BOA-73`
  - `BOA-80`
  - `BOA-81`
  - `BOA-82`
  - `BOA-83`

### Phase 6: Deploy Rehearsal + Rollback + Production Rollout

- ซ้อม deploy rehearsal และ rollback ให้ผ่านจริงก่อน production
- ทำ production rollout และ post-release verification ตาม gate ที่ล็อกไว้
- Linear mapping:
  - `BOA-74`
  - `BOA-75`

## Dependency Chain

- `BOA-68` -> blocks `BOA-69`
- `BOA-69` -> blocks `BOA-70`, `BOA-71`
- `BOA-70` -> blocks `BOA-73`, `BOA-74`
- `BOA-71` -> blocks `BOA-72`, `BOA-76`, `BOA-77`, `BOA-78`, `BOA-79`
- `BOA-72`, `BOA-76`, `BOA-77`, `BOA-78`, `BOA-79` -> block `BOA-73`
- `BOA-73` -> blocks `BOA-80`, `BOA-81`, `BOA-82`, `BOA-83`
- `BOA-74`, `BOA-80`, `BOA-81`, `BOA-82`, `BOA-83` -> block `BOA-75`

## Linear Mirror Contract

- project summary และ project description ใน Linear ต้องสรุปจาก plan นี้เท่านั้น
- attached project document `Laravel 8 → 9 Upgrade Checklist (PHP 8.2)` ต้องทำหน้าที่เป็น execution checklist/gate ของแต่ละ phase
- ถ้า repo plan กับ Linear mirror ไม่ตรงกัน ให้ถือว่า Linear stale และต้อง sync ใหม่
- field ที่สื่อ policy, scope, current track, หรือ source-of-truth boundary ห้าม stale เมื่อเทียบกับ repo plan
- ห้ามสร้าง policy ใหม่ใน checklist ที่ไม่มีอยู่ใน plan นี้
- execution metadata เช่น priority, status, labels, และ issue workflow ให้ Linear เป็น owner ได้ ตราบใดที่ไม่ขัดกับ plan นี้

## Acceptance Criteria

1. มี plan นี้ใน `docs/04_PLANS/` และสื่อชัดว่า Laravel 10 เป็น practical execution target ปัจจุบัน
2. `docs/04_PLANS/README.md` อ้าง plan นี้ถูกต้อง
3. phase และ issue mapping ของ Linear สอดคล้องกับ plan นี้
4. Linear project summary/description ไม่ขัดกับ `docs-first`, `linear-mirror-only`, และ target Laravel 10 ปัจจุบัน
5. attached checklist document อ้าง repo plan เป็น source of truth และทำหน้าที่เป็น execution gate เท่านั้น
6. dependency chain ไม่เหลืองาน implementation ที่เริ่มได้ก่อน dependency สำคัญโดยไม่ตั้งใจ โดยเฉพาะ `BOA-79`
7. เอกสารทุกจุดต้องตาม implementation จริงในรอบนี้ และห้ามค้างที่ target Laravel 9 เมื่อ execution target ถูก retarget เป็น Laravel 10 แล้ว

## Assumptions

- ใช้ target runtime เป็น Laravel 10 + PHP 8.2 เป็น practical milestone ปัจจุบัน
- Laravel 12 เป็น aspirational target เท่านั้น และยังไม่ถือว่า approved จนกว่า compatibility matrix จะยืนยันได้ว่า package ecosystem หลักรองรับจริง
- checklist เดิมใน Linear จะถูก retained และเปลี่ยนบทบาทเป็น execution gate
- Linear project อาจยังใช้ชื่อ `Laravel 8 to 9 Upgrade` ชั่วคราวได้ แต่ summary/description/checklist ต้องสะท้อน target implementation ปัจจุบันให้ตรงกับ plan นี้
