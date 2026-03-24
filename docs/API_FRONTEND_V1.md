# คู่มือ Frontend API V1 (Gametech)

อัปเดตล่าสุด: 2026-03-24

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

HTTP status
- สำเร็จ: `200`
- validation ไม่ผ่าน: `422`

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
    "user_name": "9000000011"
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

---

### 3) Wallet

#### 3.1 ส่งคำขอถอนเงิน
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
  "game": "1"
}
```

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

Response จะขึ้นกับกติกาและสถานะงวด ณ ขณะนั้น (ผ่านบริการ Lotto เดิม)

#### 5.4 รายการโพยของสมาชิก
- `GET /lotto/tickets`
- Auth: ต้องใช้ token
- รองรับภาษา (`language/lang/locale/X-Language`) และคืน `language` ใน response

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
      "status": "active",
      "total_amount": 90,
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
    "status": "active",
    "total_amount": 90,
    "created_at": "2026-03-24 12:00:00",
    "items": []
  },
  "language": "th",
  "message": "ดึงรายละเอียดโพยสำเร็จ"
}
```

#### 5.6 ยกเลิกโพย
- `POST /lotto/tickets/{id}/cancel`
- Auth: ต้องใช้ token

Response ตัวอย่างเมื่อไม่พบโพย
```json
{
  "success": false,
  "message": "ไม่พบโพยที่ระบุ"
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

## สรุปสถานะทดสอบ (2026-03-21)

ผ่านและตอบ JSON ถูกต้อง:
- `POST /auth/login`
- `POST /auth/logout`
- `GET /member/profile`
- `GET /member/balance`
- `POST /wallet/withdraw`
- `GET /games/types`
- `GET /games/providers/{type}`
- `GET /games/{type}/{provider}`
- `POST /games/login` (กรณีเข้าไม่ได้ ตอบ error JSON)
- `GET /lotto/draws`
- `GET /lotto/draws/{id}`
- `GET /deposit/channels`
- `POST /deposit/loadbank`
- `GET /wheel/list`
- `POST /wheel/spin`
- `GET /wheel/history`
- `GET /promotion/list`
- `POST /promotion/select`
- `POST /promotion/deselect`

ข้อจำกัดที่ยังพบใน environment ทดสอบ:
- `POST /auth/register` มีโอกาส timeout จาก dependency ภายในระบบเดิม (โดยเฉพาะส่วนที่พึ่งพา queue/redis ของระบบ)
