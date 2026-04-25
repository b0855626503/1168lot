# Frontend API V1 - Endpoints

อัปเดตล่าสุด: 2026-04-25

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

## Contract Notes

- `POST /api/v1/auth/login` ออก active token ล่าสุดได้ครั้งละ 1 ตัวต่อ member; token เดิมของ member เดียวกันจะใช้ต่อไม่ได้หลัง login ใหม่
- `POST /api/v1/deposit/loadbank` และ `/deposit/loadbank/random` ส่ง `qr_pic` เป็น `""` เมื่อบัญชีไม่มีรูป QR ที่อัปโหลดไว้
