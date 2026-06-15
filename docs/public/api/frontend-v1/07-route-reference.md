# Frontend API V1 - Route Reference (Source of Truth)

อัปเดตล่าสุด: 2026-06-15

เอกสารนี้เป็น source of truth ของ endpoint detail สำหรับ `/docs/api/frontend-v1`

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

<a id="get-apiv1authregisterbanks"></a>
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

<a id="post-apiv1authregisterbank-account-name"></a>
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

<a id="post-apiv1authregister"></a>
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


<a id="post-apiv1authregister-with-username"></a>
### `POST /api/v1/auth/register-with-username`
- คำอธิบาย: สมัครสมาชิกใหม่ด้วย flow ที่กำหนด username ตามนโยบายหน้าเว็บ
- ใช้เมื่อ: หน้าสมัครใช้รูปแบบ username-based register
- Auth: ไม่ต้องใช้ token
- Request example:
```json
{
  "user_name": "newuser001",
  "password": "pass1234",
  "password_confirm": "pass1234",
  "name": "New User",
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

<a id="post-apiv1authlogin"></a>
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

<a id="get-apiv1gamestypes"></a>
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

<a id="get-apiv1gamesproviderstype"></a>
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

<a id="get-apiv1gamestypeprovider"></a>
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

<a id="get-apiv1slides"></a>
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

<a id="get-apiv1metaonline-members"></a>
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

<a id="get-apiv1metacontact-channels"></a>
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

<a id="get-apiv1metasite"></a>
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

<a id="get-apiv1theme"></a>
### `GET /api/v1/theme`
- คำอธิบาย: ดึงธีมหน้าบ้านปัจจุบันสำหรับ frontend
- ใช้เมื่อ: โหลด design tokens สำหรับ render UI ฝั่งลูกค้า
- Auth: ไม่ต้องใช้ token
- Request example:
```http
GET /api/v1/theme
```
- Response example:
```json
{
  "success": true,
  "message": "ดึง Frontend Theme สำเร็จ",
  "data": {
    "preset_key": "midnight",
    "preset_name": "Midnight",
    "is_customized": false,
    "version": 1,
    "tokens": {
      "surface-subtle": "#1e293b",
      "surface-card": "#0f172a",
      "surface-page": "#020617",
      "surface-navbar": "rgba(15,23,42,0.92)",
      "surface-highlight": "#f59e0b",
      "brand-primary": "#fbbf24",
      "brand-primary-hover": "#fcd34d",
      "text-strong": "#f8fafc",
      "text-default": "#cbd5e1",
      "text-muted": "#94a3b8",
      "border-default": "rgba(241,245,249,0.12)",
      "status-error": "#f87171",
      "status-success": "#34d399",
      "status-warning": "#fbbf24"
    },
    "updated_at": "2026-05-09T14:00:00+07:00"
  }
}
```

<a id="get-apiv1realtimeconfig"></a>
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

<a id="get-apiv1lottodraws"></a>
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

<a id="get-apiv1lottodrawsid"></a>
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

<a id="get-apiv1lottomarketslatest"></a>
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

<a id="get-apiv1lottomarketsmarketidbetting-context"></a>
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

<a id="get-apiv1lottomarketsmarketidresults"></a>
### `GET /api/v1/lotto/markets/{marketId}/results`
- คำอธิบาย: ดึงผลรางวัลย้อนหลังตามตลาด อ่านจาก `lotto_result_archive_legacy_results` (snapshot) เสริมด้วยข้อมูลจาก `lotto_markets` + `lottery_groups`
- ใช้เมื่อ: แสดงประวัติผลรางวัลของตลาดที่ผู้ใช้เลือก
- Auth: ไม่ต้องใช้ token
- Path params:
  - `marketId` เช่น `1`
- Query params:
  - `limit` (integer, default 20, max 100)
  - `page` (integer, default 1)
- Request example:
```http
GET /api/v1/lotto/markets/1/results
GET /api/v1/lotto/markets/1/results?limit=10&page=1
```
- Response example:
```json
{
  "success": true,
  "data": {
    "market": {
      "id": 1,
      "name": "หวยออมสิน",
      "group_id": 1,
      "group_name": "หวยไทย",
      "logo": "...",
      "icon": "..."
    },
    "latest_result": {
      "draw_id": 100,
      "draw_date": "2026-05-13",
      "result_at": "2026-05-13 16:00:00",
      "status": "resulted",
      "result_number": {"first_prize": "0860959", "last_2_digits": "59"},
      "first_prize": "0860959",
      "last_2_digits": "59",
      "result_top_3": "",
      "result_top_2": "",
      "result_bottom_2": "59"
    },
    "history": [ /* array of same shape */ ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "count": 20,
      "total": 104,
      "has_more": true
    },
    "language": "th"
  },
  "message": "ดึงผลย้อนหลังสำเร็จ"
}
```

<a id="get-apiv1lottomarketsmarketiddrawsdrawidresult"></a>
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

<a id="get-apiv1lottoresultsby-date"></a>
### `GET /api/v1/lotto/results/by-date`
- คำอธิบาย: ดึงผลหวยรวมตามวันที่ระบุ อ่านจาก `lotto_result_archive_legacy_results` (snapshot + mirror จาก `lotto_draws`) เสริมด้วย market/group info
- ใช้เมื่อ: ทำหน้าค้นหาผลหวยตามวันหรือหน้าสรุปประจำวัน
- Auth: ไม่ต้องใช้ token
- Query params:
  - `date` หรือ `draw_date` (YYYY-MM-DD, required)
- Query example:
```http
GET /api/v1/lotto/results/by-date?date=2026-05-13
GET /api/v1/lotto/results/by-date?draw_date=2026-05-13
```
- Response example:
```json
{
  "success": true,
  "data": {
    "draw_date": "2026-05-13",
    "groups": [
      {
        "group_id": 1,
        "group_code": "thai",
        "group_name": "หวยไทย",
        "markets": [
          {
            "market_id": 1,
            "market_name": "ฮานอยพิเศษ",
            "market_logo": "https://api.1168lot.com/storage/lotto/media/...png",
            "market_icon": "",
            "result": {
              "draw_id": 100,
              "draw_date": "2026-05-13",
              "result_at": "2026-05-13 17:30:00",
              "status": "resulted",
              "result_number": {"first_prize": "35037", "last_2_digits": "51", "top_3": "037", "top_2": "37", "bottom_2": "51"},
              "result_top_3": "037",
              "result_top_2": "37",
              "result_bottom_2": "51",
              "first_prize": "35037",
              "last_2_digits": "51"
            }
          }
        ]
      }
    ],
    "summary": {"group_count": 3, "market_count": 61, "result_count": 61},
    "language": "th"
  },
  "message": "ดึงผลรางวัลตามวันที่สำเร็จ"
}
```


### `GET /api/v1/lotto/result-archive-legacy`
- คำอธิบาย: ดึงผลหวยย้อนหลังจาก snapshot `lotto_result_archive_legacy_results` ในรูปแบบ flat-list (legacy)
- ใช้เมื่อ: ต้องการ query แบบช่วงวันที่ หรือ legacy frontend compatibility
- Auth: ไม่ต้องใช้ token
- Query params:
  - `type` (string, optional) — external API type code เช่น `xsthm`, `gsb`; alias เช่น `hanoi-special` จะถูก normalize โดยอัตโนมัติ
  - `date` (YYYY-MM-DD, optional) — วันเดียว ห้ามใช้ร่วมกับ from_date/to_date
  - `from_date` + `to_date` (YYYY-MM-DD, optional) — ช่วงวันที่ ต้องคู่กัน
  - `page` (integer, default 1)
  - `per_page` (integer, default 100, max 500)
- Request example:
```
```json
{
  "type": "egx30",
  "nameTH": "หุ้นอียิปต์",
  "date": "2026-04-01..2026-04-30",
  "page": 1,
  "count": 1,
  "results": [
    {
      "id": 100,
      "lottosName": "egx30",
      "lottosTH": "หุ้นอียิปต์",
      "lottosDate": "2026-04-22",
      "lottosTime": "",
      "lottosNumber": "785",
      "lottosUnder": "71"
    }
  ],
  "errors": []
}
```
- หมายเหตุ: อ่านจาก `lotto_result_archive_legacy_results` เป็นหลัก เติม market/group info จาก `lotto_markets` และ `lottery_groups` ผ่าน `LotteryRelayTypeRegistry` ไม่ query `lotto_draws`, ไม่รวมหวยยี่กี

