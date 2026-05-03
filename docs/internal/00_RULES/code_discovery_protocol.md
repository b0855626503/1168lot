# Code Discovery Protocol

## Purpose
ใช้สำหรับบังคับ agent ค้นหาไฟล์และ call flow ที่เกี่ยวข้องก่อนเริ่มแก้โค้ดทุกครั้ง เพื่อป้องกันการเดาไฟล์ผิด, แก้ผิด layer, หรือพลาด side effects

## Risk Levels

เลือก level ก่อนเริ่ม discovery เพื่อกำหนดว่าต้องอ่านอะไรบ้าง

### Light
ใช้เมื่อ:
- แก้ doc-only / typo / markdown formatting
- ระบุไฟล์เป้าหมายชัดเจน และไม่กระทบ behavior ใด

สิ่งที่ต้องทำ:
- อ่าน `docs/START_HERE.md`
- verify ไฟล์เป้าหมาย
- ไม่ต้องอ่าน `system_current_state.md` / `decision_log.md` ถ้าไม่เสี่ยง

### Standard
ใช้เมื่อ:
- code task ทั่วไป
- bug fix ที่ไม่ใช่เงิน / permission / schema

สิ่งที่ต้องทำ:
- อ่าน `docs/START_HERE.md`
- อ่าน `docs/internal/01_SYSTEM/system_map.md`
- อ่าน domain discovery/note ที่เกี่ยวข้อง
- `rg`/`git grep` อย่างน้อย business keyword + technical keyword + table/class
- สรุป Discovery Report แบบย่อ

### High Risk
ใช้เมื่อ:
- wallet / payment / deposit / withdraw
- lotto settlement / auto-result / yeekee shooting
- permission / member policy
- migration / schema change
- financial dashboard
- realtime / broadcast / queue
- auth / security / ACL

สิ่งที่ต้องทำ:
- อ่าน `docs/START_HERE.md`
- อ่าน `docs/internal/01_SYSTEM/system_map.md`
- อ่าน domain discovery/note ที่เกี่ยวข้อง
- อ่าน `docs/internal/01_SYSTEM/system-current-state/index.md`
- อ่าน `docs/internal/02_DECISIONS/decision-log/index.md`
- `rg`/`git grep`/IDE/Octocode หลาย keyword
- สรุป full Discovery Report
- ระบุ contract / side effects / tests ที่เกี่ยวข้อง

---

## When Required
ต้องทำ protocol นี้ก่อนงานต่อไปนี้:
- แก้ backend service/controller/model/command/job/listener/observer
- แก้ frontend Vue/JS/API integration
- แก้ database migration/schema/query/report
- แก้ wallet/payment/lotto/permission/policy
- เพิ่ม feature ใหม่
- refactor
- bug fix ที่ยังไม่รู้ root cause
- งานที่กระทบหลายไฟล์

ไม่จำเป็นสำหรับ:
- แก้ typo ใน doc
- update markdown ที่ไม่กระทบ behavior
- งาน formatting เล็กน้อยที่ระบุไฟล์ชัดเจนแล้ว

## Required Discovery Steps

### Step 1: Read Source of Truth
อ่านตาม startup path ที่กำหนดใน `docs/START_HERE.md`:
1. `docs/START_HERE.md`
2. `docs/internal/01_SYSTEM/system_map.md`
3. domain note ที่เกี่ยวข้อง (ดู `docs/internal/03_DOMAINS/`)
4. สำหรับ High Risk เท่านั้น: `docs/internal/01_SYSTEM/system-current-state/index.md` + `docs/internal/02_DECISIONS/decision-log/index.md`
5. แผนงาน active ที่เกี่ยวข้อง: `docs/04_PLANS/_current_work.md`

ถ้าเอกสารกับ code ไม่ตรงกัน ต้องรายงาน mismatch ก่อน implement

### Step 2: Extract Search Keywords
จาก task/plan ต้องแตก keyword อย่างน้อย 4 กลุ่ม:

1. Business keywords
   ตัวอย่าง:
   - แทงหวย
   - หวยยี่กี่
   - ออกผล
   - rollback
   - เติมเงิน
   - ถอนเงิน
   - สิทธิ์สมาชิก

