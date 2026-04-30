# Frontend API V1 - Endpoint Index

อัปเดตล่าสุด: 2026-04-30

## Core First

- Auth: `/auth/register`, `/auth/login`, `/auth/logout`
- Member/Wallet: `/member/profile`, `/member/balance`, `/wallet/transactions`
- Lotto Core: `/lotto/draws`, `/lotto/markets/latest`, `/lotto/bet`, `/lotto/tickets`

## Business Groups

- User Lifecycle: auth/member/profile
- Financial: wallet/deposit/smkpay/deeppay/withdraw
- Gaming: games/providers/login
- Lotto: draws/markets/bet/tickets/results
- Engagement: promotion/coupon/reward/wheel
- Realtime: realtime config/auth/heartbeat
- Yeekee: `/lotto/yeekee/*`

รายละเอียด payload/request/response ให้ดูไฟล์เดียว:
- [07-route-reference.md](./07-route-reference.md)
