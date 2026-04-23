# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-23

เอกสารฉบับนี้ rewrite ใหม่ให้เป็นรูปแบบ API Reference เต็มรูปแบบ โดยรวบรวมครบทุก route ที่ประกาศใน `packages/Gametech/FrontendApi/src/Routes/api.php` และจัดโครงหัวข้อให้อ่านง่ายสำหรับทีม Frontend/QA/Backend

## 1) Scope และแหล่งอ้างอิง

- Source of truth (routes): `packages/Gametech/FrontendApi/src/Routes/api.php`
- Source เสริมด้านตัวอย่าง payload:
  - `docs/public/api/frontend-v1/05-route-reference.md`
  - test suite ที่เกี่ยวข้องใน `tests/Feature/FrontendApi/*`
- จำนวน route ทั้งหมดในเอกสารนี้: **63 routes**

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
| 4 | Auth | `POST` | `/api/v1/auth/register-with-username` | No | `frontend.api.v1.auth.register_with_username` |
| 5 | Auth | `POST` | `/api/v1/auth/login` | No | `frontend.api.v1.auth.login` |
| 6 | Games | `GET` | `/api/v1/games/types` | No | `frontend.api.v1.games.types` |
| 7 | Games | `GET` | `/api/v1/games/providers/{type}` | No | `frontend.api.v1.games.providers` |
| 8 | Games | `GET` | `/api/v1/games/{type}/{provider}` | No | `frontend.api.v1.games.list` |
| 9 | Other | `GET` | `/api/v1/slides` | No | `frontend.api.v1.slides.list` |
| 10 | Realtime & Meta | `GET` | `/api/v1/meta/online-members` | No | `frontend.api.v1.meta.online_members` |
| 11 | Realtime & Meta | `GET` | `/api/v1/meta/contact-channels` | No | `frontend.api.v1.meta.contact_channels` |
| 12 | Realtime & Meta | `GET` | `/api/v1/meta/site` | No | `frontend.api.v1.meta.site` |
| 13 | Realtime & Meta | `GET` | `/api/v1/realtime/config` | No | `frontend.api.v1.realtime.config` |
| 14 | Lotto | `GET` | `/api/v1/lotto/draws` | No | `frontend.api.v1.lotto.draws` |
| 15 | Lotto | `GET` | `/api/v1/lotto/draws/{id}` | No | `frontend.api.v1.lotto.draw` |
| 16 | Lotto | `GET` | `/api/v1/lotto/markets/latest` | No | `frontend.api.v1.lotto.markets.latest` |
| 17 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/betting-context` | No | `frontend.api.v1.lotto.betting_context` |
| 18 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/results` | No | `frontend.api.v1.lotto.market_results` |
| 19 | Lotto | `GET` | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | No | `frontend.api.v1.lotto.draw_result` |
| 20 | Lotto | `GET` | `/api/v1/lotto/results/by-date` | No | `frontend.api.v1.lotto.results_by_date` |
| 21 | Lotto | `GET` | `/api/v1/lotto/navbar-config` | No | `frontend.api.v1.lotto.navbar_config` |
| 22 | Auth | `POST` | `/api/v1/auth/logout` | Yes | `frontend.api.v1.auth.logout` |
| 23 | Member | `GET` | `/api/v1/member/profile` | Yes | `frontend.api.v1.member.profile` |
| 24 | Member | `GET` | `/api/v1/member/balance` | Yes | `frontend.api.v1.member.balance` |
| 25 | Member | `GET` | `/api/v1/member/loadbalance` | Yes | `frontend.api.v1.member.loadbalance` |
| 26 | Member | `POST` | `/api/v1/member/change-password` | Yes | `frontend.api.v1.member.change_password` |
| 27 | Member | `POST` | `/api/v1/member/wallet-address` | Yes | `frontend.api.v1.member.wallet_address` |
| 28 | Member | `GET` | `/api/v1/member/contributor` | Yes | `frontend.api.v1.member.contributor` |
| 29 | Member | `GET` | `/api/v1/member/history` | Yes | `frontend.api.v1.member.history` |
| 30 | Member | `GET` | `/api/v1/member/history/{type}` | Yes | `frontend.api.v1.member.history.type` |
| 31 | Member | `GET` | `/api/v1/member/realtime-context` | Yes | `frontend.api.v1.member.realtime_context` |
| 32 | Member | `POST` | `/api/v1/member/heartbeat` | Yes | `frontend.api.v1.member.heartbeat` |
| 33 | Realtime & Meta | `POST` | `/api/v1/realtime/auth` | Yes | `frontend.api.v1.realtime.auth` |
| 34 | Wallet & Payment | `POST` | `/api/v1/wallet/withdraw` | Yes | `frontend.api.v1.wallet.withdraw` |
| 35 | Wallet & Payment | `POST` | `/api/v1/wallet/claim` | Yes | `frontend.api.v1.wallet.claim` |
| 36 | Wallet & Payment | `GET` | `/api/v1/wallet/transactions` | Yes | `frontend.api.v1.wallet.transactions` |
| 37 | Wallet & Payment | `POST` | `/api/v1/coupon/redeem` | Yes | `frontend.api.v1.coupon.redeem` |
| 38 | Wallet & Payment | `GET` | `/api/v1/coupon/my` | Yes | `frontend.api.v1.coupon.my` |
| 39 | Wallet & Payment | `POST` | `/api/v1/coupon/my/{code}/claim` | Yes | `frontend.api.v1.coupon.claim` |
| 40 | Wallet & Payment | `GET` | `/api/v1/deposit/channels` | Yes | `frontend.api.v1.deposit.channels` |
| 41 | Wallet & Payment | `POST` | `/api/v1/deposit/loadbank` | Yes | `frontend.api.v1.deposit.loadbank` |
| 42 | Wallet & Payment | `GET` | `/api/v1/smkpay/deposit/status/{txid}` | Yes | `api.smkpay.deposit.status` |
| 43 | Wallet & Payment | `POST` | `/api/v1/smkpay/deposit/expire/{txid}` | Yes | `api.smkpay.deposit.expire` |
| 44 | Wallet & Payment | `POST` | `/api/v1/smkpay/deposit/create` | Yes | `api.smkpay.deposit` |
| 45 | Wallet & Payment | `GET` | `/api/v1/smkpay/qrcode/{id}` | Yes | `api.smkpay.index` |
| 46 | Promotion | `GET` | `/api/v1/promotion/list` | Yes | `frontend.api.v1.promotion.list` |
| 47 | Promotion | `POST` | `/api/v1/promotion/select` | Yes | `frontend.api.v1.promotion.select` |
| 48 | Promotion | `POST` | `/api/v1/promotion/deselect` | Yes | `frontend.api.v1.promotion.deselect` |
| 49 | Games | `POST` | `/api/v1/games/login` | Yes | `frontend.api.v1.games.login` |
| 50 | Games | `GET` | `/api/v1/games/login/{game}/{code}` | Yes | `frontend.api.v1.games.login.path` |
| 51 | Lotto | `POST` | `/api/v1/lotto/bet` | Yes | `frontend.api.v1.lotto.bet` |
| 52 | Lotto | `GET` | `/api/v1/lotto/groups/{groupId}/packages` | Yes | `frontend.api.v1.lotto.packages` |
| 53 | Lotto | `POST` | `/api/v1/lotto/groups/{groupId}/select-package` | Yes | `frontend.api.v1.lotto.select_package` |
| 54 | Lotto | `GET` | `/api/v1/lotto/groups/{groupId}/selected-package` | Yes | `frontend.api.v1.lotto.selected_package` |
| 55 | Lotto | `GET` | `/api/v1/lotto/tickets` | Yes | `frontend.api.v1.lotto.tickets` |
| 56 | Lotto | `GET` | `/api/v1/lotto/tickets/{id}` | Yes | `frontend.api.v1.lotto.ticket` |
| 57 | Lotto | `POST` | `/api/v1/lotto/tickets/{id}/cancel` | Yes | `frontend.api.v1.lotto.cancel` |
| 58 | Wheel | `GET` | `/api/v1/wheel/list` | Yes | `frontend.api.v1.wheel.list` |
| 59 | Wheel | `POST` | `/api/v1/wheel/spin` | Yes | `frontend.api.v1.wheel.spin` |
| 60 | Wheel | `GET` | `/api/v1/wheel/history` | Yes | `frontend.api.v1.wheel.history` |
| 61 | Reward | `GET` | `/api/v1/reward/list` | Yes | `frontend.api.v1.reward.list` |
| 62 | Reward | `POST` | `/api/v1/reward/redeem` | Yes | `frontend.api.v1.reward.redeem` |
| 63 | Reward | `GET` | `/api/v1/reward/history` | Yes | `frontend.api.v1.reward.history` |
## 4) Detailed Route Reference

