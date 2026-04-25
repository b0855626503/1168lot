# System Current State - Flows

อัปเดตล่าสุด: 2026-04-25

## Critical Flows

- Auth/Register/Login/Logout ผ่าน FrontendApi controllers เท่านั้น
- Frontend API login จำกัด active token 1 ตัวต่อ member; login ใหม่จากอุปกรณ์อื่นทำให้ token เดิมใช้ต่อไม่ได้
- Wallet transactions/claim/withdraw ต้องยึด `wallet_transactions` เป็น financial source-of-truth
- Lotto lifecycle หลัก: `draft -> open -> closed -> resulted`
- Lotto navbar config lifecycle:
  - draft update ต้องไม่กระทบ public response
  - publish จะ snapshot + bump `published_version` (monotonic per code)
  - public API อ่านเฉพาะ active published row
- Dashboard summary ใช้ queue+bucket และต้องระวัง dedup/merge payload
- Realtime ลูกค้าแยก channel จาก admin เสมอ

## Targeted Lookup

- auth flow: `AuthController` + `AuthenticateFrontendToken` + `config/auth.php`
- wallet flow: `WalletController`, `WithdrawController`, wallet repositories/services
- lotto flow: `LottoController`, draw/settlement services/repositories
- dashboard flow: `app/Jobs/SyncDashboardSummaryBucket.php` + `app/Services/Dashboard/`
