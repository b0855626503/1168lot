# LOTTO Global Config Migration Plan

## ภาพรวม

เอกสารนี้ใช้เป็นแผนงานสำหรับปรับโมดูล `packages/Gametech/Lotto` จากโครงสร้างเดิมที่มี **member-specific configuration / override** ไปเป็นโครงสร้างใหม่แบบ **global configuration only**

เป้าหมายคือให้กติกาการแทงของหวยทุกประเภท เช่น
- อัตราจ่าย
- ขั้นต่ำ
- สูงสุด
- สูงสุดต่อเลข

ถูกกำหนดจาก config กลางเพียงชุดเดียว และเมื่อมีการเพิ่มหรือแก้ไข จะมีผลตาม policy ของระบบกับสมาชิกทุกคน โดย **ไม่รองรับ override รายสมาชิกอีกต่อไป**

เอกสารนี้ต่อยอดจากแผนเดิมของ Lotto และต้องถือว่า cleanup ฝั่ง Concord/Proxy เป็นงานรองจาก change เชิงโดเมนรอบนี้ ไม่ใช่แกนหลักของงานนี้

อ้างอิงแผนเดิม:
- `docs/04_PLANS/2026-03-21_lotto-concord-proxy-cleanup.md`
- `docs/04_PLANS/2026-03-21_lotto-execution-phases.md`
- `docs/04_PLANS/2026-03-20_lotto-system-roadmap.md`

---

## เป้าหมายหลัก

1. ตัด member-specific override ออกจาก runtime path ทั้งหมด
2. ทำให้ระบบใช้ single source of truth สำหรับกติกาการแทง
3. รักษาเสถียรภาพของ flow สำคัญ:
  - bet
  - exposure
  - draw lifecycle
  - settlement
4. rollout แบบปลอดภัย โดยไม่ drop ของเดิมทันที
5. ทำให้ admin, API, และ service layer สอดคล้องกับ model ใหม่ทั้งหมด

---

## สิ่งที่ไม่อยู่ใน scope

- ไม่ redesign bet type ใหม่
- ไม่เปลี่ยน payout formula หลัก
- ไม่เปลี่ยน transaction/concurrency strategy หลัก
- ไม่ refactor Concord/Proxy เชิงลึกในรอบเดียวกับ domain rewrite นี้
- ไม่เปลี่ยน external interface เดิมโดยไม่จำเป็น

---

## สรุป Domain Model ใหม่

### ของเดิม
ระบบเดิมมีแนวคิดประมาณนี้

- default/global config
- rate plan
- member permission
- member override
- การ resolve config ตอนแทงแบบหลายชั้น เช่น member > plan > default

### ของใหม่
ระบบใหม่ต้องเหลือแค่

- `LotteryGroup`
- `LotteryMarket`
- `MarketBetSetting` หรือ equivalent global setting
- `DrawBetSettingSnapshot`

### Target Model

```text
LotteryGroup
  └── LotteryMarket
        └── GlobalBetSetting
              - payout
              - min_bet
              - max_bet
              - max_per_number
```

Draw Snapshot:

```text
LottoDraw
  └── DrawBetSettingSnapshot
        - payout
        - min_bet
        - max_bet
        - max_per_number
```

### หลักการสำคัญ

- ทุก member ใช้กติกาเดียวกัน
- ไม่มี per-member rate/min/max/max_per_number
- ถ้ายังมี member permission บางแบบ ต้องแยกให้ชัดว่าเป็นแค่ allow/deny การใช้งาน ไม่ใช่ config override

---

## Business Rules ใหม่ที่ต้องยึด

### Rule 1 — Global Config Only
การ validate ตอนแทงต้องอิง global market config หรือ draw snapshot เท่านั้น

### Rule 2 — No Member Override
ต้องไม่มี logic ประเภทนี้ใน runtime path
- `resolveMemberRatePlan()`
- `resolveMemberBetSetting()`
- `resolveMemberPermissionOverride()`
- fallback chain แบบ member > plan > default

### Rule 3 — Draw Snapshot Policy
**แนะนำให้ใช้แบบ production-safe:**
- config ที่ admin แก้ใน master มีผลกับ **งวดใหม่**
- งวดที่เปิดแล้วใช้ snapshot ตอนเปิดงวด

เหตุผล:
- กัน dispute ระหว่าง ticket ก่อนแก้/หลังแก้
- ทำให้ทั้งงวดใช้กติกาชุดเดียวกัน
- settlement/reconciliation ชัด

