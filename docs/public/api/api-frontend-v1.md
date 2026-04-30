# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-30

เอกสารนี้คือไฟล์หลักสำหรับ `/docs/api/frontend-v1`

หลักการเอกสารฉบับนี้:
- อ้างอิงจากโค้ดจริงใน `packages/Gametech/FrontendApi/src/Routes/api.php` และ controller ที่เกี่ยวข้อง
- ไม่ใส่ response ตัวอย่างแบบเดา
- ระบุรูปแบบ response ตาม helper ที่โค้ดใช้จริง

## 1) พื้นฐาน

- Base URL: `/api/v1`
- Header พื้นฐาน: `Content-Type: application/json`
- Header ภาษา: `X-Language: th|en|kh|la`
- Endpoint ที่ต้อง auth: `Authorization: Bearer <access_token>`

## 2) Response Contract ที่ใช้จริงในโค้ด

อิงจาก `Gametech\Wallet\Http\Controllers\AppBaseController` และ `Gametech\FrontendApi\Http\Controllers\Api\V1\BaseController`

### 2.1 `sendResponse($result, $message)`
ตอบเป็น:

```json
{
  "success": true,
  "data": "<result>",
  "message": "<message>"
}
```

### 2.2 `sendResponseNew($result, $message, $code=200)`
ตอบเป็น `$result` แล้วเติม `success=true` และ `message` ที่ root:

```json
{
  "...": "fields from result",
  "success": true,
  "message": "<message>"
}
```

### 2.3 `sendResponseFail($result, $message, $code)`
ตอบเป็น payload ที่ `success=false` และ `message` ที่ root (มักใช้กรณี business fail)

### 2.4 `sendError($message, $code)`
ตอบเป็น error envelope ของระบบ (`success=false` + message)

### 2.5 `sendSuccess($message)`
ตอบเป็น:

```json
{
  "success": true,
  "message": "<message>"
}
```

### 2.6 `normalizedJsonResponse($payload, $status=200)`
ตอบ raw payload ตามที่ method ส่ง (ใช้ในบาง endpoint เช่น realtime/deposit)

## 3) Route Catalog (ครบทุกเส้น)

ตารางนี้คือ mapping จาก route จริง → handler จริง → response helper จริง

