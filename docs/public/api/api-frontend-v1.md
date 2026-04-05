# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-04-04

## Base URL
- `http://api.<domain>/api/v1`
- ตัวอย่าง local: `http://127.0.0.1:18080/api/v1` + Header `Host: api.localhost`

## มาตรฐานการเรียก

### Headers
- `Content-Type: application/json`
- Endpoint ที่ต้องยืนยันตัวตน ให้ส่ง `Authorization: Bearer <access_token>`
- รองรับ `X-Language: th|en|kh|la` (ไม่ส่ง = `th`)

### Language (ทุก endpoint ใน Frontend API)
- ระบบรองรับภาษา: `th`, `en`, `kh`, `la`
- ส่งภาษาได้จากอย่างใดอย่างหนึ่ง:
1. Query/Body: `language`
2. Query/Body: `lang`
3. Query/Body: `locale`
4. Header: `X-Language`
5. Header: `Accept-Language` (ระบบจะอ่านตัวแรก เช่น `en-US` -> `en`)
- ถ้าไม่ส่งหรือส่งค่าที่ไม่รองรับ ระบบจะ fallback เป็น `th` อัตโนมัติ

### โครง response มาตรฐาน
```json
{
  "success": true,
  "message": "ข้อความ",
  "data": {}
}
```
หรือบาง endpoint จะคืน object เฉพาะของระบบเดิมพร้อม `success/message`

### กรณี token ไม่ถูกต้อง
```json
{
  "success": false,
  "message": "token ไม่ถูกต้องหรือหมดอายุ"
}
```

## รายการเส้นทาง API

### 1) Auth

#### 1.1 สมัครสมาชิก
- `POST /auth/register`
- Auth: ไม่ต้องใช้ token

เงื่อนไขสำคัญของฟิลด์
- `user_name`
  - ต้องเป็นเบอร์โทรตัวเลขเท่านั้น
  - ระบบจะ normalize โดยตัดอักขระที่ไม่ใช่ตัวเลขออกก่อน validate
  - หลัง normalize ต้องตรงรูปแบบ `^0\d{9}$` (10 หลัก และขึ้นต้นด้วย `0`)
  - ใช้เป็นค่า `tel` และ `wallet_id` ด้วยในฝั่งระบบ
- `password`
  - ประเภท `string`
  - ความยาวต่ำสุด `6` ตัวอักษร
  - ความยาวสูงสุด `10` ตัวอักษร
- `password_confirm`
  - ถ้าส่งมา ต้องตรงกับ `password`
- `acc_no`
  - ตัวเลขเท่านั้น (ระบบ normalize เช่นเดียวกัน)
  - ต้องไม่ซ้ำภายใต้ `bank` เดียวกัน
- `bank`
  - ต้องเป็นจำนวนเต็ม และต้องส่งเสมอ
- `refer`
  - ต้องเป็นจำนวนเต็ม และต้องส่งเสมอ
- `referral_code` (หรือ `invite_code` / `recommend_code`)
  - ไม่บังคับส่ง
  - ถ้าส่งมาแล้วตรงกับรหัสแนะนำของสมาชิกเดิม ระบบจะ map `upline_code` ให้โดยอัตโนมัติ
  - ระบบ normalize เป็นตัวพิมพ์ใหญ่ และแทน `O` เป็น `0` ก่อนเทียบ

##### Field Matrix (POST /auth/register)

| Field | Required | Type | Rules / Notes |
|---|---|---|---|
| `user_name` | Yes | string | เบอร์โทร 10 หลักขึ้นต้น 0 (`^0\d{9}$`), ระบบจะ normalize ให้เหลือเฉพาะตัวเลข |
| `password` | Yes | string | ความยาว 6-10 ตัวอักษร |
| `password_confirm` | No | string | ถ้าส่งมา ต้องตรงกับ `password` |
| `name` | Conditionally* | string | ใช้กรอกชื่อเต็ม แล้วระบบแยกเป็น `firstname`/`lastname` |
| `firstname` | Conditionally* | string | ต้องเป็นตัวอักษร/ช่องว่าง/ขีด (`\pL \pM space -`) |
| `lastname` | Conditionally* | string | ต้องเป็นตัวอักษร/ช่องว่าง/ขีด (`\pL \pM space -`) |
| `bank` | Yes | integer | รหัสธนาคาร |
| `acc_no` | Yes | string | ตัวเลขเท่านั้น, 1-14 หลัก, ห้ามซ้ำใน `bank` เดียวกัน |
| `refer` | Yes | integer | รหัสแหล่งที่มา (`refer code`) |
| `referral_code` | No | string | รหัสแนะนำ 8 หลัก (alias: `invite_code`, `recommend_code`) ใช้ map `upline_code` อัตโนมัติ |
| `marketing` | No | string | โค้ดลิงก์การตลาด, ถ้ามีจะ map ไป `team_id/campaign_id` |
| `lineid` | No | string | LINE ID |
| `upline` | No | integer | รหัส upline (default = `0`) |
| `promotion` | No | string | ค่าเริ่มต้น `N` |

\* `name` หรือ (`firstname` + `lastname`) ต้องมีอย่างน้อยหนึ่งรูปแบบ

Request body
```json
{
  "user_name": "0900000014",
  "password": "pass1234",
  "password_confirm": "pass1234",
  "name": "Api User",
  "acc_no": "1234567890",
  "bank": 1,
  "refer": 1,
  "referral_code": "AB12C3D4",
  "marketing": "MK2026ABC"
}
```

Response (success)
```json
{
  "success": true,
  "message": "สมัครสมาชิกสำเร็จ"
}
```

Response (validation fail)
```json
{
  "success": false,
  "message": "ข้อมูลสมัครสมาชิกไม่ถูกต้อง",
  "errors": {
    "user_name": [
      "เบอร์โทรต้องขึ้นต้นด้วย 0 และมี 10 หลัก"
    ],
    "acc_no": [
      "เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบบัญชีธนาคารภายใน"
    ]
  },
  "error_fields": [
    "user_name",
    "acc_no"
  ],
  "duplicate_fields": [
    "acc_no"
  ],
  "details": {
    "user_name": {
      "messages": [
        "เบอร์โทรต้องขึ้นต้นด้วย 0 และมี 10 หลัก"
      ],
      "failed_rules": [
        "Regex"
      ],
      "is_duplicate": false
    },
    "acc_no": {
      "messages": [
        "เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบบัญชีธนาคารภายใน"
      ],
      "failed_rules": [],
      "is_duplicate": true
    }
  }
}
```

Response (runtime fail after validation)
```json
{
  "success": false,
  "message": "ไม่สามารถเชื่อมต่อระบบเกมเพื่อสร้างบัญชีได้ในขณะนี้",
  "error_code": "REGISTER_GAME_ACCOUNT_CONNECT_FAILED",
  "details": {
    "stage": "game_account_create",
    "reason": "connect_failed",
    "upstream_message": "เชื่อมต่อไม่ได้"
  }
}
```