<a id="get-apiv1lottonavbar-config-old"></a>
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

<a id="post-apiv1authlogout"></a>
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

<a id="get-apiv1memberprofile"></a>
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

<a id="get-apiv1memberbalance"></a>
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

<a id="get-apiv1memberloadbalance"></a>
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

<a id="post-apiv1memberchange-password"></a>
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

<a id="post-apiv1memberwallet-address"></a>
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

<a id="get-apiv1membercontributor"></a>
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

<a id="get-apiv1memberhistory"></a>
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

<a id="get-apiv1memberhistorytype"></a>
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

<a id="get-apiv1memberrealtime-context"></a>
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

<a id="post-apiv1memberheartbeat"></a>
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

<a id="post-apiv1realtimeauth"></a>
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

<a id="post-apiv1walletwithdraw"></a>
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

<a id="post-apiv1walletclaim"></a>
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

<a id="get-apiv1wallettransactions"></a>
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

<a id="post-apiv1couponredeem"></a>
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

<a id="get-apiv1couponmy"></a>
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

<a id="post-apiv1couponmycodeclaim"></a>
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

<a id="get-apiv1depositchannels"></a>
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

<a id="post-apiv1depositloadbank"></a>
### `POST /api/v1/deposit/loadbank`
- คำอธิบาย: ดึงข้อมูลบัญชีธนาคารปลายทางของระบบสำหรับฝาก
- ใช้เมื่อ: ผู้ใช้เลือกฝากผ่านธนาคารและต้องการข้อมูลบัญชีรับโอน
- Request example: `{ "method": "bank" }`
- Response shape: `{ "success": true, "bank": [BankAccount] }`
- `BankAccount` fields: `acc_no`, `acc_name`, `bank_name`, `bank_pic`, `qr_pic`, `qrcode`, `code`, `deposit_min`, `remark`
- หมายเหตุ: `bank_pic` เป็น public URL; `qr_pic` เป็น URL เมื่อมีรูปจริง; `deposit_min` ใช้ค่าบัญชีก่อน ถ้าเป็น `0` จึง fallback ไป `configs.deposit_min`