> ส่วนนี้เป็นรายละเอียดราย route พร้อมตัวอย่าง request/response ครบทุกเส้น

## Public Routes

### `GET /api/v1/auth/register/banks`
- คำอธิบาย: ดึงรายการธนาคารที่ระบบรองรับสำหรับใช้ในฟอร์มสมัครสมาชิก
- ใช้เมื่อ: ต้องการแสดง dropdown ธนาคารก่อนกรอกเลขบัญชีตอนสมัคร
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
- คำอธิบาย: ตรวจสอบชื่อบัญชีจากธนาคารตามเลขบัญชีที่กรอก เพื่อช่วยยืนยันความถูกต้อง
- ใช้เมื่อ: ผู้ใช้กรอกเลขบัญชีและต้องการพรีวิวชื่อเจ้าของบัญชีก่อนสมัคร
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
- คำอธิบาย: สมัครสมาชิกใหม่และผูกข้อมูลบัญชีธนาคารสำหรับธุรกรรม
- ใช้เมื่อ: สร้างบัญชีผู้ใช้ใหม่จากหน้าสมัครสมาชิก
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

### `POST /api/v1/auth/register-with-username`
- คำอธิบาย: สมัครสมาชิกแบบแยก `user_name` (ไม่บังคับเป็นเบอร์โทร)
- ใช้เมื่อ: หน้า signup เวอร์ชันที่รองรับ username แยกจากเบอร์
- Auth: ไม่ต้องใช้ token
- Request example:
```json
{
  "user_name": "apiuser01",
  "tel": "0900000014",
  "password": "pass1234",
  "password_confirm": "pass1234",
  "firstname": "Api",
  "lastname": "User",
  "acc_no": "1234567890",
  "bank": 1
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
- คำอธิบาย: เข้าสู่ระบบและออก access token สำหรับเรียก API ที่ต้องยืนยันตัวตน
- ใช้เมื่อ: ผู้ใช้ล็อกอินจากหน้าแรก/หน้าเข้าสู่ระบบ
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
- คำอธิบาย: ดึงประเภทเกมที่เปิดให้บริการ
- ใช้เมื่อ: แสดงแท็บหรือหมวดหมู่เกมบนหน้าเกม
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/games/types
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงประเภทเกมสำเร็จ",
  "data": [
    {
      "id": "slot",
      "name": "Slot",
      "status_open": "Y"
    }
  ]
}
```