| Method | Path | Auth | Handler | Response Helper ที่ใช้ในโค้ด |
|---|---|---|---|---|
| GET | `/api/v1/auth/register/banks` | No | `AuthController@registerBanks` | `sendResponse` |
| POST | `/api/v1/auth/register/bank-account-name` | No | `AuthController@resolveRegisterBankAccount` | `sendResponse` / `sendResponseFail` |
| POST | `/api/v1/auth/register` | No | `AuthController@register` | `sendSuccess` / `normalizedJsonResponse` |
| POST | `/api/v1/auth/register-with-username` | No | `AuthController@registerWithUsername` | `sendSuccess` / `normalizedJsonResponse` |
| POST | `/api/v1/auth/login` | No | `AuthController@login` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/games/types` | No | `GameController@types` | `sendResponse` |
| GET | `/api/v1/games/providers/{type}` | No | `GameController@providers` | `sendResponse` |
| GET | `/api/v1/games/{type}/{provider}` | No | `GameController@games` | `sendResponse` |
| GET | `/api/v1/slides` | No | `SlideController@list` | `sendResponse` |
| GET | `/api/v1/meta/online-members` | No | `OnlineController@count` | `sendResponseNew` |
| GET | `/api/v1/meta/contact-channels` | No | `ContactChannelController@list` | `sendResponse` |
| GET | `/api/v1/meta/site` | No | `SiteMetaController@info` | `sendResponseNew` |
| GET | `/api/v1/realtime/config` | No | `RealtimeController@config` | `sendResponseNew` |
| GET | `/api/v1/lotto/draws` | No | `LottoController@draws` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/draws/{id}` | No | `LottoController@draw` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/markets/latest` | No | `LottoController@marketsLatestByGroup` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/markets/{marketId}/betting-context` | No | `LottoController@bettingContext` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/markets/{marketId}/results` | No | `LottoController@marketResults` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | No | `LottoController@drawResult` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/results/by-date` | No | `LottoController@resultsByDate` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/navbar-config` | No | `LottoNavbarConfigController@show` | `sendResponse` / `sendError` |
| POST | `/api/v1/auth/logout` | Yes | `AuthController@logout` | `sendSuccess` |
| GET | `/api/v1/member/profile` | Yes | `MemberController@profile` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/balance` | Yes | `MemberController@balance` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/loadbalance` | Yes | `MemberController@loadBalance` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/member/change-password` | Yes | `MemberController@changePassword` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/member/wallet-address` | Yes | `MemberController@updateWalletAddress` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/contributor` | Yes | `MemberController@contributor` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/history` | Yes | `MemberController@history` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/history/{type}` | Yes | `MemberController@history` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/member/realtime-context` | Yes | `RealtimeController@memberContext` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/member/heartbeat` | Yes | `OnlineController@heartbeat` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/realtime/auth` | Yes | `RealtimeController@authenticate` | `normalizedJsonResponse` / `sendError` |
| POST | `/api/v1/wallet/withdraw` | Yes | `WithdrawController@store` | `sendSuccess` / `sendError` |
| POST | `/api/v1/wallet/claim` | Yes | `WalletController@claim` | `sendResponse` / `sendError` |
| GET | `/api/v1/wallet/transactions` | Yes | `WalletController@transactions` | `sendResponse` / `sendError` |
| POST | `/api/v1/coupon/redeem` | Yes | `CouponController@redeem` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/coupon/my` | Yes | `CouponController@myCoupons` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/coupon/my/{code}/claim` | Yes | `CouponController@claim` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/deposit/channels` | Yes | `DepositController@channels` | `sendResponse` / `sendError` |
| POST | `/api/v1/deposit/loadbank` | Yes | `DepositController@loadBank` | `normalizedJsonResponse` / `sendError` |
| POST | `/api/v1/deposit/loadbank/random` | Yes | `DepositController@loadRandomBank` | `normalizedJsonResponse` / `sendError` |
| GET | `/api/v1/smkpay/deposit/status/{txid}` | Yes | `SmkPayController@checkStatus` | (controller จาก Payment module) |
| POST | `/api/v1/smkpay/deposit/expire/{txid}` | Yes | `SmkPayController@expire` | (controller จาก Payment module) |
| POST | `/api/v1/smkpay/deposit/create` | Yes | `SmkPayController@deposit` | (controller จาก Payment module) |
| GET | `/api/v1/smkpay/qrcode/{id}` | Yes | `SmkPayController@index` | (controller จาก Payment module) |
| POST | `/api/v1/deeppay/deposit/expire/{txid}` | Yes | `DeepPayController@expire` | (controller จาก Payment module) |
| POST | `/api/v1/deeppay/deposit/create` | Yes | `DeepPayController@deposit` | (controller จาก Payment module) |
| GET | `/api/v1/deeppay/qrcode/{id}` | Yes | `DeepPayController@index` | (controller จาก Payment module) |
| GET | `/api/v1/promotion/list` | Yes | `PromotionController@list` | `sendResponse` / `sendError` |
| POST | `/api/v1/promotion/select` | Yes | `PromotionController@select` | `sendResponse` / `sendError` |
| POST | `/api/v1/promotion/deselect` | Yes | `PromotionController@deselect` | `sendSuccess` / `sendError` |
| POST | `/api/v1/games/login` | Yes | `GameController@login` | `sendResponse` / `sendError` |
| GET | `/api/v1/games/login/{game}/{code}` | Yes | `GameController@loginByPath` | `sendResponse` / `sendError` |
| POST | `/api/v1/lotto/bet` | Yes | `LottoController@bet` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/groups/{groupId}/packages` | Yes | `LottoController@packages` | `sendResponse` / `sendError` |
| POST | `/api/v1/lotto/groups/{groupId}/select-package` | Yes | `LottoController@selectPackage` | `sendResponse` / `sendResponseFail` / `sendError` |
| GET | `/api/v1/lotto/groups/{groupId}/selected-package` | Yes | `LottoController@selectedPackage` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/lotto/tickets` | Yes | `LottoController@tickets` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/tickets/{id}` | Yes | `LottoController@ticket` | `sendResponse` / `sendError` |
| POST | `/api/v1/lotto/tickets/{id}/cancel` | Yes | `LottoController@cancel` | `sendSuccess` / `sendError` |
| POST | `/api/v1/lotto/yeekee/rounds/{roundId}/shoot` | Yes | `LottoController@submitShoot` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/yeekee/markets/{marketId}/current-round` | Yes | `LottoController@yeekeeCurrentRound` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/shoots` | Yes | `LottoController@yeekeeShoots` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/reward-status` | Yes | `LottoController@yeekeeRewardStatus` | `sendResponse` / `sendError` |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/result-proof` | Yes | `LottoController@yeekeeResultProof` | `sendResponse` / `sendError` |
| GET | `/api/v1/wheel/list` | Yes | `WheelController@list` | `sendResponse` / `sendError` |
| POST | `/api/v1/wheel/spin` | Yes | `WheelController@spin` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/wheel/history` | Yes | `WheelController@history` | `sendResponse` / `sendError` |
| GET | `/api/v1/reward/list` | Yes | `RewardController@list` | `sendResponseNew` / `sendError` |
| POST | `/api/v1/reward/redeem` | Yes | `RewardController@redeem` | `sendResponseNew` / `sendError` |
| GET | `/api/v1/reward/history` | Yes | `RewardController@history` | `sendResponseNew` / `sendError` |