2. English/domain synonyms
   ตัวอย่าง:
   - lotto
   - bet
   - ticket
   - draw
   - market
   - yeekee
   - yiki
   - policy
   - settlement
   - exposure

3. Technical keywords
   ตัวอย่าง:
   - BetService
   - DrawService
   - SettlementService
   - ResultApplier
   - MemberMarketPolicyService
   - WalletTransactionService
   - Controller
   - Command
   - Observer
   - DataTable

4. Database/table keywords
   ตัวอย่าง:
   - lotto_draws
   - lotto_tickets
   - lotto_ticket_items
   - lotto_draw_bet_settings
   - lotto_market_bet_settings
   - member_lotto_market_policies
   - wallet_transactions

### Step 3: Search Codebase With Multiple Methods
ต้องค้นด้วยอย่างน้อย 3 วิธีจากรายการนี้:

1. rg / ripgrep
   - `rg "keyword"`
   - `rg "table_name"`
   - `rg "class name"`
   - `rg "route name"`
   - `rg "command signature"`

2. git grep
   - `git grep "keyword"`

3. IDE index / PhpStorm MCP
   - search class
   - find usages
   - navigate symbol

4. Octocode / semantic index
   - ใช้เพื่อหาแนวทางหรือไฟล์ใกล้เคียง
   - ห้ามใช้เป็น source เดียว

5. docs search
   - `docs/04_PLANS`
   - `docs/internal/03_DOMAINS`
   - `docs/internal/02_DECISIONS`
   - `docs/public/api`

ข้อบังคับ:
- ถ้า search แรกไม่เจอ ต้องลอง synonym อย่างน้อย 2 รอบ
- ถ้า keyword ภาษาไทยไม่เจอ ต้องลองภาษาอังกฤษ
- ถ้า business keyword ไม่เจอ ต้องลอง table/model/service keyword
- ถ้าเจอไฟล์ archive/bk ต้องตรวจว่ามีไฟล์ active package หรือไม่ เช่น `packages/Gametech/Lotto` ก่อน `Lottobk`
- ห้ามแก้ไฟล์ใน archive/bk โดยไม่ยืนยันว่าเป็น active code path

### Step 4: Identify Entrypoints
ต้องระบุ entrypoint ที่เกี่ยวข้องก่อนแก้ เช่น:

Backend:
- route
- controller
- command
- job
- listener
- observer
- scheduled task
- API endpoint

Frontend:
- page/component
- route
- store/composable
- API client
- modal/component lifecycle

CLI/Scheduler:
- artisan command signature
- scheduler registration
- queue/job entrypoint

ถ้าหา entrypoint ไม่เจอ ต้องรายงานว่าไม่พบ และระบุวิธีค้นที่ลองแล้ว

### Step 5: Trace Call Flow
ต้องสรุป call flow แบบสั้นก่อน implement

รูปแบบที่ต้องรายงาน:

Entrypoint:
- `path/to/Controller.php::method`

Flow:
1. Controller/Command รับ input
2. Service A validate
3. Service B write DB
4. Model/Table ที่ถูกแตะ
5. Event/Observer/Queue ที่ตามมา
6. Response/output

ห้ามแก้ service ปลายทางโดยไม่เข้าใจว่าใครเรียก

### Step 6: Identify Data Contracts
ต้องระบุ contract ที่ห้ามเปลี่ยน:
- public method signature
- request payload
- response format
- command signature
- event payload
- database schema
- enum value
- config key
- frontend prop/event name

ถ้าจำเป็นต้องเปลี่ยน contract ต้องรายงานก่อน พร้อมผลกระทบ

### Step 7: Identify Tables and Side Effects
ต้องสรุป:
- tables read
- tables written
- transaction/lock behavior
- wallet/payment side effects
- cache/broadcast/event/queue side effects
- permission/ACL impact

โดยเฉพาะโดเมน:
- wallet
- lotto
- payment
- member permission
- auto-result
- settlement
- report/dashboard

### Step 8: Identify Tests
ต้องค้น test ที่เกี่ยวข้อง:
- `tests/Feature`
- `tests/Unit`
- package tests ถ้ามี
- test เดิมที่ควรแก้
- test ใหม่ที่ควรเพิ่ม