HTTP status
- สำเร็จ: `200`
- validation ไม่ผ่าน: `422`
- fail หลัง validation: `422`

หมายเหตุ: ใน environment ที่ Redis queue มีปัญหา อาจเกิด timeout จาก process ภายในระบบเดิม

#### 1.1.1 รายการธนาคารสำหรับหน้าสมัคร
- `GET /auth/register/banks`
- Auth: ไม่ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "ดึงรายการธนาคารสำหรับสมัครสมาชิกสำเร็จ",
  "data": {
    "banks": [
      {
        "code": 1,
        "name": "กสิกรไทย",
        "name_th": "กสิกรไทย",
        "name_en": "Kasikorn Bank",
        "shortcode": "KBANK"
      }
    ]
  }
}
```

#### 1.2 ล็อกอิน
- `POST /auth/login`
- Auth: ไม่ต้องใช้ token

Request body
```json
{
  "user_name": "9000000011",
  "password": "pass1234"
}
```

Response
```json
{
  "access_token": "<jwt>",
  "token_type": "Bearer",
  "expires_at": "2026-03-21 20:55:17",
  "expires_in": 21600,
  "member": {
    "code": 1,
    "user_name": "9000000011",
    "name": "API Test User",
    "confirm": "Y"
  },
  "success": true,
  "message": "เข้าสู่ระบบสำเร็จ"
}
```

#### 1.3 ล็อกเอาต์
- `POST /auth/logout`
- Auth: ต้องใช้ token

Response
```json
{
  "success": true,
  "message": "ออกจากระบบสำเร็จ"
}
```

#### 1.4 ข้อมูลเว็บไซต์ (Site Meta)
- `GET /meta/site`
- Auth: ไม่ต้องใช้ token
- คืนค่าแบรนด์หลักสำหรับหน้าเว็บ เช่น `logo`, `title`, `name`, `description`
- ฟิลด์ `logo` จะถูก normalize เป็น Full URL (absolute URL) อัตโนมัติ เมื่อในระบบเก็บเป็น path

Response ตัวอย่าง
```json
{
  "logo": "https://api.1168lot.com/storage/img/logo.png?v=1742970000000",
  "title": "1168LOT",
  "name": "1168LOT",
  "description": "เว็บตรง ฝากถอนออโต้",
  "success": true,
  "message": "ดึงข้อมูลเว็บไซต์สำเร็จ"
}
```

---

### 2) Member

#### 2.1 โปรไฟล์สมาชิก
- `GET /member/profile`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "profile": {
    "balance": "0.00",
    "point_deposit": "0.00",
    "diamond": "0.00",
    "balance_free": "0.00",
    "user_name": "9000000011",
    "name": "API Test User",
    "acc_no": "1234567890",
    "tel": "0900000011",
    "phone": "0900000011",
    "bank_name": "กสิกรไทย",
    "bank_image": "/storage/bank_img/kbank.png",
    "bank_image_url": "https://api.1168lot.com/storage/bank_img/kbank.png"
  },
  "system": {
    "point": false,
    "diamond": false,
    "notice": null
  },
  "success": true,
  "message": "complete"
}
```

#### 2.2 ยอดเงินคงเหลือ
- `GET /member/balance`
- Auth: ต้องใช้ token
- response แบบย่อ (เทียบเท่า `HomeController@loadCreditMin`)

Response ตัวอย่าง (ย่อ)
```json
{
  "profile": {
    "user_name": "9000000011",
    "balance": "0.00",
    "balance_free": "0.00",
    "point_deposit": "0.00",
    "diamond": "0.00",
    "credit": "0.00"
  },
  "system": {
    "point": false,
    "diamond": false,
    "notice": null,
    "multi": false
  },
  "success": true,
  "message": "complete"
}
```

#### 2.3 ยอดเงินคงเหลือ (compat route)
- `GET /member/loadbalance`
- Auth: ต้องใช้ token
- response เต็ม เท่ากับ route `customer.home.credit` (`HomeController@loadCredit`)

#### 2.4 เปลี่ยนรหัสผ่าน
- `POST /member/change-password`
- Auth: ต้องใช้ token
- ไม่ต้องส่งรหัสผ่านเดิม

Request body
```json
{
  "password": "654321",
  "password_confirmation": "654321"
}
```

หมายเหตุ:
- รองรับ `password_confirm` เป็น alias ของ `password_confirmation`
- `password` ต้องยาว `6-10` ตัวอักษร

Response ตัวอย่าง
```json
{
  "success": true,
  "member_code": 1,
  "message": "เปลี่ยนรหัสผ่านสำเร็จ"
}
```

Response ตัวอย่างเมื่อ validation ไม่ผ่าน
```json
{
  "success": false,
  "message": "The password confirmation and password must match."
}
```

#### 2.5 ข้อมูลแนะนำเพื่อน
- `GET /member/contributor`
- Auth: ต้องใช้ token

โครง response จริง
- `summary`
  - `referred_members` (`integer`) จำนวนสมาชิกที่อยู่ใต้ `upline_code` และ `enable = Y`
  - `referral_code` (`string`) รหัสแนะนำสมาชิก (ถ้ายังไม่มีจะเป็นค่าว่าง)
  - `referral_income` (`number`) รายได้แนะนำสะสมจาก `members.faststart`
  - `promotion_bonus_income` (`number`) ยอดโบนัสแนะนำรวมจาก `payments_promotion.credit_bonus`
  - `promotion_bonus_count` (`integer`) จำนวนรายการโบนัสแนะนำจาก `payments_promotion`
- `rule`
  - `promotion_id` (`string`) ค่าคงที่ `pro_faststart`
  - `length_type` (`string|null`) เช่น `PERCENT` หรือ `PRICE`
  - `bonus_percent` (`number|null`)
  - `bonus_price` (`number|null`)
  - `display_value` (`string|null`) ค่าที่พร้อมแสดงผล เช่น `1.50 %` หรือ `50.00`
  - `more_message` (`string|null`) ข้อความอธิบายจาก `app.con.more` โดย backend แทน `:field` ด้วย `display_value` แล้ว
- `referrals` (`array<object>`) รายชื่อสมาชิกที่แนะนำได้
  - `username` (`string`)
  - `name` (`string`)
  - `regis_date` (`string|null`, format `Y-m-d`)
  - `first_deposit_amount` (`number`) ยอดฝากแรก (ถ้ายังไม่เคยฝาก = `0`)
  - `first_deposit_date` (`string|null`, format `Y-m-d H:i:s`)