<a id="post-apiv1depositloadbankrandom"></a>
### `POST /api/v1/deposit/loadbank/random`
- คำอธิบาย: ดึงบัญชีธนาคารปลายทางแบบสุ่ม 1 รายการจากรายการที่เปิดใช้งาน
- ใช้เมื่อ: ต้องการให้ลูกค้าเห็นบัญชีรับโอนเพียงบัญชีเดียวต่อครั้ง
- Request example: `{ "method": "bank" }`
- Response shape: `{ "success": true, "bank": BankAccount }`
- Empty response: `{ "success": false, "bank": "" }`
- Request `method` รองรับ `bank`, `tw`, `slip`
- หมายเหตุ: `qr_pic` เป็น `""` เมื่อไม่มีรูป QR; `deposit_min` ใช้กติกาเดียวกับ `/deposit/loadbank`

<a id="get-apiv1smkpaydepositstatustxid"></a>
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

<a id="post-apiv1smkpaydepositexpiretxid"></a>
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

<a id="post-apiv1smkpaydepositcreate"></a>
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

<a id="get-apiv1smkpayqrcodeid"></a>
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


<a id="post-apiv1deeppaydepositexpiretxid"></a>
### `POST /api/v1/deeppay/deposit/expire/{txid}`
- คำอธิบาย: สั่งให้รายการฝาก DeepPay ที่ยังค้างอยู่หมดอายุ
- ใช้เมื่อ: ผู้ใช้ยกเลิกรายการฝากเดิมหรือหมดเวลาโอน
- Auth: ต้องใช้ token
- Path params:
  - `txid` รหัสรายการฝาก
- Request example:
```http
POST /api/v1/deeppay/deposit/expire/REQ-202604130001
```
- Response example:
```json
{
  "success": true,
  "message": "ทำรายการสำเร็จ"
}
```

