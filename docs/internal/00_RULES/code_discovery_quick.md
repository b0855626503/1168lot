# Code Discovery — Quick Reference

> ใช้ไฟล์นี้สำหรับงาน Light และ Standard
> งาน High Risk: ใช้ `code_discovery_protocol.md` แทน

---

## เลือก Level ก่อน

| Level | ใช้เมื่อ | Report |
|-------|----------|--------|
| **Light** | doc-only / typo / ระบุไฟล์ชัดแล้ว ไม่กระทบ behavior | 1 บรรทัด |
| **Standard** | bug fix ทั่วไป / code ที่ไม่ใช่ wallet/payment/settlement | 3–5 บรรทัด |
| **High Risk** | wallet / payment / lotto settlement / yeekee / permission / schema / auth | full report → ดู `code_discovery_protocol.md` |

---

## Light — Minimal Read

1. ยืนยันไฟล์เป้าหมาย
2. ไม่ต้องอ่าน startup docs ถ้าระบุ path ชัดแล้ว

**Mini report (1 บรรทัด):**
```
Light: แก้ [ชื่อไฟล์] — [สิ่งที่แก้] — ไม่กระทบ behavior
```

---

## Standard — Compact Read

1. อ่าน `docs/START_HERE.md`
2. อ่าน `docs/internal/01_SYSTEM/system_map.md` (domain section ที่เกี่ยวข้อง)
3. อ่าน discovery doc 1 ไฟล์: `docs/internal/03_DOMAINS/*_discovery.md`
4. `rg` อย่างน้อย 2 keyword (business + class/table)

**Compact report (3–5 บรรทัด):**
```
Standard: [สรุปงาน]
Entrypoint: [controller/command/route]
Tables: [read] → [write]
Side effects: [ถ้ามี] หรือ none
Tests: [test file ที่เกี่ยวข้อง หรือ none found]
```

---

## Required Search Keywords (by domain)

### Lotto Betting
`BetService`, `lotto_draws`, `lotto_tickets`, `lotto_draw_bet_settings`, `member_lotto_market_policies`

### Lotto Auto Result / Settlement
`ResultApplier`, `SettlementService`, `result_number`, `lotto_result_sources`

### Yeekee
`YeekeeShootService`, `YeekeeResultEngineService`, `SettleYeekeeRoundsCommand`, `yeekee_rounds`, `yeekee_shoots`

### Wallet / Payment
`WalletTransactionService`, `wallet_transactions`, `ref_type`, `CREDIT`, `DEBIT`

### Frontend API
`packages/Gametech/FrontendApi/src/Routes/api.php`, controller in `Http/Controllers/Api/V1/`

### Member Policy
`MemberMarketPolicyService`, `member_lotto_market_policies`, `rollout_mode`

---

## Rules

- ถ้า search ไม่เจอ → ลอง synonym 1 รอบก่อน escalate เป็น Standard/High Risk
- ถ้าเจอไฟล์ archive/bk → ตรวจ active path ก่อนแตะ
- ถ้า confidence low → verify ด้วย `rg` ก่อน assume path ถูก
- ถ้า code ไม่ตรง doc → report mismatch ก่อน implement เสมอ
