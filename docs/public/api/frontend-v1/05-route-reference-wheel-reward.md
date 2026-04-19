# Frontend API V1 - Route Reference (Wheel, Reward, Errors)

อัปเดตล่าสุด: 2026-04-19

เอกสารนี้เป็น chapter ต่อจาก [05-route-reference.md](./05-route-reference.md)

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
