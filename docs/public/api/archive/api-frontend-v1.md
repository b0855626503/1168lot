# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-30

เอกสารนี้เป็นไฟล์หลักสำหรับหน้า `/docs/api/frontend-v1` ที่เน้นให้ทีม frontend เริ่มใช้งาน API ได้เร็วที่สุด

## Navigation (Developer-first)

1. [Quick Start](#quick-start-login---balance)
2. [Auth / Headers / Language](#auth--headers--language)
3. [Common Contract](#common-contract-current--target)
4. [Flows](#flows-optional)
5. [Endpoint Groups](#endpoint-groups-core-first)
6. [Full Reference (Appendix)](#full-reference-appendix)

## Quick Start (Login -> Balance)

### Step 1) Login เพื่อรับ token

`POST /api/v1/auth/login`

```bash
curl -X POST "/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Language: th" \
  -d '{
    "user_name": "0900000014",
    "password": "pass1234"
  }'
```

ตัวอย่าง response:

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

### Step 2) ใช้ token เรียก balance

`GET /api/v1/member/balance`

```bash
curl "/api/v1/member/balance" \
  -H "Authorization: Bearer eyJ..." \
  -H "X-Language: th"
```

ตัวอย่าง response:

```json
{
  "success": true,
  "message": "ดึงยอดเงินสำเร็จ",
  "data": {
    "balance": 1200
  }
}
```

### Axios Example

```js
const login = await axios.post("/api/v1/auth/login", {
  user_name: "0900000014",
  password: "pass1234",
}, {
  headers: { "X-Language": "th" },
});

const token = login.data?.data?.access_token;

const balance = await axios.get("/api/v1/member/balance", {
  headers: {
    Authorization: `Bearer ${token}`,
    "X-Language": "th",
  },
});
```

## Auth / Headers / Language

- Base URL: `/api/v1`
- Required header: `Content-Type: application/json`
- Language header: `X-Language: th|en|kh|la`
- Auth header (authenticated routes): `Authorization: Bearer <access_token>`

Language fallback order:
1. `language` / `lang` / `locale` (body/query)
2. `X-Language`
3. `Accept-Language`
4. fallback `th`

## Common Contract (Current + Target)

Current contracts (ของจริงในระบบตอนนี้):
- Standard (ส่วนใหญ่): `{ success, message, data }`
- Success แบบไม่มี data: `{ success, message }`
- บาง endpoint เป็น custom payload (เช่น reward list/history)

Target contract (แนวทางที่ควร normalize ในงานถัดไป):
- Standard: `{ success, message, data }`
- List: `data.items`
- Pagination: `data.meta`
- Error: `{ success: false, message, errors? }`

หมายเหตุ: ส่วน Target เป็น roadmap เพื่อความเสถียรของ frontend mapping; ยังไม่ใช่การเปลี่ยน contract runtime ทันที

## Error Handling

| HTTP | กรณี | แนวทางฝั่ง frontend |
|---|---|---|
| 401 | token หาย/หมดอายุ/ไม่ถูกต้อง | เด้ง login และล้าง token เดิม |
| 422 | validation หรือ business rule ไม่ผ่าน | แสดง message จาก API ตรงๆ |
| 404 | route/resource ไม่พบ | แสดง not found ตามบริบท |
| 500 | server error | retry แบบ backoff และแจ้งผู้ใช้ |

Retry guideline:
- `GET` retry ได้ (1-2 ครั้งด้วย exponential backoff)
- `POST/PUT` ให้ retry เฉพาะกรณีมี idempotency key และ timeout/network fail

## Idempotency Guideline

- แนะนำใช้ header `X-Idempotency-Key` กับ write endpoint ที่เสี่ยงยิงซ้ำ
- ตัวอย่าง endpoint ที่รองรับในเอกสารนี้: `POST /api/v1/reward/redeem`
- รูปแบบ key แนะนำ: `memberId-action-uuid`

## Terminology (Lock Vocabulary)

- `member` = ผู้ใช้ระบบฝั่งสมาชิก (ไม่ใช้สลับกับ `user`)
- `wallet` = กระเป๋า/บัญชีธุรกรรม
- `balance` = ยอดคงเหลือเชิงตัวเลข
- `draw` = งวดหวยหลัก
- `round` = รอบย่อย (เช่นยี่กี่)

## Flows (Optional)

- auth: register -> login -> logout
- member: profile -> balance -> history
- wallet: withdraw/claim/transactions
- lotto: draws/markets -> bet -> tickets -> cancel
- yeekee: current-round -> shoot -> result-proof -> reward-status

## Endpoint Groups (Core-first)

Core ที่ใช้บ่อยสุด:
1. Auth: `/auth/register`, `/auth/login`, `/auth/logout`
2. Member/Wallet: `/member/profile`, `/member/balance`, `/wallet/transactions`
3. Lotto Core: `/lotto/draws`, `/lotto/markets/latest`, `/lotto/bet`, `/lotto/tickets`

Domain groups:
- Auth
- Member
- Wallet
- Payment (deposit/smkpay/deeppay)
- Lotto
- Game
- Promotion / Coupon
- Wheel / Reward
- Yeekee (อยู่ท้ายสุดใน authenticated list)

## Edge-case Cross Reference

- `/api/v1/lotto/navbar-config` -> ดู fallback locale policy ใน `Edge Cases`
- `/api/v1/deposit/loadbank` และ `/api/v1/deposit/loadbank/random` -> ดู fallback `deposit_min` / `qr_pic` ใน `Contract Notes`
- `/api/v1/lotto/yeekee/*` -> ดู timing/reveal behavior ในหัวข้อ `Yeekee API`

## Full Reference (Appendix)

- [Route Reference (Main)](../frontend-v1/05-route-reference.md)
- [Route Reference (Wheel/Reward Addendum)](../frontend-v1/05-route-reference-wheel-reward.md)

Source of truth (code):
- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`



---

# Frontend API V1 - Overview

อัปเดตล่าสุด: 2026-04-19

## Base URL

- `/api/v1`

## Headers

- `Content-Type: application/json`
- `X-Language: th|en|kh|la`
- auth endpoints ใช้ `Authorization: Bearer <token>`

## Response Baseline

- `success`
- `message`
- `data` หรือ payload root ตาม endpoint


---

# Frontend API V1 - Flows

อัปเดตล่าสุด: 2026-04-18

## Main Flows

- auth: register/login/logout
- member: profile/balance/history/change-password
- wallet: withdraw/claim/transactions
- lotto: draws/markets/tickets/bet/cancel
- realtime: config/auth/member-context/heartbeat

## Fast Trace

- route -> controller -> service/repository -> response contract


---

# Frontend API V1 - Endpoints

อัปเดตล่าสุด: 2026-04-30

## การยืนยันตัวตน (Auth)

- API ที่ไม่ต้อง login: อยู่ในกลุ่ม Public Endpoints
- API ที่ต้อง login: อยู่ในกลุ่ม Authenticated Endpoints
- รูปแบบ token:
  - Header: `Authorization: Bearer <access_token>`
  - ถ้าไม่มี token จะได้ `401` + message `ไม่พบ Bearer token`
  - ถ้า token ไม่ถูกต้อง/หมดอายุ จะได้ `401` + message `token ไม่ถูกต้องหรือหมดอายุ`

## การส่งภาษา (Language)

- ระบบรองรับ `th|en|kh|la`
- ส่งภาษาได้หลายทาง (ระบบ resolve ตามลำดับ):
  - body/query: `language` หรือ `lang` หรือ `locale`
  - header: `X-Language`
  - header: `Accept-Language`
- ถ้าไม่ส่งหรือส่งค่าไม่รองรับ จะ fallback เป็น `th`

## Public Endpoints

- `GET /api/v1/auth/register/banks`
- `POST /api/v1/auth/register/bank-account-name`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/games/types`
- `GET /api/v1/games/providers/{type}`
- `GET /api/v1/games/{type}/{provider}`
- `GET /api/v1/slides`
- `GET /api/v1/meta/online-members`
- `GET /api/v1/meta/contact-channels`
- `GET /api/v1/meta/site`
- `GET /api/v1/realtime/config`
- `GET /api/v1/lotto/draws`
- `GET /api/v1/lotto/draws/{id}`
- `GET /api/v1/lotto/markets/latest`
- `GET /api/v1/lotto/markets/{marketId}/betting-context`
- `GET /api/v1/lotto/markets/{marketId}/results`
- `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- `GET /api/v1/lotto/results/by-date`
- `GET /api/v1/lotto/navbar-config`

## Authenticated Endpoints

- `POST /api/v1/auth/logout`
- `GET /api/v1/member/profile`
- `GET /api/v1/member/balance`
- `GET /api/v1/member/loadbalance`
- `POST /api/v1/member/change-password`
- `POST /api/v1/member/wallet-address`
- `GET /api/v1/member/contributor`
- `GET /api/v1/member/history`
- `GET /api/v1/member/history/{type}`
- `GET /api/v1/member/realtime-context`
- `POST /api/v1/member/heartbeat`
- `POST /api/v1/realtime/auth`
- `POST /api/v1/wallet/withdraw`
- `POST /api/v1/wallet/claim`
- `GET /api/v1/wallet/transactions`
- `POST /api/v1/coupon/redeem`
- `GET /api/v1/coupon/my`
- `POST /api/v1/coupon/my/{code}/claim`
- `GET /api/v1/deposit/channels`
- `POST /api/v1/deposit/loadbank`
- `POST /api/v1/deposit/loadbank/random`
- `GET /api/v1/smkpay/deposit/status/{txid}`
- `POST /api/v1/smkpay/deposit/expire/{txid}`
- `POST /api/v1/smkpay/deposit/create`
- `GET /api/v1/smkpay/qrcode/{id}`
- `GET /api/v1/promotion/list`
- `POST /api/v1/promotion/select`
- `POST /api/v1/promotion/deselect`
- `POST /api/v1/games/login`
- `GET /api/v1/games/login/{game}/{code}`
- `POST /api/v1/lotto/bet`
- `GET /api/v1/lotto/groups/{groupId}/packages`
- `POST /api/v1/lotto/groups/{groupId}/select-package`
- `GET /api/v1/lotto/groups/{groupId}/selected-package`
- `GET /api/v1/lotto/tickets`
- `GET /api/v1/lotto/tickets/{id}`
- `POST /api/v1/lotto/tickets/{id}/cancel`
- `GET /api/v1/wheel/list`
- `POST /api/v1/wheel/spin`
- `GET /api/v1/wheel/history`
- `GET /api/v1/reward/list`
- `POST /api/v1/reward/redeem`
- `GET /api/v1/reward/history`
- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`


## Contract Notes

- `GET /api/v1/meta/site` ส่ง `deposit_min` จาก `configs.deposit_min`
- `GET /api/v1/member/profile` ส่ง `profile.deposit_min` จาก `configs.deposit_min`
- `POST /api/v1/auth/login` ออก active token ล่าสุดได้ครั้งละ 1 ตัวต่อ member; token เดิมของ member เดียวกันจะใช้ต่อไม่ได้หลัง login ใหม่
- `POST /api/v1/deposit/loadbank` และ `/deposit/loadbank/random` ส่ง `qr_pic` เป็น `""` เมื่อบัญชีไม่มีรูป QR ที่อัปโหลดไว้
- `deposit_min` ของ `/deposit/loadbank` และ `/deposit/loadbank/random` ใช้ `bank_account.deposit_min` ก่อน ถ้าเป็น `0` จึง fallback ไป `configs.deposit_min`; ถ้าทั้งคู่เป็น `0` ส่ง `0`



---

# Frontend API V1 - Edge Cases

อัปเดตล่าสุด: 2026-04-19

## Compatibility Notes

- realtime event migration: prefer `member.activity.updated`, keep legacy fallback ชั่วคราว
- lotto latest draw selection ต้องข้าม `draft` ตาม policy
- wallet transaction history ต้องแสดงข้อมูลรวมจาก source เดียวกันอย่างสม่ำเสมอ
- lotto navbar config (`GET /api/v1/lotto/navbar-config`) ใช้ fallback locale แบบ deterministic:
  - requested locale -> `th` -> `en` -> `key`
- ถ้าไม่ส่ง `code` จะใช้ default `mobile_bottom_nav`
- ถ้า default code ถูก unpublish หรือไม่มี published row จะตอบ `404` ทุกครั้ง (ไม่ fallback hardcoded payload)

## Contract Safety

- เปลี่ยน route/controller behavior ต้องอัปเดต `03-endpoints.md` ทันที
- เปลี่ยน response field ต้องอัปเดต `01-overview.md` หรือ note เฉพาะ flow


---

# Frontend API V1 - Route Reference (Full)

อัปเดตล่าสุด: 2026-04-19

เอกสารนี้สรุปครบทุก route ใน `packages/Gametech/FrontendApi/src/Routes/api.php` พร้อมตัวอย่าง request/response แบบใช้งานจริงหน้าบ้าน

## Conventions

- Base URL: `/api/v1`
- Header มาตรฐาน:
  - `Content-Type: application/json`
  - `X-Language: th|en|kh|la`
  - endpoint ที่ต้อง auth: `Authorization: Bearer <access_token>`
- Success envelope (ส่วนใหญ่):

```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {}
}
```

- Error envelope (ส่วนใหญ่):

```json
{
  "success": false,
  "message": "เกิดข้อผิดพลาด"
}
```

---

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

### `POST /api/v1/auth/login`
- คำอธิบาย: เข้าสู่ระบบและออก access token สำหรับเรียก API ที่ต้องยืนยันตัวตน
- ใช้เมื่อ: ผู้ใช้ล็อกอินจากหน้าแรก/หน้าเข้าสู่ระบบ
- Session policy: สมาชิก 1 คนมี active token ได้ครั้งละ 1 ตัว; login ใหม่จะทำให้ token เดิมใช้ต่อไม่ได้
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
- คำอธิบาย: ดึงประเภทเกมที่เปิดให้บริการ เช่น slot, casino, sport
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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "type": "slot", "title": "Slot" }
    ]
  }
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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "treasures-aztec", "name": "Treasures of Aztec" }
    ]
  }
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
- ใช้เมื่อ: โหลดค่าคอนฟิกพื้นฐานก่อน render แอป รวมถึง `header_code` สำหรับ script/header integration ที่ frontend ต้องนำไปใช้งาน
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
    "logo": "https://example.com/storage/img/logo.png",
    "title": "1168lot",
    "name": "1168lot",
    "description": "เว็บเกมออนไลน์",
    "header_code": "<script>window.analytics=true;</script>",
    "deposit_min": "100.00"
  }
}
```

### `GET /api/v1/realtime/config`
- คำอธิบาย: ดึงคอนฟิกระบบ realtime ที่ frontend ต้องใช้เชื่อมต่อ
- ใช้เมื่อ: ตั้งค่า websocket/reverb client ตอนเริ่มแอป
- Broadcast note: `.public.activity.updated` ของ Lotto มี `message` พร้อมแสดงผลสำหรับ draw closed/resulted/reopened และ ticket-list resulted update
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
    "name": "Api User",
    "deposit_min": "100.00"
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
- ใช้เมื่อ: ตั้งค่า presence/channel หลังล็อกอิน
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
- คำอธิบาย: ส่ง heartbeat เพื่ออัปเดตสถานะออนไลน์ของสมาชิก
- ใช้เมื่อ: ยิงเป็นช่วงเวลาเพื่อคงสถานะ active session
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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "smkpay", "name": "SMKPay" }
    ]
  }
}
```