ถ้าไม่มี test ต้องระบุว่า:
- no direct test found
- เสนอชื่อ test file ใหม่
- ระบุ scenario ที่ต้องครอบคลุม

### Step 9: Produce Discovery Report
ก่อน implement ต้องส่งรายงานรูปแบบนี้:

## Discovery Report

### Task Summary
สรุปงาน 2-4 บรรทัด

### Source of Truth Read
- [ ] `docs/START_HERE.md`
- [ ] `agent_rules.md`
- [ ] `system_current_state.md`
- [ ] `decision_log.md`
- [ ] current plan

### Keywords Used
Business:
- ...

Technical:
- ...

Tables:
- ...

Synonyms:
- ...

### Files Found
Active code:
- `path/to/file.php` — เหตุผลที่เกี่ยวข้อง

Possible related:
- `path/to/file.php` — เหตุผล

Ignored:
- `path/to/archive.php` — เหตุผลที่ไม่แตะ

### Entrypoints
- route/controller/command/job

### Call Flow
1. ...
2. ...
3. ...

### Tables / Side Effects
Reads:
- ...

Writes:
- ...

Side effects:
- ...

### Contracts To Preserve
- ...

### Tests Found / Needed
Existing:
- ...

New/Updated:
- ...

### Risk Assessment
Low/Medium/High:
เหตุผล

### Proposed Change Scope
จะแก้ไฟล์:
- ...

จะไม่แตะ:
- ...

ห้ามเริ่ม implement จนกว่าจะมี Discovery Report นี้ครบ

## Search Patterns By Domain

### Lotto Betting
ต้องค้น:
- BetService
- DrawService
- LottoConfigResolver
- LottoPackageResolver
- ExposureService
- lotto_draws
- lotto_draw_bet_settings
- lotto_market_bet_settings
- lotto_tickets
- lotto_ticket_items
- member_lotto_market_policies

### Lotto Auto Result
ต้องค้น:
- ResultApplier
- SettlementService
- AutoResult
- ResultHash
- result_number
- result_fetch_status
- result_normalized_payload_json
- lotto_result_sources
- lotto_result_fetch_logs

### Lotto Policy / Member Access
ต้องค้น:
- MemberMarketPolicyService
- BootstrapMemberMarketPoliciesCommand
- member_lotto_market_policies
- rollout_mode
- policy_version
- is_allowed
- applyMarketRollout
- applyGroupRollout

### Wallet / Payment
ต้องค้น:
- WalletTransactionService
- wallet_transactions
- direction
- ref_type
- ref_id
- CREDIT
- DEBIT
- payment callback/webhook controller
- observer ที่ sync dashboard

### Dashboard
ต้องค้น:
- DashboardService
- DashboardSummaryProjector
- DashboardSummarySyncService
- observer
- summary table
- cache key
- broadcast

### Frontend API
ต้องค้น:
- packages/Gametech/FrontendApi
- route/api
- controller
- transformer/resource
- docs/public/api
- request/response contract

## Mandatory Rules
- ห้ามแก้จาก memory อย่างเดียว
- ห้ามเดาจากชื่อไฟล์อย่างเดียว
- ห้ามแก้ไฟล์ backup/archive ก่อนตรวจ active path
- ห้ามเปลี่ยน public interface โดยไม่รายงาน
- ห้ามเพิ่ม dependency โดยไม่รายงาน
- ห้ามแก้หลายโดเมนใน PR เดียวถ้าไม่จำเป็น
- ถ้า code ไม่ตรง doc ให้รายงาน mismatch ก่อน
- ถ้าเจอ design ที่เสี่ยง ต้องเสนอทางเลือกก่อน implement

## Deprecated File Rule

ถ้าไฟล์มี `DEPRECATED` หรือ `ARCHIVED` ในบรรทัดแรก หรือ header:
- **ห้ามใช้เป็น primary source**
- ต้องตาม pointer ในไฟล์นั้นไปยัง active source ทันที
- ถ้าไม่มี pointer ให้ใช้ `07-route-reference.md` (frontend-v1) หรือ domain note ที่เกี่ยวข้อง

Discovery docs (`*_discovery.md`) มี freshness header — ถ้า confidence = low:
- ต้อง verify ด้วย `rg` ก่อนนำไปตัดสินใจ
- ห้าม assume ว่า path ถูกต้อง 100%
