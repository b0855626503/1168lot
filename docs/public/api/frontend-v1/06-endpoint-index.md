# Frontend API V1 - Endpoint Index

อัปเดตล่าสุด: 2026-05-02

ตารางสรุป endpoint และลิงก์เข้า detail ใน `07-route-reference.md`

## Public Routes

| Method | Endpoint | Auth | Description | Detail |
|---|---|---|---|---|
| GET | `/api/v1/auth/register/banks` | No token | ดึงรายการธนาคารที่ระบบรองรับสำหรับใช้ในฟอร์มสมัครสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1authregisterbanks) |
| POST | `/api/v1/auth/register/bank-account-name` | No token | ตรวจสอบชื่อบัญชีจากธนาคารตามเลขบัญชีที่กรอก เพื่อช่วยยืนยันความถูกต้อง | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregisterbank-account-name) |
| POST | `/api/v1/auth/register` | No token | สมัครสมาชิกใหม่และผูกข้อมูลบัญชีธนาคารสำหรับธุรกรรม | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregister) |
| POST | `/api/v1/auth/register-with-username` | No token | สมัครสมาชิกใหม่ด้วย flow ที่กำหนด username ตามนโยบายหน้าเว็บ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authregister-with-username) |
| POST | `/api/v1/auth/login` | No token | เข้าสู่ระบบและออก access token สำหรับเรียก API ที่ต้องยืนยันตัวตน | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogin) |
| GET | `/api/v1/games/types` | No token | ดึงประเภทเกมที่เปิดให้บริการ เช่น slot, casino, sport | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamestypes) |
| GET | `/api/v1/games/providers/{type}` | No token | ดึงรายชื่อค่ายเกมตามประเภทที่เลือก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamesproviderstype) |
| GET | `/api/v1/games/{type}/{provider}` | No token | ดึงรายการเกมของค่ายที่เลือกในประเภทนั้น | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gamestypeprovider) |
| GET | `/api/v1/slides` | No token | ดึงข้อมูลสไลด์/แบนเนอร์สำหรับหน้าแรก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1slides) |
| GET | `/api/v1/meta/online-members` | No token | ดึงจำนวนสมาชิกออนไลน์แบบสรุป | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metaonline-members) |
| GET | `/api/v1/meta/contact-channels` | No token | ดึงช่องทางติดต่อที่เปิดใช้งาน เช่น Line, Telegram | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metacontact-channels) |
| GET | `/api/v1/meta/site` | No token | ดึงข้อมูลเมตาของเว็บ เช่น ชื่อเว็บ สถานะบำรุงรักษา | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1metasite) |
| GET | `/api/v1/theme` | No token | ดึงธีมหน้าบ้านปัจจุบันสำหรับ frontend (preset + tokens + version) | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1theme) |
| GET | `/api/frontend/theme` | No token | Alias สำหรับดึงธีมหน้าบ้านปัจจุบัน | [Open](/docs/api/frontend-v1/07-route-reference#get-apifrontendtheme) |
| GET | `/api/v1/realtime/config` | No token | ดึงคอนฟิกระบบ realtime ที่ frontend ต้องใช้เชื่อมต่อ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1realtimeconfig) |
| GET | `/api/v1/lotto/draws` | No token | ดึงงวดหวยตาม market ที่ระบุ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottodraws) |
| GET | `/api/v1/lotto/draws/{id}` | No token | ดึงรายละเอียดงวดหวยรายงวด | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottodrawsid) |
| GET | `/api/v1/lotto/markets/latest` | No token | ดึงตลาดหวยล่าสุดที่กำลังเปิดให้เล่น | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketslatest) |
| GET | `/api/v1/lotto/markets/{marketId}/betting-context` | No token | ดึงบริบทเดิมพันของตลาด เช่น draw ปัจจุบัน และเวลาปิดรับ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketidbetting-context) |
| GET | `/api/v1/lotto/markets/{marketId}/results` | No token | ดึงผลรางวัลย้อนหลังตามตลาด | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketidresults) |
| GET | `/api/v1/lotto/markets/{marketId}/draws/{drawId}/result` | No token | ดึงผลรางวัลของงวดเฉพาะเจาะจง | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottomarketsmarketiddrawsdrawidresult) |
| GET | `/api/v1/lotto/results/by-date` | No token | ดึงผลหวยรวมตามวันที่ระบุ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoresultsby-date) |
| GET | `/api/v1/lotto/navbar-config` | No token | ดึงคอนฟิกเมนูนำทางของโมดูลหวยตามโค้ดที่กำหนด | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottonavbar-config) |