### `POST /api/v1/deposit/loadbank`
- คำอธิบาย: ดึงข้อมูลบัญชีธนาคารปลายทางของระบบสำหรับฝาก
- ใช้เมื่อ: ผู้ใช้เลือกฝากผ่านธนาคารและต้องการข้อมูลบัญชีรับโอน
- Request example: `{ "method": "bank" }`
- Response shape: `{ "success": true, "bank": [BankAccount] }`
- `BankAccount` fields: `acc_no`, `acc_name`, `bank_name`, `bank_pic`, `qr_pic`, `qrcode`, `code`, `deposit_min`, `remark`
- หมายเหตุ: `bank_pic` เป็น public URL; `qr_pic` เป็น URL เมื่อมีรูปจริง; `deposit_min` ใช้ค่าบัญชีก่อน ถ้าเป็น `0` จึง fallback ไป `configs.deposit_min`

### `POST /api/v1/deposit/loadbank/random`
- คำอธิบาย: ดึงบัญชีธนาคารปลายทางแบบสุ่ม 1 รายการจากรายการที่เปิดใช้งาน
- ใช้เมื่อ: ต้องการให้ลูกค้าเห็นบัญชีรับโอนเพียงบัญชีเดียวต่อครั้ง
- Request example: `{ "method": "bank" }`
- Response shape: `{ "success": true, "bank": BankAccount }`
- Empty response: `{ "success": false, "bank": "" }`
- Request `method` รองรับ `bank`, `tw`, `slip`
- หมายเหตุ: `qr_pic` เป็น `""` เมื่อไม่มีรูป QR; `deposit_min` ใช้กติกาเดียวกับ `/deposit/loadbank`

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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "code": "PRO2026", "name": "โบนัสต้อนรับ" }
    ]
  }
}
```

### `POST /api/v1/promotion/select`
- คำอธิบาย: เลือกเข้าร่วมโปรโมชั่น
- ใช้เมื่อ: ผู้ใช้กดยืนยันรับโปรที่ต้องการ
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
- คำอธิบาย: ยกเลิกการเข้าร่วมโปรโมชั่นปัจจุบัน
- ใช้เมื่อ: ผู้ใช้ต้องการออกจากโปรก่อนเปลี่ยนโปรใหม่
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
- คำอธิบาย: ขอ URL/Session สำหรับเข้าเล่นเกมแบบยิงจาก frontend
- ใช้เมื่อ: ผู้ใช้กดเข้าเกมจากการ์ดเกม
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


### `POST /api/v1/lotto/tickets/{id}/cancel` (Moved)
- ดูรายละเอียดที่ [05-route-reference-wheel-reward.md](../frontend-v1/05-route-reference-wheel-reward.md)

### Wheel / Reward / Common Errors (Moved)

เนื้อหาส่วนนี้ถูกแยกออกไปที่:

- [05-route-reference-wheel-reward.md](../frontend-v1/05-route-reference-wheel-reward.md)


---

# Frontend API V1 - Route Reference (Wheel, Reward, Errors)

อัปเดตล่าสุด: 2026-04-19

เอกสารนี้เป็น chapter ต่อจาก [05-route-reference.md](../frontend-v1/05-route-reference.md)

## Additional Moved Section

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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "id": 1, "name": "Lucky Wheel", "spins_left": 1 }
    ]
  }
}
```