### Rule 4 — Validation Order ต้องตายตัว
ลำดับ validation ตอนแทงต้องคงเป็น:
1. draw เปิดอยู่
2. market เปิดใช้งาน
3. bet type ถูกต้อง
4. number block
5. min/max validation
6. max_per_number validation
7. exposure validation
8. persist ticket/item และ update exposure

### Rule 5 — Transaction Safety ห้ามถอยหลัง
ต้องคง invariant เดิมของ flow เสี่ยง race condition ไว้
- `DB::transaction`
- `lockForUpdate`
- atomic exposure update

---

## ผลกระทบเชิงสถาปัตยกรรม

งานนี้ไม่ใช่ CRUD change ธรรมดา แต่เป็น **domain simplification** ของ Lotto

### ผลดี
- ลดความซับซ้อนของ config resolution
- ลด admin surface area
- ลดจุดที่ config ไม่ตรงกัน
- maintenance ง่ายขึ้น
- behavior คาดเดาได้มากขึ้น

### ผลเสีย/สิ่งที่ต้องรับรู้
- สมาชิกที่เคยได้ค่าพิเศษจะหายทันที
- flexibility ด้านการทำโปรเฉพาะคนหายไป
- ถ้าตัด logic ไม่ครบจะเกิด ghost path/bug ได้ง่าย

---

## ขอบเขตที่กระทบ

### 1) Database / Schema
ตารางหรือโมเดลที่คาดว่าจะเกี่ยวข้อง
- `lotto_market_bet_settings`
- `lotto_rate_plans`
- `lotto_rate_plan_items`
- `member_lotto_permissions`
- `member_rate_plans` หรือ table mapping รายสมาชิกที่เกี่ยวข้อง
- draw snapshot tables

### 2) Service Layer
กระทบแน่นอน
- `BetService`
- `ExposureService`
- `DrawService`
- `SettlementService` (ฝั่งที่อ่าน payout/settings snapshot)
- `MemberMarketPolicyService` หรือ equivalent service
- helper/repository ที่ resolve member-specific config

### 3) Admin Module
เมนูที่ต้องทบทวน
- `member_permissions`
- `member_rate_plans`
- หน้าจอ assign rate plan/member override
- หน้าจอแก้ limit รายสมาชิก
- หน้าจอ market/global settings

### 4) API / Member Flow
- draws list/detail
- place bet
- ticket history/detail
- API metadata ที่แสดง rules/config

### 5) Reporting / Exposure / Settlement
- exposure limit ต้องอ่านค่าจาก global only
- settlement ต้องใช้ snapshot ที่ถูกต้อง
- reports ต้องสะท้อน model ใหม่

---

## Decision ที่ต้อง lock ก่อนเริ่ม implement

ก่อนลงมือ ทีมต้องยืนยันเรื่องนี้ให้ชัด

### D1 — ระดับของ global config
ต้องยืนยันว่าค่า payout/min/max/max_per_number อยู่ระดับไหน

แนะนำ:
- ระดับ `market + bet_type`

เพราะกติกามักต่างกันตามประเภทเดิมพัน

### D2 — การมีผลของ config ใหม่
ต้องเลือกทางเดียว

#### ทางเลือก A — มีผลเฉพาะงวดใหม่ (แนะนำ)
- เปิดงวดแล้ว snapshot ค่าไว้
- แก้ master วันนี้ มีผลตอนเปิดงวดใหม่

#### ทางเลือก B — มีผลทันทีแม้งวดเปิดอยู่
- เสี่ยง dispute สูง
- ต้องตอบให้ได้ว่าตั๋วเก่า/ใหม่ใช้อะไร
- ไม่แนะนำสำหรับ production

### D3 — member permission ยังเหลือไหม
ต้องตอบให้ชัดว่า
- ตัด member permission ทิ้งทั้งหมด
  หรือ
- ยังมี member permission แบบ allow/deny อย่างเดียว

ถ้ายังมี allow/deny อยู่ แปลว่ายังมี member policy layer อยู่บางส่วน แต่ **ต้องไม่เกี่ยวกับ rate/limit/payout**

---

## หลักการ rollout

ห้ามทำแบบนี้:
- drop table เก่าทันที
- rewrite service ทั้งหมดใน release เดียว
- ลบ admin menu โดยไม่เผื่อ transition

ต้องทำแบบ phased migration เท่านั้น

---

## Execution Plan แบบครบถ้วน