<a id="post-apiv1deeppaydepositcreate"></a>
### `POST /api/v1/deeppay/deposit/create`
- คำอธิบาย: สร้างรายการฝากผ่าน DeepPay และคืนข้อมูลสำหรับชำระเงิน
- ใช้เมื่อ: ผู้ใช้เลือกฝากผ่าน DeepPay
- Auth: ต้องใช้ token
- Request example:
```json
{
  "amount": 300,
  "channel": "deeppay"
}
```
- Response example:
```json
{
  "success": true,
  "message": "สร้างรายการฝากสำเร็จ",
  "data": {
    "txid": "REQ-202604130001",
    "payment_url": "https://..."
  }
}
```

<a id="get-apiv1deeppayqrcodeid"></a>
### `GET /api/v1/deeppay/qrcode/{id}`
- คำอธิบาย: ดึงข้อมูล QR สำหรับรายการฝาก DeepPay
- ใช้เมื่อ: หน้า deposit ต้องแสดง QR code ให้ผู้ใช้สแกน
- Auth: ต้องใช้ token
- Path params:
  - `id` เช่น `REQ-202604130001`
- Request example:
```http
GET /api/v1/deeppay/qrcode/REQ-202604130001
```
- Response example:
```json
{
  "success": true,
  "message": "สำเร็จ",
  "data": {
    "txid": "REQ-202604130001",
    "qr_code": "data:image/png;base64,..."
  }
}
```

<a id="get-apiv1promotionlist"></a>
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

<a id="post-apiv1promotionselect"></a>
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

<a id="post-apiv1promotiondeselect"></a>
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

<a id="post-apiv1gameslogin"></a>
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

<a id="get-apiv1gameslogingamecode"></a>
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

<a id="post-apiv1lottobet"></a>
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

<a id="get-apiv1lottogroupsgroupidpackages"></a>
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

<a id="post-apiv1lottogroupsgroupidselect-package"></a>
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

<a id="get-apiv1lottogroupsgroupidselected-package"></a>
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

<a id="get-apiv1lottotickets"></a>
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

<a id="get-apiv1lottoticketsid"></a>
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

---

## Additional Moved Section

<a id="post-apiv1lottoticketsidcancel"></a>
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

<a id="get-apiv1wheellist"></a>
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

<a id="post-apiv1wheelspin"></a>
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

<a id="get-apiv1wheelhistory"></a>
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

<a id="get-apiv1rewardlist"></a>
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

<a id="post-apiv1rewardredeem"></a>
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

<a id="get-apiv1rewardhistory"></a>
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

<a id="post-apiv1lottoyeekeeroundsroundidshoot"></a>
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
  - `HTTP 429` + `error_code=YEEKEE_SHOOT_COOLDOWN` พร้อม fields:
    - `cooldown_seconds`
    - `remaining_cooldown_seconds`
    - `next_allowed_at`

<a id="get-apiv1lottoyeekeerounds"></a>
### `GET /api/v1/lotto/yeekee/rounds`
- คำอธิบาย: ดึงรอบยี่กี่ทั้งหมดของวันที่ระบุ (ข้ามตลาดได้ หรือเจาะตลาดเดียวผ่าน query)
- Auth: ต้องใช้ token
- Query params:
  - `draw_date` (optional, format `YYYY-MM-DD`, default = วันนี้ของ server)
  - `market_id` (optional, ถ้าระบุจะกรองเฉพาะ market นั้น)
- Response example:
```json
{
  "success": true,
  "message": "ดึงรอบยี่กี่ทั้งหมดสำเร็จ",
  "data": {
    "draw_date": "2026-04-29",
    "market_id": null,
    "count": 2,
    "items": [
      {
        "market_id": 9,
        "draw_id": 301,
        "round_id": 701,
        "round_no": 1,
        "status": "open_bet",
        "is_open_for_play": true,
        "is_final": false
      }
    ],
    "server_time": "2026-04-29 10:05:00"
  }
}
```

<a id="get-apiv1lottoyeekeeroundsroundid"></a>
### `GET /api/v1/lotto/yeekee/rounds/{roundId}`
- คำอธิบาย: ดึงรายละเอียดรอบยี่กี่รายรอบ
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Response example:
```json
{
  "success": true,
  "message": "ดึงรอบยี่กี่สำเร็จ",
  "data": {
    "market_id": 9,
    "draw_id": 311,
    "round_id": 711,
    "round_no": 11,
    "status": "open_bet",
    "is_open_for_play": true,
    "is_final": false
  }
}
```

<a id="get-apiv1lottoyeekeemarketsmarketidcurrent-round"></a>
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

