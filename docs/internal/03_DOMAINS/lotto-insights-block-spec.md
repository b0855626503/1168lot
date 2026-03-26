# Lotto Dashboard Block Spec — Bet Type Summary + Top Numbers

## Objective
เพิ่ม block บน dashboard เพื่อบอกว่า **วันนี้แต่ละ bet type มีจำนวนรายการเท่าไร, ยอดรวมเท่าไร, และเลขอะไรถูกแทงมากสุด**

block นี้เป็น **product insight**
ไม่ใช่ cash metric
ไม่ใช่ recent activity

---

## Scope
แสดงข้อมูลระดับ **today summary**
แยกตาม `bet_type`

---

## Non-Negotiable Rules
- ห้ามใช้ `lotto_tickets` เป็น source หลัก
- ใช้ item-level aggregation เท่านั้น
- dashboard ห้าม query raw จาก `lotto_ticket_items`
- ต้องอ่านจาก summary table
- metric นี้เป็น product layer

---

## Metrics

### item_count
- count จาก `lotto_ticket_items`
- date = `bet_confirmed_at`

### total_amount
- sum(amount) จาก item
- date = `bet_confirmed_at`

### unique_players
- count(distinct member_id)
- date = `bet_confirmed_at`

### top_number
- number ที่ stake_total สูงสุด
- tiebreak: amount > item_count > number asc

### top_number_amount
- sum(amount) ของ top_number

---

## Table Design

### lotto_dashboard_bet_type_summary_daily

- summary_date
- bet_type
- item_count
- total_amount
- unique_players
- top_number
- top_number_amount
- created_at
- updated_at

UNIQUE:
(summary_date, bet_type)

INDEX:
(summary_date)
(summary_date, bet_type)

---

## UI

| Bet Type | รายการ | ยอดรวม | ผู้เล่น | เลขแทงสูงสุด | ยอดเลขสูงสุด |
|---|---:|---:|---:|---|---:|

---

## Pipeline

Observer
→ dispatchForModelChange
→ BucketResolver
→ Queue
→ Projector
→ upsert summary
→ broadcast

---

## API Example

```json
{
  "lotto_insights_today": [
    {
      "bet_type": "TOP_3",
      "label": "3 ตัวบน",
      "item_count": 128,
      "total_amount": 24500,
      "unique_players": 52,
      "top_number": "123",
      "top_number_amount": 8000
    }
  ]
}
```

---

## Acceptance Criteria
- item_count ถูกต้องระดับ item
- total_amount ตรง
- unique_players ถูกต้อง
- top_number ถูกต้อง
- dashboard ไม่ query raw
- rebuild idempotent

---

## Final
Block นี้ต้องมี และต้องใช้ summary table เท่านั้น
