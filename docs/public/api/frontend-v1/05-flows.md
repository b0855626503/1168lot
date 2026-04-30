# Frontend API V1 - Flows

อัปเดตล่าสุด: 2026-04-30

## Register/Login Flow

1. `POST /api/v1/auth/register` สมัครสมาชิก
2. `POST /api/v1/auth/login` รับ access token
3. `GET /api/v1/member/profile` โหลดโปรไฟล์
4. `GET /api/v1/member/balance` โหลดยอดคงเหลือ

## Deposit Flow

1. `POST /api/v1/auth/login` รับ token
2. `GET /api/v1/deposit/channels` ดูช่องทางเติมเงิน
3. `POST /api/v1/deposit/loadbank` หรือ `POST /api/v1/deposit/loadbank/random` ขอข้อมูลบัญชีรับโอน
4. `POST /api/v1/smkpay/deposit/create` (หรือ `POST /api/v1/deeppay/deposit/create`) สร้างคำขอฝาก
5. `GET /api/v1/smkpay/deposit/status/{txid}` ตรวจสถานะ
6. `GET /api/v1/member/balance` เช็กยอดหลังเติม

## Lotto Bet Flow

1. `GET /api/v1/lotto/markets/latest` เลือกตลาดที่เปิด
2. `GET /api/v1/lotto/draws?market_id={id}` ดึงงวด
3. `GET /api/v1/lotto/markets/{marketId}/betting-context` ดึงเงื่อนไขเดิมพัน
4. `POST /api/v1/lotto/bet` ส่งบิลเดิมพัน
5. `GET /api/v1/lotto/tickets` ตรวจรายการโพย
6. `GET /api/v1/lotto/tickets/{id}` ดูรายละเอียดโพย

## Withdraw Flow

1. `POST /api/v1/auth/login` รับ token
2. `GET /api/v1/member/balance` ตรวจยอดก่อนถอน
3. `POST /api/v1/wallet/withdraw` สร้างคำขอถอน
4. `GET /api/v1/wallet/transactions` ติดตามสถานะรายการ

## Yeekee Flow

1. `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round` ดึงรอบปัจจุบัน
2. `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot` ยิงเลข 5 หลัก
3. `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots` ดูลำดับยิงล่าสุด
4. `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status` ตรวจสิทธิ์รางวัล
5. `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof` ตรวจ proof ก่อน/หลัง reveal