Response ตัวอย่าง
```json
{
  "summary": {
    "referred_members": 3,
    "referral_code": "AB12C3D4",
    "referral_income": 250.0,
    "promotion_bonus_income": 120.0,
    "promotion_bonus_count": 2
  },
  "rule": {
    "promotion_id": "pro_faststart",
    "length_type": "PERCENT",
    "bonus_percent": 1.5,
    "bonus_price": 0,
    "display_value": "1.50 %",
    "more_message": "ลิ้งค์ช่วยแชร์รับ 1.50 %  ฟรี (แค่ก๊อปปี้ลิ้งค์ไปแชร์ก็ได้เงินแล้ว) ยิ่งแชร์มากยิ่งได้มากท่านสามารถนำลิ้งค์ด้านล่างนี้หรือนำไปแชร์ในช่องทางต่างๆ ไม่ว่าจะเป็น เว็บไชต์ส่วนตัว, Blog, Facebook หรือ Social Network อื่นๆหากมีการสมัครสมาชิกโดยคลิกผ่านลิ้งค์ของท่านเข้ามา ลูกค้าที่สมัครเข้ามาก็จะอยู่ภายใต้การแนะนำของท่านทันที และหากลูกค้าภายใต้การแนะนำของท่านมีการเติมเงินเข้ามาครั้งแรก ท่านจะได้รับส่วนแบ่งในการแนะนำ 1.50 %  ทันทีโดยไม่มีเงื่อนไข"
  },
  "referrals": [
    {
      "username": "0900000012",
      "name": "Ref User One",
      "regis_date": "2026-04-01",
      "first_deposit_amount": 300.0,
      "first_deposit_date": "2026-04-01 12:33:21"
    }
  ],
  "success": true,
  "message": "complete"
}
```

HTTP status
- สำเร็จ: `200`
- token ไม่ถูกต้อง/หมดอายุ: `401`
- เกิดข้อผิดพลาดภายใน: `422`

#### 2.6 คูปองของสมาชิก
- `POST /coupon/redeem`
- Auth: ต้องใช้ token

Request body
```json
{
  "coupon": "ABC123"
}
```

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "รับคูปองสำเร็จ",
  "item": {
    "code": "BONUS001",
    "name": "โบนัสต้อนรับ",
    "status": "pending_claim",
    "status_label": "รอรับโบนัส",
    "type": "credit",
    "type_label": "เครดิต",
    "value": 150,
    "turnpro": 1,
    "amount_limit": 2,
    "rate": "",
    "date_expire": null,
    "can_claim": true
  }
}
```

- `GET /coupon/my`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "ดึงรายการคูปองสำเร็จ",
  "items": [
    {
      "code": "BONUS001",
      "name": "โบนัสต้อนรับ",
      "status": "pending_claim",
      "status_label": "รอรับโบนัส",
      "type": "credit",
      "type_label": "เครดิต",
      "value": 150,
      "turnpro": 1,
      "amount_limit": 2,
      "rate": "",
      "date_expire": null,
      "can_claim": true
    }
  ],
  "summary": {
    "count": 1
  }
}
```

- `POST /coupon/my/{code}/claim`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "รับโบนัสจากคูปองสำเร็จ",
  "item": {
    "code": "BONUS001",
    "name": "โบนัสต้อนรับ",
    "status": "claimed",
    "status_label": "รับโบนัสแล้ว",
    "type": "credit",
    "type_label": "เครดิต",
    "amount": 150,
    "turnpro": 1,
    "amount_limit": 2,
    "balance_after": 150
  }
}
```

HTTP status
- สำเร็จ: `200`
- token ไม่ถูกต้อง/หมดอายุ: `401`
- คูปองไม่ถูกต้อง/หมดอายุ/ผิดเงื่อนไข/รายการรับไม่ได้: `422`

#### 2.7 ประวัติธุรกรรมสมาชิก (อ้างอิงหน้า `/member/history`)
- `GET /member/history`
- `GET /member/history/{type}`
- Auth: ต้องใช้ token

Query params
- `type` (ใช้กับ `/member/history`): ค่าเดียวกับ path `{type}`
- `date_start` (optional): วันที่เริ่ม filter
- `date_stop` (optional): วันที่สิ้นสุด filter

ประเภทที่รองรับ (`type`)
- `deposit` ฝาก
- `withdraw` ถอน
- `transfer` โยกเงิน wallet/game
- `spin` วงล้อ
- `money` โอนเงินสมาชิก
- `cashback` คืนยอดเสีย
- `memberic` ค่าเสียเพื่อน
- `bonus` โบนัส
- `other` รายการปรับยอด (`ROLLBACK`, `SETWALLET`)

Response ตัวอย่าง
```json
{
  "type": "deposit",
  "date_start": "2026-04-01",
  "date_stop": "2026-04-03",
  "items": [
    {
      "id": "#DP00001234",
      "date_create": "03/04/2026 14:20",
      "amount": 100.0,
      "amount_request": 100.0,
      "pro_name": null,
      "credit_bonus": 0,
      "credit_before": 1000.0,
      "credit_after": 1100.0,
      "status": "Y",
      "image": "ic_success",
      "transfer_type": "+",
      "method": "เติมเงิน",
      "status_color": "bg-success",
      "status_display": "สำเร็จ"
    }
  ],
  "success": true,
  "message": "complete"
}
```

---

### 3) Wallet

#### 3.1 ประวัติการเงินรวม
- `GET /wallet/transactions`
- Auth: ต้องใช้ token

Query params
- `type` (optional, default=`all`)
- `date_start` (optional): วันที่เริ่ม filter
- `date_stop` (optional): วันที่สิ้นสุด filter
- `page` (optional, default=`1`)
- `limit` (optional, default=`20`, max=`100`)

ประเภทที่รองรับ (`type`)
- `all`
- `deposit`
- `withdraw`
- `lotto_bet`
- `lotto_refund`
- `referral`
- `cashback`
- `ic`
- `bonus`
- `game`
- `admin_adjust`
- `rollback`
- `other`

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "filters": {
      "type": "all",
      "date_start": "2026-04-01",
      "date_stop": "2026-04-05"
    },
    "summary": {
      "count": 3,
      "total_credit_amount": 550,
      "total_debit_amount": 100,
      "net_amount": 450
    },
    "items": [
      {
        "id": 9001,
        "created_at": "2026-04-05 14:00:00",
        "type": "lotto_refund",
        "type_label": "คืนเงินหวย",
        "ref_type": "LOTTO_CANCEL",
        "direction": "CREDIT",
        "direction_label": "รับเข้า",
        "amount": 50,
        "signed_amount": 50,
        "balance_before": 1350,
        "balance_after": 1400,
        "status": "SUCCESS",
        "title": "คืนเงินหวย",
        "detail": "คืนเงินโพยหวยเข้ากระเป๋าหลัก: หวยรัฐบาล งวดวันที่ 2026-04-05",
        "description": "คืนเงินจากการยกเลิกโพยหวย",
        "ref_id": 1001,
        "ref_code": "1001",
        "group_code": "LOTTO_CANCEL_1001",
        "lotto": {
          "ticket_id": 1001,
          "market_name": "หวยรัฐบาล",
          "draw_date": "2026-04-05"
        }
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "count": 1,
      "total": 3,
      "has_more": false
    },
    "language": "th"
  },
  "message": "ดึงประวัติการเงินสำเร็จ"
}
```