### `POST /api/v1/wheel/spin`
- คำอธิบาย: หมุนวงล้อและรับผลรางวัล
- ใช้เมื่อ: ผู้ใช้กดปุ่มหมุนวงล้อ
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
  "message": "สำเร็จ",
  "data": {
    "items": [
      { "wheel_id": 1, "prize": "BONUS_20", "created_at": "2026-04-19 10:30:00" }
    ]
  }
}
```

### `GET /api/v1/reward/list`
- คำอธิบาย: ดึงรายการ reward ที่สมาชิกสามารถแลกได้ ณ เวลาปัจจุบัน (คัดเฉพาะที่ active, ไม่ซ่อน, ยังมีสต๊อก และอยู่ในช่วงเวลาใช้งาน)
- ใช้เมื่อ: แสดงหน้ารายการ reward ให้สมาชิกเลือกแลก
- Query params:
  - `page` (optional, default `1`)
  - `per_page` (optional, default `20`, max `20`)
  - `reward_type` (optional) เช่น `wallet_credit`, `wallet_gem`, `external`
  - `q` (optional) ค้นหาโดยชื่อ/รหัส/รายละเอียด
  - `featured_only` (optional) เช่น `1`, `true`, `Y`
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
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### `POST /api/v1/reward/redeem`
- คำอธิบาย: แลกแต้มกับ reward ที่เลือก โดยระบบตรวจแต้ม, เวลาใช้งาน, สต๊อก, limit และบันทึก redemption
- ใช้เมื่อ: สมาชิกกดยืนยันแลกรางวัล
- Headers:
  - optional `X-Idempotency-Key` สำหรับกันการยิงซ้ำ
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
- คำอธิบาย: ดึงประวัติการแลก reward และสรุปเป็นเส้นเวลา (timeline) แยกตามวัน
- ใช้เมื่อ: แสดงหน้าประวัติการแลกรางวัล
- Query params:
  - `page` (optional, default `1`)
  - `per_page` (optional, default `20`, max `50`)
  - `q` (optional) ค้นหาโดย reward code/name snapshot
  - `status` (optional) `pending|fulfilled|rejected|cancelled`
  - `reward_type` (optional)
  - `mode` (optional) `auto|manual|approval`
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
      "count": 1,
      "items": [
        {
          "id": 501,
          "status": "fulfilled"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```


