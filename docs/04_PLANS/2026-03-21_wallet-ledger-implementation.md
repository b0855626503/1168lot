> สถานะ: PENDING
> วันที่: 2026-03-21
> โดเมน/เรื่อง: Wallet / Ledger
> แทนแผนเก่า: -

WALLET LEDGER IMPLEMENTATION PLAN (PRODUCTION-READY)
0) Objective

รวมศูนย์การเปลี่ยนยอดเงินทั้งหมดไว้ที่เดียว

members.balance = source of truth (ยอดปัจจุบัน)
wallet_transactions = ledger (ประวัติการเปลี่ยนยอดทั้งหมด)
ทุกการเปลี่ยนยอดต้องผ่าน WalletMutationService เท่านั้น
1) Scope ของระบบ

ใช้ wallet เดียว (MEMBER เท่านั้น)

ครอบคลุม:

deposit
withdraw
admin adjust
bonus / cashback / rebate
lotto bet / lotto win
game (เฉพาะที่กระทบ main wallet)
2) Data Contract (ต้อง lock ก่อนทำ)
   2.1 direction
   CREDIT = เพิ่มเงิน
   DEBIT = ลดเงิน
   2.2 ref_type (ENUM กลาง ห้ามเพิ่มมั่ว)
   DEPOSIT
   WITHDRAW
   WITHDRAW_REFUND

ADMIN_CREDIT
ADMIN_DEBIT

BONUS_WHEEL
CASHBACK
REBATE

LOTTO_BET
LOTTO_WIN

GAME_BET
GAME_WIN
GAME_REFUND

ADJUST
REVERSAL
2.3 created_by_type
member
admin
system
2.4 status

ใช้แค่:

SUCCESS

(ห้ามใช้ PENDING ใน ledger)

3) Schema Rules (wallet_transactions)
   REQUIRED FIELDS
   member_id
   direction
   amount (always positive)
   balance_before
   balance_after
   ref_type
   ref_id
   created_at
   OPTIONAL (แต่แนะนำให้ใช้)
   ref_code (เลข business เช่น tx no)
   group_code (รวมหลาย row)
   related_txn_id (โยง reversal/refund)
   meta (json context)
   RULES
   append-only (ห้าม update/delete)
   1 row = 1 balance mutation
   amount ห้ามติดลบ
4) Core Service Design
   WalletMutationService (ศูนย์กลาง)

input

member_id
direction (CREDIT/DEBIT)
amount
ref_type
ref_id
ref_code (optional)
meta (optional)
created_by_type
created_by_id

flow

begin DB transaction
lock member row (SELECT ... FOR UPDATE)
read balance_before
compute balance_after
CREDIT → +
DEBIT → -
validate: balance_after >= 0 (ยกเว้น design อนุญาตติดลบ)
update members.balance
insert wallet_transactions
commit
HARD RULE

ห้ามมี code ไหนแก้ members.balance นอก service นี้

5) Idempotency Policy (สำคัญมาก)
   ต้องกัน insert ซ้ำสำหรับ:
   deposit callback
   withdraw callback
   lotto payout batch
   bonus auto credit
   game settlement
   วิธีขั้นต่ำ

ก่อน insert:

check exists where
(ref_type, ref_id, direction, member_id)
ถ้า flow เสี่ยงสูง (แนะนำเพิ่ม)

เพิ่ม field:

idempotency_key (unique)
6) Flow Mapping (IMPLEMENTATION CONTRACT)
   6.1 Deposit

trigger: deposit SUCCESS เท่านั้น

direction: CREDIT
ref_type: DEPOSIT
ref_id: deposits.id
ref_code: deposit_no
6.2 Withdraw
ตอนหักยอด (submit withdraw)
direction: DEBIT
ref_type: WITHDRAW
ref_id: withdraws.id
ถอน fail → คืนเงิน
direction: CREDIT
ref_type: WITHDRAW_REFUND
related_txn_id: txn ตอนหัก
6.3 Admin Adjust
เพิ่มเงิน
direction: CREDIT
ref_type: ADMIN_CREDIT
ลดเงิน
direction: DEBIT
ref_type: ADMIN_DEBIT
6.4 Bonus / Cashback / Rebate
direction: CREDIT
ref_type:
BONUS_WHEEL
CASHBACK
REBATE
6.5 Lotto
แทง
direction: DEBIT
ref_type: LOTTO_BET
ref_id: lotto_orders.id
ถูกรางวัล
direction: CREDIT
ref_type: LOTTO_WIN
ref_id: lotto_payout.id
6.6 Game
bet / transfer เข้าเกม
direction: DEBIT
ref_type: GAME_BET
win / settlement
direction: CREDIT
ref_type: GAME_WIN
refund / rollback
direction: CREDIT
ref_type: GAME_REFUND
7) Migration Plan
   Phase 1 (Shadow Mode)
   implement WalletMutationService
   integrate:
   admin adjust
   bonus
   lotto

ยังไม่แตะ deposit/withdraw/game

Phase 2
integrate deposit
integrate withdraw
Phase 3
integrate game flow
8) Reconciliation (ต้องมี)

ทำ job ตรวจ:

8.1 Balance Check
sum(CREDIT - DEBIT) per member
== members.balance
8.2 Missing Ledger
business table มี แต่ ledger ไม่มี
8.3 Duplicate Ledger
ref_type + ref_id ซ้ำ
9) Index Strategy (ลด write overhead)

เก็บเฉพาะ:

(member_id, created_at)
(ref_type, ref_id)
(ref_code)

ตัด index ซ้ำ/ไม่ใช้

10) Constraints / Guardrails
    ห้าม update/delete ledger
    ห้าม module ไหนแก้ balance ตรง
    ทุก flow ต้องผ่าน WalletMutationService
    ledger insert ต้องอยู่ใน DB transaction เดียวกับ balance update
    ทุก ref_type ต้องอยู่ใน catalog กลางเท่านั้น
11) Deliverables สำหรับ Agent

Agent ต้องทำ:

สร้าง WalletMutationService
enforce lock + transaction
implement idempotency check
refactor flows:
admin adjust
bonus
lotto
add logging (error + duplicate prevention)
create reconciliation command/job
document mapping (ตาม section 6)
สรุปสั้น
ใช้ ledger กลาง = ถูกทาง
แต่ต้อง “รวมจุดแก้ balance” ให้ได้ก่อน
rollout ทีละ flow
มี idempotency + reconciliation ตั้งแต่วันแรก