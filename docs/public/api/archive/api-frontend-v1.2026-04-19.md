# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-19

เอกสารฉบับนี้ rewrite ใหม่ให้เป็นรูปแบบ API Reference เต็มรูปแบบ โดยรวบรวมครบทุก route ที่ประกาศใน `packages/Gametech/FrontendApi/src/Routes/api.php` และจัดโครงหัวข้อให้อ่านง่ายสำหรับทีม Frontend/QA/Backend

## 1) Scope และแหล่งอ้างอิง

- Source of truth (routes): `packages/Gametech/FrontendApi/src/Routes/api.php`
- Source เสริมด้านตัวอย่าง payload:
  - `docs/public/api/frontend-v1/05-route-reference.md`
  - test suite ที่เกี่ยวข้องใน `tests/Feature/FrontendApi/*`
- จำนวน route ทั้งหมดในเอกสารนี้: **59 routes**

## 2) มาตรฐานการเรียกใช้งาน

### Base URL
- `https://api.<domain>/api/v1`
- local ที่ใช้บ่อย: `http://127.0.0.1:18080/api/v1` (พร้อม Host ตามการตั้งค่า domain)

### Headers
- `Content-Type: application/json`
- `X-Language: th|en|kh|la` (default: `th`)
- protected routes ต้องส่ง `Authorization: Bearer <access_token>`