## Yeekee API

- `shoot` คือการส่งเลข 5 หลักเพื่อชิงลำดับ (position) ในรอบยี่กี่ ไม่ใช่การแทงโพย
- lifecycle: `betting open -> betting closed -> shoot window -> pending result -> resulted/voided`
- Yeekee ไม่มี manual result
- response เดิมของ lotto จะเพิ่ม field แบบไม่กระทบของเดิม เช่น `result_mode`, `market_type`, `is_yeekee`, `has_shoot`, `round_status`

### `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- คำอธิบาย: ส่งเลข 5 หลักเพื่อชิงลำดับยิงในรอบยี่กี่
- ใช้เมื่อ: อยู่ในช่วงยิงเลขของรอบ และสมาชิกต้องการยิงเลข
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Request example:
```json
{
  "number": "12345"
}
```
- Response example:
```json
{
  "success": true,
  "message": "ยิงเลขสำเร็จ",
  "data": {
    "round_id": 901,
    "position": 128,
    "number_text": "12345",
    "submitted_at": "2026-04-30 12:00:01",
    "round_status": "shoot_open"
  }
}
```
- Error example (จากโค้ดจริง):
  - `ยังไม่ถึงเวลายิงเลข`
  - `หมดเวลายิงเลขแล้ว`
  - `กรุณากรอกเลข 5 หลัก`
  - `รายการหวยนี้ไม่รองรับการยิงเลข`
  - `รอบนี้ไม่สามารถยิงเลขได้`
  - `เกินจำนวนการยิงเลขสูงสุดต่อรอบ`