#### 3.2 ส่งคำขอถอนเงิน
- `POST /wallet/withdraw`
- Auth: ต้องใช้ token

Request body
```json
{
  "amount": 100
}
```

Response ตัวอย่าง (ยอดไม่พอ)
```json
{
  "success": false,
  "message": "พบข้อผิดพลาด ยอดที่แจ้งถอนมา มากกว่ายอดที่มีอยู่"
}
```

หมายเหตุสำคัญ:
- endpoint นี้ใช้ flow `withdrawRepository->withdrawSeamless` สำหรับเส้น frontend นี้

---

### 4) Game

#### 4.1 ประเภทเกม
- `GET /games/types`
- Auth: ไม่ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "data": [
    { "id": "slot", "name": "", "status_open": "Y" },
    { "id": "casino", "name": "", "status_open": "Y" }
  ],
  "message": "ดึงประเภทเกมสำเร็จ"
}
```

#### 4.2 รายการค่ายเกมตามประเภท
- `GET /games/providers/{type}`
- Auth: ไม่ต้องใช้ token

Path param
- `type`: เช่น `slot`, `casino`, `sport`

Response ตัวอย่าง (ย่อ)
```json
{
  "success": true,
  "data": [
    {
      "provider": "PGSOFT",
      "providerName": "PG Soft",
      "providerType": "SLOT",
      "status": "ACTIVE"
    }
  ],
  "message": "ดึงรายการค่ายเกมสำเร็จ"
}
```

#### 4.3 รายการเกม
- `GET /games/{type}/{provider}`
- Auth: ไม่ต้องใช้ token
- ก่อนอ่านข้อมูลจาก `GameListProxy` ระบบจะ trigger `gamelist` ของ provider ก่อนทุกครั้งเพื่อ refresh/sync ข้อมูลล่าสุด

Path param
- `type`: เช่น `slot`
- `provider`: เช่น `PGSOFT`

Response ตัวอย่าง (ย่อ)
```json
{
  "success": true,
  "data": [
    {
      "id": "treasures-aztec",
      "provider": "PGSOFT",
      "gameName": "Treasures of Aztec",
      "gameCategory": "seamless",
      "gameType": ["SLOT"],
      "loginURL": "https://api.1168lot.com/api/v1/games/login/PGSOFT/treasures-aztec",
      "status": "ACTIVE"
    }
  ],
  "message": "ดึงรายการเกมสำเร็จ"
}
```

#### 4.4 Login เกม
- `POST /games/login`
- Auth: ต้องใช้ token

Request body
```json
{
  "id": "PGSOFT",
  "game": "treasures-aztec"
}
```

Response ตัวอย่างสำเร็จ
```json
{
  "success": true,
  "data": {
    "url": "https://game-provider.example/launch?token=....",
    "provider": "PGSOFT",
    "code": "treasures-aztec"
  },
  "message": "เข้าสู่เกมสำเร็จ"
}
```

#### 4.5 Login เกม (Path Parameter)
- `GET /games/login/{game}/{code}`
- Auth: ต้องใช้ token

Path param
- `game`: รหัสค่ายเกม เช่น `PGSOFT`
- `code`: รหัสเกม เช่น `treasures-aztec`

ตัวอย่าง
- `GET /games/login/PGSOFT/treasures-aztec`

Response ตัวอย่างกรณีเข้าเกมไม่ได้
```json
{
  "success": false,
  "message": "ไม่สามารถเข้าสู่เกมได้ในขณะนี้"
}
```

---

### 5) Lotto

#### 5.1 รายการงวด
- `GET /lotto/draws`
- Auth: ไม่ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response
- policy:
  - คืนงวดล่าสุดต่อรายการหวย
  - ข้ามงวดที่ `status = draft`
  - ถ้า market เดียวกันมีงวด `draft` ใหม่กว่า แต่ยังมี `open/closed/resulted` อยู่ ระบบจะคืน non-draft ล่าสุดแทน

Response ตัวอย่าง
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "market_id": 3,
      "market_name": "Government Savings Bank",
      "market_logo": "/storage/lotto/markets/gsb-logo.png",
      "market_icon": "/storage/lotto/markets/gsb-icon.png",
      "group_name": "Thai Lotto",
      "draw_date": "2026-03-24",
      "open_at": "2026-03-24 09:00:00",
      "close_at": "2026-03-24 15:30:00",
      "status": "open"
    }
  ],
  "language": "en",
  "message": "ดึงรายการงวดสำเร็จ"
}
```

#### 5.1.1 รายการหวยพร้อมงวดล่าสุด
- `GET /lotto/markets/latest`
- Auth: ไม่ต้องใช้ token
- policy:
  - ฟิลด์ `latest_draw` ของแต่ละ market จะเลือกตามลำดับ:
    - `open` ล่าสุด
    - ถ้าไม่มี `open` ค่อยใช้ non-draft ล่าสุด
  - ห้ามคืนงวด `draft`
  - สถานะที่ frontend จะได้รับ:
    - `open` / `status_label = แทงหวย`
    - `closed` / `status_label = รอผล`
    - `resulted` / `status_label = ออกผล`
    - `no_result` / `status_label = ยกเลิก`
    - `refunded` / `status_label = ยกเลิก`
  - ถ้า draw ถูก mark `no_result` และยังไม่ได้คืนเงินทั้งงวด จะได้ `status = no_result`
  - ถ้า draw มี `manual_cancelled_all_tickets=true` จะได้ `status = refunded`