### Response baseline
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {}
}
```

หมายเหตุ: บาง endpoint โดยเฉพาะ legacy จะมีโครง response เฉพาะ แต่ยังคงมี `success`/`message` เป็นหลัก

### Error baseline
```json
{
  "success": false,
  "message": "เกิดข้อผิดพลาด"
}
```

กรณี token ไม่ถูกต้อง/หมดอายุ:
```json
{
  "success": false,
  "message": "token ไม่ถูกต้องหรือหมดอายุ"
}
```

## 3) Route Catalog (ครบทุกเส้น)

| # | Domain | Method | Path | Auth | Route Name |
|---|---|---|---|---|---|
| 1 | Auth | `GET` | `/api/v1/auth/register/banks` | No | `frontend.api.v1.auth.register.banks` |
| 2 | Auth | `POST` | `/api/v1/auth/register/bank-account-name` | No | `frontend.api.v1.auth.register.bank_account_name` |
| 3 | Auth | `POST` | `/api/v1/auth/register` | No | `frontend.api.v1.auth.register` |
| 4 | Auth | `POST` | `/api/v1/auth/login` | No | `frontend.api.v1.auth.login` |
| 5 | Games | `GET` | `/api/v1/games/types` | No | `frontend.api.v1.games.types` |
| 6 | Games | `GET` | `/api/v1/games/providers/{type}` | No | `frontend.api.v1.games.providers` |
| 7 | Games | `GET` | `/api/v1/games/{type}/{provider}` | No | `frontend.api.v1.games.list` |
| 8 | Other | `GET` | `/api/v1/slides` | No | `frontend.api.v1.slides.list` |
| 9 | Realtime & Meta | `GET` | `/api/v1/meta/online-members` | No | `frontend.api.v1.meta.online_members` |
| 10 | Realtime & Meta | `GET` | `/api/v1/meta/contact-channels` | No | `frontend.api.v1.meta.contact_channels` |
| 11 | Realtime & Meta | `GET` | `/api/v1/meta/site` | No | `frontend.api.v1.meta.site` |
| 12 | Realtime & Meta | `GET` | `/api/v1/realtime/config` | No | `frontend.api.v1.realtime.config` |
| 13 | Lotto | `GET` | `/api/v1/lotto/draws` | No | `frontend.api.v1.lotto.draws` |
| 14 | Lotto | `GET` | `/api/v1/lotto/draws/{id}` | No | `frontend.api.v1.lotto.draw` |
| 15 | Lotto | `GET` | `/api/v1/lotto/markets/latest` | No | `frontend.api.v1.lotto.markets.latest` |
| 16 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/betting-context` | No | `frontend.api.v1.lotto.betting_context` |
| 17 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/results` | No | `frontend.api.v1.lotto.market_results` |
| 18 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | No | `frontend.api.v1.lotto.draw_result` |
| 19 | Lotto | `GET` | `/api/v1/lotto/results/by-date` | No | `frontend.api.v1.lotto.results_by_date` |
| 20 | Lotto | `GET` | `/api/v1/lotto/navbar-config` | No | `frontend.api.v1.lotto.navbar_config` |
| 21 | Auth | `POST` | `/api/v1/auth/logout` | Yes | `frontend.api.v1.auth.logout` |
| 22 | Member | `GET` | `/api/v1/member/profile` | Yes | `frontend.api.v1.member.profile` |
| 23 | Member | `GET` | `/api/v1/member/balance` | Yes | `frontend.api.v1.member.balance` |
| 24 | Member | `GET` | `/api/v1/member/loadbalance` | Yes | `frontend.api.v1.member.loadbalance` |
| 25 | Member | `POST` | `/api/v1/member/change-password` | Yes | `frontend.api.v1.member.change_password` |
| 26 | Member | `POST` | `/api/v1/member/wallet-address` | Yes | `frontend.api.v1.member.wallet_address` |
| 27 | Member | `GET` | `/api/v1/member/contributor` | Yes | `frontend.api.v1.member.contributor` |
| 28 | Member | `GET` | `/api/v1/member/history` | Yes | `frontend.api.v1.member.history` |
| 29 | Member | `GET` | `/api/v1/member/history/{type}` | Yes | `frontend.api.v1.member.history.type` |
| 30 | Member | `GET` | `/api/v1/member/realtime-context` | Yes | `frontend.api.v1.member.realtime_context` |
| 31 | Member | `POST` | `/api/v1/member/heartbeat` | Yes | `frontend.api.v1.member.heartbeat` |
| 32 | Realtime & Meta | `POST` | `/api/v1/realtime/auth` | Yes | `frontend.api.v1.realtime.auth` |
| 33 | Wallet & Payment | `POST` | `/api/v1/wallet/withdraw` | Yes | `frontend.api.v1.wallet.withdraw` |
| 34 | Wallet & Payment | `POST` | `/api/v1/wallet/claim` | Yes | `frontend.api.v1.wallet.claim` |
| 35 | Wallet & Payment | `GET` | `/api/v1/wallet/transactions` | Yes | `frontend.api.v1.wallet.transactions` |
| 36 | Wallet & Payment | `POST` | `/api/v1/coupon/redeem` | Yes | `frontend.api.v1.coupon.redeem` |
| 37 | Wallet & Payment | `GET` | `/api/v1/coupon/my` | Yes | `frontend.api.v1.coupon.my` |
| 38 | Wallet & Payment | `POST` | `/api/v1/coupon/my/{code}/claim` | Yes | `frontend.api.v1.coupon.claim` |
| 39 | Wallet & Payment | `GET` | `/api/v1/deposit/channels` | Yes | `frontend.api.v1.deposit.channels` |
| 40 | Wallet & Payment | `POST` | `/api/v1/deposit/loadbank` | Yes | `frontend.api.v1.deposit.loadbank` |
| 41 | Wallet & Payment | `GET` | `/api/v1/smkpay/deposit/status/{txid}` | Yes | `api.smkpay.deposit.status` |
| 42 | Wallet & Payment | `POST` | `/api/v1/smkpay/deposit/expire/{txid}` | Yes | `api.smkpay.deposit.expire` |
| 43 | Wallet & Payment | `POST` | `/api/v1/smkpay/deposit/create` | Yes | `api.smkpay.deposit` |
| 44 | Wallet & Payment | `GET` | `/api/v1/smkpay/qrcode/{id}` | Yes | `api.smkpay.index` |
| 45 | Promotion | `GET` | `/api/v1/promotion/list` | Yes | `frontend.api.v1.promotion.list` |
| 46 | Promotion | `POST` | `/api/v1/promotion/select` | Yes | `frontend.api.v1.promotion.select` |
| 47 | Promotion | `POST` | `/api/v1/promotion/deselect` | Yes | `frontend.api.v1.promotion.deselect` |
| 48 | Games | `POST` | `/api/v1/games/login` | Yes | `frontend.api.v1.games.login` |
| 49 | Games | `GET` | `/api/v1/games/login/{game}/{code}` | Yes | `frontend.api.v1.games.login.path` |
| 50 | Lotto | `POST` | `/api/v1/lotto/bet` | Yes | `frontend.api.v1.lotto.bet` |
| 51 | Lotto | `GET` | `/api/v1/lotto/groups/{groupId}/packages` | Yes | `frontend.api.v1.lotto.packages` |
| 52 | Lotto | `POST` | `/api/v1/lotto/groups/{groupId}/select-package` | Yes | `frontend.api.v1.lotto.select_package` |
| 53 | Lotto | `GET` | `/api/v1/lotto/groups/{groupId}/selected-package` | Yes | `frontend.api.v1.lotto.selected_package` |
| 54 | Lotto | `GET` | `/api/v1/lotto/tickets` | Yes | `frontend.api.v1.lotto.tickets` |
| 55 | Lotto | `GET` | `/api/v1/lotto/tickets/{id}` | Yes | `frontend.api.v1.lotto.ticket` |
| 56 | Lotto | `POST` | `/api/v1/lotto/tickets/{id}/cancel` | Yes | `frontend.api.v1.lotto.cancel` |
| 57 | Wheel | `GET` | `/api/v1/wheel/list` | Yes | `frontend.api.v1.wheel.list` |
| 58 | Wheel | `POST` | `/api/v1/wheel/spin` | Yes | `frontend.api.v1.wheel.spin` |
| 59 | Wheel | `GET` | `/api/v1/wheel/history` | Yes | `frontend.api.v1.wheel.history` |

## 4) Detailed Route Reference

> ส่วนนี้เป็นรายละเอียดราย route พร้อมตัวอย่าง request/response ครบทุกเส้น

## Public Routes

### `GET /api/v1/auth/register/banks`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/auth/register/banks
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงรายการธนาคารสำเร็จ",
  "data": {
    "banks": [
      { "code": 1, "name": "Kasikorn Bank", "shortcode": "KBANK" }
    ]
  }
}
```