### `GET /api/v1/games/providers/{type}`
- คำอธิบาย: ดึงรายชื่อค่ายเกมตามประเภทที่เลือก
- ใช้เมื่อ: ผู้ใช้เลือกประเภทเกมและต้องการเห็นค่ายที่เกี่ยวข้อง
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
  "message": "ดึงรายการค่ายเกมสำเร็จ",
  "data": [
    {
      "provider": "PGSOFT",
      "providerTier": "standard",
      "providerName": "PG Soft",
      "providerType": "SLOT",
      "logoURL": "https://...",
      "logoTransparentURL": "https://...",
      "status": "ACTIVE",
      "detailStatus": "1"
    }
  ]
}
```

### `GET /api/v1/games/{type}/{provider}`
- คำอธิบาย: ดึงรายการเกมของค่ายที่เลือกในประเภทนั้น
- ใช้เมื่อ: โหลดลิสต์เกมเพื่อแสดงการ์ดเกมในหน้า lobby
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
  "message": "ดึงรายการเกมสำเร็จ",
  "data": [
    {
      "id": "treasures-aztec",
      "provider": "PGSOFT",
      "gameName": "Treasures of Aztec",
      "loginURL": "https://.../api/v1/games/login/PGSOFT/treasures-aztec",
      "status": "ACTIVE"
    }
  ]
}
```

