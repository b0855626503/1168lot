# System Current State - Overview

อัปเดตล่าสุด: 2026-04-18

## Runtime Baseline

- Framework: Laravel 10.x
- Main domains: Admin, Wallet, Payment, FrontendApi, Lotto, Realtime
- Source-of-truth docs: `/docs/internal` (public contract อยู่ `/docs/public`)

## Fast Entry Points

- App core: `app/`, `routes/`, `config/`
- Frontend API routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Frontend API controllers: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`
- Lotto: `packages/Gametech/Lotto/src/`
- Wallet: `packages/Gametech/Wallet/src/`
- Payment: `packages/Gametech/Payment/src/`

## Document Escalation Rule

1. เริ่มจาก domain note
2. เปิด chapter นี้เพื่อยืนยัน baseline
3. ค่อยไป chapter flow/edge-case เมื่อ task เสี่ยงสูง
