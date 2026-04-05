> สถานะ: PENDING
> วันที่: 2026-03-21
> โดเมน/เรื่อง: Lotto / Dashboard
> แทนแผนเก่า: -

# LOTTO DASHBOARD IMPLEMENTATION PLAN (FINAL)

## 1. Objective
เพิ่ม Lotto เข้า Dashboard โดยไม่ทำให้ Financial Movement หลักเพี้ยน

ระบบต้องตอบได้ 3 มิติ:
1. Cash Impact (เงินจริงเข้า/ออก)
2. Sales & Risk (ยอดแทง + exposure)
3. Operations (สถานะรอบ + settlement)

---

## 2. Scope

### IN SCOPE
- Lotto Cash metrics (รวมใน dashboard หลัก)
- Lotto product metrics (แยก module)
- Lotto risk snapshot
- Lotto summary tables (daily + snapshot)
- Event → Queue → Projector flow
- Reconcile / rebuild tools

### OUT OF SCOPE
- ML anomaly
- player segmentation ลึก
- realtime per-second aggregation

---

## 3. Architecture (ต้องยึด)

Observer
→ dispatchForModelChange
→ BucketResolver (date + web_code)
→ Queue (SyncDashboardSummaryBucket)
→ Projector
→ Summary Tables
→ Broadcast

ห้าม bypass flow นี้

หมายเหตุ implementation:
- bucket เดียว (`summary_date + web_code`) ต้อง collapse งานซ้ำก่อนเข้าคิว
- ใช้ pending payload merge สำหรับ `updated_sections`
- queue runtime ต้องกันทั้ง:
  - duplicate queued jobs ก่อนเริ่มรัน
  - concurrent execution ของ bucket เดียวกัน

---

## 4. Time Reference (CRITICAL)

| Metric | Time Source |
|------|--------|
| Lotto Sales | bet_confirmed_at |
| Lotto Payout | wallet_credit_at |
| Exposure | snapshot_at |
| Settlement | settled_at |

---

## 5. Data Source of Truth

### CASH
→ wallet_transactions

### LOTTO PRODUCT
→ lotto_tickets / lotto_bets

### RISK
→ aggregation จาก bet

---

## 6. Summary Tables

### dashboard_summary_daily
เพิ่ม:
- lotto_sales_cash
- lotto_payout_cash

### lotto_dashboard_summary_daily
- total_sales
- total_payout
- total_tickets
- total_players
- win_tickets
- lose_tickets
- pending_tickets
- settled_tickets

### lotto_dashboard_market_summary
- market_id
- round_id
- total_sales
- total_tickets
- total_players
- total_payout
- status

### lotto_dashboard_risk_snapshot
- snapshot_at
- market_id
- round_id
- bet_type
- number
- stake_total
- payout_if_hit
- liability

---

## 7. Metric Definition

### Lotto Sales
wallet_transactions:
type = LOTTO_BET
direction = DEBIT
status = SUCCESS

### Lotto Payout
type = LOTTO_PAYOUT
direction = CREDIT
status = SUCCESS

### Net Lotto
net_lotto = lotto_sales - lotto_payout

### Exposure
sum(payout_if_hit)

### Liability
max(exposure)

---

## 8. Flow Implementation

1. Lotto Event (bet / payout / settlement)
2. Observer trigger
3. dispatchForModelChange
4. Queue → SyncDashboardSummaryBucket
5. Projector aggregate
6. upsert summary tables
7. broadcast

รายละเอียด queue:
- dispatch หลายครั้งของ bucket เดียวกันต้อง merge เป็น pending payload เดียวก่อน
- job ควร consume payload ล่าสุดตอนเริ่มรัน ไม่ใช่ยึด snapshot เก่าจากตอน dispatch อย่างเดียว

---

## 9. Rules (ห้ามพัง)

- Lotto Sales ต้องมาจาก wallet เท่านั้น
- Lotto Payout ต้องมาจาก wallet เท่านั้น
- ห้าม aggregate จาก bet ตรง
- Risk = snapshot เท่านั้น
- Dashboard ห้าม query raw

---

## 10. Phase Plan

### Phase 1
- lotto_sales_cash
- lotto_payout_cash
- lotto net
- tickets / players
- market summary

### Phase 2
- exposure snapshot
- top risk number
- liability
- settlement status

### Phase 3
- hourly summary
- anomaly detection
- player segmentation

---

## 11. Deliverables

- Migration tables
- Observer integration
- Projector logic
- Queue job
- API endpoint
- Dashboard UI
- Rebuild command

---

## 12. Final Definition

Deposit  = date_create
Withdraw = date_approve
Lotto Sales = wallet debit
Lotto Payout = wallet credit

Net Balance = เงินเข้า - เงินออก ของ cash movement หลักเท่านั้น
Lotto Net = แสดงแยกใน block Lotto Cash

Dashboard นี้ = Cash Movement + Lotto Insight โดยแยก block กันชัดเจน