#### 5.2 รายละเอียดงวด
- `GET /lotto/draws/{id}`
- Auth: ไม่ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "id": 1,
    "market": {
      "id": 1,
      "name": "ออมสิน",
      "group_id": 1,
      "logo": "/storage/lotto/markets/gsb-logo.png",
      "icon": "/storage/lotto/markets/gsb-icon.png",
      "group_name": "หวยไทย"
    },
    "draw_date": "2026-04-01",
    "open_at": "2026-03-22 15:55:00",
    "close_at": "2026-03-31 15:55:00",
    "status": "draft",
    "result_number": null,
    "bet_settings": []
  },
  "language": "th",
  "message": "ดึงรายละเอียดงวดสำเร็จ"
}
```

#### 5.3 ส่งโพย
- `POST /lotto/bet`
- Auth: ต้องใช้ token

Request body (ตัวอย่างเบื้องต้น)
```json
{
  "draw_id": 1,
  "package_id": 9,
  "items": [
    {
      "bet_type": "top_3",
      "number": "456",
      "amount": 10
    }
  ]
}
```

หมายเหตุ `bet_type` ที่รองรับ: `top_3`, `tod_3`, `top_2`, `bottom_2`, `run_top`, `run_bottom`
หมายเหตุ: `package_id` เป็น field บังคับ

Error code ที่เกี่ยวข้องกับ package:
- `PACKAGE_REQUIRED` -> HTTP `400`
- `PACKAGE_NOT_IN_GROUP` -> HTTP `400`
- `PACKAGE_INACTIVE` -> HTTP `409`
- `BET_TYPE_NOT_CONFIGURED` -> HTTP `422`

Response จะขึ้นกับกติกาและสถานะงวด ณ ขณะนั้น (ผ่านบริการ Lotto เดิม)

#### 5.4 รายการโพยของสมาชิก
- `GET /lotto/tickets`
- Auth: ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response
- endpoint นี้คืน `status` แบบพร้อมใช้บน UI:
  - `won` เมื่อ `result_outcome=won` และ `is_winner=true`
  - `lose` เมื่อ `result_outcome=lose` และ `is_winner=false`
  - กรณีอื่นคงค่า lifecycle เดิม เช่น `active`, `cancelled`
- และคืน field สรุปผลที่พร้อมใช้บน UI:
  - `draw_status`, `draw_status_label`
  - `result_outcome`, `result_outcome_label`, `result_message`
  - `is_final`, `is_winner`
  - `item_count`, `winning_item_count`, `losing_item_count`, `pending_item_count`
  - cancel context ระดับโพย:
    - `cancelled_at`
    - `cancelled_by_name`
    - `cancelled_by_type`
    - `cancel_reason`

Response ตัวอย่าง
```json
{
  "success": true,
  "data": [
    {
      "id": 1101,
      "draw_id": 12,
      "draw_date": "2026-03-24",
      "market_name": "ออมสิน",
      "market_logo": "/storage/lotto/markets/gsb-logo.png",
      "market_icon": "/storage/lotto/markets/gsb-icon.png",
      "group_name": "หวยไทย",
      "status": "won",
      "status_label": "ถูกรางวัล",
      "draw_status": "resulted",
      "draw_status_label": "ออกผลแล้ว",
      "result_outcome": "won",
      "result_outcome_label": "ถูกรางวัล",
      "result_message": "โพยนี้ถูกรางวัล 540.00 บาท",
      "is_final": true,
      "is_winner": true,
      "total_amount": 90,
      "total_bet_amount": 100,
      "total_discount_amount": 10,
      "total_net_amount": 90,
      "total_win_amount": 540,
      "refund_amount": 0,
      "cancelled_at": null,
      "cancelled_by_name": "",
      "cancelled_by_type": "",
      "cancel_reason": "",
      "item_count": 2,
      "winning_item_count": 1,
      "losing_item_count": 1,
      "pending_item_count": 0,
      "draw_result_at": "2026-03-24 16:00:00",
      "created_at": "2026-03-24 12:00:00"
    }
  ],
  "language": "th",
  "message": "ดึงประวัติโพยสำเร็จ"
}
```

#### 5.5 รายละเอียดโพย
- `GET /lotto/tickets/{id}`
- Auth: ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response
- endpoint นี้คง field summary แบบเดียวกับรายการโพย และเพิ่ม field ระดับรายการใน `items[]`:
  - `result_status` (`win` / `lose` / `pending`)
  - `raw_result_status` (ค่าเดิมจากระบบภายในก่อน normalize)
  - `is_winner`
  - `result_status_label`
  - `result_message`
  - summary ของ detail จะมี cancel context เดียวกับ list:
    - `cancelled_at`
    - `cancelled_by_name`
    - `cancelled_by_type`
    - `cancel_reason`

Response ตัวอย่างเมื่อไม่พบโพย
```json
{
  "success": false,
  "message": "ไม่พบโพยที่ระบุ"
}
```

Response ตัวอย่าง (พบข้อมูล)
```json
{
  "success": true,
  "data": {
    "id": 1101,
    "draw_id": 12,
    "draw_date": "2026-03-24",
    "market_name": "ออมสิน",
    "market_logo": "/storage/lotto/markets/gsb-logo.png",
    "market_icon": "/storage/lotto/markets/gsb-icon.png",
    "group_name": "หวยไทย",
    "status": "won",
    "status_label": "ถูกรางวัล",
    "draw_status": "resulted",
    "draw_status_label": "ออกผลแล้ว",
    "result_outcome": "won",
    "result_outcome_label": "ถูกรางวัล",
    "result_message": "โพยนี้ถูกรางวัล 540.00 บาท",
    "is_final": true,
    "is_winner": true,
    "total_amount": 90,
    "total_bet_amount": 100,
    "total_discount_amount": 10,
    "total_net_amount": 90,
    "total_win_amount": 540,
    "refund_amount": 0,
    "cancelled_at": null,
    "cancelled_by_name": "",
    "cancelled_by_type": "",
    "cancel_reason": "",
    "item_count": 2,
    "winning_item_count": 1,
    "losing_item_count": 1,
    "pending_item_count": 0,
    "draw_result_at": "2026-03-24 16:00:00",
    "created_at": "2026-03-24 12:00:00",
    "items": [
      {
        "bet_type": "top_3",
        "bet_type_label": "3 ตัวบน",
        "number": "450",
        "amount": 50,
        "payout_at_time": 9,
        "discount_percent_at_time": 10,
        "discount_amount_at_time": 5,
        "payable_amount_at_time": 45,
        "potential_win_amount_at_time": 450,
        "result_status": "win",
        "raw_result_status": "win",
        "result_status_label": "ถูกรางวัล",
        "result_message": "รายการนี้ถูกรางวัล 450.00 บาท",
        "is_winner": true,
        "win_amount": 450
      },
      {
        "bet_type": "run_bottom",
        "bet_type_label": "วิ่งล่าง",
        "number": "5",
        "amount": 10,
        "payout_at_time": 9,
        "discount_percent_at_time": 0,
        "discount_amount_at_time": 0,
        "payable_amount_at_time": 10,
        "potential_win_amount_at_time": 90,
        "result_status": "lose",
        "raw_result_status": "lose",
        "result_status_label": "ไม่ถูกรางวัล",
        "result_message": "รายการนี้ไม่ถูกรางวัล",
        "is_winner": false,
        "win_amount": 0
      }
    ]
  },
  "language": "th",
  "message": "ดึงรายละเอียดโพยสำเร็จ"
}
```

#### 5.6 ยกเลิกโพย
- `POST /lotto/tickets/{id}/cancel`
- Auth: ต้องใช้ token
- เงื่อนไขการยกเลิก:
  - ต้องเป็นโพยของสมาชิกคนนั้น
  - ticket ต้องมีสถานะ `active`
  - draw ต้องยัง `open`
  - ต้องยกเลิกก่อนเวลาปิดรับอย่างน้อย `10` นาที
  - สมาชิกยกเลิกได้ไม่เกินวันละ `4` ครั้ง

Response ตัวอย่างเมื่อผิดเงื่อนไข daily limit
```json
{
  "success": false,
  "message": "สมาชิกยกเลิกโพยได้ไม่เกินวันละ 4 ครั้ง"
}
```

Response ตัวอย่างเมื่อเกินช่วงเวลาที่ยกเลิกได้
```json
{
  "success": false,
  "message": "ยกเลิกโพยได้ก่อนเวลาปิดรับอย่างน้อย 10 นาทีเท่านั้น"
}
```

Response ตัวอย่างเมื่อไม่พบโพย
```json
{
  "success": false,
  "message": "ไม่พบโพยที่ระบุ"
}
```

#### 5.7 รายการหวยตามกลุ่ม พร้อมงวดล่าสุด
- `GET /lotto/markets/latest`
- Auth: ไม่ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response
- ฟิลด์รูปใน response (`group_logo`, `group_icon`, `group_image`, `market_logo`, `market_icon`) จะถูกแปลงเป็น **Full URL (absolute URL)** เช่น `https://api.1168lot.com/storage/...`

