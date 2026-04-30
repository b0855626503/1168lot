# Frontend API V1 - Start Here

อัปเดตล่าสุด: 2026-04-30

เอกสารนี้เป็น entrypoint หลักของ `/docs/api/frontend-v1` สำหรับทีมที่นำ API ไปใช้งานจริง

## Start Here

## Quick Jump

- Login -> [POST /api/v1/auth/login](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogin)
- Balance -> [GET /api/v1/member/balance](/docs/api/frontend-v1/07-route-reference#get-apiv1memberbalance)
- Deposit -> [POST /api/v1/smkpay/deposit/create](/docs/api/frontend-v1/07-route-reference#post-apiv1smkpaydepositcreate)
- Bet -> [POST /api/v1/lotto/bet](/docs/api/frontend-v1/07-route-reference#post-apiv1lottobet)


1. [01-quick-start.md](/docs/api/frontend-v1/01-quick-start)
2. [02-auth-language.md](/docs/api/frontend-v1/02-auth-language)
3. [03-common-contract.md](/docs/api/frontend-v1/03-common-contract)
4. [04-error-handling.md](/docs/api/frontend-v1/04-error-handling)
5. [05-flows.md](/docs/api/frontend-v1/05-flows)
6. [06-endpoint-index.md](/docs/api/frontend-v1/06-endpoint-index)
7. [07-route-reference.md](/docs/api/frontend-v1/07-route-reference)
8. [08-edge-cases.md](/docs/api/frontend-v1/08-edge-cases)
9. [09-legacy-contract.md](/docs/api/frontend-v1/09-legacy-contract)

## Scope Rules

- `07-route-reference.md` คือ source of truth ของ request/response examples
- `archive/api-frontend-v1.md` เป็น historical snapshot เท่านั้น
- `agent-lean.md` สำหรับงาน agent/internal (ไม่ใช่ public manual)

## Code Source of Truth

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`

## Full API

- [ดู API ทั้งหมด](/docs/api/frontend-v1/07-route-reference)
