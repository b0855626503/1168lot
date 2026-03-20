# Lotto System Handover (v1)

อัปเดตล่าสุด: 2026-03-19
แพ็กเกจหลัก: `packages/Gametech/Lotto`

## 1) ภาพรวมที่ต้องเข้าใจก่อน
- โปรเจคหลักเป็น Laravel 8 + Concord (package-first) และ `Lotto` ถูกทำเป็นโมดูลแยก
- โมดูลถูก register 2 จุด:
  - `config/concord.php` -> `Gametech\Lotto\Providers\ModuleServiceProvider`
  - `config/app.php` -> `Gametech\Lotto\Providers\LottoServiceProvider`
- ตอนนี้โค้ดเน้น Core Schema + Service layer เป็นหลัก; ฝั่ง Controller/View ยังไม่ลง
- ข้อมูลสมาชิก/กระเป๋าเงิน/auth ไม่ทำซ้ำใน Lotto แต่พึ่งพา `members` จากระบบหลัก

## 2) โครงสร้างโมดูลที่มีอยู่จริง
- `src/Enums/BetType.php` -> fixed bet types (ห้าม dynamic)
- `src/Database/Migrations/*` -> ตารางหลักครบตาม phase core
- `src/Models/*` + `*Proxy.php` -> model + Concord proxy mapping
- `src/Services/BetService.php` -> flow แทงเลข + transaction
- `src/Services/ExposureService.php` -> lock exposure row (`lockForUpdate`)
- `src/Services/DrawService.php` -> create/open/close draw + snapshot settings
- `src/Routes/admin.php` -> admin route ที่โหลดจริงแล้วสำหรับหน้า Lotto แต่ละเมนู
- `src/Http/Controllers/Admin/SectionController.php` -> placeholder controller สำหรับเมนู admin
- `src/Resources/views/admin/module/lotto/section.blade.php` -> placeholder page ใช้ร่วมกันทุกเมนู
- `src/Routes/api.php` -> route scaffold ฝั่ง member API (ยังไม่โหลดใช้งานจริง)
- `src/Config/admin-menu.php`, `src/Config/acl.php` -> มี menu/ACL ขั้นต่ำแล้ว

## 3) Data model ที่เป็นแกนระบบ
- Config layer: `lotto_groups`, `lotto_markets`, `lotto_market_bet_settings`, `lotto_rate_plans`, `lotto_rate_plan_items`
- Draw layer: `lotto_draws`, `lotto_draw_bet_settings` (snapshot จาก market)
- Risk layer: `lotto_number_exposures` (unique: `draw_id + bet_type + number`)
- Blocking layer: `lotto_number_blocks` (`mode` มี `block`, `limit_future` แต่ v1 ใช้ block)
- Ticket layer: `lotto_tickets`, `lotto_ticket_items`
- Member layer: `member_lotto_permissions`, `member_lotto_settings`

## 4) Flow สำคัญ (ตอนแทง) ที่ห้ามเปลี่ยนลำดับ
1. เช็ค draw เป็น `open`
2. เช็ค member permission
3. เช็ค bet type เปิดใช้ใน draw snapshot
4. เช็คเลขอั้น (`lotto_number_blocks`)
5. เช็ค min/max ต่อรายการ
6. lock exposure row
7. เช็ค `sold + amount <= max_per_number`
8. insert `lotto_tickets` + `lotto_ticket_items`
9. increment `lotto_number_exposures.sold_amount`

อ้างอิง implementation: `src/Services/BetService.php`, `src/Services/ExposureService.php`

## 5) Rules ที่เป็น invariant ของระบบ
- `max_per_number` = limit รวมทั้งเว็บในงวดนั้น (ไม่ใช่ per member)
- Draw ต้อง snapshot ค่าจาก market ตอนเปิดงวด (`DrawService::openDraw` + `snapshotBetSettings`)
- Exposure ต้อง atomic ด้วย transaction + row lock (ห้ามใช้ sum query ธรรมดา)
- Bet type ต้องมาจาก enum `BetType::all()` เท่านั้น

## 6) สถานะ implementation ปัจจุบัน
- ทำแล้ว:
  - schema migration ครบแกนหลัก v1
  - model relations + proxy registration ใน `ModuleServiceProvider`
  - service หลัก: `BetService`, `ExposureService`, `DrawService`
  - admin menu + ACL + route wiring สำหรับ Lotto
  - placeholder admin pages ที่กดเข้าแต่ละเมนูได้
  - route scaffold ของ member API
- ยังไม่ทำ/ต้องต่อ:
  - controller/request จริงของแต่ละ section (CRUD / DataTable / validation)
  - integration กับ UI/admin datatable/report
  - automated tests ของ Lotto package (โดยเฉพาะ concurrent bet)

## 7) จุดเสี่ยงที่ควรเช็คก่อนพัฒนาต่อ
- หน้า admin Lotto ตอนนี้เป็น placeholder เพื่อให้กดเข้าแต่ละเมนูได้ก่อน ยังไม่ใช่ CRUD จริง
- ใน service ยังเรียก model class ตรงหลายจุด; ถ้าจะเข้ม Concord ควรทบทวนแนวใช้ Proxy/contract ให้สอดคล้องโมดูลอื่น
- ต้อง confirm business rule เพิ่มเติมก่อนตัดสินผล:
  - logic วิ่งบน/วิ่งล่าง
  - format เลขเก็บเป็น string แบบ zero-padded หรือไม่
  - format `result_number` ของแต่ละ market

## 8) คำสั่งเริ่มงานต่อ (จาก root โปรเจค)
```bash
cd /home/boat/Projects/1168lot
php artisan optimize:clear
php artisan migrate
php artisan route:list | grep lotto
php artisan test --filter=Lotto
```

ถ้า `route:list` ยังไม่เห็น route ของ Lotto ให้เริ่มจากเพิ่ม `loadRoutesFrom` ใน `packages/Gametech/Lotto/src/Providers/LottoServiceProvider.php` ก่อน