### `POST /api/v1/auth/register/bank-account-name`
- Auth: ไม่ต้องใช้ token
- Request example:
```json
{
  "bank": 1,
  "acc_no": "1234567890"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ตรวจสอบชื่อบัญชีสำเร็จ",
  "data": {
    "bank": 1,
    "acc_no": "1234567890",
    "account_name": "สมชาย ใจดี",
    "firstname": "สมชาย",
    "lastname": "ใจดี"
  }
}
```

### `POST /api/v1/auth/register`
- Auth: ไม่ต้องใช้ token
- Request example:
```json
{
  "user_name": "0900000014",
  "password": "pass1234",
  "password_confirm": "pass1234",
  "name": "Api User",
  "acc_no": "1234567890",
  "bank": 1,
  "refer": 1
}
```
- Response example:
```json
{
  "success": true,
  "message": "สมัครสมาชิกสำเร็จ"
}
```

### `POST /api/v1/auth/login`
- Auth: ไม่ต้องใช้ token
- Request example:
```json
{
  "user_name": "0900000014",
  "password": "pass1234"
}
```
- Response example:
```json
{
  "success": true,
  "message": "เข้าสู่ระบบสำเร็จ",
  "data": {
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### `GET /api/v1/games/types`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/games/types
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "type": "slot", "title": "Slot" }
    ]
  }
}
```

### `GET /api/v1/games/providers/{type}`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `type` เช่น `slot`
- Request example:
```http
GET /api/v1/games/providers/slot
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "type": "slot",
    "providers": [
      { "code": "PGSOFT", "name": "PG Soft" }
    ]
  }
}
```

### `GET /api/v1/games/{type}/{provider}`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `type` เช่น `slot`
  - `provider` เช่น `PGSOFT`
- Request example:
```http
GET /api/v1/games/slot/PGSOFT
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "treasures-aztec", "name": "Treasures of Aztec" }
    ]
  }
}
```

### `GET /api/v1/slides`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/slides
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "id": 1, "title": "Welcome", "image_url": "https://..." }
    ]
  }
}
```

### `GET /api/v1/meta/online-members`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/meta/online-members
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "count": 126
  }
}
```

### `GET /api/v1/meta/contact-channels`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/meta/contact-channels
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "channels": [
      { "type": "line", "label": "@1168lot", "url": "https://line.me/..." }
    ]
  }
}
```

### `GET /api/v1/meta/site`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/meta/site
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "site_name": "1168lot",
    "maintenance": false
  }
}
```