### `GET /api/v1/slides`
- คำอธิบาย: ดึงข้อมูลสไลด์/แบนเนอร์สำหรับหน้าแรก
- ใช้เมื่อ: เรนเดอร์ banner carousel ในหน้า Home
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
- คำอธิบาย: ดึงจำนวนสมาชิกออนไลน์แบบสรุป
- ใช้เมื่อ: แสดง social proof หรือสถิติผู้ใช้งานสดบนหน้าเว็บ
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
- คำอธิบาย: ดึงช่องทางติดต่อที่เปิดใช้งาน เช่น Line, Telegram
- ใช้เมื่อ: เรนเดอร์ปุ่มติดต่อฝ่ายบริการลูกค้า
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
- คำอธิบาย: ดึงข้อมูลเมตาของเว็บ เช่น ชื่อเว็บ สถานะบำรุงรักษา
- ใช้เมื่อ: โหลดค่าคอนฟิกพื้นฐานก่อน render แอป
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
- คำอธิบาย: ดึงคอนฟิกระบบ realtime ที่ frontend ต้องใช้เชื่อมต่อ
- ใช้เมื่อ: ตั้งค่า websocket/reverb client ตอนเริ่มแอป
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
- คำอธิบาย: ดึงงวดหวยตาม market ที่ระบุ
- ใช้เมื่อ: ผู้ใช้เลือกตลาดหวยและต้องการเลือกงวดที่จะเดิมพัน
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
- คำอธิบาย: ดึงรายละเอียดงวดหวยรายงวด
- ใช้เมื่อ: ต้องการข้อมูลเชิงลึกของงวดก่อนวางบิล
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
- คำอธิบาย: ดึงตลาดหวยล่าสุดที่กำลังเปิดให้เล่น
- ใช้เมื่อ: โหลดหน้า lotto ให้เห็นตลาดที่ active ล่าสุดทันที
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
- คำอธิบาย: ดึงบริบทเดิมพันของตลาด เช่น draw ปัจจุบัน และเวลาปิดรับ
- ใช้เมื่อ: ใช้คุม state ปุ่มเดิมพันและ countdown ในหน้าแทงหวย
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
- คำอธิบาย: ดึงผลรางวัลย้อนหลังตามตลาด
- ใช้เมื่อ: แสดงประวัติผลรางวัลของตลาดที่ผู้ใช้เลือก
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
- คำอธิบาย: ดึงผลรางวัลของงวดเฉพาะเจาะจง
- ใช้เมื่อ: ผู้ใช้เปิดดูผลของงวดที่สนใจรายงวด
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
- คำอธิบาย: ดึงผลหวยรวมตามวันที่ระบุ
- ใช้เมื่อ: ทำหน้าค้นหาผลหวยตามวันหรือหน้าสรุปประจำวัน
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
- คำอธิบาย: ดึงคอนฟิกเมนูนำทางของโมดูลหวยตามโค้ดที่กำหนด
- ใช้เมื่อ: ประกอบ navbar/dynamic menu ของหน้า lotto
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
- คำอธิบาย: ออกจากระบบและยกเลิก token ปัจจุบัน
- ใช้เมื่อ: ผู้ใช้กดออกจากระบบจากโปรไฟล์/เมนู
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
- คำอธิบาย: ดึงข้อมูลโปรไฟล์สมาชิกที่ล็อกอินอยู่
- ใช้เมื่อ: แสดงข้อมูลบัญชีในหน้าโปรไฟล์
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
- คำอธิบาย: ดึงยอดเงินและยอดที่เกี่ยวข้องในกระเป๋าสมาชิก
- ใช้เมื่อ: รีเฟรชยอดเงินก่อน/หลังทำรายการ
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
- คำอธิบาย: ดึงยอดเงินแบบเบาเพื่อรีเฟรชเร็ว
- ใช้เมื่อ: polling ยอดเงินบน header/wallet widget
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
- คำอธิบาย: เปลี่ยนรหัสผ่านของสมาชิก
- ใช้เมื่อ: ผู้ใช้ต้องการอัปเดตความปลอดภัยของบัญชี
- Request example:
```json
{
  "password": "pass5678",
  "password_confirmation": "pass5678"
}
```
- หมายเหตุ: ระบบรองรับ `password_confirm` ได้ด้วย
- Response example:
```json
{
  "success": true,
  "message": "เปลี่ยนรหัสผ่านสำเร็จ",
  "data": {
    "member_code": 9001
  }
}
```