## Phase 0 — Audit & Baseline Freeze

### วัตถุประสงค์
ตรึง baseline และทำ impact discovery ให้ครบก่อนแก้ logic

### งาน
1. สำรวจทุกจุดใน `packages/Gametech/Lotto` ที่ยังใช้ member-specific logic
2. ทำ config resolution map ปัจจุบัน ว่า field ไหนอ่านจากไหน
3. ทำ affected file list ของ service/controller/model/repository/view/route/test
4. ยืนยัน baseline tests ของ Lotto ให้ผ่านทั้งหมด
5. ยืนยัน route/admin/api scaffold ที่มีอยู่ไม่ regress

### Deliverables
- impact map
- current config resolution map
- affected file list
- baseline test report

### Acceptance Criteria
- รู้ครบว่าจุดไหนมี member override
- baseline tests ผ่านก่อนเริ่ม phase ถัดไป

### หมายเหตุ
phase นี้ต้องยึดแนวคิด guardrail/baseline ตามแผน cleanup เดิมก่อนเสมอ

---

## Phase 1 — Finalize Domain Contract v2

### วัตถุประสงค์
ล็อก business rule ใหม่ให้ชัด 100% ก่อนแตะ schema/service

### งาน
1. ยืนยัน source of truth ใหม่ของ payout/min/max/max_per_number
2. ยืนยันระดับ config: market หรือ market+bet_type
3. ยืนยัน draw snapshot policy
4. ยืนยันว่า member permission ยังมีหรือไม่
5. ยืนยันว่าไม่มี fallback chain อื่นนอกจาก global/snapshot
6. สรุปผลกระทบต่อ admin, API, tests, reports

### Deliverables
- domain contract v2
- setting precedence ใหม่
- draw snapshot policy ใหม่
- business rule sign-off

### Acceptance Criteria
- ไม่มีจุดคลุมเครือเรื่อง “แก้ค่าแล้วมีผลเมื่อไร”
- backend/admin/qa ใช้กติกาเดียวกัน

---

## Phase 2 — Schema Preparation (Backward-Compatible)

### วัตถุประสงค์
เตรียม schema ให้รองรับ global-only model โดยยังไม่ทำของเดิมพัง

### งาน
1. ตรวจ table global setting ว่าเก็บ field ครบหรือยัง
2. ถ้ายังไม่ครบ ให้เพิ่ม field ที่จำเป็น
  - `payout`
  - `min_bet`
  - `max_bet`
  - `max_per_number`
3. ตรวจ draw snapshot table ว่ารองรับ field ชุดเดียวกันครบหรือยัง
4. mark tables/member mapping ที่จะเลิกใช้เป็น deprecated
5. migration ทุกตัวต้อง backward-compatible
6. ยังไม่ drop table/member data ใน phase นี้

### Deliverables
- schema update migrations
- deprecated schema note
- migration impact note

### Acceptance Criteria
- schema ใหม่พร้อมให้ service ใช้งาน
- deploy ได้โดยยังไม่เปลี่ยน behavior production ทันที

---

## Phase 3 — Implement Global Config Resolver

### วัตถุประสงค์
ทำ abstraction กลางสำหรับ config resolution ใหม่

### งาน
1. สร้างหรือปรับ helper/service เช่น
  - `resolveMarketBetSetting()`
  - `resolveDrawBetSnapshot()`
2. ปรับ read path ให้เรียก resolver กลางนี้แทนการอ่านหลายแหล่ง
3. ตัด read path ที่ผูกกับ member override ออกทีละส่วน
4. หลีกเลี่ยงการกระจาย logic ซ้ำใน controller/service หลายจุด

### ไฟล์หลักที่ควรตรวจ
- `BetService`
- `DrawService`
- `ExposureService`
- helper/repository ที่เกี่ยวข้องกับการอ่าน setting

### Deliverables
- unified config resolver
- read path alignment patch

### Acceptance Criteria
- config ถูกอ่านจาก source เดียว
- ไม่มี member-based config read หลงเหลือใน read path สำคัญ

---

## Phase 4 — Draw Snapshot Alignment

### วัตถุประสงค์
ทำให้ draw lifecycle สอดคล้องกับ global config model ใหม่

### งาน
1. ปรับ `DrawService` ให้ snapshot ค่า global config ลง draw ตอนเปิดงวด
2. ยืนยันว่า snapshot เก็บ field ครบ
3. ปรับ `BetService` ให้ใช้ draw snapshot เป็นหลัก ไม่อ่าน master สดมั่วๆ
4. เพิ่ม validation กรณี snapshot ไม่ครบ/null
5. ปรับ admin draw flow ให้มองเห็น snapshot ที่ใช้จริง

