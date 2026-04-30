> สถานะ: DONE
> วันที่: 2026-04-30
> โดเมน/เรื่อง: lotto / yeekee
> แทนแผนเก่า: -
> อ้างอิง: BOA-175

# PR-01: Yeekee Codebase Contract Lock

## เป้าหมาย
ล็อก execution contract จากโค้ดจริงสำหรับ PR-02..PR-12 โดยไม่เปลี่ยน behavior ระบบเดิม

## Current Table Map (ล็อกจาก migration จริง)
- `lotto_groups` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100000_create_lotto_groups_and_markets.php`
- `lotto_markets` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100000_create_lotto_groups_and_markets.php`
- `lotto_draws` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100001_create_lotto_draws.php`
- `lotto_tickets` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100002_create_lotto_exposure_and_tickets.php`
- `lotto_ticket_items` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100002_create_lotto_exposure_and_tickets.php`
- `lotto_number_exposures` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_18_100002_create_lotto_exposure_and_tickets.php`
- `wallet_transactions` - `packages/Gametech/Lotto/src/Database/Migrations/2026_03_23_230000_create_wallet_transactions_table.php`

## Runtime Flow Contract (จาก service จริง)

### 1) Bet placement
Source: `packages/Gametech/Lotto/src/Services/BetService.php`
- Entry: `placeBet(int $memberId, int $drawId, int $packageId, array $items): LottoTicket`
- อยู่ใน `DB::transaction(...)`
- ลำดับหลัก: validate draw/member/market -> validate item -> create `lotto_tickets` -> debit wallet `ref_type=LOTTO_BET` -> persist `lotto_ticket_items` -> update exposure
- Wallet debit ผ่าน `WalletTransactionService::debitMemberBalance(...)`
- Contract สำคัญ:
  - ตั๋วและ wallet debit เกิดใน transaction เดียว
  - การเดิมพันที่สำเร็จต้องมี `wallet_transactions` กลุ่ม `LOTTO_BET_*`

### 2) Settlement
Source: `packages/Gametech/Lotto/src/Services/SettlementService.php`
- Entry: `settleDraw(LottoDraw $draw, array $resultNumber, string $mode = 'settlement'): array`
- มี `normalizeResultNumber(...)` ก่อน settle
- มี `ResultHash` + `SettlementBatch` สำหรับ idempotency/replay safety
- lock draw ด้วย `lockForUpdate()`
- materialize win ราย item ลง `lotto_winnings` และเครดิตผ่าน wallet (`LOTTO_SETTLE_WIN`) เมื่อเข้าเงื่อนไข
- Contract สำคัญ:
  - settlement รับผลในรูป normalized result เท่านั้น
  - draw ที่ resulted + result_hash เดิมจะคืน summary เดิม (ไม่ re-settle ซ้ำ)

### 3) Reporting / dashboard anchors
- Revenue: `packages/Gametech/Lotto/src/DataTables/LottoRevenueReportDataTable.php`
- Exposure: `packages/Gametech/Lotto/src/DataTables/LottoExposureReportDataTable.php`
- Forecast: `packages/Gametech/Lotto/src/DataTables/LottoProfitLossForecastReportDataTable.php`
- Cancel: `packages/Gametech/Lotto/src/DataTables/LottoTicketsCancelReportDataTable.php`
- Blocked numbers: `packages/Gametech/Lotto/src/DataTables/LottoBlockedNumbersReportDataTable.php`
- Member bet types: `packages/Gametech/Lotto/src/DataTables/LottoMemberBetTypesReportDataTable.php`
- Admin dashboard service: `packages/Gametech/Admin/src/Services/DashboardService.php`

### 4) Frontend wallet transaction surface
Source: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- Transaction API อิง table `wallet_transactions`
- แยกประเภทด้วย `ref_type`/mapping ภายใน query
- Contract สำคัญ: ref_type ใหม่ใน PR ถัดไปต้องไม่ทำให้รายการเก่าผิด semantic

## Extension Points ที่ใช้ได้ใน PR ถัดไป
- Market identity/config extension: เพิ่มแบบ additive รอบ `lotto_markets` + table ใหม่เฉพาะ Yeekee
- Draw/round lifecycle extension: map เข้ากับ `lotto_draws` ไม่แยก flow ออกจาก draw เดิม
- Shoot/Result/Reward/Refund เป็น module เพิ่ม โดยไม่ทับ contract ของ `BetService` และ `SettlementService`
- Report layer รองรับ filter แยก market type แบบ backend-side query

## Guardrail Contract (ห้ามแตก)
- ห้ามใส่ business logic ใน controller
- ห้ามเปลี่ยน API contract เดิมแบบ breaking
- ห้ามทำให้หวยปกติเปลี่ยนพฤติกรรม
- ห้ามให้ reward/refund ปนความหมายกับ winning payout
- ห้ามใช้ shoot count แทน bet entries (`lotto_ticket_items`) สำหรับ policy เงิน

## Known Risks / Mismatch ที่ต้องระวัง
- Repo มีทั้ง `packages/Gametech/Lotto` และ `packages/Gametech/Lottobk` ต้องยึด `Lotto` เป็น execution path หลัก
- report/query จำนวนมากยังอิงโครงหวยเดิมและอาจต้องเพิ่ม filter/index เมื่อมี Yeekee volume สูง
- `wallet_transactions.ref_type` ถูกใช้กว้างในหลายจุด ต้องกำหนด ref_type ใหม่ให้ชัดและไม่ชน semantic เดิม

## Regression Baseline สำหรับ PR-02+
- `tests/Feature/FrontendApi/LottoTicketsControllerTest.php`
- `tests/Feature/FrontendApi/LottoTicketCancelPolicyTest.php`
- `tests/Feature/Lotto/LottoWinningSettlementMaterializationTest.php`
- `tests/Feature/Lotto/LottoWinningReportCommandsTest.php`
- `tests/Feature/Lotto/AdminLottoTicketCancelControllerTest.php`
- `tests/Feature/Lotto/AdminLottoReportsDataTableTest.php`

## Non-goals ของเอกสารนี้
- ไม่มี migration/code behavior change
- ไม่ออกแบบ schema ใหม่เชิง implementation detail ของ PR-02+
- ไม่รัน settlement/refund/reward flow ใหม่ใน PR-01
