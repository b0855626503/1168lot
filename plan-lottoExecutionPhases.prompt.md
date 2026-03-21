## แผน: Lotto Execution Phases

สถานะล่าสุด (2026-03-21):
- Phase 1 เสร็จเป็นหลัก: เมนูแกนหลักใช้งานได้, pattern controller/DataTable/Transformer/views ถูกล็อกตาม `groups`, และมี test guard/coverage หลักใน `tests/Unit/Lotto/*`
- Phase 2 เสร็จเป็นหลัก: `LottoDrawController` ผูกกับ `DrawService`, มี open/close/settle flow และ route พร้อมใช้งาน
- Phase 3 เสร็จเป็นหลัก: `SettlementService` ใช้งานจริงใน `LottoDrawController::settle`, ตั๋ว/รายการถูกอัปเดตผลหลังประกาศผล
- Phase 4 เสร็จแล้ว: `member_permissions` และ `reports/exposure` เปลี่ยนจาก placeholder เป็นโมดูลจริงแล้ว
- Phase 5 เสร็จแล้ว: API routes (`draws/bet/tickets`) โหลดจริงผ่าน `LottoServiceProvider` และเห็นใน `php artisan route:list | grep lotto`
- Phase 6 เสร็จแล้ว (2026-03-21): เพิ่ม test suite ครบ 6 ชุดใหม่ และขยาย Concord guardrails ใน API/Admin controllers รวม 141 tests ผ่านทั้งหมด:
  - `BetTypeTest` – ครอบ BetType enum (all(), label(), distinct constants, snake_case)
  - `SettlementEdgeCasesTest` – edge cases: leading zeros, non-numeric stripping, TOD_3 permutations/repeated-digits, run top/bottom boundary, unknown type fallback
  - `LottoConcurrencyGuardTest` – code analysis: ยืนยัน DB::transaction + lockForUpdate ครบทุก service/controller ที่เสี่ยง race condition
  - `LottoConcordProxyAuditTest` – code analysis: ยืนยันว่า service layer ไม่ใช้ `new ModelName()` ตรง, ทุก model มี *Proxy.php, ModuleServiceProvider ลงทะเบียนครบ
  - `SettlementReconciliationTest` – ตรวจสอบ win-amount formula (amount × payout rounded 2dp), totals, net revenue, settlement result structure ทุก scenario (all win / no win / mixed / TOD_3 permutations)
  - `ExposureRaceConditionTest` – code analysis + mathematical invariants: limit-check formula (at/over/under boundary + fractional), race-condition stale-read scenario, atomic increment guard ordering

แผนนี้แตกต่อจาก `plan-lottoSystemRoadmap.prompt.md` เพื่อใช้ขับงานแบบเป็น phase โดยยึดสถานะจริงของโมดูล `packages/Gametech/Lotto` ณ วันที่ 2026-03-20 และเน้นลำดับแบบ `admin-first` เพื่อให้ทีมส่งงานทีละส่วนได้โดยไม่กระทบ flow แทงและตัดสินผลเร็วเกินไป

### Phase 1 — Harden Admin Core ที่มีของจริงแล้ว
**ขอบเขต**
- เมนู `groups`, `markets`, `rate_plans`, `default_settings`, `member_rate_plans`, `number_blocks`, `tickets`
- controller / DataTable / Transformer / views ที่ถูกใช้งานจริงแล้ว

**งานหลัก**
1. ล็อก pattern ของเมนูให้สอดคล้อง golden reference `groups` แบบเดียวกันทั้ง route, controller, DataTable, Transformer, views.
2. Harden จุดเสี่ยงใน controller เช่น allowlist field ที่ toggle ได้, validation ซ้ำ, และ normalize ค่า input ที่ form ส่งมา.
3. เพิ่ม unit/feature tests สำหรับ logic ที่ไม่ควรพังจากการแก้ admin CRUD.
4. อัปเดต `AGENTS.md` และไฟล์แผนให้สะท้อน pattern ล่าสุดของ Lotto admin.

**Definition of Done**
- เมนู admin core เปิดได้ครบ
- action toggle/edit ไม่ยอมให้ update field ที่อยู่นอก allowlist
- มี test รองรับอย่างน้อยส่วน guard/validation ที่เพิ่มใหม่
- ไม่มี error ใหม่จาก static analysis ของไฟล์ที่แก้

