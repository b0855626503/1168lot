# Frontend API V1 - Endpoint Index

อัปเดตล่าสุด: 2026-04-30

## Quick Rule

- กด endpoint ด้านล่างเพื่อไปตำแหน่ง detail ใน `07-route-reference.md` ได้ทันที

## Core First

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/auth/register` | [Open](./07-route-reference.md#post-apiv1authregister) |
| POST | `/api/v1/auth/login` | [Open](./07-route-reference.md#post-apiv1authlogin) |
| GET | `/api/v1/lotto/draws` | [Open](./07-route-reference.md#get-apiv1lottodraws) |
| GET | `/api/v1/lotto/markets/latest` | [Open](./07-route-reference.md#get-apiv1lottomarketslatest) |
| POST | `/api/v1/auth/logout` | [Open](./07-route-reference.md#post-apiv1authlogout) |
| GET | `/api/v1/member/profile` | [Open](./07-route-reference.md#get-apiv1memberprofile) |
| GET | `/api/v1/member/balance` | [Open](./07-route-reference.md#get-apiv1memberbalance) |
| GET | `/api/v1/wallet/transactions` | [Open](./07-route-reference.md#get-apiv1wallettransactions) |
| POST | `/api/v1/lotto/bet` | [Open](./07-route-reference.md#post-apiv1lottobet) |
| GET | `/api/v1/lotto/tickets` | [Open](./07-route-reference.md#get-apiv1lottotickets) |

## Business Groups

### Public Endpoints

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/auth/register/banks` | [Open](./07-route-reference.md#get-apiv1authregisterbanks) |
| POST | `/api/v1/auth/register/bank-account-name` | [Open](./07-route-reference.md#post-apiv1authregisterbank-account-name) |
| POST | `/api/v1/auth/register` | [Open](./07-route-reference.md#post-apiv1authregister) |
| POST | `/api/v1/auth/login` | [Open](./07-route-reference.md#post-apiv1authlogin) |
| GET | `/api/v1/games/types` | [Open](./07-route-reference.md#get-apiv1gamestypes) |
| GET | `/api/v1/games/providers/{type}` | [Open](./07-route-reference.md#get-apiv1gamesproviderstype) |
| GET | `/api/v1/games/{type}/{provider}` | [Open](./07-route-reference.md#get-apiv1gamestypeprovider) |
| GET | `/api/v1/slides` | [Open](./07-route-reference.md#get-apiv1slides) |
| GET | `/api/v1/meta/online-members` | [Open](./07-route-reference.md#get-apiv1metaonline-members) |
| GET | `/api/v1/meta/contact-channels` | [Open](./07-route-reference.md#get-apiv1metacontact-channels) |
| GET | `/api/v1/meta/site` | [Open](./07-route-reference.md#get-apiv1metasite) |
| GET | `/api/v1/realtime/config` | [Open](./07-route-reference.md#get-apiv1realtimeconfig) |
| GET | `/api/v1/lotto/draws` | [Open](./07-route-reference.md#get-apiv1lottodraws) |
| GET | `/api/v1/lotto/draws/{id}` | [Open](./07-route-reference.md#get-apiv1lottodrawsid) |
| GET | `/api/v1/lotto/markets/latest` | [Open](./07-route-reference.md#get-apiv1lottomarketslatest) |
| GET | `/api/v1/lotto/markets/{marketId}/betting-context` | [Open](./07-route-reference.md#get-apiv1lottomarketsmarketidbetting-context) |
| GET | `/api/v1/lotto/markets/{marketId}/results` | [Open](./07-route-reference.md#get-apiv1lottomarketsmarketidresults) |
| GET | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | [Open](./07-route-reference.md#get-apiv1lottomarketsmarketiddrawsdrawidresult) |
| GET | `/api/v1/lotto/results/by-date` | [Open](./07-route-reference.md#get-apiv1lottoresultsby-date) |
| GET | `/api/v1/lotto/navbar-config` | [Open](./07-route-reference.md#get-apiv1lottonavbar-config) |

### Auth / Member / Realtime

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/auth/logout` | [Open](./07-route-reference.md#post-apiv1authlogout) |
| GET | `/api/v1/member/profile` | [Open](./07-route-reference.md#get-apiv1memberprofile) |
| GET | `/api/v1/member/balance` | [Open](./07-route-reference.md#get-apiv1memberbalance) |
| GET | `/api/v1/member/loadbalance` | [Open](./07-route-reference.md#get-apiv1memberloadbalance) |
| POST | `/api/v1/member/change-password` | [Open](./07-route-reference.md#post-apiv1memberchange-password) |
| POST | `/api/v1/member/wallet-address` | [Open](./07-route-reference.md#post-apiv1memberwallet-address) |
| GET | `/api/v1/member/contributor` | [Open](./07-route-reference.md#get-apiv1membercontributor) |
| GET | `/api/v1/member/history` | [Open](./07-route-reference.md#get-apiv1memberhistory) |
| GET | `/api/v1/member/history/{type}` | [Open](./07-route-reference.md#get-apiv1memberhistorytype) |
| GET | `/api/v1/member/realtime-context` | [Open](./07-route-reference.md#get-apiv1memberrealtime-context) |
| POST | `/api/v1/member/heartbeat` | [Open](./07-route-reference.md#post-apiv1memberheartbeat) |
| POST | `/api/v1/realtime/auth` | [Open](./07-route-reference.md#post-apiv1realtimeauth) |

### Wallet / Coupon / Deposit / Payment

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/wallet/withdraw` | [Open](./07-route-reference.md#post-apiv1walletwithdraw) |
| POST | `/api/v1/wallet/claim` | [Open](./07-route-reference.md#post-apiv1walletclaim) |
| GET | `/api/v1/wallet/transactions` | [Open](./07-route-reference.md#get-apiv1wallettransactions) |
| POST | `/api/v1/coupon/redeem` | [Open](./07-route-reference.md#post-apiv1couponredeem) |
| GET | `/api/v1/coupon/my` | [Open](./07-route-reference.md#get-apiv1couponmy) |
| POST | `/api/v1/coupon/my/{code}/claim` | [Open](./07-route-reference.md#post-apiv1couponmycodeclaim) |
| GET | `/api/v1/deposit/channels` | [Open](./07-route-reference.md#get-apiv1depositchannels) |
| POST | `/api/v1/deposit/loadbank` | [Open](./07-route-reference.md#post-apiv1depositloadbank) |
| POST | `/api/v1/deposit/loadbank/random` | [Open](./07-route-reference.md#post-apiv1depositloadbankrandom) |
| GET | `/api/v1/smkpay/deposit/status/{txid}` | [Open](./07-route-reference.md#get-apiv1smkpaydepositstatustxid) |
| POST | `/api/v1/smkpay/deposit/expire/{txid}` | [Open](./07-route-reference.md#post-apiv1smkpaydepositexpiretxid) |
| POST | `/api/v1/smkpay/deposit/create` | [Open](./07-route-reference.md#post-apiv1smkpaydepositcreate) |
| GET | `/api/v1/smkpay/qrcode/{id}` | [Open](./07-route-reference.md#get-apiv1smkpayqrcodeid) |
| POST | `/api/v1/deeppay/deposit/expire/{txid}` | [Open](./07-route-reference.md#post-apiv1deeppaydepositexpiretxid) |
| POST | `/api/v1/deeppay/deposit/create` | [Open](./07-route-reference.md#post-apiv1deeppaydepositcreate) |
| GET | `/api/v1/deeppay/qrcode/{id}` | [Open](./07-route-reference.md#get-apiv1deeppayqrcodeid) |

### Promotion / Game

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/promotion/list` | [Open](./07-route-reference.md#get-apiv1promotionlist) |
| POST | `/api/v1/promotion/select` | [Open](./07-route-reference.md#post-apiv1promotionselect) |
| POST | `/api/v1/promotion/deselect` | [Open](./07-route-reference.md#post-apiv1promotiondeselect) |
| POST | `/api/v1/games/login` | [Open](./07-route-reference.md#post-apiv1gameslogin) |
| GET | `/api/v1/games/login/{game}/{code}` | [Open](./07-route-reference.md#get-apiv1gameslogingamecode) |

### Lotto

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/lotto/bet` | [Open](./07-route-reference.md#post-apiv1lottobet) |
| GET | `/api/v1/lotto/groups/{groupId}/packages` | [Open](./07-route-reference.md#get-apiv1lottogroupsgroupidpackages) |
| POST | `/api/v1/lotto/groups/{groupId}/select-package` | [Open](./07-route-reference.md#post-apiv1lottogroupsgroupidselect-package) |
| GET | `/api/v1/lotto/groups/{groupId}/selected-package` | [Open](./07-route-reference.md#get-apiv1lottogroupsgroupidselected-package) |
| GET | `/api/v1/lotto/tickets` | [Open](./07-route-reference.md#get-apiv1lottotickets) |
| GET | `/api/v1/lotto/tickets/{id}` | [Open](./07-route-reference.md#get-apiv1lottoticketsid) |
| POST | `/api/v1/lotto/tickets/{id}/cancel` | [Open](./07-route-reference.md#post-apiv1lottoticketsidcancel) |

### Wheel / Reward

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/wheel/list` | [Open](./07-route-reference.md#get-apiv1wheellist) |
| POST | `/api/v1/wheel/spin` | [Open](./07-route-reference.md#post-apiv1wheelspin) |
| GET | `/api/v1/wheel/history` | [Open](./07-route-reference.md#get-apiv1wheelhistory) |
| GET | `/api/v1/reward/list` | [Open](./07-route-reference.md#get-apiv1rewardlist) |
| POST | `/api/v1/reward/redeem` | [Open](./07-route-reference.md#post-apiv1rewardredeem) |
| GET | `/api/v1/reward/history` | [Open](./07-route-reference.md#get-apiv1rewardhistory) |

### Yeekee

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/lotto/yeekee/rounds/{roundId}/shoot` | [Open](./07-route-reference.md#post-apiv1lottoyeekeeroundsroundidshoot) |
| GET | `/api/v1/lotto/yeekee/markets/{marketId}/current-round` | [Open](./07-route-reference.md#get-apiv1lottoyeekeemarketsmarketidcurrent-round) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/shoots` | [Open](./07-route-reference.md#get-apiv1lottoyeekeeroundsroundidshoots) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/reward-status` | [Open](./07-route-reference.md#get-apiv1lottoyeekeeroundsroundidreward-status) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/result-proof` | [Open](./07-route-reference.md#get-apiv1lottoyeekeeroundsroundidresult-proof) |