### `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- คำอธิบาย: ดึงรอบยี่กี่ปัจจุบันของ market
- ใช้เมื่อ: หน้า frontend ต้องรู้สถานะรอบล่าสุดและ timeline ของรอบ
- Auth: ต้องใช้ token
- Path params:
  - `marketId` = id ของตลาดหวย
- Response example:
```json
{
  "success": true,
  "message": "ดึงรอบยี่กี่ปัจจุบันสำเร็จ",
  "data": {
    "market_id": 12,
    "draw_id": 7788,
    "round_id": 901,
    "result_mode": "yeekee",
    "round_no": 42,
    "status": "shoot_open",
    "bet_open_at": "2026-04-30 12:00:00",
    "bet_close_at": "2026-04-30 12:10:00",
    "shoot_open_at": "2026-04-30 12:10:00",
    "shoot_close_at": "2026-04-30 12:11:00",
    "result_compute_at": "2026-04-30 12:12:00",
    "server_time": "2026-04-30 12:10:15"
  }
}
```
- Error example:
  - `ไม่พบหวยที่ระบุ`
  - `รายการหวยนี้ไม่รองรับการยิงเลข`
  - `ไม่พบรอบยี่กี่ที่เปิดอยู่`

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- คำอธิบาย: ดึงรายการยิงเลขล่าสุดในรอบ (เรียง position ล่าสุดก่อน)
- ใช้เมื่อ: หน้าแสดง feed ยิงเลขของรอบ
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Query params:
  - `limit` (optional, default `50`, max `100`)
