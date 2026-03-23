# แผนงาน Frontend API V1 (Gametech)

## เป้าหมาย
- สร้างแพ็กเกจใหม่ `packages/Gametech/FrontendApi` สำหรับให้ Frontend เรียกใช้งาน API โดยตรง
- ใช้มาตรฐานเส้นทาง `api/v1`
- ใช้การยืนยันตัวตนแบบ `Bearer Token`
- ออกแบบแบบ BFF Aggregator โดย reuse business logic จาก Wallet/API/Lotto เดิม

## ขอบเขตงาน
1. สร้าง package และ service provider
2. สร้าง middleware สำหรับตรวจ Bearer token
3. สร้าง token service (issue/decode/blacklist)
4. สร้าง API controller สำหรับ auth/member/wallet/game/lotto
5. ประกาศ route ใหม่ให้ครบตาม frontend use-case
6. ลงทะเบียน package ใน `composer.json`, `config/app.php`, `config/concord.php`
7. ตรวจ syntax และ route ที่สร้าง

## API ที่ต้องมี (v1)

### Auth
- `POST /api/v1/auth/register`
  - รับข้อมูลสมัครสมาชิก
- `POST /api/v1/auth/login`
  - รับ user/pass และคืน Bearer token
- `POST /api/v1/auth/logout` (ต้องมี token)
  - blacklists token ปัจจุบัน

### Member
- `GET /api/v1/member/profile` (ต้องมี token)
  - ส่งข้อมูลสมาชิก
- `GET /api/v1/member/balance` (ต้องมี token)
  - ส่งยอดเงินคงเหลือ

### Wallet
- `POST /api/v1/wallet/withdraw` (ต้องมี token)
  - ส่งคำขอถอนเงิน

### Game
- `GET /api/v1/games/types`
  - ส่งประเภทเกม เช่น slot/casino/sport/lotto
- `GET /api/v1/games/providers/{type}`
  - ส่งรายการค่ายเกม
- `GET /api/v1/games/{type}/{provider}`
  - ส่งรายการเกม
- `POST /api/v1/games/login` (ต้องมี token)
  - game login / auto login

### Lotto
- `GET /api/v1/lotto/draws`
  - ส่งงวดหวยที่เปิด/เกี่ยวข้อง
- `GET /api/v1/lotto/draws/{id}`
  - ส่งรายละเอียดงวด
- `POST /api/v1/lotto/bet` (ต้องมี token)
  - รับโพยจากลูกค้า
- `GET /api/v1/lotto/tickets` (ต้องมี token)
  - ประวัติโพย
- `GET /api/v1/lotto/tickets/{id}` (ต้องมี token)
  - รายละเอียดโพย
- `POST /api/v1/lotto/tickets/{id}/cancel` (ต้องมี token)
  - ยกเลิกโพย (ตามเงื่อนไขระบบ)

## แนวทางออกแบบ
- ไม่เปลี่ยน route เดิมและไม่แก้ payload ของระบบเดิม
- wrapper controller จะเรียกใช้ logic เดิมจาก Wallet/API/Lotto โดยตรงเพื่อลด regression
- token auth ใหม่ใช้เฉพาะเส้นทาง Frontend API v1
- logout เป็นการ blacklist token ที่ระดับ cache

## ลำดับการทำงาน (Step)
1. สร้างโครง package `FrontendApi`
2. เขียน `FrontendApiServiceProvider` และ `ModuleServiceProvider`
3. เขียน `FrontendTokenService`
4. เขียน middleware `AuthenticateFrontendToken`
5. เขียน controllers:
   - `AuthController`
   - `MemberController`
   - `WithdrawController`
   - `GameController`
   - `LottoController`
6. เขียน `Routes/api.php` สำหรับ `/api/v1/*`
7. ลงทะเบียน package ใน config/autoload
8. รัน `composer dump-autoload`
9. ตรวจ syntax (`php -l`) และตรวจ route (`php artisan route:list`)

## หมายเหตุด้านความปลอดภัย
- token ใช้ JWT (HS256) อิง `APP_KEY`
- ตรวจ `exp` และ `jti` ทุกครั้ง
- บังคับใช้ token middleware กับ route ที่ต้อง auth เท่านั้น
- blacklist token ตอน logout

