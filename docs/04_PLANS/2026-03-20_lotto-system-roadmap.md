> สถานะ: SUPERSEDED
> วันที่: 2026-03-20
> โดเมน/เรื่อง: Lotto / System Roadmap
> แทนแผนเก่า: ถูกแทนโดย docs/04_PLANS/2026-03-21_lotto-execution-phases.md

## แผน: Lotto System Roadmap

สถานะล่าสุด (2026-03-21):
- ปิดแล้ว: เมนู admin หลักและเมนูที่เคยเป็น placeholder (`member_permissions`, `reports/exposure`) ถูกทำเป็นโมดูลจริง
- ปิดแล้ว: draw lifecycle + settlement ใช้งานได้จาก `LottoDrawController` ร่วมกับ `DrawService`/`SettlementService`
- ปิดแล้ว: member/API scaffold ถูกเปิดใช้จริง (`api/lotto/draws`, `api/lotto/bet`, `api/lotto/tickets`)
- ปิดแล้ว: policy C rollout scaffold สำหรับ member-market policy มี migration/service/command/tests ครบ baseline
- ปิดแล้ว (2026-03-21): เพิ่ม test เชิงเสี่ยงสูงครบ:
  - `SettlementReconciliationTest` – ตรวจสอบ win-amount formula (amount × payout, rounded 2dp), reconciliation totals (total_win, winning_item_count, net_revenue) ทุก scenario
  - `ExposureRaceConditionTest` – code analysis + mathematical invariants ของ limit-check formula, race-condition scenario (stale read → oversell), atomic increment guard
  - รวม 141 tests, 598 assertions ผ่านทั้งหมด
- คงค้าง: roadmap Concord/Proxy cleanup ก่อน refactor ใหญ่ (เริ่มแยกแผนแล้วที่ `docs/04_PLANS/2026-03-21_lotto-concord-proxy-cleanup.md`)

แผนงานนี้ใช้สำหรับขับงานระบบ Lotto แยกจากแผนเมนูทีมงาน โดยอิงจากสถานะจริงของโมดูล `packages/Gametech/Lotto`, เอกสาร `docs/internal/01_SYSTEM/lotto-system-handover-th.md`, route ที่ถูกโหลดแล้วใน `packages/Gametech/Lotto/src/Routes/admin.php`, route scaffold ใน `packages/Gametech/Lotto/src/Routes/api.php`, และ test ที่มีอยู่ใน `tests/Unit/Lotto/SettlementServiceTest.php`