- Response example:
```json
{
  "success": true,
  "message": "ดึงรายการยิงเลขสำเร็จ",
  "data": {
    "round_id": 901,
    "limit": 50,
    "count": 2,
    "items": [
      {
        "position": 128,
        "number_text": "12345",
        "submitted_at": "2026-04-30 12:10:01"
      },
      {
        "position": 127,
        "number_text": "54321",
        "submitted_at": "2026-04-30 12:09:58"
      }
    ]
  }
}
```

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- คำอธิบาย: ดึงสถานะว่ารอบนี้สมาชิกได้รับรางวัลยิงเลขหรือไม่
- ใช้เมื่อ: หน้า profile/round status ต้องแสดงสิทธิ์รางวัลยิงเลขของสมาชิก
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Response example:
```json
{
  "success": true,
  "message": "ดึงสถานะรางวัลยิงเลขสำเร็จ",
  "data": {
    "round_id": 901,
    "member_id": 61240,
    "reward_enabled": true,
    "reward_count": 1,
    "rewarded": true,
    "items": [
      {
        "position": 88,
        "credit_amount": 20
      }
    ]
  }
}
```

### `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`
- คำอธิบาย: ดึงข้อมูล proof สำหรับตรวจสอบความโปร่งใสของผลยี่กี่
- ใช้เมื่อ: หน้า result/proof ต้องแสดงหลักฐานก่อนหรือหลัง reveal
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Response example (ก่อน reveal):
```json
{
  "success": true,
  "message": "ดึงข้อมูลผลและหลักฐานสำเร็จ",
  "data": {
    "round_id": 901,
    "draw_id": 7788,
    "status": "result_pending",
    "is_revealed": false,
    "proof": {
      "formula_label": "PRECOMMITTED_BASE64_MD5",
      "precommit_signature": "7f4d...",
      "proof_signature": "",
      "external_seed_reference": "",
      "result_payload": null
    },
    "server_time": "2026-04-30 12:12:00"
  }
}
```
- Response example (หลัง reveal):
```json
{
  "success": true,
  "message": "ดึงข้อมูลผลและหลักฐานสำเร็จ",
  "data": {
    "round_id": 901,
    "draw_id": 7788,
    "status": "resulted",
    "is_revealed": true,
    "proof": {
      "formula_label": "PRECOMMITTED_BASE64_MD5",
      "precommit_signature": "7f4d...",
      "proof_signature": "a9bc...",
      "external_seed_reference": "NTP:2026-04-30T12:12:00Z",
      "result_payload": {
        "raw_result": "12345",
        "top_3": "123",
        "bottom_2": "45"
      }
    },
    "server_time": "2026-04-30 12:13:00"
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