<a id="get-apiv1lottoyeekeemarketsmarketidrounds"></a>
### `GET /api/v1/lotto/yeekee/markets/{marketId}/rounds`
- คำอธิบาย: ดึงรอบยี่กี่ทั้งหมดของวันที่ระบุใน market
- ใช้เมื่อ: หน้า frontend ต้องแสดง "ทุกรอบของวัน" ให้ลูกค้าเลือกเข้าร่วมเล่นตามรอบที่ต้องการ
- Auth: ต้องใช้ token
- Path params:
  - `marketId` = id ของตลาดหวย
- Query params:
  - `draw_date` (optional, format `YYYY-MM-DD`, default = วันนี้ของ server)
- Response example:
```json
{
  "success": true,
  "message": "ดึงรอบยี่กี่ทั้งหมดของวันที่ระบุสำเร็จ",
  "data": {
    "market_id": 9,
    "draw_date": "2026-04-29",
    "count": 2,
    "items": [
      {
        "market_id": 9,
        "draw_id": 202,
        "round_id": 601,
        "result_mode": "yeekee",
        "round_no": 1,
        "status": "open_bet",
        "bet_open_at": "2026-04-29 10:00:00",
        "bet_close_at": "2026-04-29 10:15:00",
        "shoot_open_at": "2026-04-29 10:15:00",
        "shoot_close_at": "2026-04-29 10:16:00",
        "result_compute_at": "2026-04-29 10:17:00",
        "server_time": "2026-04-29 10:05:00",
        "is_open_for_play": true,
        "is_final": false
      },
      {
        "market_id": 9,
        "draw_id": 202,
        "round_id": 602,
        "result_mode": "yeekee",
        "round_no": 2,
        "status": "open_bet",
        "bet_open_at": "2026-04-29 10:15:00",
        "bet_close_at": "2026-04-29 10:30:00",
        "shoot_open_at": "2026-04-29 10:30:00",
        "shoot_close_at": "2026-04-29 10:31:00",
        "result_compute_at": "2026-04-29 10:32:00",
        "server_time": "2026-04-29 10:05:00",
        "is_open_for_play": false,
        "is_final": false
      }
    ],
    "server_time": "2026-04-29 10:05:00"
  }
}
```
- Error example:
  - `ไม่พบหวยที่ระบุ`
  - `รายการหวยนี้ไม่รองรับการยิงเลข`
  - `กรุณาระบุ draw_date รูปแบบ YYYY-MM-DD`

<a id="get-apiv1lottoyeekeeroundsroundidshoots"></a>
### `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- คำอธิบาย: ดึงรายการยิงเลขล่าสุดในรอบ (เรียง position ล่าสุดก่อน)
- ใช้เมื่อ: หน้าแสดง feed ยิงเลขของรอบ
- Auth: ต้องใช้ token
- Path params:
  - `roundId` = id ของรอบยี่กี่
