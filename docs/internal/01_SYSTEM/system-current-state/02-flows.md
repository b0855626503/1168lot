# System Current State - Flows

อัปเดตล่าสุด: 2026-04-18

## Critical Flows

- Auth/Register/Login/Logout ผ่าน FrontendApi controllers เท่านั้น
- Wallet transactions/claim/withdraw ต้องยึด `wallet_transactions` เป็น financial source-of-truth
- Lotto lifecycle หลัก: `draft -> open -> closed -> resulted`
- Dashboard summary ใช้ queue+bucket และต้องระวัง dedup/merge payload
- Realtime ลูกค้าแยก channel จาก admin เสมอ

## Targeted Lookup

- auth flow: `AuthController` + `AuthenticateFrontendToken` + `config/auth.php`
- wallet flow: `WalletController`, `WithdrawController`, wallet repositories/services
- lotto flow: `LottoController`, draw/settlement services/repositories
- dashboard flow: `app/Jobs/SyncDashboardSummaryBucket.php` + `app/Services/Dashboard/`
