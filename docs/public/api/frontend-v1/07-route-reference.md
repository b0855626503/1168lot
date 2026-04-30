# Frontend API V1 - Route Reference (Source of Truth)

อัปเดตล่าสุด: 2026-04-30

ไฟล์นี้เป็น source of truth ของ route ทั้งหมดใน `packages/Gametech/FrontendApi/src/Routes/api.php`

กติกา:
- endpoint ใหม่ต้องเพิ่มในไฟล์นี้ก่อน
- ใช้ placeholder path params (`{id}`) ไม่ใช้ค่า hardcode ใน list
- request/response example แบบละเอียดอ้างอิง:
  - `05-route-reference.md`
  - `05-route-reference-wheel-reward.md`
  - `03-endpoints.md` (Yeekee detail)

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

### Auth / Member / Realtime

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

### Wallet / Coupon / Deposit / Payment

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
- `POST /api/v1/deeppay/deposit/expire/{txid}`
- `POST /api/v1/deeppay/deposit/create`
- `GET /api/v1/deeppay/qrcode/{id}`

### Promotion / Game

- `GET /api/v1/promotion/list`
- `POST /api/v1/promotion/select`
- `POST /api/v1/promotion/deselect`
- `POST /api/v1/games/login`
- `GET /api/v1/games/login/{game}/{code}`

### Lotto

- `POST /api/v1/lotto/bet`
- `GET /api/v1/lotto/groups/{groupId}/packages`
- `POST /api/v1/lotto/groups/{groupId}/select-package`
- `GET /api/v1/lotto/groups/{groupId}/selected-package`
- `GET /api/v1/lotto/tickets`
- `GET /api/v1/lotto/tickets/{id}`
- `POST /api/v1/lotto/tickets/{id}/cancel`

### Wheel / Reward

- `GET /api/v1/wheel/list`
- `POST /api/v1/wheel/spin`
- `GET /api/v1/wheel/history`
- `GET /api/v1/reward/list`
- `POST /api/v1/reward/redeem`
- `GET /api/v1/reward/history`

### Yeekee

- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`
