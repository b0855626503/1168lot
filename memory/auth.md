# Auth Memory

อัปเดตล่าสุด: 2026-04-25

## Responsibility

ดูแล customer auth contract ของ FrontendApi: register/login/logout + token guard

## Key Endpoints

- `POST /api/v1/auth/register`
- `GET /api/v1/auth/register/banks`
- `POST /api/v1/auth/register/bank-account-name`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `POST /api/v1/realtime/auth` (repeated active usage: channel auth handshake)

## Module Map

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/AuthController.php`
- `packages/Gametech/FrontendApi/src/Http/Middleware/AuthenticateFrontendToken.php`
- `config/auth.php`

## Main Services / Actions

- register validation + member creation
- token issue/revoke lifecycle
- single active token ต่อ member; login ใหม่ invalidate token เดิมทันทีผ่าน active token cache
- auth guard enforcement for protected endpoints

## Important Dependencies

- member domain (`packages/Gametech/Wallet/src/`)
- language middleware / response contract ของ FrontendApi

## Short Execution Flow

- register/login request -> AuthController -> domain/repository flow -> token/response
- protected request -> AuthenticateFrontendToken -> controller action
- realtime private/presence subscription -> `/api/v1/realtime/auth` -> auth middleware -> channel authorization

## Source-of-Truth References

- `docs/internal/03_DOMAINS/auth.md`
- `docs/public/api/frontend-v1/03-endpoints.md`
- `docs/internal/01_SYSTEM/system-current-state/03-endpoints.md`
