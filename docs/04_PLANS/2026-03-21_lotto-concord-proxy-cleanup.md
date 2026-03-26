> สถานะ: PENDING
> วันที่: 2026-03-21
> โดเมน/เรื่อง: Lotto / Concord Proxy Cleanup
> แทนแผนเก่า: docs/04_PLANS/2026-03-20_lotto-system-roadmap.md

## แผน: Lotto Concord/Proxy Cleanup

สถานะล่าสุด (2026-03-21):
- จุดคงค้างหลักของ Lotto หลังปิดงาน test เสี่ยงสูงแล้ว คือ roadmap การ cleanup ฝั่ง Concord/Proxy ก่อน refactor ใหญ่
- ชุดทดสอบปัจจุบันผ่านทั้งหมด: 141 tests, 598 assertions
- API/admin routes ของ Lotto โหลดครบ (`route:list --name=lotto`)
- เริ่มแล้ว (Phase 0): ขยาย guardrail ใน `tests/Unit/Lotto/LottoConcordProxyAuditTest.php` ให้ครอบคลุม API/Admin controllers เพิ่ม (ห้าม `new` Lotto model ตรงใน controller)
- ยืนยันแล้ว: guardrail tests ผ่านด้วย `phpunit` = 49 tests, 344 assertions
- เริ่มแล้ว (Phase 1): cleanup read path ฝั่ง API ใน `DrawController` และ `TicketController` โดยแยก helper กลาง (member/limit/query/payload mapping) และคง response เดิม
- ยืนยันแล้ว: Lotto unit suite ยังผ่านครบ 141 tests, 598 assertions
- อัปเดตรอบล่าสุด: ทำ readability cleanup ใน service หลัก (`BetService`, `DrawService`, `SettlementService`, `MemberMarketPolicyService`, `ExposureService`) โดยไม่เปลี่ยน behavior

แผนนี้เน้นลดความเสี่ยงแบบเป็นเฟส: ล็อก guardrail ก่อน, เก็บ quick win ใน read path ก่อน, ค่อยแตะ service/controller write path, และแยกงาน refactor ลึกเป็น optional gate

### Phase 0 — Baseline + Guardrail Freeze
**Entry Criteria**
- baseline ปัจจุบันเขียวทั้งหมด

**ขอบเขตไฟล์หลัก**
- `tests/Unit/Lotto/LottoConcordProxyAuditTest.php`
- `tests/Unit/Lotto/LottoApiRouteScaffoldTest.php`
- `packages/Gametech/Lotto/src/Providers/ModuleServiceProvider.php`
- `packages/Gametech/Lotto/src/Models/*Proxy.php`

**งานหลัก**
1. ตรึงขอบเขตกติกา Concord/Proxy ที่ต้องถือเป็น baseline (โดยไม่ขยายจน false positive สูง)
2. ยืนยัน mapping model/proxy/provider ยังครบและ route scaffold ยังไม่ถดถอย

**Definition of Done**
- Guardrail tests ผ่าน
- ไม่มี regression ของ route scaffolding

**Validation Commands**
```bash
php artisan route:list | grep lotto
php -d memory_limit=512M vendor/bin/phpunit tests/Unit/Lotto/LottoConcordProxyAuditTest.php tests/Unit/Lotto/LottoApiRouteScaffoldTest.php
```

### Phase 1 — Quick Win: Read Path Cleanup (No Behavior Change)
**Entry Criteria**
- Phase 0 ผ่าน

**ขอบเขตไฟล์หลัก**
- `packages/Gametech/Lotto/src/Http/Controllers/Api/DrawController.php`
- `packages/Gametech/Lotto/src/Http/Controllers/Api/TicketController.php`
- `packages/Gametech/Lotto/src/Services/ExposureService.php`

**งานหลัก**
1. ลด query logic ซ้ำใน controller read path
2. จัดรูปแบบการเข้าถึง model ให้สอดคล้องแนวทางเดียวกันทั้งโมดูล
3. รักษา response payload เดิม 100%

**Definition of Done**
- ไม่มี behavior change ที่สังเกตได้จาก API response
- Query path อ่านง่ายขึ้นและซ้ำซ้อนน้อยลง