Query ที่รองรับ:
- `code` (แนะนำ) เช่น `thai` เพื่อขอเฉพาะกลุ่มนั้น
- `group_code` (เทียบเท่า `code`)
- `group_id`
- `group_name` หรือ `group`

ตัวอย่าง: ขอเฉพาะกลุ่มหวยไทยด้วย code
`GET /lotto/markets/latest?code=thai&lang=th`

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "language": "th",
    "filters": {
      "group_id": null,
      "group_code": "thai",
      "group_name": null
    },
    "groups": [
      {
        "group_id": 1,
        "group_code": "thai",
        "group_name": "หวยไทย",
        "description": "หวยรัฐบาลไทย อัปเดตผลไว",
        "group_logo": "/storage/lotto/groups/thai-logo.png",
        "group_icon": "/storage/lotto/groups/thai-icon.png",
        "group_image": "/storage/lotto/groups/thai-logo.png",
        "markets": [
          {
            "market_id": 3,
            "market_name": "ออมสิน",
            "market_logo": "/storage/lotto/markets/gsb-logo.png",
            "market_icon": "/storage/lotto/markets/gsb-icon.png",
            "is_enabled": true,
            "latest_draw": {
              "draw_id": 120,
              "draw_date": "2026-03-24",
              "open_at": "2026-03-24 09:00:00",
              "close_at": "2026-03-24 15:30:00",
              "result_at": "2026-03-24 16:00:00",
              "status": "open",
              "status_label": "เปิดรับแทง",
              "is_open_bet": true,
              "result_top_3": "123",
              "result_bottom_2": "45"
            }
          }
        ]
      }
    ]
  },
  "message": "ดึงรายการหวยพร้อมงวดล่าสุดสำเร็จ"
}
```

#### 5.7.1 ผลรางวัลทั้งหมดตามวันที่ (จัดกลุ่มตามกลุ่มหวย)
- `GET /lotto/results/by-date?draw_date=2026-04-03`
- Auth: ไม่ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response
- `draw_date` เป็นค่าบังคับ รูปแบบ `YYYY-MM-DD`
- ระบบจะแสดงเฉพาะรายการหวยที่ “มีงวดในวันที่เลือก และสถานะงวด = resulted”

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "draw_date": "2026-04-03",
    "groups": [
      {
        "group_id": 1,
        "group_code": "thai",
        "group_name": "หวยไทย",
        "markets": [
          {
            "market_id": 3,
            "market_name": "ออมสิน",
            "market_logo": "/storage/lotto/markets/gsb-logo.png",
            "market_icon": "/storage/lotto/markets/gsb-icon.png",
            "result": {
              "draw_id": 120,
              "draw_date": "2026-04-03",
              "result_at": "2026-04-03 16:00:00",
              "status": "resulted",
              "result_top_3": "123",
              "result_bottom_2": "45",
              "first_prize": "12345"
            }
          }
        ]
      }
    ],
    "summary": {
      "group_count": 1,
      "market_count": 1,
      "result_count": 1
    },
    "language": "th"
  },
  "message": "ดึงผลรางวัลตามวันที่สำเร็จ"
}
```

#### 5.8 รายการ Package ตามกลุ่มหวย
- `GET /lotto/groups/{groupId}/packages`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "data": [
    {
      "id": 9,
      "group_id": 1,
      "name": "แพ็กเกจมาตรฐาน",
      "image": "/storage/lotto/media/package-standard.png",
      "is_active": true,
      "bet_settings": [
        {
          "bet_type": "top_3",
          "payout": 900,
          "discount_percent": 0
        }
      ]
    }
  ],
  "message": "ดึง package สำเร็จ"
}
```

#### 5.9 เลือก Package สำหรับ flow หน้าแทง (Helper State)
- `POST /lotto/groups/{groupId}/select-package`
- Auth: ต้องใช้ token

Request body
```json
{
  "package_id": 9
}
```

Response ตัวอย่าง (success)
```json
{
  "success": true,
  "data": {
    "group_id": 1,
    "package_id": 9,
    "selected": true
  },
  "message": "เลือก package สำเร็จ"
}
```

Response ตัวอย่าง (ผิด group)
```json
{
  "success": false,
  "message": "package ไม่อยู่ใน group เดียวกัน",
  "data": {
    "error_code": "PACKAGE_NOT_IN_GROUP"
  }
}
```

หมายเหตุ:
- endpoint นี้เป็น helper state สำหรับ UI เท่านั้น
- submit bet จริง ระบบจะยึด `package_id` ใน `POST /lotto/bet` เท่านั้น
- ถ้าเลือก package เดิมซ้ำ ผลลัพธ์ต้อง idempotent (HTTP `200`)

#### 5.10 ดู Package ที่เลือกไว้ล่าสุด (Helper State)
- `GET /lotto/groups/{groupId}/selected-package`
- Auth: ต้องใช้ token

Response ตัวอย่าง (ยังไม่เลือก)
```json
{
  "success": true,
  "data": null,
  "selected": false,
  "message": "ยังไม่ได้เลือก package"
}
```

Response ตัวอย่าง (เลือกแล้ว)
```json
{
  "success": true,
  "data": {
    "group_id": 1,
    "package_id": 9,
    "name": "แพ็กเกจมาตรฐาน",
    "image": "/storage/lotto/media/package-standard.png",
    "bet_settings": [
      {
        "bet_type": "top_3",
        "payout": 650,
        "discount_percent": 27
      },
      {
        "bet_type": "bottom_2",
        "payout": 69,
        "discount_percent": 27
      }
    ]
  },
  "selected": true,
  "message": "ดึงสถานะ package ที่เลือกสำเร็จ"
}
```

---

### 6) Deposit

#### 6.1 ดึงสถานะช่องทางเติมเงิน
- `GET /deposit/channels`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "deposit": {
      "bank": 1,
      "payment": 0,
      "tw": 0,
      "slip": 0,
      "sort": {
        "payment": null,
        "tw": null,
        "slip": null,
        "bank": 1
      }
    }
  },
  "message": "ดึงช่องทางเติมเงินสำเร็จ"
}
```