### `POST /api/v1/member/wallet-address`
- คำอธิบาย: บันทึกหรืออัปเดตที่อยู่กระเป๋า crypto ของสมาชิก
- ใช้เมื่อ: เตรียมข้อมูลปลายทางสำหรับการถอนแบบ crypto
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
- คำอธิบาย: ดึงข้อมูลผู้แนะนำ/ผู้สนับสนุนที่ผูกกับสมาชิก
- ใช้เมื่อ: แสดงข้อมูลสายแนะนำหรือหน้า referral
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
- คำอธิบาย: ดึงประวัติธุรกรรมรวมของสมาชิก
- ใช้เมื่อ: ทำหน้า history หลักที่รวมหลายประเภทรายการ
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
- คำอธิบาย: ดึงประวัติธุรกรรมแยกตามประเภท
- ใช้เมื่อ: ผู้ใช้เลือกแท็บประเภทประวัติ เช่น ฝาก ถอน เดิมพัน
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
- คำอธิบาย: ดึงข้อมูล context สำหรับ subscribe ช่อง realtime ของสมาชิก
- ใช้เมื่อ: ตั้งค่า private channel หลังล็อกอิน
- Request example:
```http
GET /api/v1/member/realtime-context
```
- Response example:
```json
{
  "success": true,
  "message": "ดึง realtime member context สำเร็จ",
  "data": {
    "member_code": 9001,
    "private_channel": "app_members.9001"
  }
}
```

