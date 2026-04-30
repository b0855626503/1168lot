# Frontend API V1 - Route Reference (Source of Truth)

อัปเดตล่าสุด: 2026-04-30

ไฟล์นี้เป็น source of truth ของ route ทั้งหมดใน `packages/Gametech/FrontendApi/src/Routes/api.php`

กติกา:
- endpoint ใหม่ต้องเพิ่มในไฟล์นี้ก่อน
- รายละเอียด request/response ตัวอย่างเต็ม ดูได้จาก:
  - `05-route-reference.md`
  - `05-route-reference-wheel-reward.md`
  - `03-endpoints.md` (Yeekee detail)

## Public Endpoints

#### `GET /api/v1/auth/register/banks`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/auth/register/bank-account-name`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/auth/register`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/auth/login`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/games/types`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/games/providers/{type}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/games/{type}/{provider}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/slides`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/meta/online-members`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/meta/contact-channels`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/meta/site`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/realtime/config`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/draws`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/draws/{id}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/markets/latest`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/markets/{marketId}/betting-context`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/markets/{marketId}/results`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/results/by-date`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/navbar-config`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

## Authenticated Endpoints

### Auth / Member / Realtime

#### `POST /api/v1/auth/logout`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/profile`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/balance`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/loadbalance`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/member/change-password`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/member/wallet-address`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/contributor`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/history`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/history/{type}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/member/realtime-context`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/member/heartbeat`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/realtime/auth`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

### Wallet / Coupon / Deposit / Payment

#### `POST /api/v1/wallet/withdraw`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/wallet/claim`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/wallet/transactions`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/coupon/redeem`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/coupon/my`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/coupon/my/{code}/claim`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/deposit/channels`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/deposit/loadbank`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/deposit/loadbank/random`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/smkpay/deposit/status/{txid}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/smkpay/deposit/expire/{txid}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/smkpay/deposit/create`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/smkpay/qrcode/{id}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/deeppay/deposit/expire/{txid}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/deeppay/deposit/create`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/deeppay/qrcode/{id}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

### Promotion / Game

#### `GET /api/v1/promotion/list`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/promotion/select`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/promotion/deselect`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/games/login`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/games/login/{game}/{code}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

### Lotto

#### `POST /api/v1/lotto/bet`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/groups/{groupId}/packages`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/lotto/groups/{groupId}/select-package`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/groups/{groupId}/selected-package`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/tickets`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/tickets/{id}`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/lotto/tickets/{id}/cancel`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

### Wheel / Reward

#### `GET /api/v1/wheel/list`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/wheel/spin`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/wheel/history`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/reward/list`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `POST /api/v1/reward/redeem`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/reward/history`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

### Yeekee

#### `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)

#### `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`
- Detail: ดูตัวอย่าง request/response จากเอกสาร route reference ฉบับเต็ม (ไฟล์ 05)
