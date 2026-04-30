# Frontend API V1 - Error Handling

อัปเดตล่าสุด: 2026-04-30

## HTTP Guidelines

- `401`: token missing/invalid -> redirect login
- `422`: validation/business rule -> show message from API
- `404`: resource not found -> show not-found state
- `500`: server/internal error -> retry with backoff

## Retry Policy

- `GET`: retry 1-2 ครั้งได้
- `POST/PUT`: retry เฉพาะกรณี network fail/timeout และควรมี idempotency key

## Idempotency

- แนะนำใช้ `X-Idempotency-Key` กับ write endpoints
- endpoint ที่ระบุชัดใน docs: `POST /api/v1/reward/redeem`