## 4) Request Payload ที่ยืนยันจาก validation ในโค้ด (เฉพาะที่สำคัญ)

### Auth
- `POST /auth/login`: ต้องมี `user_name`, `password`
- `POST /auth/register-banks`: ไม่มี payload
- `POST /auth/register-bank-account-name`: ใช้ `ResolveRegisterBankAccountRequest`

### Yeekee/Lotto
- `POST /lotto/yeekee/rounds/{roundId}/shoot`: validator บังคับ `number` (string)
- `POST /lotto/groups/{groupId}/select-package`: validator บังคับ `package_id` (integer, exists)
- `GET /lotto/yeekee/rounds/{roundId}/shoots`: query `limit` (default 50, max 100)

### Wallet/Coupon
- `GET /wallet/transactions`: มี filter/query params ตามโค้ด (`type`, paging, date filters)
- `POST /coupon/redeem`: ใช้ `CouponRedeemRequest`
- `POST /coupon/my/{code}/claim`: รับ `code` จาก path

## Yeekee API

## 5) Response Key ที่ยืนยันจากโค้ด (critical endpoints)

### `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
ส่งผ่าน `sendResponse([...], 'ยิงเลขสำเร็จ')` โดย `data` มี key:
- `round_id` (int)
- `position` (int)
- `number_text` (string)
- `submitted_at` (string)
- `round_status` (string|null)

### `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
ส่งผ่าน `sendResponse(mapYeekeeRoundPayload(...))` โดย payload หลักมีบริบทรอบยี่กี่ปัจจุบัน (round/draw/status/timing)

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
`data` มี key:
- `round_id`, `limit`, `count`
- `items[]`: `position`, `number_text`, `submitted_at`

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
`data` มี key:
- `round_id`, `member_id`, `reward_enabled`, `reward_count`, `rewarded`
- `items[]`: `position`, `credit_amount`

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`
`data` มี key:
- `round_id`, `draw_id`, `status`, `is_revealed`, `server_time`
- `proof`: `formula_label`, `precommit_signature`, `proof_signature`, `external_seed_reference`, `result_payload`

### `POST /api/v1/lotto/groups/{groupId}/select-package`
กรณีสำเร็จ `sendResponse`:
- `data.group_id`, `data.package_id`, `data.selected`

กรณี business fail `sendResponseFail`:
- root มี `success=false`, `message`
- พร้อม `error_code` (เช่น `PACKAGE_NOT_IN_GROUP`, `PACKAGE_INACTIVE`)

## 6) หมายเหตุสำคัญ

- เส้นทางใน Payment module (`SmkPayController`, `DeepPayController`) อยู่คนละ package จึงต้องยึด response จาก controller ของ package นั้นโดยตรง
- FrontendApi `BaseController` จะ normalize image URLs ใน response อัตโนมัติสำหรับ field ประเภทรูป
- หากมีการเพิ่ม/แก้ route หรือ contract ต้องอัปเดตไฟล์นี้ทันที

