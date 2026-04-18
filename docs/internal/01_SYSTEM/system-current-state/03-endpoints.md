# System Current State - Endpoints

อัปเดตล่าสุด: 2026-04-18

## Canonical Endpoint Source

- Frontend API: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Public API docs index: `docs/public/api/frontend-v1/index.md`

## Endpoint Groups

- Public: auth register/login, games, slides, meta, lotto read, realtime config
- Authenticated: member, wallet, coupon, deposit, promotion, game login, lotto bet/ticket, wheel

## Guardrails

- endpoint เปลี่ยน = ต้องอัปเดต `docs/public/api/frontend-v1/03-endpoints.md`
- contract เปลี่ยน = ต้องอัปเดต overview/edge-case ที่เกี่ยวข้อง