- Query params:
  - `limit` (optional, default จาก config `shoot_list_default_limit`, max จาก `shoot_list_max_limit`)
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
        "number_text": "123**",
        "number_text_masked": "123**",
        "submitted_at": "2026-04-30 12:10:01"
      },
      {
        "position": 127,
        "number_text": "543**",
        "number_text_masked": "543**",
        "submitted_at": "2026-04-30 12:09:58"
      }
    ]
  }
}
```
- หมายเหตุ:
  - `number_text` ใน endpoint นี้เป็นค่า masked (ไม่ใช่เลขเต็ม)
  - field มาตรฐานใหม่คือ `number_text_masked` และยังคงส่ง `number_text` แบบ masked เพื่อ compatibility

<a id="get-apiv1lottoyeekeeroundsroundidreward-status"></a>
### `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- คำอธิบาย: ดึงสถานะว่ารอบนี้สมาชิกที่ login อยู่ได้รับรางวัลยิงเลขหรือไม่
- ใช้เมื่อ: หน้า profile/round status ต้องแสดงสิทธิ์รางวัลยิงเลขของสมาชิกปัจจุบันเท่านั้น ไม่ใช่ endpoint ประกาศผู้ชนะทั้งรอบ
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
        "credit_amount": 20,
        "member_id": 61240,
        "member_name_prefix_masked": "085*******",
        "member_name_masked": "*******503"
      }
    ]
  }
}
```

<a id="get-apiv1lottoyeekeeroundsroundidresult-proof"></a>
### `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`
- คำอธิบาย: ดึงข้อมูลประกาศผล/หลักฐานผลยี่กี่หลังออกผล พร้อมข้อมูลรางวัลยิงเลขระดับรอบ
- ใช้เมื่อ: หน้า result/proof ต้องแสดงหลักฐานผล, วิธีคำนวณ, summary, reward policy และ winner rows แบบ public round-level
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
    "shoot_summary": {
      "shoot_sum": "0",
      "shoot_count": 0,
      "shoot_source": "snapshot"
    },
    "shoot_rewards": {
      "policy": [
        {
          "position": 1,
          "label": "รางวัลยิงเลขลำดับที่ 1",
          "credit_amount": 20
        }
      ],
      "policy_meta": {
        "source": "round_snapshot",
        "reward_enabled": true,
        "currency": "THB",
        "policy_hash": "..."
      },
      "winners": []
    },
    "winning_shoots": {
      "first": null,
      "sixteenth": null
    },
    "proof": {
      "formula_label": "<runtime_formula_preset>",
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
    "shoot_summary": {
      "shoot_sum": "164530",
      "shoot_count": 3,
      "shoot_source": "snapshot"
    },
    "shoot_rewards": {
      "policy": [
        {
          "position": 1,
          "label": "รางวัลยิงเลขลำดับที่ 1",
          "credit_amount": 20
        }
      ],
      "policy_meta": {
        "source": "round_snapshot",
        "reward_enabled": true,
        "currency": "THB",
        "policy_hash": "..."
      },
      "winners": [
        {
          "position": 1,
          "label": "รางวัลยิงเลขลำดับที่ 1",
          "credit_amount": 20,
          "member_id": 61240,
          "member_name_prefix_masked": "085*******",
          "member_name_masked": "*******503",
          "winner_credit_status": "rewarded",
          "shoot": {
            "number_text": "25095",
            "number_text_masked": "250**",
            "number_text_revealed": "25095",
            "is_number_revealed": true,
            "submitted_at": "2026-05-08 09:41:41"
          }
        }
      ]
    },
    "winning_shoots": {
      "first": {
        "position": 1,
        "number_text": "25095",
        "number_text_masked": "250**",
        "number_text_revealed": "25095",
        "is_number_revealed": true,
        "member_name_prefix_masked": "085*******",
        "member_name_masked": "*******503",
        "submitted_at": "2026-05-08 09:41:41"
      },
      "sixteenth": null
    },
    "proof": {
      "formula_label": "<runtime_formula_preset>",
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

## Yeekee Display Mode Addendum (2026-05-03)

For GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots:
- display_mode: live_masked | result_revealed
- is_number_revealed: boolean
- Use this endpoint as the paginated shoot participant list
- number_text is compatibility display field: masked before reveal, full after reveal
- New frontend should use number_text_masked and number_text_revealed as primary fields
- number_text_revealed is null before reveal and full after reveal
- Response includes shoot_sum, shoot_count, shoot_source, and pagination
- Response includes member_name_prefix_masked and member_name_masked
- Sensitive fields are never exposed: member_id, member_code, customer_id, ip_address, user_agent

For GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof:
- Includes shoot_summary even before reveal
- Includes round-level shoot_rewards.policy and shoot_rewards.winners
- shoot_rewards.policy is derived from the actual reward policy/config for the round or market
- shoot_rewards.winners is derived from reward winner logs and includes masked member fields only
- If reward winner logs are not created yet, winners can be derived from reward policy + shoot snapshot with winner_credit_status = pending
- For historical rounds with config snapshot but missing reward_config, policy_meta.source = snapshot_missing_reward_config and the API does not fallback to current market settings
- winning_shoots.first and winning_shoots.sixteenth are legacy aliases derived from shoot_rewards.winners
- Does not return full shoots list
- Before reveal, winner numbers remain masked and number_text_revealed = null
- If proof.result_payload is hidden by reveal rule, shoot_* fields must not appear in result_payload

For GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status:
- Use this endpoint as "my reward status" for the authenticated member
- items[] represents reward winner rows for the authenticated member only
- items[] includes member_id, member_name_prefix_masked, and member_name_masked
- Do not use this endpoint as the round-level winner announcement
