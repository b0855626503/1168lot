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
- `GET /api/v1/lotto/yeekee/markets/{marketId}/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`

## Contract Notes

- `GET /api/v1/meta/site` ส่ง `deposit_min` จาก `configs.deposit_min`
- `GET /api/v1/member/profile` ส่ง `profile.deposit_min` จาก `configs.deposit_min`
- `POST /api/v1/auth/login` ออก active token ล่าสุดได้ครั้งละ 1 ตัวต่อ member; token เดิมของ member เดียวกันจะใช้ต่อไม่ได้หลัง login ใหม่
- `POST /api/v1/deposit/loadbank` และ `/deposit/loadbank/random` ส่ง `qr_pic` เป็น `""` เมื่อบัญชีไม่มีรูป QR ที่อัปโหลดไว้
- `deposit_min` ของ `/deposit/loadbank` และ `/deposit/loadbank/random` ใช้ `bank_account.deposit_min` ก่อน ถ้าเป็น `0` จึง fallback ไป `configs.deposit_min`; ถ้าทั้งคู่เป็น `0` ส่ง `0`

## Yeekee API

### Current Runtime Contract
ส่วนนี้คือ endpoint ที่ active อยู่จริงในระบบปัจจุบัน

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

### `GET /api/v1/lotto/yeekee/markets/{marketId}/rounds`
- คำอธิบาย: ดึงรอบยี่กี่ทั้งหมดของ market ตามวันที่ระบุ เพื่อใช้แสดงรายการรอบให้สมาชิกเลือกเล่น
- ใช้เมื่อ: frontend ต้องแสดงรอบทั้งวันของตลาดยี่กี่ และสถานะว่าแต่ละรอบยังเปิดรับเล่นอยู่หรือไม่
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

- หมายเหตุด้านสัญญา:
  - `formula_label` ต้องสะท้อน runtime preset จริงจากระบบปัจจุบัน
  - ห้ามใช้ `PRECOMMITTED_BASE64_MD5` เป็น canonical label ถ้า runtime ไม่ได้ใช้
  - ข้อนี้เป็น doc correction only ใน PR-01 และจะ lock รายละเอียด result-proof อีกครั้งใน PR-05

### Target Contract (Planned)
ส่วนนี้เป็น target contract สำหรับ Yeekee hardening เท่านั้น

Status: `Target Contract / Planned`  
Implemented in: `PR-04`  
Do not treat as active runtime endpoint until `PR-04` is merged.

- `GET /api/v1/lotto/yeekee/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{round}`
- `POST /api/v1/lotto/yeekee/rounds/{round}/shoot`
- `GET /api/v1/lotto/yeekee/rounds/{round}/shoots`

หมายเหตุ:
- ห้ามลบหรือเขียนทับ endpoint เดิมใน current runtime contract
- PR-01 เป็น docs-only ไม่มี runtime behavior change