### Steps
1. ตรึงกติกาหลักของโดเมนก่อนแตกงานย่อย โดยยึด flow ใน `packages/Gametech/Lotto/src/Services/BetService.php` และ invariant ใน `docs/internal/01_SYSTEM/lotto-system-handover-th.md` ได้แก่ draw ต้องเป็น `open`, ต้องเช็ค permission, bet type ต้องมาจาก `BetType::all()`, ต้องเช็คเลขอั้น, min/max, exposure, แล้วจึงสร้าง ticket/item และอัปเดตยอด sold.
2. จัด maturity map ของฝั่ง admin จาก `packages/Gametech/Lotto/src/Routes/admin.php` แยกเมนูที่เป็น CRUD จริงแล้ว (`groups`, `markets`, `rate_plans`, `default_settings`, `member_rate_plans`, `draws`, `number_blocks`, `tickets`, `reports/revenue`) ออกจากเมนูที่ยังเป็น section/placeholder (`member_permissions`, `reports/exposure`) เพื่อให้รู้ว่าส่วนไหนต้องพัฒนา controller + validation + DataTable ต่อ.
3. ปิดงาน config layer ให้ครบทั้งสายก่อน โดยทวนความสัมพันธ์ของ `LotteryGroup`, `LotteryMarket`, `LottoMarketBetSetting`, `LottoRatePlan`, `LottoRatePlanItem`, และ `MemberLottoSetting` ให้สอดคล้องกับ pattern เมนูมาตรฐานของ Lotto (DataTable + Transformer + Controller + Route + Views) ตามที่อ้างอิงใน `docs/internal/05_ARCHIVE/rules/agents.md` และโฟลเดอร์ `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/`.
4. ทำ draw lifecycle ให้ครบจากต้นน้ำถึงปลายน้ำ โดยใช้ `packages/Gametech/Lotto/src/Services/DrawService.php` สำหรับ create/open/close draw, ใช้ `packages/Gametech/Lotto/src/Services/SettlementService.php` สำหรับบันทึกผลเลขออกและตัดสินบิล, และทำหน้า admin เมนู `draws` ให้รองรับการกรอก `result_number`, การสั่ง settle, และการแสดงผลสรุปที่สอดคล้องกับ `SettlementService::normalizeResultNumber`, `isWinningBet`, และ `describeResultNumber`.
5. ขยายงานเลขอั้นและความเสี่ยงจากโครงสร้างที่มีอยู่จริง โดยยึด `lotto_number_blocks` และคำอธิบายใน `BetService::resolveBlockMode()` ว่า `block` คืออั้นเฉพาะงวดที่ระบุ ส่วน `limit_future` ใช้กับงวดถัดไปและงวดต่อ ๆ ไปของ market เดียวกัน จากนั้นต่อยอดมุมมอง admin/report ให้เห็นผลกระทบต่อการรับแทงและ exposure ได้ชัด.
6. ต่อ member/API layer จาก scaffold ใน `packages/Gametech/Lotto/src/Routes/api.php` ให้ครบสำหรับ list draws, draw detail, place bet, ticket history, ticket detail, cancel ticket โดยให้ controller จริงเรียก service layer เดิมแทนการย้าย business rule ไปไว้ใน controller และต้องคงลำดับ validation ตาม `BetService`.
7. ปิด reporting และผลลัพธ์หลังออกรางวัล โดยเชื่อมเมนู `tickets`, `reports/exposure`, `reports/revenue` เข้ากับข้อมูลจริงหลัง settlement เพื่อให้ตอบคำถามงานปฏิบัติการได้ว่าเลขใดรับไปเท่าไร บิลไหนถูกรางวัล บิลไหนไม่ถูกรางวัล และยอดจ่ายรวมของงวดเป็นเท่าไร.
8. เพิ่ม test coverage ให้รองรับของที่เสี่ยงพังมากที่สุดก่อน โดยต่อจาก `tests/Unit/Lotto/SettlementServiceTest.php` ไปยังกรณี normalize/settle, invalid result, block mode, member permission, payout lookup, และโดยเฉพาะ concurrent bet ที่เกี่ยวกับ transaction + `lockForUpdate` ใน `ExposureService`.
9. ทบทวนขอบเขต Concord/Proxy ในโมดูล Lotto ก่อนขยายงานเพิ่ม เพราะเอกสาร handover ระบุว่ายังมีหลายจุดที่ service เรียก model class ตรง หากจะทำให้สอดคล้องกับโมดูลอื่นควรวางแนวทางให้ชัดก่อน refactor รอบใหญ่.

### Further Considerations
1. ก่อนแตกงานละเอียด ควรเลือกแนวลำดับงานให้ชัดว่าเป็น `admin-first`, `end-to-end`, หรือ `reporting-first` เพราะมีผลกับลำดับของ draw/ticket/settlement/report.
2. ต้อง confirm business rule ที่ยังค้างก่อน finalize settlement และ UI ได้แก่ วิ่งบน/วิ่งล่าง, การเก็บเลขแบบ zero-padded, และ format `result_number` ของแต่ละ market เพราะจะกระทบทั้ง validation, การเทียบรางวัล, และรายงานย้อนหลัง.
3. แม้ `packages/Gametech/Lotto/src/Routes/api.php` มี route scaffold แล้ว แต่เอกสาร handover ระบุว่ายังไม่โหลดใช้งานจริง ดังนั้นก่อนต่อ member flow ต้องเช็ค `LottoServiceProvider` และ `php artisan route:list | grep lotto` ทุกครั้ง.
4. ถ้าจะเปิดใช้งาน member permissions แบบจริงจัง เมนู `member_permissions` ต้องเปลี่ยนจาก section placeholder ไปเป็นโครงสร้างมาตรฐานเดียวกับเมนู `groups` และต้องนิยาม rule default/override ต่อ member ให้ชัดก่อน.
5. หากต้องการให้ admin ใช้งานได้จริงแบบ production-ready ควรมี phase hardening เพิ่มสำหรับ audit log, bulk operations, การยกเลิก ticket หลังรับแทง, และ reconciliation หลัง settlement.