**Validation Commands**
```bash
php -d memory_limit=512M vendor/bin/phpunit tests/Unit/Lotto/LottoApiRouteScaffoldTest.php tests/Unit/Lotto/LottoConcordProxyAuditTest.php
```

### Phase 2 — Service-Layer Proxy Alignment (Moderate Risk)
**Entry Criteria**
- Phase 1 ผ่าน

**ขอบเขตไฟล์หลัก**
- `packages/Gametech/Lotto/src/Services/BetService.php`
- `packages/Gametech/Lotto/src/Services/DrawService.php`
- `packages/Gametech/Lotto/src/Services/SettlementService.php`
- `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php`

**งานหลัก**
1. ทำให้ service layer ใช้แนวทาง model-access เดียวกัน
2. คง invariant เดิม: `DB::transaction` และ `lockForUpdate` ใน flow เสี่ยง race condition
3. แยกการปรับทีละ service เพื่อลด blast radius

**Definition of Done**
- concurrency/reconciliation tests ยังผ่าน
- settlement/exposure flow ไม่เปลี่ยนผลลัพธ์

**Validation Commands**
```bash
php -d memory_limit=512M vendor/bin/phpunit tests/Unit/Lotto/LottoConcurrencyGuardTest.php tests/Unit/Lotto/ExposureRaceConditionTest.php tests/Unit/Lotto/SettlementReconciliationTest.php tests/Unit/Lotto/SettlementServiceTest.php
```

### Phase 3 — Controller Write Boundary Hardening (Moderate-High Risk)
**Entry Criteria**
- Phase 2 ผ่าน

**ขอบเขตไฟล์หลัก**
- `packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoDrawController.php`
- `packages/Gametech/Lotto/src/Http/Controllers/Admin/LotteryGroupController.php`
- `packages/Gametech/Lotto/src/Http/Controllers/Admin/LotteryMarketController.php`
- `packages/Gametech/Lotto/src/Http/Controllers/Api/TicketController.php`

**งานหลัก**
1. ลด persistence logic ใน controller
2. ให้ write operations delegate ไป service อย่างสม่ำเสมอ
3. คุม endpoint สำคัญ: `open`, `close`, `settle`, `apply-rollout`, `cancel`

**Definition of Done**
- เข้าสู่ standard controller boundary ที่คาดเดาได้
- admin/API write flow ยังทำงานครบ

**Validation Commands**
```bash
php artisan route:list | grep lotto
php -d memory_limit=512M vendor/bin/phpunit tests/Unit/Lotto/
```

### Phase 4 — Deep Concord Contract/Proxy Refactor (Optional Gate)
**Entry Criteria**
- ต้องมี sign-off ชัดเจนหลัง Phase 0-3 ผ่าน

**ขอบเขตไฟล์หลัก**
- `packages/Gametech/Lotto/src/Models/*.php`
- `packages/Gametech/Lotto/src/Contracts/*.php`
- `packages/Gametech/Lotto/src/Providers/ModuleServiceProvider.php`

**งานหลัก**
1. ตัดสินใจแนว contract/proxy strictness สำหรับ Lotto ทั้งโมดูล
2. ปรับ type/relations/provider mapping ตามแนวทางที่เลือก
3. อัปเดต guardrail tests ให้สะท้อน baseline ใหม่

**Definition of Done**
- ได้ baseline Concord/Proxy ที่นิยามชัดและ maintainable
- tests ที่เกี่ยวข้องผ่านครบ

### Rollback Strategy
1. แยก commit ตาม phase หรืออย่างน้อยตาม service/controller กลุ่มเล็ก
2. ถ้าเจอ regression ให้ rollback เฉพาะ phase ล่าสุดก่อน
3. หลีกเลี่ยงแตะหลายแกนพร้อมกัน (route + service + contract) ใน PR เดียว

### Suggested Execution Order
1. เริ่มทันที: Phase 0 -> Phase 1 (quick win)
2. เมื่อนิ่ง: Phase 2
3. แยก release: Phase 3
4. ทำเป็น RFC/refactor track แยก: Phase 4
