# WealthWave Payment Gateway — เอกสารอ้างอิง API ฉบับเต็ม (`llms-full-th.md`)

> **เอกสารไฟล์เดียว (ภาษาไทย)** สำหรับ WealthWave Classic payment-gateway REST API เวอร์ชัน **1.0** วาง paste ลงใน ChatGPT / Claude / Cursor / Copilot พร้อมโจทย์ของคุณ AI จะมีข้อมูลครบทุก endpoint, request, response, callback, error และ pattern การเชื่อมต่อ เพื่อสร้าง integration ที่ถูกต้องพร้อม production
>
> ⭐ **สำหรับการรับฝาก ใช้ `POST /payment-flex/create` (FLEX, หน้าชำระเงิน) — ดู §8.2** ระบบจะใช้ทุกช่องทางการชำระเงินที่มีโดยอัตโนมัติ และคืน `payment_url` ที่จัดการ flow ของลูกค้าให้ทั้งหมด ส่วน endpoint สร้างคำสั่งชำระเงินอื่น (`/payment/create`, `/paymentv2/create`, `/payment-transfer/create`) **จะถูกยกเลิก (deprecate) ในเร็ว ๆ นี้** — ห้ามใช้กับ integration ใหม่
>
> ภาษาอังกฤษ: ดู `llms-full.md`
>
> อัปเดตล่าสุด: 2026-06-14 · เอกสารแบบเว็บ: <https://doc-th.wealthwave.tech/?lang=th>

---

## สารบัญ

