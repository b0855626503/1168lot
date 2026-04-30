# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-30

เอกสารนี้เป็น **ไฟล์หลักสำหรับหน้า** `/docs/api/frontend-v1` และเป็น entrypoint สำหรับทีมที่นำ API ไปใช้งานจริง

ข้อสำคัญ:
- เอกสารฉบับเต็มแยกเป็น chapter เพื่อให้ดูแลง่ายและผ่าน docs-validation
- เมื่อมีการเพิ่ม/แก้ route หรือ payload ต้องอัปเดต chapter ที่เกี่ยวข้อง + ไฟล์นี้

## Source of Truth (Code)

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`

## Contract พื้นฐาน

- Base URL: `/api/v1`
- Header: `Content-Type: application/json`
- Language: `X-Language: th|en|kh|la`
- Auth endpoints: `Authorization: Bearer <access_token>`

Response helpers ที่ใช้จริง:
- `sendResponse(result, message)` => `{ success, data, message }`
- `sendResponseNew(result, message)` => `result + { success: true, message }`
- `sendSuccess(message)` => `{ success: true, message }`
- `sendError(message, code)` => error envelope ของระบบ
- `normalizedJsonResponse(payload)` => ส่ง payload ตรง

## 3) Route Catalog (ครบทุกเส้น)

> รายละเอียด endpoint แบบใช้งานจริง (ใช้ทำอะไร, token, request/response) อยู่ในไฟล์อ้างอิงด้านล่าง

### Public / Auth / Member / Wallet / Payment / Lotto / Wheel / Reward

- [Route Reference (Main)](./frontend-v1/05-route-reference.md)
- [Route Reference (Wheel/Reward Addendum)](./frontend-v1/05-route-reference-wheel-reward.md)

เส้นทางใหม่ที่เพิ่มจาก route ปัจจุบันและต้องใช้ร่วมด้วย:
- `POST /api/v1/auth/register-with-username`
- `POST /api/v1/deeppay/deposit/expire/{txid}`
- `POST /api/v1/deeppay/deposit/create`
- `GET /api/v1/deeppay/qrcode/{id}`

## Yeekee API

ชุด endpoint ยี่กี่:
- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`

Response key ที่ยืนยันจากโค้ด:
- `shoot`: `round_id`, `position`, `number_text`, `submitted_at`, `round_status`
- `shoots`: `round_id`, `limit`, `count`, `items[]`
- `reward-status`: `round_id`, `member_id`, `reward_enabled`, `reward_count`, `rewarded`, `items[]`
- `result-proof`: `round_id`, `draw_id`, `status`, `is_revealed`, `proof`, `server_time`

## Chapter Index

- [Overview](./frontend-v1/01-overview.md)
- [Flows](./frontend-v1/02-flows.md)
- [Endpoints Summary](./frontend-v1/03-endpoints.md)
- [Edge Cases](./frontend-v1/04-edge-cases.md)
- [Route Reference (Main)](./frontend-v1/05-route-reference.md)
- [Route Reference (Wheel/Reward)](./frontend-v1/05-route-reference-wheel-reward.md)

## Rule การอัปเดต

เมื่อมีการเปลี่ยน Frontend API:
1. อัปเดต route/controller
2. อัปเดต chapter ที่เกี่ยวข้อง (`frontend-v1/*.md`)
3. อัปเดตไฟล์หลักนี้ (entrypoint)
4. รัน `bash scripts/docs-validation/run.sh` ก่อน push