### Deliverables
- draw snapshot v2
- aligned draw open flow
- draw config visibility note

### Acceptance Criteria
- ตั๋วทุกใบในงวดเดียวกันใช้ config เดียวกัน
- แก้ master หลังเปิดงวดไม่กระทบงวดที่เปิดไปแล้ว

---

## Phase 5 — Bet Flow Rewrite (Core Change)

### วัตถุประสงค์
ย้าย flow แทงทั้งหมดให้ใช้ global-only policy

### งาน
1. ตัด member-specific resolution ออกจาก `BetService`
2. rewrite validation ให้เหลือแค่ global/snapshot rule
3. รักษา order validation เดิมตาม invariant ของระบบ
4. คง transaction + lockForUpdate ใน flow เสี่ยง oversell/race condition
5. รักษา persistence contract เดิมของ ticket/item ให้มากที่สุด
6. remove query หรือ lookup ที่ผูกกับ member override ออกจาก critical path

### Deliverables
- new BetService behavior
- updated validation flow
- runtime path simplification

### Acceptance Criteria
- การแทงใช้ global/snapshot config เท่านั้น
- concurrency tests และ exposure tests ยังผ่าน
- ไม่เกิด regression กับ settlement downstream

---

## Phase 6 — Admin Module Simplification

### วัตถุประสงค์
ทำให้หลังบ้านสะท้อน model ใหม่ และตัด UI ที่ไม่ใช้แล้ว

### งาน
1. ปรับเมนู config ให้เหลือเฉพาะ global setting flow
2. ปิด/ซ่อน/redirect เมนูที่เลิกใช้
  - `member_permissions`
  - `member_rate_plans`
  - member override pages
3. ปรับ form ของ market setting ให้แก้ค่าหลักได้ครบ
  - payout
  - min_bet
  - max_bet
  - max_per_number
4. เพิ่ม warning/help text ว่าค่านี้มีผลตาม snapshot policy ใหม่
5. ปรับ controller / DataTable / Transformer / views ที่เกี่ยวข้อง

### Deliverables
- simplified admin config flow
- deprecated menu handling
- updated admin UX copy

### Acceptance Criteria
- admin เห็นเฉพาะฟังก์ชันที่ใช้จริง
- ไม่มีหน้า/member override ที่ยังเปิดใช้งานหลงเหลือ

---

## Phase 7 — API / Member Contract Alignment

### วัตถุประสงค์
ทำให้ member/API layer ไม่อ้างอิง concept เดิมอีก

### งาน
1. ตรวจ endpoint `draws`, `bet`, `tickets` ว่ามี field/message ที่สื่อถึง member-specific config หรือไม่
2. ปรับ response/metadata ให้สะท้อน global-only model
3. deprecate endpoint ที่เกี่ยวกับ member config ถ้ามี
4. ยืนยันว่า frontend flow หลักยังทำงานได้
5. เก็บ interface เดิมให้มากที่สุด ถ้าจำเป็นต้องเปลี่ยนต้องทำ compatibility note

### Deliverables
- updated API contract note
- frontend compatibility checklist

### Acceptance Criteria
- member side ไม่อ้างอิง concept เก่า
- frontend ไม่พังจาก API mismatch

---

## Phase 8 — Dead Code Cleanup

### วัตถุประสงค์
ลบ code path เก่าหลังระบบใหม่เสถียรแล้ว

### งาน
1. ลบ service/class ที่ obsolete เช่น `MemberMarketPolicyService` ถ้าไม่เหลือหน้าที่
2. ลบ helper/repository/query path ที่ใช้ member override
3. ลบ controller/route/view/menu ที่ไม่ใช้งานแล้ว
4. ปรับหรือลบ tests ของ behavior เดิม
5. วาง migration cleanup สำหรับ drop table deprecated เป็น phase สุดท้ายแยกต่างหาก

### Deliverables
- codebase cleanup patch
- deprecated component removal list
- final schema cleanup plan

### Acceptance Criteria
- ไม่มี reference ถึง member override ใน runtime path
- codebase สะอาดและสอดคล้องกับ domain ใหม่

---

## Phase 9 — Hardening, QA, Release, Rollback

### วัตถุประสงค์
ทำให้งานพร้อม deploy production

