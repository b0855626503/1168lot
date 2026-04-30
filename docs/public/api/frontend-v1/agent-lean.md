# Frontend API V1 - Agent Lean Reference

อัปเดตล่าสุด: 2026-04-30

วัตถุประสงค์: เอกสารสั้นสำหรับ agent/internal เพื่อลด token cost

- Canonical routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Public docs (สำหรับคนใช้งานจริง): `docs/public/api/frontend-v1/index.md`
- Public docs URL: `/docs/api/frontend-v1`

## Quick Rules

- ถ้าแก้ route/controller ใน Frontend API ต้องอัปเดต:
  - `docs/public/api/frontend-v1/index.md` (public manual entrypoint)
  - `docs/public/api/frontend-v1/07-route-reference.md` (endpoint details)
  - และไฟล์นี้ (lean notes) เฉพาะถ้ามีผลต่อภาพรวม

## Minimal Contract

- Base: `/api/v1`
- Public middleware: `api`, `ResolveFrontendLanguage`
- Auth middleware: `api`, `ResolveFrontendLanguage`, `AuthenticateFrontendToken`
- Envelope หลัก: `success`, `message`, `data` (บาง endpoint มี root fields เฉพาะ)

## Domain Map

- Auth: register/login/logout
- Member: profile/balance/history/realtime context
- Wallet/Coupon: withdraw/claim/transactions/coupon
- Payment: deposit channels + smkpay + deeppay
- Lotto: draws/markets/betting/tickets/bet/cancel
- Yeekee: shoot/current-round/shoots/reward-status/result-proof
- Wheel/Reward: list/spin/history/redeem
