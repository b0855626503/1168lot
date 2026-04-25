# System Current State - Endpoints

อัปเดตล่าสุด: 2026-04-25

## Canonical Endpoint Source

- Frontend API: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Public API docs index: `docs/public/api/frontend-v1/index.md`

## Endpoint Groups

- Public: auth register/login, games, slides, meta, lotto read, realtime config
- Authenticated: member, wallet, coupon, deposit, promotion, game login, lotto bet/ticket, wheel
- Deposit endpoints:
  - `POST /api/v1/deposit/loadbank` คืนรายการบัญชีรับฝากตาม method
  - `POST /api/v1/deposit/loadbank/random` คืนบัญชีรับฝากแบบสุ่ม 1 รายการ โดยใช้ visibility scope และ media URL normalization ชุดเดียวกับ `loadbank`
- Lotto navbar endpoint:
  - `GET /api/v1/lotto/navbar-config`
  - optional query `code` (default `mobile_bottom_nav`)
  - default code ไม่มี published row => `404`

## Guardrails

- endpoint เปลี่ยน = ต้องอัปเดต `docs/public/api/frontend-v1/03-endpoints.md`
- contract เปลี่ยน = ต้องอัปเดต overview/edge-case ที่เกี่ยวข้อง
