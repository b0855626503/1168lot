# AGENT FrontendApi Playbook

อัปเดตล่าสุด: 2026-03-26

เอกสารนี้สำหรับ Agent ที่ต้องแก้/เพิ่ม/ปรับ `FrontendApi` บ่อย ให้ทำงานเร็วและลด regression

## 1) ขอบเขตโมดูล
- Module: `packages/Gametech/FrontendApi/`
- Route หลัก: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controller หลัก: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`
- Middleware หลัก:
  - `ResolveFrontendLanguage`
  - `AuthenticateFrontendToken`
- Service สำคัญ:
  - `FrontendTokenService` (JWT for frontend bearer token)

## 2) URL + Routing Pattern
- Base URL: `/api/v1`
- Domain routing: ใช้ `config('gametech.api_url')` + `config('app.admin_domain_url'| 'app.domain_url')`
- กลุ่ม public:
  - middleware `['api', ResolveFrontendLanguage::class]`
- กลุ่มต้อง auth:
  - middleware `['api', ResolveFrontendLanguage::class, AuthenticateFrontendToken::class]`

กติกาเพิ่ม endpoint:
1. เพิ่ม route ในไฟล์ `src/Routes/api.php` ให้ตรงกลุ่ม middleware
2. ตั้งชื่อ route เป็น pattern `frontend.api.v1.<feature>.<action>`
3. สร้าง/แก้ controller ใน namespace `Api\V1`

## 3) Response Contract ที่ต้องรักษา
- ส่วนใหญ่ใช้ `sendResponse()` หรือ `sendResponseNew()` จาก `BaseController`
- รูปแบบมาตรฐาน:
  - `success`
  - `message`
  - payload (เช่น `data`, หรือ field root ตาม endpoint เดิม)
- ข้อควรระวัง: `BaseController` มี normalize image URL อัตโนมัติใน key ที่เป็นรูปภาพ (`logo`, `image`, `*_logo` ฯลฯ)

## 4) Language Handling
- รองรับ `th|en|kh|la`
- อ่านจาก query/body/header โดย `ResolveFrontendLanguage`
- เวลาเพิ่ม endpoint ที่มีข้อความหลายภาษา ให้เรียก `requestLanguage($request)` และ map ภาษาให้ตรง pattern controller เดิม

## 5) Auth Handling
- Frontend token อยู่ใน `Authorization: Bearer <token>`
- ตรวจ token ผ่าน `AuthenticateFrontendToken`
- ฝั่งที่ต้อง auth ให้ใช้ `auth()->guard('customer')` และ validate ว่าพบ member ก่อนทำงานต่อ

## 6) จุดที่แก้บ่อย (และควรระวัง)
- เส้น `meta/*` (เช่น online members, contact channels, site info)
- เส้น game list/provider/game login
- เส้น member profile/balance
- เส้น lotto read/write
- เส้น realtime (`/realtime/config`, `/realtime/auth`, `/member/realtime-context`)

ข้อควรระวัง:
- อย่าทำให้ public endpoint กลายเป็น auth โดยไม่ตั้งใจ
- อย่าทำให้โครง response เปลี่ยนชน frontend เดิม
- อย่า hardcode domain/host ให้ใช้ config + helper ของระบบ

## 7) ขั้นตอนทำงานมาตรฐานสำหรับ Agent
1. ระบุ endpoint ว่าอยู่กลุ่ม public หรือ auth
2. ตรวจ route เดิมใน `src/Routes/api.php`
3. ดู controller ใกล้เคียงแล้วทำตาม style เดิม
4. ถ้ามีรูปภาพ ให้ยึด key มาตรฐานเพื่อให้ auto-normalize URL
5. อัปเดตเอกสาร `docs/API_FRONTEND_V1.md` ทุกครั้งที่เพิ่ม/เปลี่ยน contract
6. เช็ก syntax PHP อย่างน้อย:
   - `php -l <controller>`
   - `php -l packages/Gametech/FrontendApi/src/Routes/api.php`

## 8) แผนที่ไฟล์เร็ว (Quick Map)
- Routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Base Controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/BaseController.php`
- Auth: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/AuthController.php`
- Member: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/MemberController.php`
- Game: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/GameController.php`
- Lotto: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/LottoController.php`
- Realtime: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/RealtimeController.php`
- Docs หลัก API: `docs/API_FRONTEND_V1.md`

## 9) ล่าสุดที่เพิ่ม
- เพิ่ม public endpoint:
  - `GET /api/v1/meta/site`
  - route name: `frontend.api.v1.meta.site`
  - payload: `logo`, `title`, `name`, `description`
