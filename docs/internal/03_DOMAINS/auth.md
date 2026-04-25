# Auth Domain Note

อัปเดตล่าสุด: 2026-04-25

## ใช้อ่านเมื่อ

- แก้ register/login/logout
- แก้ token หรือ auth middleware
- แก้ auth-related response contract

## Entry Points

- Route: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/AuthController.php`
- Middleware: `packages/Gametech/FrontendApi/src/Http/Middleware/AuthenticateFrontendToken.php`
- Config: `config/auth.php`

## กติกาหลัก

- ลูกค้า auth contract ต้องอยู่ใน FrontendApi
- สมาชิก 1 คนมี active Frontend API token ได้ครั้งละ 1 ตัวเท่านั้น; login ใหม่ต้องทำให้ token เดิมใช้ต่อไม่ได้
- เปลี่ยน endpoint หรือ payload ต้อง sync doc API ทันที

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- API contracts -> `docs/public/api/frontend-v1/index.md`
- behavior baseline -> `docs/internal/01_SYSTEM/system-current-state/index.md`
- decisions -> `docs/internal/02_DECISIONS/decision-log/index.md`