### `GET /api/v1/realtime/config`
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/realtime/config
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "enabled": true,
    "broadcaster": "reverb",
    "auth_endpoint": "/api/v1/realtime/auth"
  }
}
```

### `GET /api/v1/lotto/draws`
- Auth: ไม่ต้องใช้ token
- Query example:
```http
GET /api/v1/lotto/draws?market_id=1
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "id": 101, "draw_date": "2026-04-19", "status": "open" }
    ]
  }
}
```

### `GET /api/v1/lotto/draws/{id}`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `id` เช่น `101`
- Request example:
```http
GET /api/v1/lotto/draws/101
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "id": 101,
    "draw_date": "2026-04-19",
    "status": "open"
  }
}
```

### `GET /api/v1/lotto/markets/latest`
- Auth: ไม่ต้องใช้ token
- Query example:
```http
GET /api/v1/lotto/markets/latest?group=government
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "group": "government",
    "items": [
      { "market_id": 1, "market_name": "หวยรัฐบาล", "draw_id": 101 }
    ]
  }
}
```

### `GET /api/v1/lotto/markets/{marketId}/betting-context`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `marketId` เช่น `1`
- Request example:
```http
GET /api/v1/lotto/markets/1/betting-context
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "market_id": 1,
    "draw_id": 101,
    "draw_date": "2026-04-19",
    "bet_close_at": "2026-04-19 15:20:00"
  }
}
```

### `GET /api/v1/lotto/markets/{marketId}/results`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `marketId` เช่น `1`
- Request example:
```http
GET /api/v1/lotto/markets/1/results
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "market_id": 1,
    "items": [
      { "draw_id": 100, "first_prize": "123456" }
    ]
  }
}
```

### `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- Auth: ไม่ต้องใช้ token
- Path params:
  - `marketId` เช่น `1`
  - `drawId` เช่น `101`
- Request example:
```http
GET /api/v1/lotto/markets/1/draws/101/result
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "market_id": 1,
    "draw_id": 101,
    "result": {
      "first_prize": "123456",
      "last_2": "88"
    }
  }
}
```

### `GET /api/v1/lotto/results/by-date`
- Auth: ไม่ต้องใช้ token
- Query example:
```http
GET /api/v1/lotto/results/by-date?date=2026-04-19
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "date": "2026-04-19",
    "items": [
      { "market_id": 1, "first_prize": "123456" }
    ]
  }
}
```

### `GET /api/v1/lotto/navbar-config`
- Auth: ไม่ต้องใช้ token
- Query example:
```http
GET /api/v1/lotto/navbar-config?code=mobile_bottom_nav&locale=th
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "code": "mobile_bottom_nav",
    "items": [
      { "key": "wallet", "label": "กระเป๋า", "action_type": "link", "action_value": "/wallet" }
    ]
  }
}
```

---

## Authenticated Routes (`Authorization: Bearer <access_token>`)