### งาน
1. รัน regression tests ครบ
2. ทำ manual QA ตาม scenario สำคัญ
3. ตรวจ admin/API routes
4. ตรวจ migration impact และ compatibility
5. เตรียม release note
6. เตรียม rollback checklist
7. เตรียม handover note ให้ operation/admin

### Deliverables
- QA result
- release checklist
- rollback checklist
- handover note

### Acceptance Criteria
- deploy ได้จริง
- rollback ได้จริง
- behavior ใหม่เสถียรใน production

---

## Test Matrix ที่ต้องมี

## 1. Config Tests
- member A และ member B ใช้ค่าเดียวกัน
- แก้ global config แล้วงวดใหม่อ่านค่าถูก
- snapshot สร้างค่าครบ

## 2. Bet Validation Tests
- ต่ำกว่า min -> reject
- สูงกว่า max -> reject
- เกิน max_per_number -> reject
- number blocked -> reject
- bet type invalid -> reject
- draw not open -> reject

## 3. Exposure Tests
- under boundary
- exact boundary
- over boundary
- concurrent bet พร้อมกัน
- stale read ไม่ทำให้ oversell

## 4. Draw Snapshot Tests
- เปิดงวดแล้ว snapshot ค่าเข้า draw ครบ
- แก้ master หลังเปิดงวดไม่กระทบงวดเก่า
- งวดใหม่ใช้ค่าล่าสุด

## 5. Settlement Tests
- payout snapshot ถูกใช้จริง
- win amount formula ไม่ regress
- mixed win/lose ยังถูก
- reconciliation totals ยังถูก

## 6. Admin Tests
- หน้า member override ถูกปิด/redirect
- หน้า global setting save ได้
- allowlist field ยังปลอดภัย

## 7. API Compatibility Tests
- route ยังอยู่ตาม contract
- payload shape จุดสำคัญไม่พัง
- member flow แทง/ดูประวัติใช้งานต่อได้

---

## Risk Register

### R1 — สมาชิกที่เคยมีค่าพิเศษหายทันที
**ผลกระทบ:** behavior change ชัดเจน

**Mitigation:**
- confirm business ก่อน rollout
- ทำ release note ชัด

### R2 — Exposure โตเร็วขึ้น
**ผลกระทบ:** global rule เดียว อาจทำให้ oversell ง่ายขึ้น

**Mitigation:**
- ทบทวน `max_per_number`
- review exposure limits ใหม่
- รักษา transaction/locking เข้มเหมือนเดิม

### R3 — ลบ logic ไม่ครบ
**ผลกระทบ:** เหลือ ghost path ทำให้บาง flow อ่าน config คนละแหล่ง

**Mitigation:**
- audit ให้ครบ
- เพิ่ม regression coverage
- cleanup phase แยกต่างหาก

### R4 — แก้ config กลางงวดแล้วเกิด dispute
**ผลกระทบ:** ticket ก่อน/หลังใช้ rule ไม่ตรงกัน

**Mitigation:**
- ใช้ draw snapshot policy
- หลีกเลี่ยง immediate effect กับ draw ที่เปิดแล้ว

### R5 — Frontend/Admin contract mismatch
**ผลกระทบ:** หน้าเว็บยังเรียก field เก่า

**Mitigation:**
- ทำ API compatibility checklist
- ประสาน frontend พร้อม backend

---

## Release Strategy

### Release 1
- schema updates
- resolver groundwork
- ยังไม่ switch behavior หลัก

### Release 2
- switch read path ไป global resolver
- align draw snapshot

### Release 3
- switch bet flow เต็ม
- ปิด admin member override UI

### Release 4
- cleanup dead code
- drop deprecated schema ในรอบสุดท้าย

---

## Rollback Strategy

1. rollback code กลับ version เดิมได้
2. ห้าม drop member tables จนกว่าจะผ่าน rollout window
3. migration ใหม่ต้อง backward-compatible
4. deprecated data ต้องยังอยู่ระหว่าง transition
5. release ที่ switch behavior ต้อง rollback แยกจาก cleanup release

---

## Definition of Done

งานนี้ถือว่าเสร็จเมื่อ:
- Lotto runtime ใช้ global config only จริง
- ไม่มี member override ใน critical runtime path
- draw snapshot policy ถูกใช้จริง
- admin ถูก simplify ตาม model ใหม่
- API/member flow ยังใช้งานได้
- tests สำคัญผ่านครบ
- cleanup dead code/schema ทำตาม phase เรียบร้อย
- มี release note + rollback note + handover note ครบ

