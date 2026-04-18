# Auth Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแล flow ยืนยันตัวตนของลูกค้าผ่าน FrontendApi (register/login/logout + token guard)

## Key Flows (สั้น)

- register -> validate payload -> create member -> respond contract
- login -> issue access token -> return profile baseline
- logout -> invalidate token/session context

## Important Modules

- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/AuthController.php`
- `packages/Gametech/FrontendApi/src/Http/Middleware/AuthenticateFrontendToken.php`
- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `config/auth.php`

## Dependencies

- member data (`packages/Gametech/Wallet/src/`)
- language middleware และ response contract ฝั่ง FrontendApi