### `POST /api/v1/auth/logout`
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "ออกจากระบบสำเร็จ"
}
```

### `GET /api/v1/member/profile`
- Request example:
```http
GET /api/v1/member/profile
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "member_code": 9001,
    "user_name": "0900000014",
    "name": "Api User"
  }
}
```

### `GET /api/v1/member/balance`
- Request example:
```http
GET /api/v1/member/balance
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "balance": 1520.5,
    "bonus": 100
  }
}
```

### `GET /api/v1/member/loadbalance`
- Request example:
```http
GET /api/v1/member/loadbalance
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "balance": 1520.5
  }
}
```

### `POST /api/v1/member/change-password`
- Request example:
```json
{
  "password_old": "pass1234",
  "password_new": "pass5678",
  "password_confirm": "pass5678"
}
```
- Response example:
```json
{
  "success": true,
  "message": "เปลี่ยนรหัสผ่านสำเร็จ"
}
```

### `POST /api/v1/member/wallet-address`
- Request example:
```json
{
  "wallet_address": "0x1234567890abcdef1234567890abcdef12345678"
}
```
- Response example:
```json
{
  "success": true,
  "message": "อัปเดตที่อยู่กระเป๋าสำเร็จ",
  "data": {
    "wallet_address": "0x1234567890abcdef1234567890abcdef12345678"
  }
}
```

### `GET /api/v1/member/contributor`
- Request example:
```http
GET /api/v1/member/contributor
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "referral_code": "AB12C3D4",
    "total_downline": 12
  }
}
```

### `GET /api/v1/member/history`
- Query example:
```http
GET /api/v1/member/history?date_start=2026-04-01&date_stop=2026-04-19
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "kind": "TOPUP", "amount": 500, "date": "2026-04-18 12:00:00" }
    ]
  }
}
```

### `GET /api/v1/member/history/{type}`
- Path params:
  - `type` เช่น `withdraw`, `deposit`, `setwallet`
- Request example:
```http
GET /api/v1/member/history/withdraw
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "type": "withdraw",
    "items": [
      { "amount": 300, "status": "SUCCESS", "date": "2026-04-18 20:00:00" }
    ]
  }
}
```

### `GET /api/v1/member/realtime-context`
- Request example:
```http
GET /api/v1/member/realtime-context
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "member_channel": "member.9001",
    "activity_channel": "member.activity.9001"
  }
}
```

### `POST /api/v1/member/heartbeat`
- Request example:
```json
{
  "at": "2026-04-19T12:34:56+07:00"
}
```
- Response example:
```json
{
  "success": true,
  "message": "heartbeat received"
}
```

### `POST /api/v1/realtime/auth`
- Request example:
```json
{
  "socket_id": "1234.5678",
  "channel_name": "private-member.9001"
}
```
- Response example:
```json
{
  "auth": "app-key:signature"
}
```

### `POST /api/v1/wallet/withdraw`
- Request example:
```json
{
  "amount": 300,
  "bank": 1,
  "acc_no": "1234567890"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ส่งคำขอถอนเงินสำเร็จ"
}
```

### `POST /api/v1/wallet/claim`
- Request example:
```json
{
  "source": "bonus"
}
```
- Response example:
```json
{
  "success": true,
  "message": "โอนยอดสำเร็จ",
  "data": {
    "target_wallet": "balance",
    "amount": 100
  }
}
```

### `GET /api/v1/wallet/transactions`
- Query example:
```http
GET /api/v1/wallet/transactions?type=all&date_start=2026-04-01&date_stop=2026-04-19&limit=20
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "summary": {
      "count": 6,
      "total_credit_amount": 600,
      "total_debit_amount": 150
    },
    "items": [
      {
        "type": "deposit",
        "direction": "CREDIT",
        "amount": 500
      }
    ]
  }
}
```

### `POST /api/v1/coupon/redeem`
- Request example:
```json
{
  "code": "WELCOME100"
}
```
- Response example:
```json
{
  "success": true,
  "message": "รับคูปองสำเร็จ"
}
```

### `GET /api/v1/coupon/my`
- Request example:
```http
GET /api/v1/coupon/my
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "WELCOME100", "status": "READY" }
    ]
  }
}
```

### `POST /api/v1/coupon/my/{code}/claim`
- Path params:
  - `code` เช่น `WELCOME100`
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "ใช้คูปองสำเร็จ"
}
```

### `GET /api/v1/deposit/channels`
- Request example:
```http
GET /api/v1/deposit/channels
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "smkpay", "name": "SMKPay" }
    ]
  }
}
```

### `POST /api/v1/deposit/loadbank`
- Request example:
```json
{
  "channel": "bank"
}
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "banks": [
      { "code": 1, "name": "Kasikorn Bank" }
    ]
  }
}
```

### `GET /api/v1/smkpay/deposit/status/{txid}`
- Path params:
  - `txid` เช่น `REQ-202604130001`
- Request example:
```http
GET /api/v1/smkpay/deposit/status/REQ-202604130001
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "txid": "REQ-202604130001",
    "status": "PENDING"
  }
}
```