### `POST /api/v1/member/heartbeat`
- คำอธิบาย: ส่ง heartbeat เพื่ออัปเดตสถานะออนไลน์ของสมาชิก
- ใช้เมื่อ: ยิงเป็นช่วงเวลาเพื่อคงสถานะ active session
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "อัปเดตสถานะออนไลน์สำเร็จ",
  "data": {
    "heartbeat": "ok",
    "online": 126
  }
}
```

### `POST /api/v1/realtime/auth`
- คำอธิบาย: authorize การเข้าช่อง realtime private/presence
- ใช้เมื่อ: เรียกโดย client realtime ตอน subscribe private channel
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
- คำอธิบาย: สร้างคำขอถอนเงินจากกระเป๋าสมาชิก
- ใช้เมื่อ: ผู้ใช้ส่งฟอร์มถอนเงิน
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
- คำอธิบาย: เคลมเครดิต/ยอดคงค้างเข้ากระเป๋าหลักตามเงื่อนไขระบบ
- ใช้เมื่อ: กดปุ่มรับเครดิตหรือโอนยอดที่รอเคลม
- Request example:
```json
{
  "type": "bonus"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ดำเนินการโยก เข้ากระเป๋าสำเร็จแล้ว",
  "data": {
    "type": "bonus",
    "legacy_type": "BONUS",
    "claimed_amount": 100,
    "target_wallet": "balance",
    "profile": {
      "balance": 1200,
      "balance_free": 0,
      "bonus": 0,
      "cashback": 0,
      "ic": 0,
      "faststart": 0
    }
  }
}
```

### `GET /api/v1/wallet/transactions`
- คำอธิบาย: ดึงรายการเดินบัญชีกระเป๋า (wallet ledger)
- ใช้เมื่อ: แสดงประวัติรับ-จ่ายในหน้ากระเป๋า
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
- คำอธิบาย: ตรวจสอบและแลกคูปองเข้าระบบของสมาชิก
- ใช้เมื่อ: ผู้ใช้กรอกโค้ดคูปองจากแคมเปญ
- Request example:
```json
{
  "coupon": "WELCOME100"
}
```
- Response example:
```json
{
  "success": true,
  "message": "รับคูปองสำเร็จ",
  "data": {
    "item": {
      "code": "WELCOME100"
    }
  }
}
```

### `GET /api/v1/coupon/my`
- คำอธิบาย: ดึงคูปองที่สมาชิกมีอยู่
- ใช้เมื่อ: แสดงรายการคูปองที่ใช้ได้/เคลมได้
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
- คำอธิบาย: เคลมคูปองที่สมาชิกถืออยู่ด้วย code ที่ระบุ
- ใช้เมื่อ: ผู้ใช้กดรับสิทธิ์จากคูปองเฉพาะใบ
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
- คำอธิบาย: ดึงช่องทางฝากเงินที่เปิดใช้งานสำหรับสมาชิก
- ใช้เมื่อ: แสดงตัวเลือกช่องทางฝากในหน้าฝากเงิน
- Request example:
```http
GET /api/v1/deposit/channels
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงช่องทางเติมเงินสำเร็จ",
  "data": {
    "deposit": {
      "bank": 1,
      "payment": 1,
      "tw": 0,
      "slip": 1,
      "sort": {
        "payment": 1,
        "tw": null,
        "slip": 3,
        "bank": 2
      }
    }
  }
}
```

### `POST /api/v1/deposit/loadbank`
- คำอธิบาย: ดึงข้อมูลบัญชีธนาคารปลายทางของระบบสำหรับฝาก
- ใช้เมื่อ: ผู้ใช้เลือกวิธีฝากและต้องการข้อมูลปลายทาง
- Request example:
```json
{
  "method": "bank"
}
```
- Response example:
```json
{
  "success": true,
  "bank": [
    {
      "acc_no": "1234567890",
      "acc_name": "COMPANY",
      "bank_name": "Kasikorn Bank"
    }
  ]
}
```

### `GET /api/v1/smkpay/deposit/status/{txid}`
- คำอธิบาย: ตรวจสอบสถานะรายการฝากผ่าน SMKPay ตาม txid
- ใช้เมื่อ: polling สถานะระหว่างรอฝากสำเร็จ
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
- คำอธิบาย: สั่งหมดอายุรายการฝาก SMKPay ที่ยังไม่ชำระ
- ใช้เมื่อ: ผู้ใช้ยกเลิกหรือ timeout QR/payment intent
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
- คำอธิบาย: สร้างรายการฝากผ่าน SMKPay และคืนข้อมูลการชำระ
- ใช้เมื่อ: เริ่ม flow ฝากเงินด้วย QR/SMKPay
- Request example:
```json
{
  "amount": 300
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
- คำอธิบาย: ดึงข้อมูล QR code ของรายการฝาก SMKPay
- ใช้เมื่อ: แสดง QR ให้ผู้ใช้สแกนชำระ
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
- คำอธิบาย: ดึงรายการโปรโมชั่นที่สมาชิกสามารถเลือกได้
- ใช้เมื่อ: แสดงโปรที่สมัครได้ในหน้ากิจกรรม
- Request example:
```http
GET /api/v1/promotion/list
```
- Response example:
```json
{
  "success": true,
  "message": "Complete",
  "data": {
    "promotions": [
      { "code": 1, "name_th": "โบนัสต้อนรับ" }
    ],
    "getpro": false
  }
}
```

### `POST /api/v1/promotion/select`
- คำอธิบาย: เลือกเข้าร่วมโปรโมชั่น
- ใช้เมื่อ: ผู้ใช้กดยืนยันรับโปรที่ต้องการ
- Request example:
```json
{
  "promotion": "PRO2026"
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
- คำอธิบาย: ยกเลิกการเข้าร่วมโปรโมชั่นปัจจุบัน
- ใช้เมื่อ: ผู้ใช้ต้องการออกจากโปรก่อนเปลี่ยนโปรใหม่
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "ยกเลิกโปรโมชั่นสำเร็จ"
}
```

### `POST /api/v1/games/login`
- คำอธิบาย: ขอ URL/Session สำหรับเข้าเล่นเกมแบบยิงจาก frontend
- ใช้เมื่อ: ผู้ใช้กดเข้าเกมจากการ์ดเกม
- Request example:
```json
{
  "id": "PGSOFT",
  "game": "treasures-aztec"
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
- คำอธิบาย: เข้าเกมผ่าน path parameter สำหรับ deep link
- ใช้เมื่อ: รองรับลิงก์ตรงเข้าเกมจากแคมเปญ/ภายนอก
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
- คำอธิบาย: ส่งคำสั่งเดิมพันหวยและสร้างบิล
- ใช้เมื่อ: ผู้ใช้ยืนยันแทงหวยจาก slip
- Request example:
```json
{
  "draw_id": 101,
  "package_id": 11,
  "items": [
    { "bet_type": "2_top", "number": "12", "amount": 100 }
  ]
}
```
- Response example:
```json
{
  "success": true,
  "message": "แทงหวยสำเร็จ",
  "data": {
    "ticket_id": 1001,
    "total_amount": 100,
    "total_bet_amount": 100,
    "total_discount_amount": 0,
    "total_net_amount": 100,
    "total_win_amount": 0,
    "status": "pending",
    "item_count": 1
  }
}
```

### `GET /api/v1/lotto/groups/{groupId}/packages`
- คำอธิบาย: ดึงชุด package ที่มีให้เลือกในกลุ่มหวย
- ใช้เมื่อ: แสดงแพ็กเกจสำเร็จรูปเพื่อแทงเร็ว
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
- คำอธิบาย: เลือก package สำหรับกลุ่มหวยที่ระบุ
- ใช้เมื่อ: ผู้ใช้กดเลือกชุดเลขที่ต้องการใช้งาน
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
- คำอธิบาย: ดึง package ที่สมาชิกเลือกไว้ล่าสุด
- ใช้เมื่อ: restore state ตอนกลับเข้าหน้าซื้อเลข
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
- คำอธิบาย: ดึงรายการบิลหวยของสมาชิก
- ใช้เมื่อ: แสดงประวัติบิลหวยพร้อมสถานะ
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
- คำอธิบาย: ดึงรายละเอียดบิลหวยรายใบ
- ใช้เมื่อ: เปิดดูเลขที่แทงและยอดในบิลนั้น
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
- คำอธิบาย: ยกเลิกบิลหวยตามเงื่อนไขเวลาที่อนุญาต
- ใช้เมื่อ: ผู้ใช้ยกเลิกบิลก่อนปิดรับเดิมพัน
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
- คำอธิบาย: ดึงรายการวงล้อ/สิทธิ์ที่สมาชิกเล่นได้
- ใช้เมื่อ: แสดงรายการวงล้อและสถานะสิทธิ์
- Request example:
```http
GET /api/v1/wheel/list
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงข้อมูลวงล้อสำเร็จ",
  "data": {
    "wheel": [
      { "code": 1, "text": "20", "name": "BONUS_20" }
    ],
    "enabled": true
  }
}
```

### `POST /api/v1/wheel/spin`
- คำอธิบาย: หมุนวงล้อและรับผลรางวัล
- ใช้เมื่อ: ผู้ใช้กดปุ่มหมุนวงล้อ
- Request example:
```json
{}
```
- Response example:
```json
{
  "success": true,
  "message": "complete",
  "diamond": 9,
  "format": {
    "title": "ยินดีด้วย",
    "msg": "ระบบเพิ่มรางวัลให้แล้ว",
    "img": "https://.../spin_img/spin-win.png",
    "point": 88,
    "diamond": 9
  },
  "spin": [
    { "text": 20, "image": "https://..." }
  ]
}
```

### `GET /api/v1/wheel/history`
- คำอธิบาย: ดึงประวัติการหมุนวงล้อของสมาชิก
- ใช้เมื่อ: แสดงผลการหมุนย้อนหลังในหน้า wheel
- Request example:
```http
GET /api/v1/wheel/history?page=1
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงประวัติวงล้อสำเร็จ",
  "data": {
    "history": [
      { "id": 1, "amount": 20, "created_at": "2026-04-19 10:30:00" }
    ]
  }
}
```

### `GET /api/v1/reward/list`
- คำอธิบาย: ดึงรายการ reward ที่แลกได้ในช่วงเวลาปัจจุบัน (active, ไม่ซ่อน, ยังมีสต๊อก)
- ใช้เมื่อ: แสดงหน้ารายการ reward ให้สมาชิกเลือกแลก
- Request example:
```http
GET /api/v1/reward/list?page=1&per_page=20&featured_only=1
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงรายการรางวัลสำเร็จ",
  "point": 120,
  "diamond": 120,
  "system": {
    "reward": true
  },
  "rewards": [
    {
      "id": 10,
      "code": "RW-CREDIT-01",
      "name": "เครดิต 50",
      "reward_type": "wallet_credit",
      "fulfillment_mode": "auto",
      "point_cost": 50,
      "stock_remaining": 12
    }
  ]
}
```

### `POST /api/v1/reward/redeem`
- คำอธิบาย: แลกแต้มกับ reward ที่เลือก โดยตรวจแต้ม/สต๊อก/limit ก่อนทำรายการ
- ใช้เมื่อ: สมาชิกกดยืนยันแลกรางวัล
- Headers:
  - optional `X-Idempotency-Key` สำหรับกันคำขอซ้ำ
- Request example:
```json
{
  "reward_id": 10
}
```
- Response example:
```json
{
  "success": true,
  "message": "ทำรายการแลกรางวัลเรียบร้อย",
  "point": 70,
  "mode": "manual",
  "redemption_status": "pending",
  "format": {
    "title": "รับเรื่องแล้ว",
    "msg": "ระบบรับรายการแล้ว กรุณารอการดำเนินการ",
    "img": ""
  },
  "redemption_id": 501
}
```

### `GET /api/v1/reward/history`
- คำอธิบาย: ดึงประวัติการแลก reward พร้อมโครง `timeline` สำหรับหน้าประวัติแบบเส้นเวลา
- ใช้เมื่อ: แสดงหน้าประวัติการแลกรางวัล
- Request example:
```http
GET /api/v1/reward/history?page=1&per_page=20
```
- Response example:
```json
{
  "success": true,
  "message": "ดึงประวัติการแลกรางวัลสำเร็จ",
  "items": [
    {
      "id": 501,
      "reward_code_snapshot": "RW-CREDIT-01",
      "reward_name_snapshot": "เครดิต 50",
      "point_cost_snapshot": 50,
      "status": "fulfilled",
      "redeemed_at": "2026-04-19 10:30:00"
    }
  ],
  "timeline": [
    {
      "date": "2026-04-19",
      "count": 1
    }
  ]
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