### Phase 2 — Draw Lifecycle
**ขอบเขต**
- เมนู `draws`
- `DrawService` และการ snapshot ค่าจาก market

**งานหลัก**
1. ให้ `LottoDrawController` พึ่ง `DrawService` มากขึ้นสำหรับ create/open/close/snapshot.
2. ล็อกกติกา `draw_date`, `open_at`, `close_at`, `status` ให้ชัดและห้ามข้ามลำดับ state.
3. เตรียม data structure สำหรับ `result_number` ให้พร้อมใช้ต่อใน settlement.

**Definition of Done**
- เปิด/ปิดงวดได้ตาม state ที่ถูกต้อง
- snapshot settings ถูกสร้างตอนเปิดงวด
- validation ของงวดไม่ขัดกับ invariant ใน handover

### Phase 3 — Settlement + Revenue
**ขอบเขต**
- `LottoDrawController::settle`
- `SettlementService`
- `tickets`, `reports/revenue`

**งานหลัก**
1. ทำ flow บันทึกผลเลขออกและประมวลผล ticket ให้ครบ.
2. แสดงผลว่าบิลไหน win/lose และยอดถูกเท่าไร.
3. ผูกผล settlement เข้ากับรายงานรายได้.

**Definition of Done**
- admin ใส่ผลรางวัลได้
- ticket/item ถูกอัปเดตสถานะหลัง settle
- revenue report อ่านข้อมูลหลัง settlement ได้จริง

### Phase 4 — Placeholder Menus to Real Modules
**ขอบเขต**
- `member_permissions`
- `reports/exposure`

**งานหลัก**
1. เปลี่ยนจาก `SectionController` เป็น controller + DataTable + Transformer + views แบบมาตรฐาน.
2. นิยาม behavior ของ permission default/override ต่อ member ให้ชัด.
3. ทำ exposure report ให้ตอบโจทย์การดูความเสี่ยงต่อเลข/งวด.

**Definition of Done**
- ไม่มีเมนู Lotto ที่ยังเป็น placeholder ใน admin flow หลัก
- รูปแบบไฟล์สอดคล้อง pattern เดียวกับ `groups`

### Phase 5 — Member/API Flow
**ขอบเขต**
- `packages/Gametech/Lotto/src/Routes/api.php`
- API controllers สำหรับ draws / bet / tickets

**งานหลัก**
1. โหลด API routes ผ่าน `LottoServiceProvider` เมื่อพร้อม.
2. ให้ controller เรียก `BetService` และ service layer เดิม ไม่ฝัง business rule ซ้ำใน controller.
3. รองรับ draw list/detail, bet, ticket history/detail, cancel.

**Definition of Done**
- `php artisan route:list | grep lotto` เห็น API routes ที่ต้องใช้
- flow แทงผ่าน API คง validation order เดิมของ `BetService`

### Phase 6 — Hardening, Test, Concord Cleanup
**ขอบเขต**
- service layer ทั้งหมด
- test suite ของ Lotto
- จุดที่ยังเรียก model class ตรง

**งานหลัก**
1. เพิ่ม test coverage สำหรับ `BetService`, `ExposureService`, `SettlementService`, concurrent bet.
2. ทบทวนการใช้ Proxy/Concord ให้สอดคล้องกับมาตรฐานของระบบ.
3. เตรียม hardening เพิ่ม เช่น audit log, reconciliation, bulk operations.

**Definition of Done**
- มี test ครอบคลุม flow สำคัญของ Lotto อย่างเพียงพอ
- จุดเสี่ยงเรื่อง race condition และ settlement มี regression test
- แผน refactor Concord ถูกนิยามชัดก่อนแตะของใหญ่

### หมายเหตุสำหรับการลงมือรอบถัดไป
1. ถ้าจะเริ่มจากงานที่กระทบผู้ใช้ admin น้อยที่สุด ให้เริ่ม Phase 1 จาก controller hardening + tests ก่อน
2. ก่อนทำ Phase 2/3 ต้องยืนยัน business rule ที่ยังค้าง: วิ่งบน/วิ่งล่าง, zero-padding, และรูปแบบ `result_number`
3. ถ้าจะเปิด API จริง ให้ทำหลัง draw + settlement นิ่งก่อน เพื่อไม่ให้ member flow วิ่งเร็วกว่าฝั่ง operations/admin