1. [แนวคิดและธรรมเนียม](#1-แนวคิดและธรรมเนียม)
2. [Base URL & Environments](#2-base-url--environments)
3. [การยืนยันตัวตน (HMAC-SHA256)](#3-การยืนยันตัวตน-hmac-sha256)
4. [โครงสร้าง Request / Response](#4-โครงสร้าง-request--response)
5. [Error Code ที่พบบ่อย](#5-error-code-ที่พบบ่อย)
6. [รหัสธนาคารไทย](#6-รหัสธนาคารไทย)
7. [คำอธิบาย Order ID](#7-คำอธิบาย-order-id)
8. [Endpoints](#8-endpoints)
9. [Webhook (Callback)](#9-webhook-callback)
10. [Flow แบบเต็ม (ฝาก / ถอน)](#10-flow-แบบเต็ม)
11. [State Machine สถานะออเดอร์](#11-state-machine-สถานะออเดอร์)
12. [ตัวอย่างโค้ด](#12-ตัวอย่างโค้ด)
13. [Postman Pre-request script](#13-postman-pre-request-script)
14. [ข้อจำกัด / จุดต้องระวัง (Operational Limits)](#14-ข้อจำกัด--จุดต้องระวัง)
15. [Checklist สำหรับ Integration ที่ดี](#15-checklist-สำหรับ-integration-ที่ดี)
16. [การฝังหน้าชำระเงินผ่าน iframe](#16-การฝังหน้าชำระเงินผ่าน-iframe)

---

## 1. แนวคิดและธรรมเนียม

WealthWave คือ payment gateway ของไทยแบบ REST ใช้สำหรับ:

- **รับฝาก (deposit)** จากลูกค้าผ่าน Thai PromptPay QR หรือผ่านการโอนเข้าบัญชีปลายทาง (TRANSFER) — ⭐ **แนะนำ:** ใช้ endpoint **FLEX** (§8.2): เรียกครั้งเดียวได้ `payment_url` ระบบเลือกช่องทางที่ดีที่สุดจากทุกช่องทางที่มี และหน้าชำระเงินจัดการ flow ลูกค้าให้ทั้งหมด (วิธีใช้งาน, QR หรือเลขบัญชี, แจ้งสลิป, สถานะ real-time, redirect กลับ)
- **ส่งถอน (withdraw)** จาก wallet ของ merchant ไปบัญชีปลายทางของลูกค้า
- **กระทบยอด (reconcile)** เช็คสถานะรายการ และอัปโหลดสลิปกรณี callback ไม่มา

ธรรมเนียมที่ใช้ทุก endpoint:

- ทุก request เป็น **`POST` + `application/json`**
- Response ทั้งหมดเป็น JSON สำเร็จคืน HTTP `200` พร้อม `{"success": 200, "data": {...}}` ถ้า error คืน HTTP `403` / `429` / `500` พร้อม `{"error": {"code": <int>, "message": <string>}}`
- จำนวนเงินทั้งหมดคิดเป็น **THB** ส่งเป็น **string ทศนิยม 2 ตำแหน่ง ไม่มี comma คั่นพัน** เช่น `"1000.00"` (ห้าม `"1,000"` หรือ `1000`)
- ฟิลด์ `time` ที่คุณส่งคือ **Unix epoch (วินาที)** เป็น integer
- Datetime ที่ API คืนกลับอยู่ในรูป `YYYY-MM-DD HH:MM:SS` เขตเวลา **Asia/Bangkok (UTC+7)**
- ทุก body ต้องเซ็น **HMAC-SHA256** ด้วย `secret_key` ของคุณ ส่งใน header **`X-Signature`** (ดู §3)
- Webhook จะถูกยิงไปที่ `notify_url` ที่คุณส่งตอนสร้างออเดอร์ ถ้าไม่ตอบ HTTP `200` ระบบจะ retry ดังนั้น handler ต้อง **idempotent** (ดู §9.3)
- โหมด QR ใช้ **decimal nudge** — `transfer_amount` อาจต่างจาก `amount` ไม่กี่สตางค์ ลูกค้าต้องโอน `transfer_amount` ให้เป๊ะ มิเช่นนั้นอาจ match ผิดออเดอร์

---

## 2. Base URL & Environments

| Environment | Base URL | หมายเหตุ |
|---|---|---|
| Production (live) | `https://api-th.wealthwave.tech` | เงินจริง ใช้กับ credential production เท่านั้น |
| Production (alt) | `https://api-server.wealthwave` | ตัวอย่างใน docs เก่า ให้ใช้ URL ที่ทีม account แจ้ง |
| Staging (UAT) | `https://api-dev.wealthwave.tech` | credential ทดสอบ ใช้ provider mock ได้ |

Credential ที่จะได้รับ:

| ฟิลด์ | คำอธิบาย |
|---|---|
| `merchant_id` | รหัสร้านค้า เช่น `AA12345678`, `TH00000000` |
| `token` | API token ยาว ส่งใน body ทุก request |
| `secret_key` | secret สำหรับเซ็น HMAC **ห้ามส่งบนสาย** ใช้คำนวณ `X-Signature` เท่านั้น |

**Test credential** (ใช้กับตัวอย่าง simulator ใน §12):

```
merchant_id: TH00000000
secret_key:  aaaaaaaaaaaaaaaaaaaa
```

---

## 3. การยืนยันตัวตน (HMAC-SHA256)

ทุก request ต้องมี 3 ฟิลด์ใน body และ 1 header:

| ที่ | ชื่อ | บังคับ | คำอธิบาย |
|---|---|---|---|
| Body | `merchant_id` | ใช่ | merchant id ของคุณ |
| Body | `token` | ใช่ | API token |
| Body | `time` | ใช่ | Unix timestamp (วินาที) ปัจจุบัน ป้องกัน replay attack |
| Header | `Content-Type` | ใช่ | `application/json` เสมอ |
| Header | `X-Signature` | ใช่ | `hash_hmac("sha256", raw_request_body, secret_key)` (hex ตัวพิมพ์เล็ก) |

> **สำคัญ**: signature คำนวณบน **byte ของ body ที่ส่งจริง** หาก parse แล้ว encode ใหม่ JSON อาจสลับ key / whitespace / number formatting ทำให้ signature ไม่ตรง — ใช้ string เดียวกับที่เขียนลง wire เสมอ

### สูตรเซ็น

```
signature = hex( HMAC-SHA256( key = secret_key, message = raw_json_body ) )
```

### ตัวอย่างเซ็น (PHP)

```php
$body      = json_encode($data);
$signature = hash_hmac('sha256', $body, $secret_key);
```

### ตัวอย่างเซ็น (Node.js)

```js
const crypto = require('crypto');
const body      = JSON.stringify(data);
const signature = crypto.createHmac('sha256', secret_key).update(body).digest('hex');
```

### ตัวอย่าง body ครบ

```jsonc
{"merchant_id":"AA12345678","token":"testtokentesttokentesttokentesttokentesttoken","time":1656272222}
```

ส่ง header:

```
X-Signature: <hex 64 ตัวอักษร ของ HMAC-SHA256 body ด้วย secret_key>
```

ถ้า signature / token / merchant_id ไม่ผ่าน:

```http
HTTP/1.1 403 Forbidden
{"error":{"code":403,"message":"authentication failed"}}
```

---

## 4. โครงสร้าง Request / Response

### ฟิลด์ที่ทุก endpoint ต้องมีใน request

```jsonc
{
  "merchant_id": "AA12345678",
  "token":       "testtokentesttokentesttokentesttokentesttoken",
  "time":        1656272222
  // ...ฟิลด์เฉพาะ endpoint
}
```

### Envelope สำเร็จ

```json
{
  "success": 200,
  "data":   { /* payload เฉพาะ endpoint */ }
}
```

### Envelope ล้มเหลว

```json
{
  "error": {
    "code":    403,
    "message": "authentication failed"
  }
}
```

HTTP status จะตรงกับค่า `code` (`403` / `429` / `500`) ค่า `200` สงวนสำหรับสำเร็จเท่านั้น

---

## 5. Error Code ที่พบบ่อย

| HTTP | `code` | `message` ที่เห็นบ่อย | เกิดเมื่อ |
|---|---|---|---|
| `200` | `200` | — | สำเร็จ |
| `403` | `403` | `authentication failed` | merchant_id / token / signature ไม่ถูก หรือ `time` คลาดเคลื่อนเกินไป |
| `429` | `429` | `Rate limit exceeded for this bank account. Maximum 5 requests per minute. Please try again in N seconds.` | สร้าง payment ของ `(bank, account_no)` เดียวกันถี่เกินไป |
| `500` | `500` | `amount must be greater than 20` | จำนวนเงินต่ำกว่า 20 |
| `500` | `500` | `amount must be less than 49999` | จำนวนเงินสูงเกินเพดาน (49,999 ทั่วไป / 500,000 ของ VIP) |
| `500` | `500` | `invalid bank` / `invalid account no` / `invalid account name` / `please provide customer info.` | ฟิลด์ไม่ถูกต้อง |
| `500` | `500` | `merchant order id not found` | หา reference ไม่เจอ (query/slip) หรือชนกัน |
| `500` | `500` | `service error. no available channel.` | ไม่มี provider ว่างสำหรับช่วงเงินนี้ — ลองใหม่ทีหลัง |
| `500` | `500` | `concurrent payment channel exceeded limitation` | ทั้งระบบมี order ค้าง ≥ 200 รอสักครู่ |
| `500` | `500` | `platform_order_id not found` | ไม่มีออเดอร์ตาม id ที่ส่ง (query/slip) |

---

## 6. รหัสธนาคารไทย

ใช้กับฟิลด์ `bank` (request) และ `deposit_bank` (response ของ TRANSFER):

| รหัส | ธนาคาร |
|---|---|
| `KBANK` | กสิกรไทย |
| `BBL`   | กรุงเทพ |
| `KTB`   | กรุงไทย |
| `TTB`   | ธนาคารทีทีบี |
| `SCB`   | ไทยพาณิชย์ |
| `UOB`   | ยูโอบี |
| `BAY`   | กรุงศรีอยุธยา |
| `CIMB`  | CIMB Thai |
| `LH`    | แลนด์แอนด์เฮ้าส์ |
| `GSB`   | ออมสิน |
| `KK`    | เกียรตินาคินภัทร |
| `CITI`  | ซิตี้แบงก์ |
| `GHB`   | อาคารสงเคราะห์ |
| `BAAC`  | ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร |
| `TISCO` | ทิสโก้ |

---

## 7. คำอธิบาย Order ID

WealthWave สร้าง `platform_order_id` ให้ทุก order — prefix บอกประเภท:

| Prefix | ความหมาย | endpoint ที่คืน |
|---|---|---|
| `THBP…` | Payment (deposit) — FLEX, QR และ TRANSFER | `/payment-flex/create` (แนะนำ), `/paymentv2/create`, `/payment-transfer/create`, `/payment/create` (เลิก) |
| `THBW…` | Withdraw (ลูกค้าได้รับเงิน) | `/withdraw/create` |
| `THBM…` | Merchant withdraw / "settlement" (merchant ได้รับเงิน) — flow ภายใน ไม่อยู่ใน public API | (internal) |

**ห้ามคิดสร้าง / เดา id เอง** ให้อ่านจาก `data.platform_order_id` ใน response เก็บ `merchant_order_id` ไว้ใช้กระทบยอด แล้วใช้ `platform_order_id` เรียก query endpoint

---

## 8. Endpoints

ทุก endpoint = `POST`, body JSON, header `Content-Type: application/json` + `X-Signature` ส่วน 3 ฟิลด์บังคับ (`merchant_id`, `token`, `time`) ไม่ระบุซ้ำในแต่ละ endpoint แต่ **ต้องมีเสมอ**

---

### 8.1 `POST /balance` — เช็คยอดเงิน

ดึงยอดเงินคงเหลือใน wallet ของ merchant

#### Request

```json
{
  "merchant_id": "AA12345678",
  "token":       "testtokentesttokentesttokentesttokentesttoken",
  "time":        1656272222
}
```

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "balance":          "10000.0000",
    "freeze_balance":   "0.0000",
    "unsettle_balance": "0.0000"
  }
}
```

| ฟิลด์ | ชนิด | คำอธิบาย |
|---|---|---|
| `balance` | string (decimal) | ยอดที่ใช้ได้ |
| `freeze_balance` | string (decimal) | ยอดที่ถูก freeze (เช่น withdraw รอประมวลผล) |
| `unsettle_balance` | string (decimal) | จ่ายแล้วแต่ยังไม่ได้กระทบยอดเข้า ledger |

---

### 8.2 `POST /payment-flex/create` — สร้างออเดอร์จ่าย FLEX (หน้าชำระเงิน) ⭐ แนะนำ

> ✅ **นี่คือวิธีสร้างคำสั่งฝากที่แนะนำ — และจะเป็นช่องทางหลักเพียงช่องทางเดียวในอนาคต** ส่วน endpoint อื่น (`/payment/create`, `/paymentv2/create`, `/payment-transfer/create`) **จะถูกยกเลิกในเร็ว ๆ นี้**

**ทำไมต้องใช้ FLEX:**

- **API เดียว ครบทุกช่องทาง** — FLEX เลือกใช้**ทุกช่องทางการชำระเงินที่แพลตฟอร์มมี**โดยอัตโนมัติ และเลือกช่องทางที่ดีที่สุดให้แต่ละรายการ — availability และอัตราสำเร็จสูงสุด
- ลูกค้าได้รับคำสั่งโอนเป็น **QR Code หรือเลขบัญชีธนาคาร** ตามช่องทางที่ระบบเลือก
- ลูกค้าทำรายการ**บนหน้าชำระเงินของ WealthWave เท่านั้น** (`payment_url`) — หน้าชำระเงินจัดการ flow ทั้งหมดเอง: วิธีใช้งาน, รายละเอียดการโอน, แจ้งสลิป, ตัวนับเวลา, สถานะ real-time และ redirect กลับเว็บร้านค้า
- ฝั่งร้านค้า**ไม่ต้องสร้างอะไรเลย**: ไม่ต้อง render QR, ไม่ต้องทำหน้าแสดงเลขบัญชี, ไม่ต้องทำหน้าอัปโหลดสลิป — แค่เปิด `payment_url` ให้ลูกค้า
- ปรับหน้าตาได้ผ่าน `payment_theme` (theme เริ่มต้น: `halo`)

> ⚠️ ลูกค้าต้องชำระผ่านหน้าชำระเงินเท่านั้น **ห้าม**ดึงข้อมูล QR/เลขบัญชีออกไปแสดงใน UI ของร้านค้าเอง — ขั้นตอนบนหน้าชำระเงิน (วิธีใช้งาน → โอน → แจ้งสลิป) เป็นส่วนหนึ่งของการยืนยันรายการ

**Method:** `POST` · **URL:** `/payment-flex/create`

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER0123456789789445566",
  "amount":            "1000.00",
  "bank":              "KBANK",
  "account_name":      "สมชาย ใสสว่าง",
  "account_no":        "1234567890",
  "notify_url":        "https://merchant.com/callback/payment",
  "redirect_url":      "https://merchant.com/return",
  "payment_theme":     "halo"
}
```

| ฟิลด์ | บังคับ | คำอธิบาย |
|---|---|---|
| `merchant_order_id` | ใช่ | id เฉพาะของฝั่งคุณ (`[0-9a-zA-Z]` ≤ 40 ตัวอักษร) |
| `amount` | ใช่ | THB ทศนิยม 2 ตำแหน่ง ขั้นต่ำ **20** สูงสุด **49,999** (หรือ 500,000 สำหรับ VIP id 9 / 39) |
| `bank` | ใช่ | ธนาคารต้นทางของลูกค้า — ดู §6 |
| `account_name` | ใช่ | ชื่อบัญชีลูกค้า (ไทย/อังกฤษ ได้) |
| `account_no` | ใช่ | เลขบัญชีลูกค้า (10–14 หลัก) |
| `notify_url` | ใช่ | URL HTTPS สำหรับรับ webhook |
| `redirect_url` | ไม่ | URL ที่หน้าชำระเงินจะพาลูกค้ากลับเมื่อจ่ายสำเร็จ |
| `payment_theme` | ไม่ | theme id ของหน้าชำระเงิน (ดูตารางด้านล่าง) ลำดับการเลือก: ค่าใน request → default ของ client → default ของ partner → theme เริ่มต้น (**`halo`**) ค่าที่ไม่รู้จักจะ fallback เป็นค่าเริ่มต้น |

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "THBP20260531...",
    "merchant_order_id": "ORDER0123456789789445566",
    "payment_method":    "TRANSFER",
    "payment_url":       "https://payment.gateway-service.net/THBP20260531.../<hash>"
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `platform_order_id` | id ของ WealthWave (`THBP…`) เก็บไว้กระทบยอด |
| `merchant_order_id` | id ของคุณ echo กลับ |
| `payment_method` | ช่องทางที่ระบบเลือก: `"TRANSFER"` หรือ `"QR"` (เป็นข้อมูลเฉย ๆ — หน้าชำระเงินแสดงผลเองตามช่องทาง) |
| `payment_url` | URL หน้าชำระเงิน **เปิดหน้านี้ให้ลูกค้า** (redirect หรือแท็บใหม่) |

#### ขั้นตอนการเชื่อมต่อ

1. `POST /payment-flex/create` → อ่าน `data.payment_url`
2. พาลูกค้าไปที่ `payment_url`
3. หน้าชำระเงินพาลูกค้าทำรายการเอง (วิธีใช้งาน, QR หรือเลขบัญชี, แจ้งสลิปถ้าต้องใช้, สถานะ real-time)
4. รับ payment webhook มาตรฐาน (§9.1) ที่ `notify_url` — หน้าชำระเงินพาลูกค้ากลับ `redirect_url` (ถ้าระบุ)

#### Error

- `403 authentication failed`
- `409` — **pending order guard**: ลูกค้าหนึ่งบัญชีเปิด FLEX order ค้างได้ครั้งละ 1 รายการ ถ้าสร้างซ้ำจะได้ `{"success": false, "code": 409, "error": "you have a pending payment order. complete or cancel it first.", "data": {"pending_order_id": "THBP..."}}` — ให้ใช้ order เดิมต่อ, ให้ลูกค้ากดยกเลิกบนหน้าชำระเงิน, หรือยกเลิกจากฝั่ง backend ผ่าน [`POST /payment/cancel`](#811-post-paymentcancel--ยกเลิกออเดอร์จ่าย) (§8.11) ก่อนสร้างใหม่
- `429 Rate limit exceeded…` — สร้างได้ 5 ครั้ง/นาที ต่อ `(bank, account_no)`
- `500` ตัวเลือก: `merchant order id not found`, `amount must be greater than 20`, `amount must be less than 49999`, `invalid account no`, `invalid bank`, `invalid account name`, `please provide customer info.`, `Try another amount or wait 6 minutes.` (กันยอดซ้ำ), `concurrent payment channel exceeded limitation…`, `service error. no available channel.`

#### Theme ของหน้าชำระเงิน

`payment_theme` รับค่า id เหล่านี้ (ดูตัวอย่างภาพ: `https://doc-th.wealthwave.tech/img/themes/theme-<id>.png`):

| id | ชื่อ | หมายเหตุ |
|---|---|---|
| `halo` | Halo | **ค่าเริ่มต้น** — ใช้เมื่อไม่ส่ง `payment_theme` และไม่ได้ตั้ง default ที่บัญชี ส่งค่า `"default"` ก็แสดง Halo เช่นกัน |
| `pristine` | Pristine | |
| `blue` | Blue | |
| `vault` | Vault | |
| `sunset` | Sunset | |
| `obsidian` | Obsidian | |
| `stack` | Stack | |
| `sienna` | Sienna | |
| `mint` | Mint | |
| `pulse` | Pulse | |

ตั้งค่า theme เริ่มต้นประจำบัญชีร้านค้าได้ (client portal หรือติดต่อ support) — ค่าใน request ชนะเสมอ

#### หมายเหตุเรื่องสถานะ

FLEX order มีสถานะเพิ่มคือ **`cancelled`** (ลูกค้ากด *ยกเลิก* บนหน้าชำระเงินก่อนโอน) — การยกเลิกจะส่ง callback สถานะ `CANCELLED` การยกเลิกอาจไม่ถือเป็นที่สิ้นสุดเสมอไป ในบางกรณีอาจมี callback `PAID` ตามมาภายหลังได้หากคำสั่งซื้อถูกชำระสำเร็จ (ให้ถือว่า `PAID` ถูกต้อง) มิฉะนั้นให้สร้าง order ใหม่เมื่อลูกค้าลองอีกครั้ง (pending guard ถูกปลดเมื่อยกเลิก) — backend ของร้านค้าสั่งยกเลิกเองได้ผ่าน [`POST /payment/cancel`](#811-post-paymentcancel--ยกเลิกออเดอร์จ่าย) (§8.11)

---

### 8.3 `POST /payment/create` — สร้างออเดอร์จ่าย (เลิกใช้แล้ว)

> ⚠️ **เลิกใช้แล้ว** จะถูกถอดออกในเร็ว ๆ นี้ ใช้ `/payment-flex/create` (§8.2) แทน
> ต่างจาก v2 เพราะ v1 ไม่ต้องส่งข้อมูลบัญชีลูกค้า การเชื่อมต่อใหม่ห้ามใช้ตัวนี้

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER0123456789789445566",
  "amount":            "1000.00",
  "notify_url":        "https://merchant.com/callback/payment"
}
```

#### Response (`200 OK`)

โครงสร้างเหมือน V2 แต่ไม่มีข้อมูลบัญชีลูกค้า

---

### 8.4 `POST /paymentv2/create` — สร้างออเดอร์จ่าย V2 (QR) (จะเลิกใช้เร็ว ๆ นี้)

> ⚠️ **Endpoint นี้จะถูกยกเลิก (deprecate) ในเร็ว ๆ นี้** ใช้ `/payment-flex/create` (§8.2) แทน — FLEX ครอบคลุมช่องทาง QR ให้อัตโนมัติ และหน้าชำระเงินจัดการ flow ลูกค้าให้ทั้งหมด

รับ PromptPay QR โดยตรง ลูกค้าสแกน QR แล้วโอนจากบัญชีที่คุณส่งให้

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER0123456789789445566",
  "amount":            "1000.00",
  "bank":              "KBANK",
  "account_name":      "สมชาย ใสสว่าง",
  "account_no":        "1234567890",
  "notify_url":        "https://merchant.com/callback/payment"
}
```

| ฟิลด์ | บังคับ | คำอธิบาย |
|---|---|---|
| `merchant_order_id` | ใช่ | id เฉพาะของฝั่งคุณ (`[0-9a-zA-Z]` ≤ 40 ตัวอักษร) |
| `amount` | ใช่ | THB ทศนิยม 2 ตำแหน่ง ขั้นต่ำ **20** สูงสุด **49,999** (หรือ 500,000 สำหรับ VIP id 9 / 39) |
| `bank` | ใช่ | ธนาคารต้นทางของลูกค้า — ดู §6 |
| `account_name` | ใช่ | ชื่อบัญชีลูกค้า (ไทย/อังกฤษ ได้) |
| `account_no` | ใช่ | เลขบัญชีลูกค้า (10–14 หลัก) |
| `notify_url` | ใช่ | URL HTTPS สำหรับรับ webhook |

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
    "merchant_order_id": "ORDER0123456789789445566",
    "order_datetime":    "2024-04-28 21:04:03",
    "expire_datetime":   "2024-04-28 21:14:03",
    "amount":            "1000.00",
    "transfer_amount":   "1000.03",
    "qrcode":            "00020101…(EMV string จะเลิกใช้ 14 Sep 2025)…",
    "qrbase64":          "iVBORw0KGgoAAAANSUhEUgAA…(base64 PNG)…"
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `platform_order_id` | id ของ WealthWave (`THBP…`) เก็บไว้กระทบยอด |
| `merchant_order_id` | id ของคุณ ส่งกลับมาเช็ค |
| `order_datetime` | เวลาสร้างออเดอร์ (Asia/Bangkok) |
| `expire_datetime` | เส้นตายให้ลูกค้าโอน ปกติ +10 นาที |
| `amount` | ยอดที่คุณขอ |
| `transfer_amount` | ยอดที่ลูกค้าต้องโอน **เป๊ะ** ต่างจาก `amount` ไม่กี่สตางค์เพื่อ matching — **โชว์ค่านี้ให้ลูกค้า** |
| `qrcode` | EMV-text PromptPay payload — **เลิกใช้ 14 Sep 2025** ใช้ `qrbase64` |
| `qrbase64` | base64-encoded PNG ของ QR — render เป็น `<img src="data:image/png;base64,…">` |

#### Error

`403 authentication failed`, `500 amount must be greater than 20`, `500 amount must be less than 49999`, `500 invalid bank/account_no/account_name`, `500 service error. no available channel.`, `429 Rate limit exceeded…` (5 req/min ต่อ `(bank, account_no)`), `500 concurrent payment channel exceeded limitation` (≥ 200 in-flight order)

---

### 8.5 `POST /payment-transfer/create` — สร้างออเดอร์จ่าย (TRANSFER / โอนเข้าบัญชี) (จะเลิกใช้เร็ว ๆ นี้)

> ⚠️ **Endpoint นี้จะถูกยกเลิก (deprecate) ในเร็ว ๆ นี้** ใช้ `/payment-flex/create` (§8.2) แทน — FLEX ครอบคลุมช่องทางโอนเข้าบัญชีให้อัตโนมัติ และหน้าชำระเงินจัดการ flow ลูกค้าให้ทั้งหมด

ทางเลือกแทน QR — ลูกค้าโอนเข้า **บัญชีปลายทาง** ที่ระบบส่งมาให้ (`deposit_bank` / `deposit_account_no` / `deposit_account_name`) พร้อม `transfer_amount` ที่ต้องโอนเป๊ะ ๆ

#### Request

โครงสร้างเหมือน `/paymentv2/create` ทุกประการ:

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER0123456789789445566",
  "amount":            "1000.00",
  "bank":              "KBANK",
  "account_name":      "สมชาย ใสสว่าง",
  "account_no":        "1234567890",
  "notify_url":        "https://merchant.com/callback/payment"
}
```

ข้อจำกัด `amount`/ฟิลด์เหมือน V2 (20 – 49,999 THB หรือ 500,000 สำหรับ VIP)

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id":    "THBP202605061004030AAAA001",
    "merchant_order_id":    "ORDER0123456789789445566",
    "payment_method":       "TRANSFER",
    "order_datetime":       "2026-05-06 10:04:03",
    "expire_datetime":      "2026-05-06 10:14:03",
    "amount":               1000,
    "transfer_amount":      "1000.07",
    "deposit_bank":         "KBANK",
    "deposit_account_no":   "1234567890",
    "deposit_account_name": "บริษัท เวลธ์เวฟ จำกัด"
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `payment_method` | `"TRANSFER"` เสมอสำหรับ endpoint นี้ |
| `transfer_amount` | ยอดที่ลูกค้าต้องโอนเป๊ะ (รวมสตางค์) |
| `deposit_bank` / `deposit_account_no` / `deposit_account_name` | บัญชีปลายทางที่ลูกค้าต้องโอนไป — **ระบบเลือกใหม่ทุก request ห้าม cache** |

#### Error

`403`, `429`, ตัวเลือก `500`: `amount must be greater than 20`, `amount must be less than 49999`, `merchant order id not found`, `invalid account no`, `invalid bank`, `invalid account name`, `please provide customer info.`, `service error. no available channel.`

#### Webhook

Body เหมือน QR payment callback (§9.1) เพิ่มฟิลด์ `payment_method: "TRANSFER"`

---

### 8.6 `POST /payment/query` — เช็คสถานะออเดอร์จ่าย

ใช้ตอน webhook ไม่มา — query สถานะปัจจุบัน

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "platform_order_id": "THBP20240401071230HW2xxxxx"
}
```

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "THBP2024042821130xxxxxxxxx",
    "merchant_order_id": "ORDER0123456789789445566",
    "order_datetime":    "2024-04-28 21:13:09",
    "amount":            "1000.0000",
    "status":            "open",
    "expire_datetime":   "2024-04-28 21:23:09",
    "payment_datetime":  null
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `status` | หนึ่งใน `open` (รอจ่าย), `settled_paid` (จ่ายแล้ว), `error` (ล้มเหลว/หมดอายุ), `cancelled` (ลูกค้ายกเลิกบนหน้าชำระเงิน — FLEX), `freeze` (admin lock) |
| `payment_datetime` | จะ set เมื่อ status เป็น `settled_paid` |

---

### 8.7 `POST /withdraw/create` — สร้างออเดอร์ถอน

โอนเงินจาก wallet ของ merchant ไปบัญชีลูกค้า ระบบทำงานแบบ async ผลสุดท้ายจะมาทาง webhook (§9.2)

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER112233445566",
  "amount":            "1000.00",
  "bank":              "KBANK",
  "account_no":        "1234567890",
  "account_name":      "สมชาย นามสมมุติ",
  "notify_url":        "https://merchant.com/callback/withdraw"
}
```

| ฟิลด์ | บังคับ | คำอธิบาย |
|---|---|---|
| `merchant_order_id` | ใช่ | id เฉพาะ (`[0-9a-zA-Z]` ≤ 40) |
| `amount` | ใช่ | THB ทศนิยม 2 |
| `bank` | ใช่ | ธนาคารปลายทาง — §6 |
| `account_no` | ใช่ | เลขบัญชีปลายทาง |
| `account_name` | ใช่ | ชื่อบัญชีปลายทาง |
| `notify_url` | ใช่ | URL HTTPS รับ webhook |

#### Response (`200 OK`) — ตอบรับไว้ก่อน status pending

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
    "merchant_order_id": "ORDER112233445566",
    "order_datetime":    "2024-04-28 21:49:39",
    "bank":              "KBANK",
    "account_no":        "1234567890",
    "account_name":      "สมชาย นามสมมุติ",
    "amount":            "1000.00"
  }
}
```

ผล `SUCCESS` / `FAIL` จะมาทาง webhook §9.2

#### Error

`403 authentication failed`, `500 amount must be greater than 20`, `500 invalid bank/account_no/account_name`, `500 insufficient balance`

---

### 8.8 `POST /withdraw/query` — เช็คสถานะถอน

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "platform_order_id": "THBW20240428214939exxxxxxx"
}
```

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "THBW20240428214939eAEByOvF",
    "client_order_id":   "ORDER112233445566",
    "order_datetime":    "2024-04-28 21:49:39",
    "bank":              "KBANK",
    "account_no":        "1234567890",
    "account_name":      "สมชาย นามสมมุติ",
    "amount":            "100.0000",
    "status":            "open",
    "done_datetime":     null
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `client_order_id` | คือ `merchant_order_id` ของคุณ (สังเกตชื่อต่างจากตอน create) |
| `status` | `open` (รอ), `success`, หรือ `failed` |
| `done_datetime` | set เมื่อ status พ้น `open` |

---

### 8.9 `POST /slip/upload` — อัปโหลดสลิปเพื่อยืนยันการชำระ

ลูกค้าโอนแล้วแต่ webhook ไม่มา ในขณะที่ออเดอร์อยู่สถานะ `open` สามารถอัปโหลดสลิปให้ระบบกระทบยอดได้ทันที โดยไม่ต้องรอ

เงื่อนไข:

- สถานะยังเป็น `open`
- รูปต้องเป็น **JPEG / PNG**, **≤ 1 MB**, ส่งเป็น **base64**

#### Request

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "platform_order_id": "THBP20240401071230HW2xxxxx",
  "image_base64":      "/9j/4AAQSkZJRgABAQAASABIAAD/4VeGRXhpZgAATU0AK....."
}
```

#### Response (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "msg":              "slip upload successful.",
    "verification_msg": "amount ok, destination ok, transfer in time."
  }
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `msg` | `"slip upload successful."` เสมอ |
| `verification_msg` | ผล verify เบื้องต้นจาก QR ในสลิป เช่น `amount ok, destination ok, transfer in time.` |

#### Error

`403 authentication failed`, `500 platform_order_id not found`, `500 status not open`, `500 order not in an uploadable state`, `500 invalid image / size > 1MB`

---

### 8.10 `POST /verify-qr` — ตรวจสอบสลิป QR (ส่วนเสริม)

ใช้ตรวจสอบ string PromptPay/Thai QR-slip ว่าถูกต้องหรือไม่ พร้อมดึงข้อมูลธุรกรรม (sender, receiver, amount, time) เหมาะสำหรับเช็ค client-side ก่อนส่ง `/slip/upload`

**Auth:** `Authorization: Bearer <API-KEY>` (key 64 ตัวอักษร แยกจาก merchant credential ปกติ ไม่ใช่ HMAC)

#### Request

```http
POST /verify-qr HTTP/1.1
Authorization: Bearer 5FA9D1E4...(64 chars)...F2C3
Content-Type: application/json

{ "qrString": "00020101021230530016A0000006770101110113006689750856020000053037645405499.955802TH..." }
```

#### Response (`200 OK`, `code: 100`)

โครงสร้างหลัก:

```jsonc
{
  "msg":  "สำเร็จ",
  "code": 100,
  "data": {
    "rqUID":         "...",
    "kbankTxnId":    "...",
    "statusCode":    "0000",
    "statusMessage": "SUCCESS",
    "data": {
      "language":     "TH",
      "transRef":     "015134200548APP00935",
      "sendingBank":  "004",
      "transDate":    "20250514",
      "transTime":    "20:05:48",
      "sender":       { "displayName": "นาง ลำพึง เ", "name": "MRS. LAMPUNG K", "proxy": {...}, "account": {...} },
      "receiver":     { "displayName": "นาย ณัฐพล เ", "name": "MR. NATTHAPHON C", "proxy": {...}, "account": {...} },
      "amount":            499.95,
      "paidLocalAmount":   499.95,
      "paidLocalCurrency": "764",
      "countryCode":       "TH",
      "transFeeAmount":    0,
      "ref1": "", "ref2": "", "ref3": "", "toMerchantId": ""
    }
  },
  "status": true
}
```

#### Error

| HTTP | `code` | กรณี |
|---|---|---|
| `200` | `500` | upstream error เช่น `ไม่พบข้อมูล` |
| `400` | – | JSON ผิด / ไม่มี `qrString` |
| `401` | – | API key หาย / ผิด / inactive |
| `502` | – | network timeout / upstream อ่านไม่ออก |

---

### 8.11 `POST /payment/cancel` — ยกเลิกออเดอร์จ่าย

ยกเลิกออเดอร์จ่ายที่ยังเปิดอยู่ (สถานะ **`open`**) จากฝั่ง backend (server-to-server) โดยใช้ credentials ของร้านค้า กรณีใช้งานหลักคือเคลียร์ FLEX pending-order `409` (ดู §8.2): เรียกด้วย `pending_order_id` แล้วจึงสร้างใหม่อีกครั้ง (ลูกค้ายกเลิกบนหน้าชำระเงินได้เองเช่นกัน — endpoint นี้คือฝั่ง backend)

**Method:** `POST` · **URL:** `/payment/cancel`

#### Request body

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "platform_order_id": "THBP20260613123300zBuseUwN"
}
```

| Field | จำเป็น | คำอธิบาย |
|---|---|---|
| `platform_order_id` | ใช่ | ออเดอร์ที่ต้องการยกเลิก ใช้ `data.pending_order_id` จาก `409` ของ FLEX หรือ `platform_order_id` จากตอนสร้าง |

#### เงื่อนไขที่ยกเลิกได้

ต้องครบทุกข้อ: ออเดอร์เป็น **ของคุณ**, สถานะ **`open`**, **ยังไม่มีการอัปโหลดสลิป** เมื่อสำเร็จระบบจะปลด **pending-order guard** — สร้างออเดอร์ใหม่ของลูกค้าบัญชีเดิมได้ทันที — และส่ง callback สถานะ **`CANCELLED`** (§9.1)

> ⚠️ การยกเลิก **อาจไม่ถือเป็นที่สิ้นสุดเสมอไป**: ในบางกรณีอาจถูกชำระภายหลังได้ ซึ่งจะมี callback **`PAID`** ตามมาสำหรับ `platform_order_id` เดิม (ให้ถือว่า `PAID` ถูกต้อง)

#### Response สำเร็จ (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "THBP20260613123300zBuseUwN",
    "merchant_order_id": "ORDER0123456789789445566",
    "status":            "cancelled"
  }
}
```

Idempotent: เรียกยกเลิกซ้ำกับออเดอร์ที่ยกเลิกไปแล้วก็คืน `200` (พร้อม `"already_cancelled": true`)

#### Errors

| HTTP | `code` | กรณี |
|---|---|---|
| `200` | `403` | auth ล้มเหลว (merchant id / token / signature) |
| `200` | `500` | ไม่พบออเดอร์ / ไม่ใช่ของคุณ หรือ `platform_order_id` ไม่ถูกต้อง |
| `409` | `409` | พบออเดอร์แต่ยกเลิกไม่ได้ — ไม่ใช่ `open` หรืออัปโหลดสลิปแล้ว Body: `{"success": false, "code": 409, "error": "order not open"}` |

---

### 8.12 `POST /payment/cancel-by-merchant-order-id` — ยกเลิกออเดอร์จ่าย (ด้วย Merchant Order ID)

เหมือน §8.11 แต่อ้างอิงด้วย `merchant_order_id` **ของคุณเอง** (รหัสที่ส่งไปยัง `/payment-flex/create`) แทน `platform_order_id` ของ WealthWave ระบบจะแปลงให้เองภายใน คุณจึง **ไม่จำเป็น** ต้องเก็บ `platform_order_id` ที่ระบบคืนกลับมา เหมาะกับการเคลียร์ FLEX pending-order `409` (§8.2) เมื่อคุณเก็บแต่รหัสคำสั่งซื้อของตัวเอง

**Method:** `POST` · **URL:** `/payment/cancel-by-merchant-order-id`

#### Request body

```json
{
  "merchant_id":       "AA12345678",
  "token":             "testtokentesttokentesttokentesttokentesttoken",
  "time":              1656272222,
  "merchant_order_id": "ORDER0123456789789445566"
}
```

| Field | จำเป็น | คำอธิบาย |
|---|---|---|
| `merchant_order_id` | ใช่ | รหัสคำสั่งซื้อของคุณเอง ตามที่ส่งไปยัง `/payment-flex/create` ต้องถูกสร้างภายใน **24 ชั่วโมง** ที่ผ่านมา (ช่วงเวลาที่ระบบใช้ค้นหา) |

#### เงื่อนไขที่ยกเลิกได้

ต้องครบทุกข้อ: ออเดอร์เป็น **ของคุณ**, ถูกสร้าง **ภายใน 24 ชั่วโมง** ที่ผ่านมา, สถานะ **`open`**, **ยังไม่มีการอัปโหลดสลิป** เมื่อสำเร็จระบบจะปลด **pending-order guard** — สร้างออเดอร์ใหม่ของลูกค้าบัญชีเดิมได้ทันที — และส่ง callback สถานะ **`CANCELLED`** (§9.1)

> ⚠️ การยกเลิก **อาจไม่ถือเป็นที่สิ้นสุดเสมอไป**: ในบางกรณีอาจถูกชำระภายหลังได้ ซึ่งจะมี callback **`PAID`** ตามมาสำหรับ `platform_order_id` เดิม (ให้ถือว่า `PAID` ถูกต้อง)

#### Response สำเร็จ (`200 OK`)

```json
{
  "success": 200,
  "data": {
    "platform_order_id": "THBP20260613123300zBuseUwN",
    "merchant_order_id": "ORDER0123456789789445566",
    "status":            "cancelled"
  }
}
```

Idempotent: เรียกยกเลิกซ้ำกับออเดอร์ที่ยกเลิกไปแล้วก็คืน `200` (พร้อม `"already_cancelled": true`)

#### Errors

| HTTP | `code` | กรณี |
|---|---|---|
| `200` | `403` | auth ล้มเหลว (merchant id / token / signature) |
| `200` | `500` | ไม่พบออเดอร์ — `merchant_order_id` ไม่ได้สร้างผ่าน FLEX, เก่ากว่า 24 ชม. หรือไม่ใช่ของคุณ Body: `{"error": {"code": 500, "message": "ไม่พบ merchant order id"}}` |
| `409` | `409` | พบออเดอร์แต่ยกเลิกไม่ได้ — ไม่ใช่ `open`, อัปโหลดสลิปแล้ว หรือเลยช่วงเวลาที่ยกเลิกได้ Body: `{"success": false, "code": 409, "error": "cannot cancel order"}` |

---

## 9. Webhook (Callback)

### 9.1 Payment Callback

ส่งเมื่อออเดอร์เปลี่ยนเป็น `settled_paid` (หรือ fail) — เหมือนกันทั้ง **FLEX**, QR และ TRANSFER (ของ TRANSFER / FLEX-transfer จะมีฟิลด์ `payment_method` เพิ่ม) ออเดอร์ FLEX ที่ถูกยกเลิกจะส่ง callback สถานะ `CANCELLED` แทน `PAID` ในบางกรณีอาจมี callback `PAID` ตามมาภายหลังได้หากถูกชำระสำเร็จ (ให้ถือว่า `PAID` ถูกต้อง)

```http
POST /your-callback-path HTTP/1.1
User-Agent: wealthwave/1.0
Content-Type: application/json
Connection: Close
X-Signature: b6f9dd313cde39ae1b87e63b9b457029bcea6e9520b5db5de20d3284e4c0259e

{
  "merchant_id":       "AA12345678",
  "platform_order_id": "THBP2024042821130xxxxxxxxx",
  "client_order_id":   "ORDER0123456789789445566",
  "mode":              "PAYMENT",
  "amount":            "1000.00",
  "status":            "PAID",
  "timestamp":         112233445566
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `merchant_id` | ส่งกลับมาให้ |
| `platform_order_id` | id WealthWave (`THBP…`) |
| `client_order_id` | `merchant_order_id` ของคุณ |
| `mode` | `"PAYMENT"` เสมอ |
| `amount` | ยอดที่ขอตอนสร้าง |
| `status` | `"PAID"` หรือ `"FAIL"` |
| `timestamp` | Unix seconds เวลายิง callback |
| `payment_method` (เฉพาะ TRANSFER) | `"TRANSFER"` |

**ต้องตอบ HTTP `200`** มิฉะนั้นระบบจะ retry

### 9.2 Withdraw Callback

ส่งเมื่อ withdraw เปลี่ยนสถานะ `pending` → `success` / `failed`

```http
POST /your-callback-path HTTP/1.1
User-Agent: wealthwave/1.0
Content-Type: application/json
Connection: Close
X-Signature: 6cd4a32478f5ec8e69343988dc9137e37d3eb02123adc3248ec4ea0aeca2e922

{
  "merchant_id":       "AA12345678",
  "platform_order_id": "THBW2024042821130xxxxxxxxx",
  "client_order_id":   "ORDER0123456789789445566",
  "mode":              "WITHDRAW",
  "bank":              "KBANK",
  "account_no":        "1234567890",
  "account_name":      "สมชาย นามสมมุติ",
  "amount":            "1000.00",
  "status":            "SUCCESS",
  "timestamp":         112233445566
}
```

| ฟิลด์ | คำอธิบาย |
|---|---|
| `mode` | `"WITHDRAW"` เสมอ |
| `bank` / `account_no` / `account_name` | ปลายทางที่โอน (echo) |
| `status` | `"SUCCESS"` หรือ `"FAIL"` — ถ้า `"FAIL"` ต้องคืนเงินกลับเข้า balance ของลูกค้า |
| `timestamp` | Unix seconds |

**ต้องตอบ HTTP `200`** มิฉะนั้นระบบจะ retry

### 9.3 การยืนยัน Signature ของ Callback (สำคัญ)

เซ็น/เช็คด้วย **raw HTTP body** เสมอ ห้าม encode JSON ใหม่ เพราะ key order / whitespace / unicode escape อาจต่าง

#### Node.js + Express

```js
const express = require('express');
const crypto  = require('crypto');

const app    = express();
const SECRET = 'aaaaaaaaaaaaaaaaaaaa';

// เก็บ raw body ก่อน parse JSON
app.use(express.json({
  verify: (req, _res, buf) => { req.rawBody = buf.toString(); }
}));

app.post('/callback', (req, res) => {
  const received = req.headers['x-signature'];
  const expected = crypto.createHmac('sha256', SECRET)
                         .update(req.rawBody)        // raw body — ห้าม JSON.stringify(req.body)
                         .digest('hex');
  if (!received || expected !== received) {
    return res.status(403).json({ error: 'Invalid signature' });
  }

  // idempotency: ตรวจ (merchant_id, platform_order_id) ห้ามประมวลผลซ้ำ
  // ทำงานต่อ...
  res.status(200).json({ success: true });
});

app.listen(3000);
```

#### PHP

```php
<?php
$secret   = 'aaaaaaaaaaaaaaaaaaaa';
$received = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$rawBody  = file_get_contents('php://input');         // raw — ห้าม json_encode($_POST)
$expected = hash_hmac('sha256', $rawBody, $secret);

if (!hash_equals($expected, $received)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$data = json_decode($rawBody, true);
// idempotency + ทำงานต่อ
http_response_code(200);
echo json_encode(['success' => true]);
```

#### ทำไมต้อง raw body?

```
ข้อมูลเดียวกัน byte ต่าง ⇒ HMAC ต่าง
{"merchant_id":"TH00000000","amount":"1000.00","status":"PAID"}
{"amount":"1000.00","merchant_id":"TH00000000","status":"PAID"}   ← key สลับ
{
  "merchant_id": "TH00000000",
  "amount": "1000.00",
  "status": "PAID"
}                                                                  ← ช่องว่างต่าง
```

ทั้งสามถูกต้องแบบ JSON-equal แต่ HMAC ต่างกันหมด

#### กฎ idempotency

Webhook **อาจมาซ้ำ** ตรวจ `(merchant_id, platform_order_id)` ในฐานข้อมูลก่อนเครดิต/อัปเดตทุกครั้ง วิธีง่ายสุดคือ unique index + insert-or-ignore

---

## 10. Flow แบบเต็ม

### 10.1 Deposit Flow — FLEX (แนะนำ)

```
ลูกค้า              Merchant Website          Merchant Backend          WealthWave API        WW Payment Page
   |                     |                          |                         |                    |
   |--[กรอกจำนวน]------>|                          |                         |                    |
   |                     |----[สร้าง deposit]------>|                         |                    |
   |                     |                          |--[POST /payment-flex]-->|                    |
   |                     |                          |<--[payment_url]---------|                    |
   |                     |<---[payment_url]---------|                         |                    |
   |--[เปิด payment_url]-|--------------------------|-------------------------|------------------->|
   |<--[วิธีใช้งาน + QR หรือเลขบัญชี + countdown]-----------------------------|--------------------|
   |--[โอน + แจ้งสลิปบนหน้าชำระเงิน]------------------------------------------|------------------->|
   |                     |                          |                    [ยืนยันการรับเงิน]        |
   |                     |                          |<--[Webhook Callback]----|                    |
   |                     |                    [เครดิตยอด]                    |                    |
   |                     |                          |---[HTTP 200 OK]-------->|                    |
   |<--[Redirect ไป redirect_url]--------------------------------------------------------------------|
```

1. ลูกค้ากดฝากบนเว็บ
2. Backend ของ merchant เซ็น payload แล้ว `POST /payment-flex/create`
3. WealthWave เลือกช่องทางที่ดีที่สุดจากทุกช่องทางที่มี แล้วคืน `payment_url`
4. Merchant เปิด `payment_url` ให้ลูกค้า — ไม่ต้อง render อะไรเองทั้งสิ้น
5. หน้าชำระเงินพาลูกค้าทำทุกอย่าง: วิธีใช้งาน, QR Code หรือบัญชีปลายทาง, ยอด `transfer_amount` เป๊ะ, countdown, แจ้งสลิป, สถานะ real-time
6. Webhook (`mode: PAYMENT`, `status: PAID`) ไปที่ `notify_url`
7. Merchant เครดิตให้ลูกค้า (idempotent) ตอบ HTTP `200`
8. หน้าชำระเงินพาลูกค้ากลับ `redirect_url` (ถ้าระบุ)

### 10.2 Deposit Flow — QR V2 (จะเลิกใช้เร็ว ๆ นี้)

> ⚠️ Flow นี้เป็นของ `/paymentv2/create` ซึ่ง**จะถูกยกเลิกในเร็ว ๆ นี้** — ใช้ FLEX (§10.1) แทน

```
ลูกค้า              Merchant Website          Merchant Backend          WealthWave API
   |                     |                          |                         |
   |--[กรอกจำนวน]------>|                          |                         |
   |                     |----[สร้าง deposit]------>|                         |
   |                     |                          |--[POST /paymentv2]----->|
   |                     |                          |<--[QR Code Response]----|
   |                     |<---[หน้า QR]-------------|                         |
   |<--[โชว์ QR]---------|                          |                         |
   |--[สแกน + จ่าย]-----|--------------------------|------------------------>|
   |                     |                          |                    [ยืนยันการรับเงิน]
   |                     |                          |<--[Webhook Callback]----|
   |                     |                    [เครดิตยอด]                    |
   |                     |                          |---[HTTP 200 OK]-------->|
   |<--[หน้า success]----|<---[Update UI]-----------|                         |
```

1. ลูกค้ากดฝากบนเว็บ
2. Backend ของ merchant เซ็น payload แล้ว `POST /paymentv2/create`
3. WealthWave validate + สร้าง QR พร้อม `transfer_amount` (มีสตางค์) และ `expire_datetime` (~10 นาที)
4. Merchant แสดง QR (`qrbase64`), `transfer_amount`, เวลาหมดอายุ และเตือนเรื่องบัญชีต้นทาง
5. ลูกค้าโอนจาก **บัญชีที่ลงทะเบียน** ยอด **เป๊ะ** ก่อน `expire_datetime`
6. WealthWave จับคู่การโอนกับออเดอร์
7. Webhook (`mode: PAYMENT`, `status: PAID`) ไปที่ `notify_url`
8. Merchant เครดิตให้ลูกค้า (idempotent) ตอบ HTTP `200`

**สำคัญ**: ลูกค้าต้องโอน `transfer_amount` ตรงเป๊ะ จากบัญชีที่ลงทะเบียน ก่อนหมดอายุ มิฉะนั้นเงินอาจ match ออเดอร์อื่น **WealthWave คืนให้ไม่ได้**

### 10.3 Withdraw Flow (8 ขั้น)

```
ลูกค้า              Merchant Website          Merchant Backend          WealthWave API
   |--[ขอถอน]---------->|                          |                         |
   |                     |---[Validate]------------>|                         |
   |                     |                    [ตัดยอดลูกค้าทันที]            |
   |                     |                          |---[POST /withdraw]----->|
   |                     |                          |<---[Pending response]---|
   |                     |<--[หน้า pending]---------|                         |
   |<--[สถานะ pending]---|                          |                         |
   |     ...รอ...                                   |                    [Process / โอนผ่านธนาคาร]
   |                     |                          |<---[Webhook callback]---|
   |                     |   [Mark final / คืนยอดถ้า FAIL]                    |
   |                     |                          |---[HTTP 200 OK]-------->|
   |<--[สถานะสุดท้าย]----|<--[แจ้งลูกค้า]----------|                         |
```

1. ลูกค้ากดถอน
2. Merchant validate balance / limit / KYC
3. Merchant **ตัดยอด available ของลูกค้าทันที** (ย้ายเป็น pending) กันเบิกซ้ำ
4. `POST /withdraw/create` พร้อมข้อมูลบัญชีปลายทางครบ
5. WealthWave รับเรื่อง (status `pending`) → ตอบ `200`
6. WealthWave ประมวลผล (queue → fraud check → bank transfer)
7. Webhook คืนผล `SUCCESS` / `FAIL`
8. Merchant: ถ้า `SUCCESS` mark complete, ถ้า `FAIL` **คืนยอดกลับเข้า available** + แจ้งลูกค้า

ถ้า webhook ไม่มาภายใน 30–60 นาที ให้ poll `/withdraw/query` (มี backoff) หรือเปิด ticket support

---

## 11. State Machine สถานะออเดอร์

### Payment (`/payment/query.status`)

```
              ┌────────────────────────────────────────┐
              ▼                                        │
   open ─────► settled_paid (terminal, จ่ายแล้ว)       │
     │                                                 │
     ├──────► error          (terminal, ล้มเหลว/หมดอายุ)│
     ├──────► freeze         (admin lock — ปลดได้) ────┘
     └──────► unsettled_paid (จ่ายแล้วแต่ยังรอกระทบยอด)
```

| ค่า | ความหมาย |
|---|---|
| `open` | สร้างแล้ว รอจ่าย |
| `settled_paid` | จ่ายและเครดิตแล้ว **terminal** |
| `unsettled_paid` | จ่ายแล้วแต่ยังไม่ได้กระทบ (manual / slip) |
| `error` | ล้มเหลว / หมดอายุ **terminal** |
| `cancelled` | ลูกค้ากดยกเลิกบนหน้าชำระเงิน (FLEX ก่อนโอน) — ส่ง callback `CANCELLED` ในบางกรณีอาจถูกชำระสำเร็จภายหลังได้ (แล้วจะมี callback `PAID` ตามมา ซึ่งถูกต้อง) มิฉะนั้นสร้าง order ใหม่เมื่อจะลองอีกครั้ง |
| `freeze` | admin hold |

### Withdraw (`/withdraw/query.status`)

```
   open ────► success  (terminal, เงินถึงบัญชี)
     │
     └─────► failed   (terminal — ต้องคืน balance ลูกค้า)
```

(บางครั้ง response ตอน create ใช้คำว่า `pending` — query จะ normalize เป็น `open`)

---

## 12. ตัวอย่างโค้ด

ทุกตัวอย่างใช้ base URL `https://api-th.wealthwave.tech` กับ test credential ที่ §3 — เปลี่ยนก่อนใช้จริง

> ⭐ **ฝากเงินให้ใช้ FLEX** ตัวอย่าง PHP ด้านล่างเป็น `/payment-flex/create` (แนะนำ) ส่วนภาษาอื่นเป็น `/paymentv2/create` (จะเลิกใช้เร็ว ๆ นี้) — แปลงเป็น FLEX ได้โดยเปลี่ยน path เป็น `/payment-flex/create`, เพิ่ม `redirect_url` / `payment_theme` (optional) แล้วเปิด `data.payment_url` ให้ลูกค้าแทนการ render QR

### 12.1 PHP — helper + balance + payment-flex

```php
<?php
define('API_URL',     'https://api-th.wealthwave.tech');
define('SECRET',      'secretkeysecretkeysecretkeysecretkeysecretkeysecretkey');
define('MERCHANT_ID', 'AA12345678');
define('TOKEN',       'testtokentesttokentesttokentesttokentesttoken');

function ww_call(string $path, array $data) {
    $body      = json_encode($data, JSON_UNESCAPED_UNICODE);
    $signature = hash_hmac('sha256', $body, SECRET);
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Signature: ' . $signature,
        ],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($res, true)];
}

[$code, $bal] = ww_call('/balance', [
    'merchant_id' => MERCHANT_ID, 'token' => TOKEN, 'time' => time(),
]);
print_r($bal);

// สร้างคำสั่งฝาก (FLEX — แนะนำ): ได้ payment_url ของหน้าชำระเงิน
[$code, $payment] = ww_call('/payment-flex/create', [
    'merchant_id'       => MERCHANT_ID,
    'token'             => TOKEN,
    'time'              => time(),
    'merchant_order_id' => 'ORDER' . time(),
    'amount'            => '1000.00',
    'bank'              => 'KBANK',
    'account_name'      => 'สมชาย ใสสว่าง',
    'account_no'        => '1234567890',
    'notify_url'        => 'https://merchant.com/callback/payment',
    'redirect_url'      => 'https://merchant.com/return',   // optional
    'payment_theme'     => 'halo',                          // optional
]);
if ($code === 200) {
    // พาลูกค้าไปหน้านี้ — หน้าชำระเงินจัดการ flow ทั้งหมดเอง
    header('Location: ' . $payment['data']['payment_url']);
} elseif ($code === 409) {
    // ลูกค้ามี order ค้างอยู่ — ใช้ order เดิมต่อ
    $pendingId = $payment['data']['pending_order_id'];
}
```

### 12.2 Node.js (axios)

```js
const axios  = require('axios');
const crypto = require('crypto');

const API_URL     = 'https://api-th.wealthwave.tech';
const SECRET      = 'secretkeysecretkeysecretkeysecretkeysecretkeysecretkey';
const MERCHANT_ID = 'AA12345678';
const TOKEN       = 'testtokentesttokentesttokentesttokentesttoken';

async function ww(path, payload) {
  const body      = JSON.stringify(payload);
  const signature = crypto.createHmac('sha256', SECRET).update(body).digest('hex');
  const res = await axios.post(API_URL + path, body, {
    headers: { 'Content-Type': 'application/json', 'X-Signature': signature },
    transformRequest: [d => d],
  });
  return res.data;
}

(async () => {
  console.log(await ww('/balance', {
    merchant_id: MERCHANT_ID, token: TOKEN, time: Math.floor(Date.now()/1000)
  }));

  console.log(await ww('/paymentv2/create', {
    merchant_id: MERCHANT_ID, token: TOKEN, time: Math.floor(Date.now()/1000),
    merchant_order_id: 'ORDER' + Date.now(),
    amount: '1000.00', bank: 'KBANK',
    account_name: 'สมชาย ใสสว่าง', account_no: '1234567890',
    notify_url: 'https://merchant.com/callback/payment',
  }));
})();
```

### 12.3 Python (requests)

```python
import requests, json, hmac, hashlib, time

API_URL     = 'https://api-th.wealthwave.tech'
SECRET      = 'secretkeysecretkeysecretkeysecretkeysecretkeysecretkey'
MERCHANT_ID = 'AA12345678'
TOKEN       = 'testtokentesttokentesttokentesttokentesttoken'

def ww(path, payload):
    body      = json.dumps(payload, ensure_ascii=False, separators=(',', ':'))
    signature = hmac.new(SECRET.encode(), body.encode(), hashlib.sha256).hexdigest()
    return requests.post(
        API_URL + path,
        data=body.encode('utf-8'),
        headers={'Content-Type': 'application/json', 'X-Signature': signature},
    ).json()

print(ww('/balance', {'merchant_id': MERCHANT_ID, 'token': TOKEN, 'time': int(time.time())}))

print(ww('/paymentv2/create', {
    'merchant_id': MERCHANT_ID, 'token': TOKEN, 'time': int(time.time()),
    'merchant_order_id': f'ORDER{int(time.time())}',
    'amount': '1000.00', 'bank': 'KBANK',
    'account_name': 'สมชาย ใสสว่าง', 'account_no': '1234567890',
    'notify_url': 'https://merchant.com/callback/payment',
}))
```

### 12.4 Java / C# / Go

ดูตัวอย่างเต็มในไฟล์ภาษาอังกฤษ [`llms-full.md`](./llms-full.md#12-code-examples-php-node-python-java-c-go) — รหัสเหมือนกัน เปลี่ยนเฉพาะ comment เป็นภาษาไทยได้

---

## 13. Postman Pre-request script

ใส่ใน **Pre-request Script** ของ collection / request เพื่อเซ็นอัตโนมัติ:

```js
var secret_key = 'YOUR_SECRET_KEY_HERE';
var signBytes  = CryptoJS.HmacSHA256(pm.request.body.raw, secret_key);
var signHex    = CryptoJS.enc.Hex.stringify(signBytes);
pm.request.headers.upsert({ key: 'X-Signature', value: signHex });
```

จากนั้นใช้ **Body → raw → JSON** Postman จะเซ็นให้ทุกครั้งที่ส่ง

---

## 14. ข้อจำกัด / จุดต้องระวัง

- **ฝากเงินให้ใช้ FLEX** — `/payment/create`, `/paymentv2/create`, `/payment-transfer/create` **จะถูกยกเลิกในเร็ว ๆ นี้**
- **FLEX pending-order guard:** ลูกค้าหนึ่งบัญชีเปิด FLEX order ค้างได้ครั้งละ 1 รายการ สร้างซ้ำจะได้ `409` พร้อม `data.pending_order_id` — ใช้ order เดิม, ยกเลิกผ่าน `/payment/cancel` (§8.11) หรือบนหน้าชำระเงิน, อย่า retry วน
- **FLEX `payment_url`:** พาลูกค้าไปหน้าชำระเงิน ห้ามดึง QR/เลขบัญชีออกมาแสดงใน UI ตัวเอง
- **ช่วง amount (payment):** ขั้นต่ำ `20.00` THB สูงสุด `49,999.00` THB (หรือ `500,000.00` สำหรับ partner id 9 / 39)
- **Rate limit ต่อ `(bank, account_no)`:** สร้าง payment ได้ 5 ครั้ง/นาที (เกิน → HTTP `429`)
- **Concurrency cap ทั้งระบบ:** ~200 ออเดอร์ in-flight → `concurrent payment channel exceeded limitation`
- **QR หมดอายุ:** ปกติ 10 นาที (`expire_datetime`) ถ้าหมดอายุ ลูกค้าต้องเริ่มใหม่
- **Decimal nudge:** ลูกค้าต้องโอน `transfer_amount` เป๊ะ (ไม่ใช่ `amount`) ถ้าผิดอาจไป match ออเดอร์ของคนอื่น **คืนไม่ได้**
- **บัญชีต้นทางต้องตรง:** ต้องโอนจาก `bank` + `account_no` ที่ส่งตอนสร้าง
- **ฟิลด์ `qrcode` (text) เลิกใช้ 14 Sep 2025** ใช้ `qrbase64` แทน
- **Webhook retry:** จนกว่าจะได้ HTTP `200` ต้องทำ idempotency บน `(merchant_id, platform_order_id)`
- **Time skew:** `time` ห่างจากเวลา server มากเกินไปจะถูก reject ตั้ง NTP ให้ดี
- **Charset:** UTF-8 — ชื่อบัญชีไทยใน `account_name` ต้องเป็น UTF-8
- **Slip upload:** อัปโหลดได้ขณะออเดอร์ `open` (ไม่ต้องรอ) รูป ≤ 1 MB JPEG/PNG เท่านั้น
- **Withdraw timing:** ปกติ 5–30 นาทีในเวลาธนาคาร นอกเวลาจะรอวันทำการถัดไป
- **`deposit_account_no` ของ TRANSFER เลือกใหม่ทุก request:** ห้าม cache

---

## 15. Checklist สำหรับ Integration ที่ดี

- [ ] ใช้ **`/payment-flex/create`** สำหรับการฝากทั้งหมด — พาลูกค้าไป `data.payment_url` แล้วให้หน้าชำระเงินจัดการที่เหลือ
- [ ] จัดการ response `409` ของ FLEX โดยใช้ `data.pending_order_id` เดิม (หรือยกเลิกออเดอร์ที่ค้างผ่าน `/payment/cancel`) แทนการ retry
- [ ] เก็บ **raw body** ก่อนเซ็น ห้าม encode ใหม่หลัง parse
- [ ] เช็ค `X-Signature` ของ webhook กับ **raw body** ที่เข้ามา ใช้ `hash_equals` เปรียบเทียบ
- [ ] Webhook handler ต้อง **idempotent** บน `(merchant_id, platform_order_id)`
- [ ] **อย่าเชื่อ** เฉพาะ source IP ของ webhook ต้องเช็ค signature เท่านั้น
- [ ] บันทึก `platform_order_id` จาก response ทันที ก่อนโชว์ QR ให้ลูกค้า
- [ ] โชว์ **`transfer_amount`** (ไม่ใช่ `amount`) และ **`expire_datetime`** เป็น countdown
- [ ] ถอน: ตัดยอดลูกค้าทันทีที่สร้าง ถ้า callback `FAIL` ต้องคืน
- [ ] มี **fallback poll** `/payment/query` และ `/withdraw/query` สำหรับออเดอร์ค้างเกิน 1 ชั่วโมง
- [ ] หุ้ม HTTP call ด้วย circuit breaker แยก `429` กับ `500 service error. no available channel.` ออกจากกัน
- [ ] Log `platform_order_id`, `merchant_order_id`, hash ของ body ทุก outgoing call (อย่า log secret)
- [ ] Production: ตั้ง timeout outgoing 10s, webhook handler 5–60s
- [ ] HTTPS ทุกที่ รวมถึง `notify_url` — ปฏิเสธ webhook ที่มา plain HTTP
- [ ] เก็บ `secret_key` ใน secret store (env var, vault) เปลี่ยนผ่าน account manager

---

## 16. การฝังหน้าชำระเงินผ่าน iframe

หน้าชำระเงิน (`payment_url` ที่ได้จาก `/payment-flex/create`) ฝังลงเว็บไซต์ร้านค้าได้ด้วย
`<iframe>` ทำตามกติกาด้านล่างเพื่อให้ปุ่ม **"คัดลอก"** (เลขบัญชี / จำนวนเงิน / รหัสอ้างอิง) ใช้งานได้

**สาเหตุที่เจอบ่อยที่สุด:** หน้าร้านค้าที่ฝัง iframe เป็น **HTTP** เบราว์เซอร์จะถือว่า iframe ทั้งอันเป็น
**non-secure context** แล้วปิด Clipboard API → ปุ่ม "คัดลอก" ใช้ไม่ได้

**โค้ดฝังที่ถูกต้องขั้นต่ำ:**

```html
<iframe
  src="https://payment.gateway-service.net/<ORDER_ID>/<HMAC>"
  allow="clipboard-write"
  title="ชำระเงิน"
  style="width:100%; max-width:480px; height:860px; border:0; border-radius:12px;">
</iframe>
```

กติกาสำคัญ:

1. **หน้าที่ฝัง iframe ต้องเป็น HTTPS** ตามมาตรฐาน Secure Contexts iframe จะ "ปลอดภัย" ก็ต่อเมื่อ
   ทั้งตัวมันเองและหน้าแม่เป็น HTTPS ทุกชั้น หน้าชำระเงินเป็น HTTPS อยู่แล้ว ถ้าหน้าแม่เป็น HTTP
   iframe ทั้งอันกลายเป็น non-secure → `navigator.clipboard` (และกล้อง/ตำแหน่ง) ถูกปิด
   (หน้าแม่ HTTPS ก็ฝัง iframe ที่เป็น HTTP ไม่ได้เช่นกัน — โดน block เป็น mixed content — แต่ของเรา
   เป็น HTTPS อยู่แล้วจึงไม่มีปัญหานี้)
2. **ใส่ `allow="clipboard-write"`** การเขียนคลิปบอร์ดจาก iframe ข้ามโดเมนถูกคุมด้วย Permissions
   Policy หน้าแม่ต้องมอบสิทธิ์ให้ ต้องการแค่ `clipboard-write` (ไม่ต้อง `clipboard-read`) หน้าชำระเงิน
   มี fallback การคัดลอกไว้กรณี Clipboard API ถูกปิด แต่ข้อ 1–2 คือวิธีที่เสถียรที่สุด
3. **ขนาด / auto-fit** หน้าออกแบบ mobile-first (อ้างอิง ~480px) และ **ตรวจจับว่าถูกฝังใน iframe แล้ว
   ซูมเนื้อหาให้พอดีความกว้างกรอบอัตโนมัติ** — ไม่ต้องตั้งค่าจากฝั่งร้านค้า:
   - ≤ 480px → มือถือปกติ เต็มกว้าง (ไม่ซูม)
   - 480–620px → ซูมเนื้อหาขึ้นเต็มกรอบ (ตัวใหญ่ขึ้น ไม่มีขอบว่าง)
   - 620–1024px → คงซูมไว้เท่าระดับ 620px อยู่กึ่งกลาง มีขอบเล็กน้อย
   - ≥ 1024px → สลับเป็นเลย์เอาต์เดสก์ท็อป 2 คอลัมน์
   เมื่อซูมขึ้น เนื้อหาจะสูงขึ้นตามด้วย ถ้าขยายกรอบให้กว้างขึ้นต้องเพิ่ม `height` ตาม
   (≈ `ความสูงที่ 480px × ความกว้าง ÷ 480` เช่น 480px→~860px, 600px→~1075px) หน้าปรับ **ความกว้าง**
   ให้อัตโนมัติ แต่ **ไม่** ส่งสัญญาณปรับ **ความสูง** (auto-resize) กลับหน้าแม่ จึงต้องตั้ง `height`
   ให้พอ หรือเต็มจอบนมือถือ
4. **เลี่ยง `sandbox`** ถ้าไม่จำเป็น ถ้าต้องใช้ ให้ใส่ครบ:
   `allow-scripts allow-same-origin allow-forms allow-popups allow-downloads allow-top-navigation-by-user-activation`
   โดย `allow-same-origin` (WebSocket/คลิปบอร์ด/การจำค่า) และ `allow-scripts` จำเป็น มิเช่นนั้นหน้า
   ทำงานไม่ได้เลย
5. **ปุ่ม "กลับสู่หน้าร้านค้า"** จะพาไป `redirect_url` *ภายในกรอบ iframe* ดังนั้นถ้า `redirect_url`
   ชี้ไปหน้าร้านค้าปกติ มันจะโหลดในกรอบเล็ก ๆ แนะนำ (A — แนะนำ) ให้ `redirect_url` ชี้ไปหน้า break-out
   เล็ก ๆ ที่สั่ง `(window.top || window).location.replace(ปลายทางจริง)` หรือ (B) ทำให้หน้าปลายทาง
   ฝังใน iframe ได้ (ไม่ตั้ง `X-Frame-Options: DENY` / CSP `frame-ancestors` ที่บล็อก) วิธี A ต้องมี
   `allow-top-navigation-by-user-activation` ถ้าใช้ `sandbox`
6. **ทางเลือกที่เสถียรที่สุด:** redirect ทั้งหน้าไปหน้าชำระเงิน (แล้ว `redirect_url` กลับ) หรือเปิดแท็บใหม่
   ทั้งสองวิธีไม่ติดข้อจำกัด secure context / clipboard / third-party cookie ของ iframe (เช่น Safari,
   3DS ของธนาคาร, ป๊อปอัป)

> คู่มือฉบับเต็มสำหรับร้านค้า (พร้อม checklist การทดสอบ): เอกสารแบบเว็บ →
> <https://doc-th.wealthwave.tech/?page=iframe_embedding&lang=th>

---

### ข้อมูลเอกสาร

- **API version:** 1.0
- **ไฟล์นี้:** `llms-full-th.md` — เอกสารไฟล์เดียวสำหรับ AI coding agent (ภาษาไทย)
- **เอกสารแบบเว็บ:** <https://doc-th.wealthwave.tech/?lang=th>
- **ภาษาอังกฤษ:** [`llms-full.md`](./llms-full.md)
- **อัปเดต:** 2026-06-06
