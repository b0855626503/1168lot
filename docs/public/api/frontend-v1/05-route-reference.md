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
    "header_code": "<script>window.analytics=true;</script>"
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
- หมายเหตุ: `bank_pic` และ `qr_pic` เป็น public URL สำหรับแสดงรูปธนาคารและรูป QR ไม่ใช่ path ใน filesystem

### `POST /api/v1/deposit/loadbank/random`
- คำอธิบาย: ดึงบัญชีธนาคารปลายทางแบบสุ่ม 1 รายการจากรายการที่เปิดใช้งาน
- ใช้เมื่อ: ต้องการให้ลูกค้าเห็นบัญชีรับโอนเพียงบัญชีเดียวต่อครั้ง
- Request example: `{ "method": "bank" }`
- Response shape: `{ "success": true, "bank": BankAccount }`
- Empty response: `{ "success": false, "bank": "" }`
- Request `method` รองรับ `bank`, `tw`, `slip`

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
- ดูรายละเอียดที่ [05-route-reference-wheel-reward.md](./05-route-reference-wheel-reward.md)

### Wheel / Reward / Common Errors (Moved)

เนื้อหาส่วนนี้ถูกแยกออกไปที่:

- [05-route-reference-wheel-reward.md](./05-route-reference-wheel-reward.md)