หมายเหตุ:
- ค่า `1` = ช่องทางเปิดใช้งาน
- ค่า `0` = ช่องทางปิดใช้งาน

#### 6.2 โหลดบัญชี/ช่องทางตาม method
- `POST /deposit/loadbank`
- Auth: ต้องใช้ token

Request body
```json
{
  "method": "bank"
}
```

`method` ที่รองรับ:
- `bank`
- `payment`
- `tw`
- `slip`

Response ตัวอย่าง
```json
{
  "success": true,
  "bank": []
}
```

---

### 7) Wheel

#### 7.1 ดึงข้อมูลวงล้อ
- `GET /wheel/list`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "wheel": [],
    "enabled": true
  },
  "message": "ดึงข้อมูลวงล้อสำเร็จ"
}
```

#### 7.2 หมุนวงล้อ
- `POST /wheel/spin`
- Auth: ต้องใช้ token

Response ตัวอย่าง (สำเร็จ)
```json
{
  "success": true,
  "message": "complete"
}
```

#### 7.3 ประวัติวงล้อ
- `GET /wheel/history`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "data": {
    "history": []
  },
  "message": "ดึงประวัติวงล้อสำเร็จ"
}
```

---

### 8) Promotion

#### 8.1 ดึงรายการโปรโมชัน
- `GET /promotion/list`
- Auth: ต้องใช้ token

Response ตัวอย่าง (ย่อ)
```json
{
  "success": true,
  "data": {
    "promotions": [],
    "getpro": false
  },
  "message": "Complete"
}
```

#### 8.2 เลือกโปรโมชัน
- `POST /promotion/select`
- Auth: ต้องใช้ token

Request body
```json
{
  "promotion": "P001"
}
```

Response ตัวอย่าง (สำเร็จ)
```json
{
  "success": true,
  "data": {
    "promotion": "P001"
  },
  "message": "ผ่านเงื่อนไขการรับโปรโมชัน"
}
```

Response ตัวอย่าง (ไม่ผ่านเงื่อนไข)
```json
{
  "success": false,
  "message": "ไม่สามารถรับโปรโมชั่นนี้ได้"
}
```

#### 8.3 ยกเลิกโปรโมชันที่เลือกไว้
- `POST /promotion/deselect`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "ยกเลิกโปรโมชันที่เลือกไว้แล้ว"
}
```

---

### 9) Realtime (อัปเดตข้อมูลแบบทันที)

หัวข้อนี้สำหรับทีม Next.js เพื่อให้เห็นข้อมูลใหม่ทันที เช่น
- มีการเติมเงินสำเร็จ -> โชว์ toast + อัปเดตยอดเงิน
- หวยปิดรับ/ออกผล -> แจ้งเตือนและรีเฟรชหน้าที่เกี่ยวข้อง

#### 9.1 ดึง realtime config
- `GET /realtime/config`
- Auth: ไม่ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "ดึงข้อมูล realtime config สำเร็จ",
  "realtime": {
    "broadcaster": "pusher",
    "key": "app-key",
    "ws_host": "api.example.com",
    "ws_port": 6001,
    "ws_path": "",
    "ws_scheme": "http",
    "force_tls": false,
    "shared_member_channel": "APP_members",
    "private_channel_member_template": "APP_members.{member_code}",
    "events": [
      "public.activity.updated",
      "member.activity.updated",
      "member.balance.updated"
    ]
  }
}
```

#### 9.2 ดึง channel ส่วนตัวของสมาชิก
- `GET /member/realtime-context`
- Auth: ต้องใช้ token

Response ตัวอย่าง
```json
{
  "success": true,
  "message": "ดึง realtime member context สำเร็จ",
  "member_code": 10001,
  "private_channel": "APP_members.10001"
}
```

#### 9.3 Auth สำหรับ Private Channel (ใช้ Bearer token)
- `POST /realtime/auth`
- Auth: ต้องใช้ token
- ใช้ endpoint นี้เป็น `authEndpoint` ของ Laravel Echo สำหรับ private channel

Request body ตัวอย่าง
```json
{
  "socket_id": "1234.5678",
  "channel_name": "private-APP_members"
}
```

Response ตัวอย่าง (สำเร็จ)
```json
{
  "auth": "app-key:signature"
}
```

Response ตัวอย่าง (ไม่มีสิทธิ์)
```json
{
  "success": false,
  "message": "ไม่มีสิทธิ์เข้าถึง channel นี้"
}
```

#### 9.4 Heartbeat + Online members
- `POST /member/heartbeat` (Auth: ต้องใช้ token)  
  แนะนำยิงทุก `10-20` วินาทีขณะ user online
- `GET /meta/online-members` (Auth: ไม่ต้องใช้ token)  
  ใช้แสดงจำนวนสมาชิกออนไลน์ล่าสุด
- `GET /meta/contact-channels` (Auth: ไม่ต้องใช้ token)  
  ใช้ดึงข้อมูลช่องทางติดต่อจากตาราง `contact_channels`
- `GET /meta/site` (Auth: ไม่ต้องใช้ token)  
  ใช้ดึงข้อมูลแบรนด์เว็บไซต์สำหรับ frontend (`logo`, `title`, `name`, `description`)

Response ตัวอย่าง heartbeat
```json
{
  "success": true,
  "message": "อัปเดตสถานะออนไลน์สำเร็จ",
  "heartbeat": "ok",
  "online": 123
}
```

Response ตัวอย่าง contact channels
```json
{
  "success": true,
  "message": "ดึงข้อมูลช่องทางติดต่อสำเร็จ",
  "data": {
    "contact_channels": [
      {
        "code": 1,
        "type": "line",
        "label": "@brand",
        "link": "https://lin.ee/xxxx",
        "sort": 1
      }
    ]
  }
}
```

Response ตัวอย่าง meta site
```json
{
  "success": true,
  "message": "ดึงข้อมูลเว็บไซต์สำเร็จ",
  "logo": "https://api.example.com/storage/img/logo.png?v=1743012345000",
  "title": "Brand Title",
  "name": "Brand Name",
  "description": "Brand description"
}
```

#### 9.5 Event ที่ฝั่ง Next.js ควร listen

รูปแบบกลางที่แนะนำ (เส้นเดียวสำหรับฝั่งสมาชิก):
- event name: `member.activity.updated`
- ใช้ key `method` เพื่อแยกประเภท:
  - `deposit`
  - `withdraw`
  - `rollback`
  - `lotto` (กรณีที่ต้องการส่งเข้า private ในอนาคต)

Shared member channel:
- ชื่อ channel: `{APP_NAME}_members`
- ประเภท: private channel
- events:
  - `public.activity.updated` (แนะนำใช้ตัวนี้เป็นหลักสำหรับ shared feed ของสมาชิก)

Private channel (ของสมาชิกคนนั้น):
- ชื่อ channel: `{APP_NAME}_members.{member_code}`
- events:
  - `member.activity.updated` (แนะนำใช้ตัวนี้เป็นหลักสำหรับ wallet update)
  - `member.balance.updated`