### `POST /api/v1/smkpay/deposit/expire/{txid}`
- Path params:
  - `txid` เช่น `REQ-202604130001`
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "ทำรายการหมดอายุสำเร็จ"
}
```

### `POST /api/v1/smkpay/deposit/create`
- Request example:
```json
{
  "amount": 300,
  "channel": "smkpay"
}
```
- Response example:
```json
{
  "success": true,
  "message": "สร้างรายการฝากสำเร็จ",
  "data": {
    "txid": "REQ-202604130001",
    "qrcode_url": "/api/v1/smkpay/qrcode/REQ-202604130001"
  }
}
```

### `GET /api/v1/smkpay/qrcode/{id}`
- Path params:
  - `id` เช่น `REQ-202604130001`
- Request example:
```http
GET /api/v1/smkpay/qrcode/REQ-202604130001
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "id": "REQ-202604130001",
    "qrcode_url": "https://api.example.com/api/v1/smkpay/qrcode/REQ-202604130001"
  }
}
```

### `GET /api/v1/promotion/list`
- Request example:
```http
GET /api/v1/promotion/list
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "PRO2026", "name": "โบนัสต้อนรับ" }
    ]
  }
}
```

### `POST /api/v1/promotion/select`
- Request example:
```json
{
  "pro_code": "PRO2026"
}
```
- Response example:
```json
{
  "success": true,
  "message": "เลือกโปรโมชั่นสำเร็จ"
}
```

### `POST /api/v1/promotion/deselect`
- Request example:
```json
{
  "pro_code": "PRO2026"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ยกเลิกโปรโมชั่นสำเร็จ"
}
```

### `POST /api/v1/games/login`
- Request example:
```json
{
  "game_code": "treasures-aztec",
  "provider_code": "PGSOFT"
}
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "login_url": "https://provider.example.com/launch?token=..."
  }
}
```

### `GET /api/v1/games/login/{game}/{code}`
- Path params:
  - `game` เช่น `PGSOFT`
  - `code` เช่น `treasures-aztec`
- Request example:
```http
GET /api/v1/games/login/PGSOFT/treasures-aztec
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "login_url": "https://provider.example.com/launch?token=..."
  }
}
```

### `POST /api/v1/lotto/bet`
- Request example:
```json
{
  "draw_id": 101,
  "market_id": 1,
  "numbers": [
    { "number": "12", "price": 100 }
  ]
}
```
- Response example:
```json
{
  "success": true,
  "message": "บันทึกโพยสำเร็จ",
  "data": {
    "ticket_id": 1001,
    "total_amount": 100
  }
}
```

### `GET /api/v1/lotto/groups/{groupId}/packages`
- Path params:
  - `groupId` เช่น `1`
- Request example:
```http
GET /api/v1/lotto/groups/1/packages
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "group_id": 1,
    "items": [
      { "package_id": 11, "name": "แพ็กเริ่มต้น", "price": 199 }
    ]
  }
}
```

### `POST /api/v1/lotto/groups/{groupId}/select-package`
- Path params:
  - `groupId` เช่น `1`
- Request example:
```json
{
  "package_id": 11
}
```
- Response example:
```json
{
  "success": true,
  "message": "เลือกแพ็กเกจสำเร็จ"
}
```

### `GET /api/v1/lotto/groups/{groupId}/selected-package`
- Path params:
  - `groupId` เช่น `1`
- Request example:
```http
GET /api/v1/lotto/groups/1/selected-package
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "group_id": 1,
    "package_id": 11
  }
}
```

### `GET /api/v1/lotto/tickets`
- Query example:
```http
GET /api/v1/lotto/tickets?status=active&page=1
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "id": 1001, "status": "active", "amount": 100 }
    ]
  }
}
```

### `GET /api/v1/lotto/tickets/{id}`
- Path params:
  - `id` เช่น `1001`
- Request example:
```http
GET /api/v1/lotto/tickets/1001
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "id": 1001,
    "status": "active",
    "amount": 100
  }
}
```

### `POST /api/v1/lotto/tickets/{id}/cancel`
- Path params:
  - `id` เช่น `1001`
- Request example:
```json
{
  "reason": "เปลี่ยนใจ"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ยกเลิกโพยสำเร็จ",
  "data": {
    "id": 1001,
    "status": "cancelled"
  }
}
```

### `GET /api/v1/wheel/list`
- Request example:
```http
GET /api/v1/wheel/list
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "id": 1, "name": "Lucky Wheel", "spins_left": 1 }
    ]
  }
}
```

### `POST /api/v1/wheel/spin`
- Request example:
```json
{
  "wheel_id": 1
}
```
- Response example:
```json
{
  "success": true,
  "message": "หมุนวงล้อสำเร็จ",
  "data": {
    "wheel_id": 1,
    "prize": "BONUS_20",
    "amount": 20
  }
}
```

### `GET /api/v1/wheel/history`
- Request example:
```http
GET /api/v1/wheel/history?page=1
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "wheel_id": 1, "prize": "BONUS_20", "created_at": "2026-04-19 10:30:00" }
    ]
  }
}
```

---

## Common Error Examples

### Validation error
```json
{
  "success": false,
  "message": "ข้อมูลไม่ถูกต้อง",
  "errors": {
    "amount": [
      "amount must be numeric"
    ]
  }
}
```

### Unauthorized token
```json
{
  "success": false,
  "message": "token ไม่ถูกต้องหรือหมดอายุ"
}
```

---

## 5) หมายเหตุการใช้งานจริง

- ตัวอย่างในเอกสารนี้อ้างอิงจาก implementation ปัจจุบันใน codebase และเอกสาร route reference ล่าสุด
- Endpoint บางตัวของ legacy อาจมี field เพิ่ม/ลดตาม config หรือ provider integration ใน environment จริง
- แนะนำให้ frontend ยึด `success/message/data` และทำ defensive parsing กับ field เฉพาะธุรกิจ