## Authenticated Routes

| Method | Endpoint | Auth | Description | Detail |
|---|---|---|---|---|
| POST | `/api/v1/auth/logout` | Bearer token | ออกจากระบบและยกเลิก token ปัจจุบัน | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1authlogout) |
| GET | `/api/v1/member/profile` | Bearer token | ดึงข้อมูลโปรไฟล์สมาชิกที่ล็อกอินอยู่ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberprofile) |
| GET | `/api/v1/member/balance` | Bearer token | ดึงยอดเงินและยอดที่เกี่ยวข้องในกระเป๋าสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberbalance) |
| GET | `/api/v1/member/loadbalance` | Bearer token | ดึงยอดเงินแบบเบาเพื่อรีเฟรชเร็ว | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberloadbalance) |
| POST | `/api/v1/member/change-password` | Bearer token | เปลี่ยนรหัสผ่านของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberchange-password) |
| POST | `/api/v1/member/wallet-address` | Bearer token | บันทึกหรืออัปเดตที่อยู่กระเป๋า crypto ของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberwallet-address) |
| GET | `/api/v1/member/contributor` | Bearer token | ดึงข้อมูลผู้แนะนำ/ผู้สนับสนุนที่ผูกกับสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1membercontributor) |
| GET | `/api/v1/member/history` | Bearer token | ดึงประวัติธุรกรรมรวมของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberhistory) |
| GET | `/api/v1/member/history/{type}` | Bearer token | ดึงประวัติธุรกรรมแยกตามประเภท | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberhistorytype) |
| GET | `/api/v1/member/realtime-context` | Bearer token | ดึงข้อมูล context สำหรับ subscribe ช่อง realtime ของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1memberrealtime-context) |
| POST | `/api/v1/member/heartbeat` | Bearer token | ส่ง heartbeat เพื่ออัปเดตสถานะออนไลน์ของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1memberheartbeat) |
| POST | `/api/v1/realtime/auth` | Bearer token | authorize การเข้าช่อง realtime private/presence | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1realtimeauth) |
| POST | `/api/v1/wallet/withdraw` | Bearer token | สร้างคำขอถอนเงินจากกระเป๋าสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1walletwithdraw) |
| POST | `/api/v1/wallet/claim` | Bearer token | เคลมเครดิต/ยอดคงค้างเข้ากระเป๋าหลักตามเงื่อนไขระบบ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1walletclaim) |
| GET | `/api/v1/wallet/transactions` | Bearer token | ดึงรายการเดินบัญชีกระเป๋า (wallet ledger) | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wallettransactions) |
| POST | `/api/v1/coupon/redeem` | Bearer token | ตรวจสอบและแลกคูปองเข้าระบบของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1couponredeem) |
| GET | `/api/v1/coupon/my` | Bearer token | ดึงคูปองที่สมาชิกมีอยู่ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1couponmy) |
| POST | `/api/v1/coupon/my/{code}/claim` | Bearer token | เคลมคูปองที่สมาชิกถืออยู่ด้วย code ที่ระบุ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1couponmycodeclaim) |
| GET | `/api/v1/deposit/channels` | Bearer token | ดึงช่องทางฝากเงินที่เปิดใช้งานสำหรับสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1depositchannels) |
| POST | `/api/v1/deposit/loadbank` | Bearer token | ดึงข้อมูลบัญชีธนาคารปลายทางของระบบสำหรับฝาก | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1depositloadbank) |
| POST | `/api/v1/deposit/loadbank/random` | Bearer token | ดึงบัญชีธนาคารปลายทางแบบสุ่ม 1 รายการจากรายการที่เปิดใช้งาน | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1depositloadbankrandom) |
| GET | `/api/v1/smkpay/deposit/status/{txid}` | Bearer token | ตรวจสอบสถานะรายการฝากผ่าน SMKPay ตาม txid | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1smkpaydepositstatustxid) |
| POST | `/api/v1/smkpay/deposit/expire/{txid}` | Bearer token | สั่งหมดอายุรายการฝาก SMKPay ที่ยังไม่ชำระ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1smkpaydepositexpiretxid) |
| POST | `/api/v1/smkpay/deposit/create` | Bearer token | สร้างรายการฝากผ่าน SMKPay และคืนข้อมูลการชำระ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1smkpaydepositcreate) |
| GET | `/api/v1/smkpay/qrcode/{id}` | Bearer token | ดึงข้อมูล QR code ของรายการฝาก SMKPay | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1smkpayqrcodeid) |
| POST | `/api/v1/deeppay/deposit/expire/{txid}` | Bearer token | สั่งให้รายการฝาก DeepPay ที่ยังค้างอยู่หมดอายุ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1deeppaydepositexpiretxid) |
| POST | `/api/v1/deeppay/deposit/create` | Bearer token | สร้างรายการฝากผ่าน DeepPay และคืนข้อมูลสำหรับชำระเงิน | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1deeppaydepositcreate) |
| GET | `/api/v1/deeppay/qrcode/{id}` | Bearer token | ดึงข้อมูล QR สำหรับรายการฝาก DeepPay | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1deeppayqrcodeid) |
| GET | `/api/v1/promotion/list` | Bearer token | ดึงรายการโปรโมชั่นที่สมาชิกสามารถเลือกได้ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1promotionlist) |
| POST | `/api/v1/promotion/select` | Bearer token | เลือกเข้าร่วมโปรโมชั่น | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1promotionselect) |
| POST | `/api/v1/promotion/deselect` | Bearer token | ยกเลิกการเข้าร่วมโปรโมชั่นปัจจุบัน | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1promotiondeselect) |
| POST | `/api/v1/games/login` | Bearer token | ขอ URL/Session สำหรับเข้าเล่นเกมแบบยิงจาก frontend | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1gameslogin) |
| GET | `/api/v1/games/login/{game}/{code}` | Bearer token | เข้าเกมผ่าน path parameter สำหรับ deep link | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1gameslogingamecode) |
| POST | `/api/v1/lotto/bet` | Bearer token | ส่งคำสั่งเดิมพันหวยและสร้างบิล | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottobet) |
| GET | `/api/v1/lotto/groups/{groupId}/packages` | Bearer token | ดึงชุด package ที่มีให้เลือกในกลุ่มหวย | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottogroupsgroupidpackages) |
| POST | `/api/v1/lotto/groups/{groupId}/select-package` | Bearer token | เลือก package สำหรับกลุ่มหวยที่ระบุ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottogroupsgroupidselect-package) |
| GET | `/api/v1/lotto/groups/{groupId}/selected-package` | Bearer token | ดึง package ที่สมาชิกเลือกไว้ล่าสุด | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottogroupsgroupidselected-package) |
| GET | `/api/v1/lotto/tickets` | Bearer token | ดึงรายการบิลหวยของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottotickets) |
| GET | `/api/v1/lotto/tickets/{id}` | Bearer token | ดึงรายละเอียดบิลหวยรายใบ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoticketsid) |
| POST | `/api/v1/lotto/tickets/{id}/cancel` | Bearer token | ยกเลิกบิลหวยตามเงื่อนไขเวลาที่อนุญาต | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottoticketsidcancel) |
| GET | `/api/v1/wheel/list` | Bearer token | ดึงรายการวงล้อ/สิทธิ์ที่สมาชิกเล่นได้ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wheellist) |
| POST | `/api/v1/wheel/spin` | Bearer token | หมุนวงล้อและรับผลรางวัล | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1wheelspin) |
| GET | `/api/v1/wheel/history` | Bearer token | ดึงประวัติการหมุนวงล้อของสมาชิก | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1wheelhistory) |
| GET | `/api/v1/reward/list` | Bearer token | ดึงรายการ reward ที่สมาชิกสามารถแลกได้ ณ เวลาปัจจุบัน (คัดเฉพาะที่ active, ไม่ซ่อน, ยังมีสต๊อก และอยู่ในช่วงเวลาใช้งาน) | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1rewardlist) |
| POST | `/api/v1/reward/redeem` | Bearer token | แลกแต้มกับ reward ที่เลือก โดยระบบตรวจแต้ม, เวลาใช้งาน, สต๊อก, limit และบันทึก redemption | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1rewardredeem) |
| GET | `/api/v1/reward/history` | Bearer token | ดึงประวัติการแลก reward และสรุปเป็นเส้นเวลา (timeline) แยกตามวัน | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1rewardhistory) |
| POST | `/api/v1/lotto/yeekee/rounds/{roundId}/shoot` | Bearer token | ส่งเลข 5 หลักเพื่อชิงลำดับยิงในรอบยี่กี่ | [Open](/docs/api/frontend-v1/07-route-reference#post-apiv1lottoyeekeeroundsroundidshoot) |
| GET | `/api/v1/lotto/yeekee/rounds` | Bearer token | ดึงรอบยี่กี่ทั้งหมดของวันที่ระบุ (รองรับกรอง market_id) | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeerounds) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}` | Bearer token | ดึงรายละเอียดรอบยี่กี่รายรอบ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundid) |
| GET | `/api/v1/lotto/yeekee/markets/{marketId}/current-round` | Bearer token | ดึงรอบยี่กี่ปัจจุบันของ market | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeemarketsmarketidcurrent-round) |
| GET | `/api/v1/lotto/yeekee/markets/{marketId}/rounds` | Bearer token | ดึงรอบยี่กี่ทั้งหมดของวันที่ระบุใน market | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeemarketsmarketidrounds) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/shoots` | Bearer token | ดึงรายการยิงเลขล่าสุดในรอบ (เรียง position ล่าสุดก่อน) | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidshoots) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/reward-status` | Bearer token | ดึงสถานะว่ารอบนี้สมาชิกได้รับรางวัลยิงเลขหรือไม่ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidreward-status) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/result-proof` | Bearer token | ดึงข้อมูล proof สำหรับตรวจสอบความโปร่งใสของผลยี่กี่ | [Open](/docs/api/frontend-v1/07-route-reference#get-apiv1lottoyeekeeroundsroundidresult-proof) |

## Yeekee Hardening Contract (Current Runtime)

Status: `Current Runtime Contract`  
Implemented in: `PR-04`

หมายเหตุ:
- `POST /shoot` ตอบ `429` พร้อม cooldown metadata เมื่อชน cooldown
- `GET /shoots` ตอบหมายเลขแบบ masked ตาม hardening contract

| Method | Endpoint | Status | Implemented in | Note |
|---|---|---|---|---|
| GET | `/api/v1/lotto/yeekee/rounds` | Current Runtime Contract | PR-04 | new endpoint (active) |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}` | Current Runtime Contract | PR-04 | new endpoint (active) |
| POST | `/api/v1/lotto/yeekee/rounds/{roundId}/shoot` | Current Runtime Contract | PR-03/PR-04 | hardened: cooldown 429 contract |
| GET | `/api/v1/lotto/yeekee/rounds/{roundId}/shoots` | Current Runtime Contract | PR-03/PR-04 | hardened: masked number_text |

### Yeekee Contract Update (2026-05-03)

- /api/v1/lotto/yeekee/rounds/{roundId}/shoots supports display modes live_masked and result_revealed
- Keeps number_text for backward compatibility; frontend should prioritize number_text_masked and number_text_revealed
- Adds shoot summary and pagination metadata
- Public response excludes sensitive fields (member_id, member_code, customer_id, ip_address, user_agent)
- /api/v1/lotto/yeekee/rounds/{roundId}/result-proof includes shoot_summary and winner-only summary (first, sixteenth) without full shoots list