Payload ตัวอย่าง `member.activity.updated`
```json
{
  "method": "deposit",
  "event": "wallet.deposit_approved",
  "member_code": 10001,
  "occurred_at": "2026-03-24 23:10:05",
  "data": {
    "amount": 100,
    "balance": 5230,
    "reference_code": 889912,
    "reason": "deposit_approved"
  }
}
```

Payload ตัวอย่าง `public.activity.updated`
```json
{
  "method": "lotto",
  "event": "lotto.draw_resulted",
  "occurred_at": "2026-03-24 23:20:10",
  "data": {
    "draw_id": 120,
    "market_name": "ออมสิน",
    "status": "resulted",
    "status_label": "ออกผล"
  }
}
```

Payload ตัวอย่าง `member.balance.updated` (legacy compatibility)
```json
{
  "member_code": 10001,
  "balance": 5230,
  "amount": 100,
  "reason": "deposit_approved",
  "reference_code": 889912,
  "occurred_at": "2026-03-24 23:10:05",
  "message": "ยอดเงินของคุณถูกอัปเดต"
}
```

#### 9.6 ตัวอย่าง Next.js (Laravel Echo)

```ts
import Echo from "laravel-echo";
import Pusher from "pusher-js";

export function connectRealtime({
  token,
  memberCode,
  realtime,
  onToast,
  onBalanceUpdate,
  onLottoChanged
}: {
  token: string;
  memberCode: number;
  realtime: {
    key: string;
    ws_host: string;
    ws_port: number;
    force_tls: boolean;
    shared_member_channel: string;
  };
  onToast: (msg: string) => void;
  onBalanceUpdate: (balance: number) => void;
  onLottoChanged: (payload: unknown) => void;
}) {
  (globalThis as any).Pusher = Pusher;

  const echo = new Echo({
    broadcaster: "pusher",
    key: realtime.key,
    wsHost: realtime.ws_host,
    wsPort: realtime.ws_port,
    forceTLS: realtime.force_tls,
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_BASE_URL}/api/v1/realtime/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  });

  echo.private(realtime.shared_member_channel)
    .listen(".public.activity.updated", (e: any) => onLottoChanged(e));

  echo.private(`${process.env.NEXT_PUBLIC_APP_NAME}_members.${memberCode}`)
    .listen(".member.activity.updated", (e: any) => {
      if (e.method === "deposit") {
        onToast(`เติมเงินสำเร็จ +${e.data?.amount ?? 0} บาท`);
      } else if (e.method === "withdraw") {
        onToast(`ถอนเงินสำเร็จ -${e.data?.amount ?? 0} บาท`);
      } else if (e.method === "rollback") {
        onToast(`ระบบคืนยอดสำเร็จ +${e.data?.amount ?? 0} บาท`);
      }
      onBalanceUpdate(Number(e.data?.balance ?? 0));
    })
    .listen(".member.balance.updated", (e: any) => {
      onToast(`เติมเงินสำเร็จ +${e.amount} บาท`);
      onBalanceUpdate(Number(e.balance || 0));
    });

  return echo;
}
```

#### 9.7 แนวทางใช้งานที่แนะนำ (Production)
1. ตอน login สำเร็จ: เก็บ `access_token`
2. เรียก `GET /realtime/config` และ `GET /member/realtime-context`
3. เปิด Echo connection และ subscribe public + private channel
4. ยิง `POST /member/heartbeat` ทุก 10-20 วินาทีจนกว่าจะ logout/close tab
5. ตอนรับ event `member.activity.updated` หรือ `member.balance.updated` ให้
   - โชว์ toast ทันที
   - อัปเดต state/cache ยอดเงินทันที
   - เรียก `GET /member/balance` ซ้ำ 1 ครั้งเพื่อ reconcile

---

## 10) Frontend Lotto Critical Path (`/api/v1/lotto/markets/*`)

> ชุด endpoint นี้ออกแบบให้ frontend หน้าแทงเรียกได้ตรงและเร็ว โดยไม่ต้องประกอบข้อมูลหลายเส้นเอง

### 10.1 Betting Context
- `GET /lotto/markets/{marketId}/betting-context`
- Auth: ไม่ต้องใช้ token
- Query (optional):
  - `exposure_scope=blocked|all` (default `blocked`)

Response หลัก:
- `market` (ข้อมูลตลาดหวย)
- `draw` (current round)
- `blocked_numbers`
- `limits` (`min_bet`, `max_bet`, `max_per_number`, แยกตาม `bet_type`)
- `number_exposure`
- `version`
- `server_time`

### 10.2 ผลย้อนหลังตามตลาด
- `GET /lotto/markets/{marketId}/results?limit=20&page=1`
- Auth: ไม่ต้องใช้ token

Response หลัก:
- `latest_result`
- `history`
- `pagination` (`page`, `limit`, `count`, `total`, `has_more`)

### 10.3 ผลรางวัลงวดเฉพาะ
- `GET /lotto/markets/{marketId}/draws/{drawId}/result`
- Auth: ไม่ต้องใช้ token

Response หลัก:
- `result` ของงวดที่ระบุ

---

## สรุป endpoint ที่พร้อมใช้งาน (อัปเดต 2026-03-24)

ผ่านและตอบ JSON ถูกต้อง:
- `POST /auth/login`
- `POST /auth/logout`
- `GET /member/profile`
- `GET /member/balance`
- `POST /member/change-password`
- `POST /wallet/withdraw`
- `GET /games/types`
- `GET /games/providers/{type}`
- `GET /games/{type}/{provider}`
- `POST /games/login` (กรณีเข้าไม่ได้ ตอบ error JSON)
- `GET /games/login/{game}/{code}` (กรณีเข้าไม่ได้ ตอบ error JSON)
- `GET /lotto/draws`
- `GET /lotto/draws/{id}`
- `GET /lotto/markets/latest`
- `GET /lotto/groups/{groupId}/packages`
- `POST /lotto/groups/{groupId}/select-package`
- `GET /lotto/groups/{groupId}/selected-package`
- `GET /deposit/channels`
- `POST /deposit/loadbank`
- `GET /wheel/list`
- `POST /wheel/spin`
- `GET /wheel/history`
- `GET /promotion/list`
- `POST /promotion/select`
- `POST /promotion/deselect`
- `GET /realtime/config`
- `GET /member/realtime-context`
- `POST /realtime/auth`
- `POST /member/heartbeat`
- `GET /meta/online-members`
- `GET /lotto/markets/{marketId}/betting-context`
- `GET /lotto/markets/{marketId}/results`
- `GET /lotto/markets/{marketId}/draws/{drawId}/result`

ข้อจำกัดที่ยังพบใน environment ทดสอบ:
- `POST /auth/register` มีโอกาส timeout จาก dependency ภายในระบบเดิม (โดยเฉพาะส่วนที่พึ่งพา queue/redis ของระบบ)
