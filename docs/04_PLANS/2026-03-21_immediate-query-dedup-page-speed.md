> สถานะ: DONE
> วันที่: 2026-03-21
> โดเมน/เรื่อง: Core/Admin / Query Dedup
> แทนแผนเก่า: -

## แผน: Immediate Query Dedup + Page Speed

สถานะล่าสุด (2026-03-21):
- เสร็จแล้ว: เพิ่ม request-scoped memoization ใน `packages/Gametech/Core/src/Core.php` ให้ `getConfigData()`, `getContact()`, `getNoticeData()`, `getNoticeNewData()`, `getGameType()`, `getProfile()`
- เสร็จแล้ว: `app/Providers/AppServiceProvider.php` ใช้ request bag (`rememberRequestValue`) ลดการ resolve ซ้ำใน view composers
- เสร็จแล้วบางส่วน: `packages/Gametech/Admin/src/Providers/AdminServiceProvider.php` ปรับ menu/acl cache เป็น request-scoped และ memoize permission lookup
- คงค้าง: sweep จุดเรียก config/query ซ้ำในโมดูลใหญ่ (`Payment`, `Member`, `Promotion`) แบบ low-risk ทีละหน้า
- อัปเดตล่าสุด: ทำ sweep ฝั่ง `Payment` เพิ่มแล้วใน `BankPaymentRepository`, `BillRepository`, `WithdrawRepository`, `PaymentPromotionRepository` โดยใช้ request-scoped config cache
- อัปเดตล่าสุด: ทำ sweep ฝั่ง `Payment` controllers ครบจุดที่เรียก `core()->getConfigData()` แล้ว โดยย้ายไปใช้ helper `getCoreConfig()` กลางใน `AppBaseController`
- อัปเดตล่าสุด: ทำ sweep ฝั่ง `Member` repositories หลัก (`MemberCashbackRepository`, `MemberIcRepository`, `MemberCreditLogRepository`, `MemberCreditFreeLogRepository`) และ `API` Announce controllers ให้ใช้ request-scoped config helper แล้ว
- อัปเดตล่าสุด: ทำ sweep ฝั่ง `Admin` controllers ที่เรียก config ตรงใน callback/report/member/withdraw flows ให้ใช้ helper `getCoreConfig()` กลางใน `Admin\Http\Controllers\Controller`
- อัปเดตล่าสุด: ปิดจุดคงค้างที่เสี่ยงสูงใน `Core`/`Game`/`Marketing` (`DailyStatRepository`, `GameUserRepository`, `MarketingController`) ให้ใช้ request-scoped config helper
- สถานะรอบนี้: แผน immediate (phase low-risk dedup) ปิดครบตามขอบเขตที่กำหนดไว้แล้ว

แผนงานนี้โฟกัสเฉพาะสิ่งที่ทำได้ทันทีเพื่อลด query ซ้ำและเร่งความเร็วโหลดหน้า โดยข้ามเรื่องการถอด `packages/Gametech/Wallet` ออกก่อน เพราะจาก dependency ที่พบตอนนี้ยังผูกกับ `config/app.php`, middleware `customer`, routes ฝั่ง user-domain, และ view `wallet::...` ในหลายโมดูลอยู่จริง หากจะถอดต้องเป็นแผนแยกอีกชุดหนึ่ง

### Steps
1. เพิ่ม request-scoped memoization ใน `packages/Gametech/Core/src/Core.php` สำหรับ `getConfigData()`, `getContact()`, `getNoticeData()`, `getNoticeNewData()`, `getGameType()`, และ `getProfile()` เพื่อลดการ query ซ้ำใน request เดียวกัน โดยเฉพาะจุดที่เมธอดหนึ่งเรียก `core()->getConfigData()` ซ้อนอีกชั้น.
2. ปรับ `app/Providers/AppServiceProvider.php` ให้ `View::share('webconfig', ...)`, `composeFrontViews()`, และ `composeAdminViews()` ใช้ข้อมูลก้อนเดียวกันต่อ request แทนการ resolve `core` และดึง config/profile/notice ซ้ำหลายรอบใน request เดียว.
3. ลด query และ loop ซ้ำใน `packages/Gametech/Admin/src/Providers/AdminServiceProvider.php` โดย cache `config('menu.admin')`, `config('acl')`, role permissions, และ permission lookup ต่อ request ให้ชัดขึ้น พร้อมหลีกเลี่ยงการเรียก `app()->make(Core::class)->getConfigData()` หนัก ๆ ตั้งแต่ `boot()` ถ้ายังเลี่ยงได้.
4. ตรวจหน้าแอดมินที่หนักที่สุดก่อน โดยเริ่มจาก dashboard และหน้าที่โหลด counters/summary หลายชุด แล้วรวม query ที่นับ/รวมข้อมูลซ้ำกันให้น้อยลง พร้อม cache ช่วงสั้นสำหรับข้อมูลที่ไม่ต้องสดทุกวินาที.
5. ทำ config-consumer sweep แบบ low-risk ใน `packages/Gametech/Admin`, `packages/Gametech/Core`, `packages/Gametech/API`, `packages/Gametech/Payment`, `packages/Gametech/Game`, `packages/Gametech/Marketing`, `packages/Gametech/Member`, และ `packages/Gametech/Promotion` เพื่อเปลี่ยนจุดที่เรียก `core()->getConfigData()` หรือข้อมูลกึ่ง static ซ้ำ ๆ ให้พึ่ง memoized result ก่อน โดยยังไม่ refactor business flow ใหญ่.
6. ใช้ `app/Providers/XrayServiceProvider.php`, `app/Http/Middleware/XraySlowRequest.php`, และ `storage/logs/slow-requests.log` เป็น baseline profiler แล้วเพิ่มการอ่านผลแบบ “query ซ้ำต่อ request” เพื่อเลือก target page ที่ควร optimize ก่อน ไม่ทำแบบสุ่ม.

### Further Considerations
1. ลำดับที่คุ้มที่สุดคือ `Core request cache` → `AppServiceProvider dedup` → `Admin menu/ACL/provider optimization` → `Dashboard/page batching`.
2. `packages/Gametech/Promotion` มี `Cache::remember` และ `LadaCacheTrait` อยู่แล้วหลายจุด จึงควรใช้เป็น baseline ว่าอะไร cache ได้อย่างปลอดภัยก่อนขยายไปโมดูลอื่น.
3. ยังไม่ควรแตะการถอด `Wallet` ในแผนนี้ เพราะจะกระทบ `config/app.php`, `app/Http/Kernel.php`, `routes/web.php`, error views ของ `Admin`, และ customer controllers ใน `Marketing`/`Payment`.
4. ถ้าจะเลือกเริ่มลงมือเพียงชุดเดียวก่อน ให้เริ่มจาก `packages/Gametech/Core/src/Core.php` กับ `app/Providers/AppServiceProvider.php` เพราะสองจุดนี้มีผลต่อหลายแพ็กเกจพร้อมกันและให้ผลกับหน้า web/admin เกือบทั้งหมด.
5. หลังจากแผนนี้เริ่มเดินแล้ว ค่อยแตกแผนย่อยต่อเป็น `coreRequestCache`, `adminMenuPermissionOptimization`, และ `dashboardQueryBatching` ถ้าต้องการแบ่งงานเป็นรอบสั้น ๆ.