---

## Task Assignment สำหรับ Agent

### Task 1 — Audit Current Member-Specific Logic
สำรวจ `packages/Gametech/Lotto` ทั้งหมดและสรุปจุดที่ยังใช้ member-specific setting, member permission, member rate plan, หรือ override logic โดยระบุไฟล์ เมธอด ตาราง และลำดับการ resolve config ปัจจุบันให้ครบ ห้ามแก้โค้ดใน task นี้

### Task 2 — Finalize New Domain Contract
จัดทำสรุป domain contract ใหม่ของ Lotto ว่า payout/min_bet/max_bet/max_per_number จะอิง global setting ระดับใด, snapshot ตอนใด, และมีผลต่อ draw ปัจจุบันหรือ draw ใหม่เท่านั้น พร้อมระบุผลกระทบต่อ service layer, admin, API, reports, และ tests

### Task 3 — Prepare Backward-Compatible Schema
ออกแบบ migration ที่ทำให้ Lotto รองรับ global-only configuration ได้ครบ โดยเพิ่ม/ปรับ fields ที่จำเป็นใน market/draw snapshot tables และ mark member-specific tables เป็น deprecated โดยยังไม่ drop ทิ้งในรอบนี้

### Task 4 — Implement Global Config Resolver
ปรับ service/helper layer ให้การอ่านค่าคอนฟิกของ Lotto ใช้ global market/draw snapshot source เดียว และตัด read path ที่อิง member override ออก โดยต้องรักษา interface ภายนอกเดิมให้มากที่สุด

### Task 5 — Align Draw Snapshot Flow
ปรับ `DrawService` และจุดที่เกี่ยวข้องให้ตอนเปิดงวดมีการ snapshot payout/min/max/max_per_number จาก global setting ลง draw อย่างครบถ้วน และให้ `BetService` ใช้ snapshot นี้เป็นหลัก

### Task 6 — Rewrite Bet Validation Flow
ปรับ `BetService` ให้ใช้ global-only validation โดยเอา member-specific resolution ออกทั้งหมด แต่ต้องคง transaction, locking, exposure logic, และ persistence contract เดิมไว้

### Task 7 — Simplify Admin Modules
ปรับ admin routes/controllers/views ของ Lotto ให้เหลือเฉพาะ global config flow และปิด/ซ่อน/redirect เมนู `member_permissions`, `member_rate_plans`, และหน้า override ที่ไม่ใช้แล้ว

### Task 8 — Align API and Member Contract
ตรวจและปรับ API routes/controllers/responses ที่เกี่ยวกับ `draws`, `bet`, `tickets` เพื่อให้ไม่อ้างอิง concept ของ member-specific plan อีกต่อไป และต้องไม่ทำให้ frontend flow หลักพัง

### Task 9 — Add Regression Coverage
เพิ่มหรือปรับ tests สำหรับ global config, draw snapshot, bet validation, exposure boundary, concurrency, settlement reconciliation, และ admin deprecation flow ให้ครอบ behavior ใหม่ครบ

### Task 10 — Final Cleanup
หลัง behavior ใหม่ผ่านครบ ให้ลบ dead code/service/model/view/route ที่เกี่ยวกับ member override และเตรียม migration cleanup สำหรับ drop deprecated tables ในรอบสุดท้าย

---

## ข้อเสนอเชิง Architecture

ถ้า requirement รอบนี้คือ “ไม่มี override รายสมาชิกเลย” ให้ทำตามนั้นได้ แต่แนะนำให้เผื่ออนาคตแค่ระดับนี้พอ:

- global default
- optional group-level override ในอนาคต

ไม่ควรย้อนกลับไปเปิด per-member override อีก เพราะจะดึงความซับซ้อนกลับเข้าระบบทันที

---

## หมายเหตุสำหรับคนลงมือ

- งานนี้เป็น domain rewrite ระดับหนึ่ง ไม่ใช่แค่ CRUD cleanup
- ห้ามลบ member layer แบบ big bang
- ต้องแยก schema prep / service switch / cleanup ออกจากกัน
- จุดเสี่ยงสุดคือ `BetService`, draw snapshot, exposure, และ admin contract
- ถ้าพบ design เดิมที่ผูก member override ไว้ลึก ให้แก้แบบค่อยเป็นค่อยไปและรักษา interface ภายนอกเดิมให้มากที่สุด
