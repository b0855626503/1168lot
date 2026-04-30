# Frontend API V1 - Endpoint Index

อัปเดตล่าสุด: 2026-04-30

## Quick Rule

- กด endpoint ด้านล่างเพื่อไปตำแหน่ง detail ใน `07-route-reference.md` ได้ทันที

## Core First

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/auth/register` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregister) |
| POST | `/api/v1/auth/login` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogin) |
| GET | `/api/v1/lotto/draws` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottodraws) |
| GET | `/api/v1/lotto/markets/latest` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketslatest) |
| POST | `/api/v1/auth/logout` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogout) |
| GET | `/api/v1/member/profile` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberprofile) |
| GET | `/api/v1/member/balance` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberbalance) |
| GET | `/api/v1/wallet/transactions` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wallettransactions) |
| POST | `/api/v1/lotto/bet` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottobet) |
| GET | `/api/v1/lotto/tickets` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottotickets) |

## Business Groups

### Public Endpoints

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/auth/register/banks` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1authregisterbanks) |
| POST | `/api/v1/auth/register/bank-account-name` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregisterbank-account-name) |
| POST | `/api/v1/auth/register` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregister) |
| POST | `/api/v1/auth/login` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogin) |
| GET | `/api/v1/games/types` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamestypes) |
| GET | `/api/v1/games/providers/{type}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamesproviderstype) |
| GET | `/api/v1/games/{type}/{provider}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamestypeprovider) |
| GET | `/api/v1/slides` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1slides) |
| GET | `/api/v1/meta/online-members` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metaonline-members) |
| GET | `/api/v1/meta/contact-channels` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metacontact-channels) |
| GET | `/api/v1/meta/site` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metasite) |
| GET | `/api/v1/realtime/config` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1realtimeconfig) |
| GET | `/api/v1/lotto/draws` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottodraws) |
| GET | `/api/v1/lotto/draws/{id}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottodrawsid) |
| GET | `/api/v1/lotto/markets/latest` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketslatest) |
| GET | `/api/v1/lotto/markets/{marketId}/betting-context` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketidbetting-context) |
| GET | `/api/v1/lotto/markets/{marketId}/results` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketidresults) |
| GET | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketiddrawsdrawidresult) |
| GET | `/api/v1/lotto/results/by-date` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoresultsby-date) |
| GET | `/api/v1/lotto/navbar-config` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottonavbar-config) |

### Auth / Member / Realtime

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/auth/logout` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogout) |
| GET | `/api/v1/member/profile` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberprofile) |
| GET | `/api/v1/member/balance` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberbalance) |
| GET | `/api/v1/member/loadbalance` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberloadbalance) |
| POST | `/api/v1/member/change-password` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberchange-password) |
| POST | `/api/v1/member/wallet-address` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberwallet-address) |
| GET | `/api/v1/member/contributor` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1membercontributor) |
| GET | `/api/v1/member/history` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberhistory) |
| GET | `/api/v1/member/history/{type}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberhistorytype) |
| GET | `/api/v1/member/realtime-context` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberrealtime-context) |
| POST | `/api/v1/member/heartbeat` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberheartbeat) |
| POST | `/api/v1/realtime/auth` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1realtimeauth) |

### Wallet / Coupon / Deposit / Payment

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/wallet/withdraw` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1walletwithdraw) |
| POST | `/api/v1/wallet/claim` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1walletclaim) |
| GET | `/api/v1/wallet/transactions` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wallettransactions) |
| POST | `/api/v1/coupon/redeem` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1couponredeem) |
| GET | `/api/v1/coupon/my` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1couponmy) |
| POST | `/api/v1/coupon/my/{code}/claim` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1couponmycodeclaim) |
| GET | `/api/v1/deposit/channels` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1depositchannels) |
| POST | `/api/v1/deposit/loadbank` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1depositloadbank) |
| POST | `/api/v1/deposit/loadbank/random` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1depositloadbankrandom) |
| GET | `/api/v1/smkpay/deposit/status/{txid}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1smkpaydepositstatustxid) |
| POST | `/api/v1/smkpay/deposit/expire/{txid}` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1smkpaydepositexpiretxid) |
| POST | `/api/v1/smkpay/deposit/create` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1smkpaydepositcreate) |
| GET | `/api/v1/smkpay/qrcode/{id}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1smkpayqrcodeid) |
| POST | `/api/v1/deeppay/deposit/expire/{txid}` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1deeppaydepositexpiretxid) |
| POST | `/api/v1/deeppay/deposit/create` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1deeppaydepositcreate) |
| GET | `/api/v1/deeppay/qrcode/{id}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1deeppayqrcodeid) |

### Promotion / Game

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/promotion/list` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1promotionlist) |
| POST | `/api/v1/promotion/select` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1promotionselect) |
| POST | `/api/v1/promotion/deselect` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1promotiondeselect) |
| POST | `/api/v1/games/login` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1gameslogin) |
| GET | `/api/v1/games/login/{game}/{code}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gameslogingamecode) |

### Lotto

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/lotto/bet` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottobet) |
| GET | `/api/v1/lotto/groups/{groupId}/packages` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottogroupsgroupidpackages) |
| POST | `/api/v1/lotto/groups/{groupId}/select-package` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottogroupsgroupidselect-package) |
| GET | `/api/v1/lotto/groups/{groupId}/selected-package` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottogroupsgroupidselected-package) |
| GET | `/api/v1/lotto/tickets` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottotickets) |
| GET | `/api/v1/lotto/tickets/{id}` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoticketsid) |
| POST | `/api/v1/lotto/tickets/{id}/cancel` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottoticketsidcancel) |

### Wheel / Reward

| Method | Endpoint | Link |
|---|---|---|
| GET | `/api/v1/wheel/list` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wheellist) |
| POST | `/api/v1/wheel/spin` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1wheelspin) |
| GET | `/api/v1/wheel/history` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wheelhistory) |
| GET | `/api/v1/reward/list` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1rewardlist) |
| POST | `/api/v1/reward/redeem` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1rewardredeem) |
| GET | `/api/v1/reward/history` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1rewardhistory) |

### Yeekee

| Method | Endpoint | Link |
|---|---|---|
| POST | `/api/v1/lotto/yeekee/rounds/{roundId}/shoot` | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottoyeekeeroundsroundidshoot) |
| GET | `/api/v1/lotto/yeekee/markets/{marketId}/current-round` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeemarketsmarketidcurrent-round) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/shoots` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidshoots) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/reward-status` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidreward-status) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/result-proof` | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidresult-proof) |
