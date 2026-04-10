# Decision Log

อ้างอิงสรุป decision ชุดแกนกลางได้ที่ `docs/internal/02_DECISIONS/adr_baseline.md`
อ้างอิงทางลัดตาม domain ได้ที่ `docs/internal/02_DECISIONS/adr_index_by_domain.md`

## 2026-04-10 — Admin `setWallet` Must Broadcast Member-Facing Realtime Message for Credit Adjustments (APPROVED)

- ปรับ flow `Gametech\Admin\Http\Controllers\MemberController@setWallet`
- behavior ใหม่:
  - เมื่อทีมงานเพิ่ม/ลดเครดิตสำเร็จ ต้องยิง `member.activity.updated` ไปที่ `{APP_NAME}_members.{member_code}`
  - ใช้ `method=adjust` และ `event=wallet.admin_adjusted`
  - payload ต้องมี `message` ที่พร้อมแสดงบน frontend ทันที
  - field `data` ต้องมีอย่างน้อย `direction`, `remark`, `adjusted_by`, `amount`, `balance`
  - ต้องยิง `member.balance.updated` คู่กันด้วยข้อความเดียวกันเพื่อคง compatibility กับ client ที่ยังฟัง event เก่า
- เหตุผล:
  - flow admin adjust เดิมอัปเดตยอดเงินจริง แต่ frontend ลูกค้าไม่รู้ว่าเป็นการเพิ่ม/ลดโดยทีมงาน
  - การแนบข้อความตรงจาก backend ลดการเดา copy ฝั่ง Next.js และทำให้กรณีลดเครดิตไม่ถูกแสดงเป็นข้อความฝากเงิน

## 2026-04-10 — Customer Wallet Realtime Must Use `member.activity.updated` as the Primary Event, and Lotto Ticket Created/Cancelled Must Not Broadcast to Shared Member Feed (APPROVED)

- ปรับ customer realtime contract
- behavior ใหม่:
  - `member.activity.updated` เป็น primary event สำหรับ wallet/customer-facing financial updates
  - ทุก flow หลักที่ยิง event นี้ต้องแนบ `message` ที่พร้อมแสดงทันที
  - `member.balance.updated` ยังอยู่เพื่อ backward compatibility แต่ถือเป็น legacy fallback
  - `lotto.ticket.list.changed` ที่เกิดจาก `created` และ `cancelled` ไม่ถูก broadcast ไป shared member channel อีก
  - เมื่อสมาชิกถูกรางวัล ระบบต้องยิง targeted private event:
    - `method=lotto`
    - `event=lotto.ticket_won`
  - เมื่อสมาชิกได้รับคืนเงินโพย ระบบต้องยิง targeted private event:
    - `method=lotto`
    - `event=lotto.ticket_refunded`
- เหตุผล:
  - ลดความซับซ้อนฝั่ง Next.js ให้ listen หลักเพียง `.member.activity.updated`
  - ลด noise ใน shared feed ของสมาชิก เพราะรายการสร้างโพย/ยกเลิกโพยไม่ใช่ public audience
  - ทำให้ wallet/lotto credit updates แนบ copy ที่ backend ควบคุมเองได้สม่ำเสมอ

## 2026-04-08 — เพิ่ม `wallet_address` Field และ FrontendApi Endpoint สำหรับสมาชิก (APPROVED)

- เพิ่ม column `members.wallet_address varchar(255) nullable` (migration `2026_04_08_000001`)
- เพิ่ม `wallet_address` ใน `Member.$fillable` และ `$casts`
- เพิ่ม endpoint `POST /api/v1/member/wallet-address` (auth required) ใน FrontendApi
- route name: `frontend.api.v1.member.wallet_address`
- เหตุผล:
  - รองรับการเก็บ crypto wallet address หรือ external payment address ของสมาชิก
  - ใช้ MemberRepository ตาม pattern เดิม (ADR-001 compliant)

## 2026-04-06 — Market-Level Auto Refund on `No Result` Is Configurable and Reuses Draw Cancel-All-Refund Flow (APPROVED)

- เพิ่ม market flag ใหม่ `lotto_markets.auto_refund_on_no_result` (default `false`)
- behavior:
  - เมื่อ draw ถูก mark เป็น `no_result` และ market เปิด flag นี้ไว้
  - ระบบต้องรัน flow `ยกเลิกโพยทั้งงวด+คืนเงิน` อัตโนมัติทันที
  - draw ต้องติด marker `result_number.manual_cancelled_all_tickets=true` เหมือน manual cancel-all-refund
- ขอบเขตที่บังคับใช้:
  - auto-result apply path
  - manual `POST /lotto/draws/mark-no-result` path
- เหตุผล:
  - ลดงาน manual ของทีมหลังเจอเคสงดออกผล
  - ทำให้ policy คืนเงินของงวด `no_result` ผูกกับการตั้งค่าในเมนู `รายการหวย` ได้ชัดเจน
  - ลดความเสี่ยง logic drift โดยบังคับ reuse service คืนเงินชุดเดียวกับ action manual

## 2026-04-06 — Laravel 10 Is the Current Practical Upgrade Target; Laravel 12 Is Deferred Pending Package Compatibility (APPROVED)

- ปรับ execution target ของงาน framework upgrade:
  - practical milestone ปัจจุบันคือ Laravel 10 + PHP 8.2
  - Laravel 12 ยังไม่ถูกอนุมัติเป็น execution target ในรอบนี้
- เหตุผล:
  - package ecosystem หลักของระบบยังต้องเปลี่ยน/อัปหลายตัวก่อน เช่น MongoDB, Concord, DataTables, และ legacy middleware packages
  - Laravel 10 เป็นจุดที่มีเส้นทางอัปเกรดของ package หลักชัดและปิด security/advisory risk ของ Laravel 9 path ได้
  - ถ้าจะขยับจาก 10 ไป 12 ต้องผ่าน compatibility matrix ใหม่อีกครั้ง ไม่ใช่ข้ามโดยอนุมาน

## 2026-04-06 — Unused or Legacy Upgrade Blockers May Be Removed Instead of Preserved (APPROVED)

- ปรับ package policy สำหรับรอบ Laravel 10 upgrade:
  - package ที่ abandoned หรือไม่ได้เป็น runtime requirement จริง ให้ remove ได้แทนการฝืน carry forward
  - package ที่ยังจำเป็นแต่มีเส้นทางใหม่ชัด ให้ replace ไปตัวที่รองรับ Laravel 10 โดยตรง
- รอบนี้ใช้ policy นี้กับอย่างน้อย:
  - `mongodb/laravel-mongodb` แทน `jenssegers/mongodb`
  - built-in framework middleware แทน `fideloper/proxy` และ `fruitcake/laravel-cors`
  - direct `yajra` packages สำหรับ Laravel 10 แทน meta package ชุดเก่า
  - remove `google/apiclient`, `irazasyed/telegram-bot-sdk`, `laravel/scout`, `barryvdh/laravel-debugbar` เพราะไม่ใช่ runtime requirement ปัจจุบันของระบบ
- เหตุผล:
  - ลด conflict surface ใน dependency graph
  - ไม่แบก package abandoned/unused เข้าไปใน Laravel 10 path โดยไม่มีประโยชน์ทาง business
  - ทำให้ Composer security gate และ package compatibility matrix ผ่านได้ด้วยชุด dependency ที่สั้นและตรงกว่าเดิม

## 2026-04-06 — Admin Self-Update Package Is Decommissioned for Laravel 10 Upgrade Path (APPROVED)

- ปรับ admin maintenance flow:
  - ถอด package `codedge/laravel-selfupdater` ออกจากระบบ
  - route `admin.update.index` และ `admin.checkupdate.index` ยังคงอยู่เพื่อไม่ให้ UI/route contract แตก
  - แต่ route ทั้งสองเปลี่ยนบทบาทเป็น decommission notice:
    - `update` ไม่ทำ auto-update แล้ว และ redirect กลับพร้อมข้อความแจ้ง
    - `checkupdate` แสดง current version + แจ้งว่าไม่มี in-app updater แล้ว
  - หน้า login/admin header ยังคงแสดง installed version ได้จาก config ภายใน
- เหตุผล:
  - package เดิมรองรับเพียง Laravel 8 path และเป็น blocker ของ dependency resolution สำหรับ Laravel 10
  - การคง route เดิมไว้ช่วยลดผลกระทบต่อ admin UI ระหว่าง upgrade
  - deployment/update ต้องย้ายไปใช้ external deployment flow แทน in-app self-update

## 2026-04-05 — Admin Manual Retry Must Bypass Auto Scheduler Retry Gating (APPROVED)

- ปรับ flow `lotto_draws.auto_result_manual_retry` / `--manual-retry`
- behavior ใหม่:
  - manual retry ของทีมงานต้อง bypass retry gating ที่ใช้กับ auto scheduler
  - bypass:
    - source backoff
    - source exhausted / `max_attempts`
    - draw fetch status เดิมที่เป็น `EXHAUSTED`
  - แต่ยังใช้ source resolution / fetch / parse / validate / apply เดิม และยังเขียน fetch logs ตามปกติ
  - ถ้า manual retry สำเร็จหลัง draw เคยค้าง `EXHAUSTED` ระบบต้องอัปเดต `result_fetch_status` ใหม่ได้ ไม่ค้าง terminal status เดิม
- เหตุผล:
  - หน้าทีมงานมี `Dry-run` ไว้ยืนยันว่าต้นทาง/parser/map พร้อมแล้ว
  - เมื่อทีมงานกด retry เอง ระบบต้องลอง apply จริงได้ แม้ auto scheduler จะ exhausted draw/source ไปก่อนหน้านั้น
  - สอดคล้องกับแผนเมนูงวดหวยที่ระบุว่า manual retry ต้อง bypass เงื่อนไขคัดกรองของ auto path

## 2026-04-06 — Frontend Lotto Ticket `status` Must Reflect Win/Lose Outcome on Final Tickets (APPROVED)

- ปรับ `GET /api/v1/lotto/tickets` และ `GET /api/v1/lotto/tickets/{id}`
- behavior ใหม่:
  - ถ้า `result_outcome=won` และ `is_winner=true` ให้ส่ง `status=won`
  - ถ้า `result_outcome=lose` และ `is_winner=false` ให้ส่ง `status=lost`
  - กรณีอื่นคง status lifecycle เดิม เช่น `active`, `cancelled`
  - `draw_status` ยังเก็บ lifecycle ของ draw แยกตามเดิม
- เหตุผล:
  - ฝั่งลูกค้าต้องใช้ `status` ที่พร้อมแสดงผลลัพธ์โพยทันที โดยไม่ต้องเขียน mapping ซ้ำจาก `result_outcome`
  - แยกความหมายของ `status` ฝั่ง customer UI ออกจาก lifecycle ภายในของ ticket สำหรับเคส final outcome

## 2026-04-05 — Auto Result Business Not-Ready Must Stay `NOT_READY` and Exhausted Alert Must Work Without Full Draw Hardening Columns (APPROVED)

- ปรับ auto-result pipeline:
  - `NOT_READY_BUSINESS_RULE`
  - `NOT_READY_PARTIAL_RESULT`
  - และผลลัพธ์ V2 ที่คืน `status=NOT_READY`
  ต้องถูก map เป็น `NOT_READY`
  - ห้ามบันทึกเป็น `VALIDATION_ERROR`
- เหตุผล:
  - ให้เคส “ผลยังไม่ออกจริง” เข้า retry/backoff/max_attempts policy ได้ถูกต้อง
  - ปิดปัญหาที่ draw ถูกลองใหม่ทุกนาทีเพราะ status ถูกจัดผิดกลุ่ม
- ปรับ exhausted alert:
  - `markExhausted()` ต้องยิง Telegram alert ได้แม้ environment ยังไม่มี hardening columns บน `lotto_draws`
  - ถ้า draw ไม่มี `result_fetch_attempts/result_fetch_error`
    ให้ fallback อ่าน `attempt_no/error_message` ล่าสุดจาก `lotto_result_fetch_logs`
- เหตุผล:
  - production บางเครื่องยังอยู่ช่วง schema transitional
  - exhausted alert ต้องไม่หายเพียงเพราะ draw table ยัง migrate ไม่ครบ

## 2026-04-05 — Dashboard Net Balance Must Exclude Lotto Cash and Recent Lotto Bets Must Show `user_name` (APPROVED)

- ปรับหน้า `admin /dashboard`
- behavior ใหม่:
  - block `คงเหลือสุทธิ` ใช้เฉพาะ `ฝากสำเร็จ - ถอนสำเร็จ`
  - ห้ามเอา `lotto net cash` มารวมใน net balance อีก
  - summary path ทั้งแบบ raw query และ `dashboard_summary_daily` ต้องใช้ semantic เดียวกัน
  - projector ของ `dashboard_summary_daily` ต้องเขียน `net_amount` ตาม semantic ใหม่ด้วย
  - block `รายการโพยล่าสุด (Recent Lotto Bets)` ต้องแสดง `members.user_name` เป็นหลัก
  - ถ้า resolve สมาชิกไม่ได้ ค่อย fallback เป็น `member_id`
  - block `Lotto Risk` ต้องใช้ copy ภาษาไทยที่อธิบายง่ายขึ้นในหน้า dashboard
- เหตุผล:
  - แยก cash movement หลักของระบบออกจาก Lotto insight ให้ชัด
  - ลดความสับสนเวลาทีมงานอ่านยอดคงเหลือสุทธิเทียบกับ block Lotto Cash
  - ให้รายการโพยล่าสุดอ่านจากตัวตนสมาชิกจริงได้ง่ายกว่าเลข id

## 2026-04-05 — Admin Lotto Badge Semantics Must Be Centralized in `loadCnt` (APPROVED)

- ปรับกติกาฝั่ง admin Lotto
- behavior ใหม่:
  - count/badge ของเมนู Lotto โดยเฉพาะ `รายการโพย` ต้องยึด `DashboardController@loadCnt` เป็น source กลาง
  - หน้า Lotto ทุกหน้าใน admin ต้อง trigger `loadCnt` ตอนเข้าเมนู ไม่ว่าหน้านั้นจะใช้ DataTable หรือ Vue/custom page
  - ห้ามใช้ page-local response มานิยาม semantic ของ badge แข่งกับ `loadCnt`
- เหตุผล:
  - ให้ความหมายของ badge และ aggregate count ตรงกันทุกเมนู
  - ลดการเกิด drift ระหว่างหน้า table, toast, realtime และ dashboard aggregate

## 2026-04-05 — FrontendApi Must Own Customer Contracts for Promotion, Wheel, and Game Flows (APPROVED)

- ปรับ boundary ของ `FrontendApi`
- behavior ใหม่:
  - route ลูกค้ากลุ่ม `promotion`, `wheel`, และ game-related endpoints ต้อง implement ใน `FrontendApi` เอง
  - อนุญาตให้ reuse domain service, repository, query, model, และ transaction/service logic จาก package อื่นได้
  - ห้าม route ฝั่ง `FrontendApi` ผูก contract กับ controller ภายในของ package domain อื่น
- เหตุผล:
  - ทำให้ `FrontendApi` เป็น BFF owner ของ customer contract จริง
  - ลด coupling เวลาย้ายหรือ reuse package domain ภายนอก

## 2026-04-05 — Wallet Evolution Must Preserve Append-Only Ledger Semantics (APPROVED)

- ปรับกติกาสำหรับงาน wallet ถัดไป
- behavior ใหม่:
  - ทุก flow ใหม่ที่กระทบ main wallet ต้องเหลือ row ใน `wallet_transactions` สำหรับอธิบายการเปลี่ยนยอด
  - ห้ามออกแบบ flow ใหม่ที่เปลี่ยน `members.balance` โดยไม่มี transaction context รองรับ
  - ระหว่างที่แผน `wallet-ledger-implementation` ยัง rollout ไม่ครบทุก flow ให้เติม `ref_type/ref_id/group_code/meta` ใน `wallet_transactions` ให้ครบที่สุด
- เหตุผล:
  - รักษา audit trail ของประวัติการเงินรวม
  - ทำให้ reconciliation และ frontend history ต่อยอดจาก ledger ชุดเดียวได้

## 2026-04-05 — Existing FrontendApi Controllers Must Stop Delegating to Other Package Controllers (APPROVED)

- ปรับ implementation ภายใน `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1`
- behavior ใหม่:
  - controller ฝั่ง `FrontendApi` ที่เคย delegate ไป controller ของ package อื่น ถูกย้ายมาเขียน native ใน `FrontendApi` เองแล้ว
  - ครอบคลุมอย่างน้อย:
    - `MemberController@balance`
    - `DepositController@loadBank`
    - `PromotionController@list/select/deselect`
    - `WheelController@list/spin/history`
    - `LottoController@draw/bet/packages/selectPackage/selectedPackage/tickets/ticket/cancel`
  - อนุญาตให้ reuse domain service โดยตรง เช่น `BetService`, `LottoPackageSelectionService`, repository/query/model และ service ภายใน `FrontendApi`
  - เพิ่ม regression test เชิงสถาปัตยกรรมคุมว่า controller ใน `FrontendApi/Api/V1` ห้าม import controller จาก package อื่น และห้าม resolve ผ่าน `app(...Controller::class)`
- เหตุผล:
  - ทำให้กติกา `FrontendApi must not call other package controllers` ถูกใช้จริงกับ endpoint เดิมทั้งหมด ไม่ใช่เฉพาะของใหม่อย่าง coupon
  - ลด coupling เวลาแยก package Lotto/Wallet ไปใช้ project อื่น

## 2026-04-05 — Frontend Coupon APIs Must Live Inside FrontendApi and Not Call Other Package Controllers (APPROVED)

- เพิ่ม coupon endpoints ใน `FrontendApi`:
  - `POST /api/v1/coupon/redeem`
  - `GET /api/v1/coupon/my`
  - `POST /api/v1/coupon/my/{code}/claim`
- behavior ใหม่:
  - flow คูปองของ frontend ถูกเขียนใน controller/service ของ `packages/Gametech/FrontendApi` เอง
  - ห้าม route ฝั่ง `FrontendApi` เรียก controller ของ package อื่นโดยตรง
  - `redeem` ต้องตรวจรหัส/สิทธิ์/เงื่อนไขและสร้างรายการโบนัสรอรับ
  - `my` ต้องส่งรายการโบนัสคูปองที่ยัง `pending_claim`
  - `claim` ต้องรับโบนัสจากรายการที่เลือกและ mark เป็น `claimed`
- เหตุผล:
  - ลด coupling ข้าม package
  - ทำให้ package domain อื่นถูกย้ายไปใช้ project อื่นได้โดยไม่พังเพราะ `FrontendApi` ไปผูกกับ controller ภายในของ package นั้น

## 2026-04-05 — Frontend Member Contributor API Must Return Localized Rule Description (APPROVED)

- ปรับ `GET /api/v1/member/contributor`
- behavior ใหม่:
  - เพิ่ม `rule.more_message`
  - ค่า field นี้มาจาก translation key `app.con.more`
  - backend ต้องแทน `:field` ด้วย `rule.display_value` ก่อนส่ง response
  - locale ต้องอิง `frontend_language` ของ request เช่นเดียวกับ endpoint อื่นใน Frontend API
- เหตุผล:
  - ลดภาระ frontend ที่ต้องประกอบข้อความอธิบาย referral rule เอง
  - ให้ frontend ใช้ข้อความที่ตรงกับหน้าเดิมและตรง locale ได้ทันที

## 2026-04-05 — Customer Realtime Channel Must Be Separated from Admin Events Channel (APPROVED)

- ปรับ `RealtimePublicActivityUpdated` และ `FrontendApi RealtimeController`
- behavior ใหม่:
  - event `public.activity.updated` ของฝั่งลูกค้าไม่ broadcast ไป `{APP_NAME}_events` อีกต่อไป
  - ย้ายไป shared private channel `{APP_NAME}_members`
  - `/api/v1/realtime/config` คืน `shared_member_channel` แทน `public_channel`
  - ฝั่ง frontend ลูกค้าต้อง subscribe `private-{APP_NAME}_members` เพื่อรับ `public.activity.updated`
  - event direct ของทีมงาน เช่น `lotto.ticket.list.changed` และ `lotto.draw.status.changed` ไม่ถูก expose ใน realtime config ฝั่งลูกค้าอีก
- เหตุผล:
  - แยก boundary ของ customer/team ให้ชัด
  - ลดการเอา admin event และ admin payload ไปปะปนกับ channel ที่ frontend ลูกค้าใช้

## 2026-04-05 — Admin Lotto Ticket Realtime Total Must Count Active Tickets Only (APPROVED)

- ปรับ observer `LottoTicketRealtimeObserver` และ `LottoDrawRealtimeObserver`
- behavior ใหม่:
  - realtime event `lotto.ticket.list.changed` จะส่ง `total` เป็นจำนวนโพย `active` ปัจจุบันเท่านั้น
  - ใช้กติกาเดียวกันทุก action ที่ยิง event:
    - `created`
    - `cancelled`
    - `resulted`
  - ห้ามนับโพย `cancelled` และ `resulted` ปนใน total เพราะหน้าแอดมินใช้ค่านี้เป็น badge ของเมนู `รายการโพย`
- เหตุผล:
  - ให้ badge หน้าแอดมินตรงกับ dataset จริงของ `/lotto/tickets` ที่แสดงเฉพาะโพย `active`
  - ลดความสับสนจาก payload เดิมที่ count ทั้งตารางแต่ UI แสดง active-only

## 2026-04-05 — Lotto Admin Menus Must Trigger Dashboard `loadCnt` on Entry Consistently (APPROVED)

- ปรับเมนู Lotto ฝั่ง admin ใน scope `admin.lotto.*`
- behavior ใหม่:
  - เมื่อกดเข้าเมนู Lotto ต้องมีการเรียก `Gametech\Admin\Http\Controllers\DashboardController@loadCnt`
  - ครอบคลุม:
    - เมนู `รายการโพย`
    - เมนูใน `ตั้งค่าหวย`
    - เมนูใน `รายงาน Lotto`
  - หน้า DataTable ของ Lotto ใช้ shared `datatables_js` เป็นจุดเรียกตามเดิม
  - ทุกหน้า Lotto index ต้อง include shared partial `admin::layouts.loadcnt_js` เป็น fallback/guarantee
  - หน้า custom dashboard / Vue page ของ Lotto ที่ไม่ได้ผ่าน DataTables ต้อง include shared partial `admin::layouts.loadcnt_js`
  - หน้า Vue reports `results-by-date` และ `profit-loss-forecast` ต้องเรียก `loadCnt` ตอนเปิดหน้าด้วย
  - ใช้ global dedupe (`window.__adminLoadCntTriggered`) กัน request `loadCnt` ซ้ำในหน้าเดียวกัน
- เหตุผล:
  - ให้ badge และตัวเลขกลางใน admin sync ทันทีเมื่อเข้าเมนู Lotto ทุกหน้า
  - ปิดช่องที่บางเมนู Lotto เรียก `loadCnt` แต่บางเมนูไม่เรียก ทำให้พฤติกรรมไม่สม่ำเสมอ

## 2026-04-05 — Admin Lotto Tickets Menu Badge Must Be Sourced from Dashboard `loadCnt` (APPROVED)

- ปรับ badge เมนู `รายการโพย` (`lotto_tickets`)
- behavior ใหม่:
  - ค่า badge ต้องมาจาก `Gametech\Admin\Http\Controllers\DashboardController@loadCnt`
  - นับเฉพาะ ticket ที่ `lotto_tickets.status=active`
  - เอา logic ดึงจำนวนจาก `xhr.dt` ของหน้า `admin /lotto/tickets` ออก
  - หน้า admin ที่เรียก `loadCnt` ต้องอัปเดต badge `lotto_tickets` จาก payload เดียวกัน
- เหตุผล:
  - ให้ badge เมนูมี source เดียวและไม่ผูกกับการเปิดหน้า DataTable ของเมนูนั้นก่อน
  - ลด logic กระจัดกระจายที่หน้าโพยไปดึงข้อมูล badge เอง

## 2026-04-05 — Profit-Loss Forecast Report Must Use Vue Matrix View by Market and Draw (APPROVED)

- ปรับหน้า `admin /lotto/reports/profit-loss-forecast`
- behavior ใหม่:
  - เลิกใช้หน้า DataTable aggregate แบบ `draw_date + market + bet_type`
  - ใช้ Vue (`x-template`) single-page async menu ตาม pattern เดียวกับ `results-by-date`
  - filter เหลือเฉพาะ:
    - `market_id`
    - `draw_id`
  - report จะยังไม่แสดงจนกว่าจะเลือกทั้ง `ตลาด` และ `งวดหวย`
  - route ถูกแยกชัดเจน:
    - `GET /lotto/reports/profit-loss-forecast` = render หน้า
    - `GET /lotto/reports/profit-loss-forecast/draw-options?market_id=` = โหลดรายการงวดของตลาดที่เลือก
    - `GET /lotto/reports/profit-loss-forecast/loaddata?market_id=&draw_id=` = โหลด payload ของรายงาน
  - layout รายงานใหม่แบ่งเป็น 2 ส่วน:
    - summary matrix ตาม bet type พร้อมแถว `ยอดแทง`, `ส่วนลด`, `รับสุทธิ`, `ยอดถูก`, `ยอดสูงสุดต่อเลข`
    - ตารางรายหมายเลข แสดงยอดแทงสะสมของแต่ละหมายเลขจาก exposure จริง
  - market filter ยังต้องใช้ grouped `select2` พร้อม logo/icon ตามนโยบาย report เดิม
- เหตุผล:
  - หน้าเดิมตอบได้แค่ aggregate ระดับแถว ไม่ตรงรูปแบบการอ่านของทีมงานเวลาจะดูความเสี่ยงทั้งงวด
  - การเลือกตลาดก่อนแล้วค่อยเลือกงวดทำให้ scope ของข้อมูลชัด และไม่แสดงข้อมูลผิดงวดก่อนผู้ใช้เลือกครบ

## 2026-04-05 — Frontend Wallet Transactions API Must Provide Unified Member Cash History (APPROVED)

- เพิ่ม `GET /api/v1/wallet/transactions`
- behavior ใหม่:
  - ใช้ `wallet_transactions` เป็น source หลักของประวัติการเงินรวม
  - จำกัดข้อมูลที่ `scope=MEMBER`
  - response ส่ง:
    - `filters`
    - `summary` (`count`, `total_credit_amount`, `total_debit_amount`, `net_amount`)
    - `items`
    - `pagination`
  - รองรับ filter:
    - `type`
    - `date_start`
    - `date_stop`
    - `page`
    - `limit`
  - `type` สำหรับ frontend ใช้ค่าอ่านง่าย เช่น:
    - `deposit`
    - `withdraw`

## 2026-04-06 — Frontend Wallet Claim API Must Reuse Domain Repositories Without Controller Delegation (APPROVED)

- เพิ่ม `POST /api/v1/wallet/claim`
- intent:
  - ให้ frontend รับยอด `bonus|faststart|cashback|ic` ได้ผ่าน API ใหม่
  - แต่ต้องไม่ delegate ไป `Gametech\Wallet\Http\Controllers\TransferWalletController`
- decision:
  - `FrontendApi` รับ `type`
  - map ไป legacy id เดิม `BONUS|FASTSTART|CASHBACK|IC`
  - เรียก `MemberCreditLogRepository::tranBonus()` หรือ `MemberCreditFreeLogRepository::tranBonus()` ตรงตาม config ระบบ
  - response ต้องคืนยอดหลังทำรายการกลับไปด้วยเพื่อให้ frontend sync state ได้ทันที
- consequence:
  - contract ฝั่งลูกค้าอยู่ใน `FrontendApi` ตาม boundary ที่กำหนด
  - ยังรักษา business rule เดิมของระบบ wallet/bonus transfer ไว้

## 2026-04-06 — Agent Startup Must Use Lean Core Docs First (APPROVED)

- ปัญหา:
  - startup เดิมบังคับอ่าน `system_current_state.md` และ `decision_log.md` ทั้งก้อนทุกงาน
  - งานเล็กเสีย token สูงมากตั้งแต่ก่อนเริ่ม implement
- decision:
  - startup default เปลี่ยนเป็นอ่านชุดสั้น:
    - `START_HERE.md`
    - `agent_rules.md`
    - `startup_digest.md`
    - `adr_baseline.md`
    - `adr_index_by_domain.md`
    - `04_PLANS/README.md`
  - ใช้ domain notes ใน `docs/internal/03_DOMAINS/` เป็นชั้นถัดไป
  - ให้ `system_current_state.md` และ `decision_log.md` เป็น escalation docs ตาม risk และ scope ของงาน
- consequence:
  - startup token ลดลงมากสำหรับงานเล็กและงานกลาง
  - ยังรักษา source-of-truth และ decision boundary ได้ เพราะ full docs ยังถูกเปิดเมื่อ task ต้องใช้จริง
    - `lotto_bet`
    - `lotto_refund`
    - `referral`
    - `cashback`
    - `ic`
    - `bonus`
    - `game`
    - `admin_adjust`
    - `rollback`
    - `other`
  - เก็บ `ref_type` เดิมไว้ใน payload เพื่อให้อ่าน audit trace ได้
  - รายการ `LOTTO_BET` และ `LOTTO_CANCEL` ต้อง enrich บริบทหวย (`ticket_id`, `market_name`, `draw_date`)
- เหตุผล:
  - แยกจาก `member/history` แบบ legacy ที่แบ่งรายประเภทและไม่ได้อิง ledger กลาง
  - ให้ frontend มีเส้นเดียวสำหรับหน้า “ประวัติการเงินรวม” และสามารถกรองดูฝากอย่างเดียว ถอนอย่างเดียว หรือรายการหวยได้จาก route เดียว

## 2026-04-05 — Frontend Lotto Ticket APIs Must Surface Cancel Context (APPROVED)

- ปรับ `GET /api/v1/lotto/tickets` และ `GET /api/v1/lotto/tickets/{id}`
- behavior ใหม่:
  - คง field เดิมเช่น `refund_amount`, `status`, `result_outcome` ไว้เหมือนเดิม
  - เพิ่ม field cancel context ระดับโพย:
    - `cancelled_at`
    - `cancelled_by_name`
    - `cancelled_by_type`
    - `cancel_reason`
  - การ resolve ผู้ยกเลิก:
    - ใช้ `wallet_transactions(ref_type=LOTTO_CANCEL)` เป็น source หลัก
    - fallback ไป `lotto_tickets.cancelled_by` ถ้าไม่พบ transaction context
  - การ resolve สาเหตุ:
    - ใช้ `lotto_tickets.reason` เป็นหลัก
    - fallback ไป `wallet_transactions.meta.reason` ถ้า environment ยังไม่ migrate คอลัมน์ `reason`
- เหตุผล:
  - ให้หน้า history/detail ของสมาชิกแสดงได้ครบว่าโพยถูกยกเลิกเมื่อไร ใครเป็นผู้ยกเลิก และเพราะอะไร
  - ลดภาระ frontend ที่ต้องไปประกอบข้อมูลยกเลิกจากหลาย endpoint เอง

## 2026-04-05 — Tickets-Cancel Report Splits Created Time from Latest Update Time (APPROVED)

- ปรับรายงาน `admin /lotto/reports/tickets-cancel`
- behavior ใหม่:
  - คอลัมน์แรกเปลี่ยนเป็น `เวลาสร้างรายการ` และแสดงจาก `lotto_tickets.created_at` เสมอ
  - เพิ่มคอลัมน์ `เวลาอัปเดทล่าสุด` หลัง `ผู้ยกเลิก`
    - ใช้ `cancelled_at` ถ้ามี
    - fallback เป็น `updated_at`
    - fallback สุดท้ายเป็น `created_at`
- เหตุผล:
  - แยกเวลา “ตอนสร้างโพย” ออกจากเวลา “ตอนมี action ล่าสุด” ให้ทีมงานอ่านย้อนหลังได้ชัดเจนขึ้น
  - ครอบคลุมทั้งเคสยกเลิกโพยและเคสออกผลโดยไม่ทำให้คอลัมน์เวลาแรกคลุมเครือ

## 2026-04-05 — Lotto Cancelled Toast Must Show Ticket Owner and Actor (APPROVED)

- ปรับ realtime event `lotto.ticket.list.changed` สำหรับ action `cancelled`
- behavior ใหม่:
  - payload ต้องส่ง `owner_id` และ `actor_id` เมื่อ resolve ได้
  - message ของ toast คืนโพยต้องอ่านได้ใน event เดียว เช่น
    - `มีการคืนโพยหวย: หวย ธกส. งวดวันที่ 2026-04-16 ของ xxx โดย xxxx`
  - `owner_id` = เจ้าของโพย
  - `actor_id` = คนที่กดยกเลิก/คืนโพย
    - resolve จาก `wallet_transactions(ref_type=LOTTO_CANCEL)` ก่อน
    - fallback ไปที่ `lotto_tickets.cancelled_by` ถ้ายังไม่พบ
- เหตุผล:
  - ให้ทีมงานรู้ทันทีว่าโพยของใครถูกคืน และใครเป็นผู้ดำเนินการ โดยไม่ต้องเปิดรายละเอียดเพิ่ม

## 2026-04-05 — Admin Ticket Cancel Must Capture Reason and Surface Cancel Context (APPROVED)

- ปรับหน้า `admin /lotto/tickets` และรายงาน `admin /lotto/reports/tickets-cancel`
- behavior ใหม่:
  - หน้า `รายการโพย/ยกเลิกโพย` เพิ่มปุ่ม `ยกเลิกโพย` ในคอลัมน์ `จัดการ`
  - การยกเลิกโพยรายใบโดยทีมงานต้องกรอก `สาเหตุการยกเลิก` ก่อนส่งทุกครั้ง
  - endpoint ภายใน `POST /lotto/tickets/{id}/cancel`
    - อนุญาตเฉพาะ ticket `active`
    - อนุญาตเฉพาะ draw ที่ยัง `open`
    - rollback exposure
    - คืนเงินผ่าน `wallet_transactions(ref_type=LOTTO_CANCEL, created_by_type=admin)`
    - เก็บ `reason`, `cancelled_at`, `cancelled_by`, `refund_amount`
  - payload รายละเอียดโพย (`tickets/loaddata`) ส่ง `reason/cancelled_at/cancelled_by_name/refund_amount`
  - report `tickets-cancel` เพิ่มคอลัมน์ `สาเหตุ`
  - กรณี draw `งดออกผล` แล้วใช้ flow `cancel-all-refund`
    - ticket ที่ถูกคืนเงินทั้งงวดต้องถูก stamp `reason = งดออกผล`
  - rollout/schema compatibility:
    - เพิ่ม migration สำหรับ `lotto_tickets.reason`
    - ระหว่างช่วงที่บาง environment ยังไม่ migrate ระบบต้องไม่พัง
    - ให้ fallback เก็บ/อ่าน `reason` จาก `wallet_transactions.meta.reason` ชั่วคราว
- เหตุผล:
  - ให้ทีมงาน audit ได้ว่าการยกเลิกหรือคืนเงินเกิดจากอะไร ใครเป็นคนทำ และทำเมื่อไร
  - ลดกรณีโพยถูกคืนเงินแล้วเหลือเพียงสถานะ `cancelled` แต่ไม่มีบริบทให้อ่านย้อนหลัง

## 2026-04-05 — All Lotto Report Menus Must Use Real Data Modules Instead of Mockups (APPROVED)

- ปรับเมนูรายงาน Lotto ที่ยังเป็น mockup:
  - `admin /lotto/reports/profit-loss-forecast`
  - `admin /lotto/reports/member-bet-types`
  - `admin /lotto/reports/tickets-cancel`
  - `admin /lotto/reports/blocked-numbers`
- behavior ใหม่:
  - ทุกเมนูข้างต้นเปลี่ยนจาก `SectionController + reports/mockup.blade.php`
  - เป็น controller + DataTable + Transformer + index/create/table views แบบโมดูลจริง
  - `profit-loss-forecast` ใช้ข้อมูลจริงจาก draw bet settings, exposure และ ticket items
  - report Lotto ทุกหน้าที่มี filter ตลาดใช้ `select2` แบบ grouped ตามกลุ่มรายการหวย พร้อม logo/icon เมื่อมีข้อมูล
    - `pending-bets`
    - `profit-loss-forecast`
    - `member-bet-types`
    - `tickets-cancel`
    - `blocked-numbers`
    - `exposure`
  - report Lotto ที่มี filter bar ใช้ immediate apply
    - หน้า DataTables เปลี่ยน select/date แล้ว redraw ทันที
    - หน้า Vue single-page fetch async ทันทีเมื่อ filter ครบ
    - ช่อง text filter ใช้ debounce สั้นก่อน redraw/fetch
    - ยกเลิกปุ่ม `ค้นหา` คงไว้เฉพาะปุ่ม `ล้างค่า`
  - `member-bet-types` aggregate ข้อมูลจริงตาม `member + market + bet_type`
  - `tickets-cancel` อ่าน ticket ทุกสถานะและแสดง `แพกเกจ/ส่วนลด/สุทธิ/ยอดถูก`
  - `tickets-cancel` resolve ผู้ยกเลิกจาก `wallet_transactions(ref_type=LOTTO_CANCEL)` ก่อน
    - เพื่อให้เคสลูกค้ายกเลิกเองแสดงชื่อผู้ยกเลิกได้ แม้ `lotto_tickets.cancelled_by` ว่าง
  - `blocked-numbers` อ่านเลขอั้น/จำกัดอนาคตจาก `lotto_number_blocks` โดยตรง
- เหตุผล:
  - ให้เมนูรายงาน Lotto ใช้งานปฏิบัติการได้ครบ ไม่เหลือ placeholder ในส่วน reports
  - รักษา pattern เดียวกับโมดูล admin Lotto ที่ทำเป็นของจริงแล้วก่อนหน้า
## 2026-04-05 — Admin Ticket Tables Split Draw Date, Market, and Package Columns (APPROVED)

- ปรับ DataTable ของหน้า `admin /lotto/tickets`
- behavior ใหม่:
  - คอลัมน์ `งวดหวย` แสดงเฉพาะวันที่งวด ไม่พ่วงชื่อรายการหวย
  - เพิ่มคอลัมน์ `รายการหวย` แยกออกมาต่างหากและแสดง logo/icon หน้าชื่อหวยเมื่อมีข้อมูล
  - เพิ่มคอลัมน์ `แพกเกจ` โดยใช้ชื่อจาก snapshot `lotto_ticket_items.package_name_at_time`
  - เอาคอลัมน์ `ยอดถูก` ออกจากหน้ารายการ
- เหตุผล:
  - ให้ทีมงานสแกนรายการโพยได้เร็วขึ้น เพราะวันงวด ชื่อหวย และแพกเกจอยู่คนละคอลัมน์
  - ใช้ package snapshot เพื่อให้ชื่อแพกเกจสะท้อนข้อมูลตอนแทงจริง ไม่ผูกกับการแก้ package ภายหลัง

## 2026-04-05 — Lotto Admin Toasts Must Carry Alert Avatar and New Ticket Toast Must Show Actor+Amount (APPROVED)

- ปรับ realtime Lotto notification ฝั่งหน้าแอดมินทีมงาน
- behavior ใหม่:
  - event `lotto.ticket.list.changed` เมื่อ action=`created` ต้องใส่ผู้ทำรายการ (`actor_id`) และยอดเงิน (`amount`) ใน payload
  - message ของ create toast ต้องอ่านได้ใน event เดียว เช่น
    - `มีรายการโพยหวยใหม่: หวยมาเลเซีย งวดวันที่ 2026-04-05 โดย 0855626503 จำนวน 200`
  - Lotto toast ที่หน้าแอดมินทีมงานมีอยู่ตอนนี้ 2 ตัว:
    - `lotto.ticket.list.changed`
    - `lotto.draw.status.changed`
  - ทั้งสองตัวต้อง render ด้วย
    - `className = rt-toast rt-info gt-toast gt-toast-info`
    - `avatar = /assets/admin/icons/alert.webp?v=1`
- เหตุผล:
  - ให้ทีมงานรู้ทันทีว่าใครเป็นคนทำรายการและยอดเท่าไร โดยไม่ต้องเปิดหน้าโพย
  - ทำให้ visual style ของ Lotto toast สม่ำเสมอกับ alert toast มาตรฐานใน admin

## 2026-04-05 — Pending Bets Report Must Use Real Active Ticket Dataset (APPROVED)

- ปรับเมนู `admin /lotto/reports/pending-bets`
- behavior ใหม่:
  - route หน้า report เปลี่ยนจาก mockup `SectionController` ไปใช้ `LottoTicketController@index`
  - ใช้ DataTable และชุด filter เดียวกับหน้า `admin /lotto/tickets`
  - dataset หลักแสดงเฉพาะ ticket ที่ `status=active`
  - เพิ่ม route `admin.lotto.reports.pending_bets.loaddata` สำหรับ modal รายละเอียดโพย
- เหตุผล:
  - เมนู `รอผลเดิมพัน` ต้องตอบงานปฏิบัติการด้วยข้อมูลจริง ไม่ใช่ placeholder
  - ลดการทำ logic query ซ้ำ เพราะ requirement ชุดข้อมูลตรงกับโพย active ที่ยังรอผลอยู่

## 2026-04-05 — Admin Lotto Tickets Menu Shows Only Active Tickets (APPROVED)

- ปรับ query ของหน้า `admin /lotto/tickets`
- behavior ใหม่:
  - DataTable หลักแสดงเฉพาะ ticket ที่ `status=active`
  - รายการ `cancelled` และ `resulted` ถูกตัดออกจากหน้ารายการนี้
  - filter/search เดิมยังทำงาน แต่ต้องทำงานบน active-only dataset
- เหตุผล:
  - ให้เมนู `รายการโพย/ยกเลิกโพย` โฟกัสเฉพาะโพยที่ยังมี action เชิงปฏิบัติการได้จริง
  - ลด noise จากโพยที่ถูกยกเลิกแล้วหรือออกผลแล้ว

## 2026-04-05 — Member Ticket Cancel Is Limited to 4 Times Per Day and Must Be 10 Minutes Before Close (APPROVED)

- ปรับ `POST /api/v1/lotto/tickets/{id}/cancel`
- behavior ใหม่:
  - ยังคงใช้ได้เฉพาะ ticket ของสมาชิกคนนั้นที่ `status=active`
  - draw ต้องยัง `open`
  - เพิ่ม time-window guard:
    - ต้องยกเลิกก่อน `draw.close_at` อย่างน้อย `10` นาที
  - เพิ่ม daily quota:
    - สมาชิกยกเลิกโพยได้ไม่เกิน `4` ครั้งต่อวัน
    - นับเฉพาะ self-cancel จาก `wallet_transactions(ref_type=LOTTO_CANCEL, created_by_type=member, created_by_id=memberId)`
    - ไม่รวมเคสทีมงานยกเลิกโพยหรือระบบคืนโพย
- เหตุผล:
  - ป้องกันการยกเลิกตอนใกล้ปิดรับมากเกินไป
  - จำกัด abuse จากการยกเลิกโพยซ้ำจำนวนมากในวันเดียวกัน

## 2026-04-05 — Frontend Lotto Ticket APIs Must Return Clear Result Summary Fields (APPROVED)

- ปรับ `GET /api/v1/lotto/tickets` และ `GET /api/v1/lotto/tickets/{id}`
- behavior ใหม่:
  - คง field เดิมไว้เพื่อ backward compatibility เช่น `status`, `total_win_amount`, `items[].result_status`, `items[].win_amount`
  - เพิ่ม field ระดับโพยที่ frontend ใช้ได้ตรง ๆ:
    - `draw_status`, `draw_result_at`
    - `result_outcome`, `is_final`, `is_winner`
    - `status_label`, `draw_status_label`, `result_outcome_label`, `result_message`
    - `item_count`, `winning_item_count`, `losing_item_count`, `pending_item_count`
  - endpoint รายละเอียดโพยเพิ่ม field ระดับรายการ:
    - `raw_result_status`
    - `is_winner`
    - `result_status_label`
    - `result_message`
  - policy การ derive `result_outcome`:
    - `cancelled` ถ้าโพยถูกยกเลิก
    - `refunded` ถ้า draw ถูกคืนเงินทั้งงวด
    - `no_result` ถ้างวดงดออกผล
    - `won` / `lose` เมื่อ settle แล้ว
    - `betting_open` ถ้างวดยังเปิดรับ
    - `pending_result` ถ้ายังรอผลหรือรอทีมงานประกาศผล
- เหตุผล:
  - ลดภาระ frontend ที่ต้องเดาสถานะจากหลาย field เอง
  - ให้หน้าโพยและหน้ารายละเอียดโพยสื่อกับผู้ใช้ได้ทันทีว่า “รอผล / ถูกรางวัล / ไม่ถูกรางวัล / คืนเงินแล้ว”

## 2026-04-04 — Ticket Resulted Realtime Notification Must Broadcast Once Per Draw (APPROVED)

- ปรับ `LottoTicketRealtimeObserver`
  - คงการ broadcast สำหรับ `created` และ `cancelled`
  - ยกเลิกการ broadcast `resulted` ระดับ ticket
- ปรับ `LottoDrawRealtimeObserver`
  - เมื่อ `draw.status` เปลี่ยนเป็น `resulted`
  - ให้ broadcast `lotto.ticket.list.changed` (`action=resulted`) เพิ่มอีก 1 ครั้งในระดับ draw
- เหตุผล:
  - settlement 1 งวดจะ update ticket หลายใบ
  - ถ้ายิง noti จาก ticket observer จะซ้ำตามจำนวน ticket ในงวดเดียวกัน
  - business event ที่ถูกต้องคือ “งวดนี้ถูกตัดสินผลแล้ว” จึงต้องยิงครั้งเดียวต่อ draw

## 2026-04-04 — Settlement and Validator Must Accept 4-digit First Prize for 4D Markets (APPROVED)

- ปรับ `SettlementService::normalizeResultNumber`
  - รองรับ `first_prize` ความยาว `3|4|5|6`
  - สำหรับค่า 4 หลัก เช่น `2575`:
    - derive `top_3 = 575`
    - derive `top_2 = 75`
    - `bottom_2` ยังมาจาก `last_2_digits` ตามเดิม
- ปรับ `ResultValidator`
  - default validation ของ `first_prize` ต้องยอม `3|4|5|6` หลัก
- ปรับ manual settle ใน admin
  - ฟอร์ม/validation ต้องยอม `first_prize` 3-6 หลัก
- เหตุผล:
  - ให้ตลาด 4D เช่น หวยมาเลเซีย settle ได้จริง
  - ปิดช่อง mismatch ที่ source test ผ่าน แต่ apply จริงล้มใน settlement ด้วย error ความยาวเลขไม่รองรับ

## 2026-04-04 — Auto Result Unhandled Exceptions Must Persist Fetch Logs and Draw Fetch State (APPROVED)

- ปรับ `AutoResultPipelineService`
  - V2 cutover path ต้องครอบ runner call ด้วย exception guard
  - ถ้า runner throw ก่อน `markAndLog()` ต้องเขียน fetch log เอง
  - ใช้ `status=VALIDATION_ERROR`, `error_code=UNHANDLED_EXCEPTION`, `error_stage=PIPELINE`
- ปรับ `lotto:fetch-auto-results`
  - command-level per-draw catch ต้องบันทึก fetch log เพิ่มด้วย
  - ห้ามปล่อยให้มีแค่ Laravel app log (`LOTTO_AUTO_RESULT_DRAW_EXCEPTION`) โดยไม่มีแถวใน `lotto_result_fetch_logs`
- ปรับ `AutoResultHardeningService`
  - เพิ่ม helper สำหรับ persist unhandled exception ลง fetch log และ draw fetch fields
- ปรับ auto-result runtime draw updates
  - field metadata ภายในเช่น source snapshot / fetch attempts / fetch status ต้อง `saveQuietly()`
  - ห้ามยิงซ้ำเข้า audit table `logs`
  - ให้ `lotto_result_fetch_logs` เป็น execution audit หลักของ auto-result
- เหตุผล:
  - ปิด blind spot ที่ `result_fetch_attempts`/`updated_at` ขยับแล้ว แต่ไม่มี fetch log ใหม่ให้ trace
  - ทำให้ debug งวดค้างแบบ `draw 548` บน live ได้จาก DB โดยไม่ต้องพึ่ง app log อย่างเดียว

## 2026-04-04 — Dashboard Summary Bucket Queue Must Merge Pending Sections and Deduplicate Per Bucket (APPROVED)

- ปรับ `SyncDashboardSummaryBucket`
  - เปลี่ยนเป็น `ShouldBeUniqueUntilProcessing`
  - คง `WithoutOverlapping` ต่อ bucket เดิม (`web_code + summary_date`)
  - ก่อน sync ต้อง consume pending payload ล่าสุดของ bucket จาก cache
- ปรับ `DashboardSummarySyncService`
  - ก่อน dispatch job ต้อง merge pending payload ของ bucket เดียวกันใน cache ก่อน
  - merge อย่างน้อย:
    - `updated_sections`
    - `source_type`
    - `source_id`
- เหตุผล:
  - ลดงาน queue ซ้ำที่ recompute bucket เดิมแล้วได้ผลเดิม
  - ยังรองรับ follow-up sync ได้เมื่อมี event ใหม่เข้ามาระหว่าง job ปัจจุบันกำลังรัน
  - แยกหน้าที่ชัดเจน:
    - `ShouldBeUniqueUntilProcessing` = dedupe ตอนยังรอคิว
    - `WithoutOverlapping` = กันรันชนกันจริง

## 2026-04-04 — Lotto Ticket Realtime Toast Message Must Include Market and Draw Date When Available (APPROVED)

- ปรับ event `LottoTicketListChanged`
- behavior ใหม่:
  - event รับ context เพิ่ม:
    - `market_name`
    - `draw_date`
  - field `message` จะ compose จาก action + context ของ draw เมื่อมีข้อมูล
  - ตัวอย่าง:
    - `มีโพยหวยถูกตัดสินผลแล้ว: หวยออมสิน งวดวันที่ 2026-04-04`
- ปรับ observer `LottoTicketRealtimeObserver`
  - ตอน broadcast create/cancel/result ต้อง load `draw.market` แล้วส่ง context เข้ามาใน event
- เหตุผล:
  - ลดความกำกวมของ toast เดิมที่บอกแค่ว่ามีโพยถูกตัดสินแล้ว แต่ไม่รู้ว่าเป็นหวยอะไรและงวดวันไหน

## 2026-04-04 — Auto Result Empty Upstream Payload Must Be `NOT_READY` and Scheduler Must Survive Per-Draw Exceptions (APPROVED)

- ปรับ `V2ResultPipelineRunner`
- behavior ใหม่:
  - ถ้า validation fail ด้วย `REQUIRED_FIELD_MISSING`
  - และ readiness ประเมินแล้วพบว่าฟิลด์ผลหลักยังไม่ครบ
  - ให้ reclassify outcome เป็น `NOT_READY` ที่ stage `READINESS`
  - ห้ามตอบ `VALIDATION_ERROR` ทันทีในเคส payload ตรงวันแต่ผลยังว่าง เช่น `results: []`
- ปรับ `lotto:fetch-auto-results`
  - ครอบ `processDraw()` ด้วย per-draw exception guard
  - เมื่อ draw ใด throw exception ต้อง log `LOTTO_AUTO_RESULT_DRAW_EXCEPTION`
  - command ต้อง continue ไป draw ถัดไปและสรุป `unhandled_draw_exceptions`
- เหตุผล:
  - ให้ draw ที่ upstream ยังไม่ออกผลจริงเข้า retry/backoff policy ตาม design
  - ป้องกัน scheduler ตันทั้งรอบจาก draw เดียวและลด blind spot ที่ไม่มี fetch log

## 2026-04-04 — Frontend Register Must Return Specific Post-Validation Failure Reasons (APPROVED)

- ปรับ `POST /api/v1/auth/register` ให้แยก failure หลัง validation เป็นสาเหตุที่ชัดเจนขึ้น
- behavior ใหม่:
  - response failure หลัง validation มี `error_code`
  - response failure อาจมี `details.stage` และ `details.reason`
  - ตัวอย่างรหัสที่เพิ่ม:
    - `REGISTER_MEMBER_CREATE_FAILED`
    - `REGISTER_GAME_ACCOUNT_CONNECT_FAILED`
    - `REGISTER_GAME_ACCOUNT_CREATE_FAILED`
    - `REGISTER_GAME_ACCOUNT_UNAUTHORIZED`
    - `REGISTER_GAME_ACCOUNT_FALLBACK_FAILED`
    - `REGISTER_UNKNOWN_FAILURE`
- เหตุผล:
  - ให้ frontend แสดงข้อความที่เจาะจงกับสาเหตุได้
  - ลดการต้อง debug จากข้อความ generic `ไม่สามารถสมัครสมาชิกได้ในขณะนี้`

## 2026-04-04 — Frontend Register Seamless Flow Must Accept Array Payload Without Post-Create Source Mutation Failure (APPROVED)

- ปรับ `Gametech\Game\Repositories\GameUserRepository::addGameUser`
- behavior ใหม่:
  - อ่าน `user_create/user_update` จาก source ได้ทั้งกรณี `array` และ `object`
  - หลังสร้าง `games_user` สำเร็จแล้ว ถ้า source เป็น `array` ต้องถือว่างานเสร็จได้ทันที
  - จะ sync `game_user` กลับไปที่ source เฉพาะเมื่อ source เป็น object และรองรับการ `save()`
- เพิ่ม regression test คุมเคส:
  - caller ส่ง `array` จาก frontend register
  - caller ส่ง `object` ที่ยังต้องการรับค่า `game_user`
- เหตุผล:
  - ปิด bug ที่ทำให้ `POST /api/v1/auth/register` ล้มใน production หลังสร้าง `games_user` แล้ว
  - ป้องกัน failure แบบ `Attempt to assign property ... on array` จาก seamless register path

## 2026-04-04 — `GET /api/v1/lotto/draws` Must Return Latest Non-Draft Draw Per Market (APPROVED)

- ปรับ `FrontendApi LottoController@draws`
- behavior ใหม่:
  - เลือกงวดล่าสุดต่อ `market_id`
  - ใช้เฉพาะแถวที่ `status != draft`
  - ถ้ามี `draft` ที่ใหม่กว่า แต่ market เดียวกันมี `open/closed/resulted` อยู่ ให้คืน non-draft ล่าสุดแทน
- เหตุผล:
  - หน้า frontend ต้องได้งวดที่ใช้งาน/อ้างอิงได้จริง ไม่ใช่งวดร่าง
  - ลด mismatch ระหว่างรายการงวดกับ flow หน้าแทง/ผลรางวัล

## 2026-04-04 — `GET /api/v1/lotto/markets/latest` Must Use Latest Non-Draft Draw Per Market (APPROVED)

- ปรับ `FrontendApi LottoController@marketsLatestByGroup`
- behavior ใหม่:
  - `latest_draw` ของแต่ละ `market` ต้องเลือกตาม priority:
    - `open` ล่าสุด
    - ถ้าไม่มี `open` ค่อยใช้ non-draft ล่าสุด
  - ห้ามคืน `draft`
  - แยกสถานะหน้า frontend เพิ่ม:
    - `no_result`
    - `refunded`
  - mapping label:
    - `open` -> `แทงหวย`
    - `closed` -> `รอผล`
    - `resulted` -> `ออกผล`
    - `no_result` -> `ยกเลิก`
    - `refunded` -> `ยกเลิก`
- เหตุผล:
  - หน้าเลือกรายการหวยต้องพา user ไปงวดที่ใช้งานได้ก่อน
  - frontend ยังแยก machine-readable `status` ได้ชัดเจน แต่ข้อความที่โชว์ผู้ใช้ควรรวมเป็น `ยกเลิก`

## 2026-04-10 — `GET /api/v1/lotto/markets/latest` Must Order Markets By Latest Close Time Ascending (APPROVED)

- ปรับ `FrontendApi LottoController@marketsLatestByGroup`
- behavior ใหม่:
  - `markets` ในแต่ละ `group` ต้องเรียงตาม `latest_draw.close_at` จากน้อยไปมาก
  - ถ้า `latest_draw.close_at` เป็น `null` ให้ไปท้ายรายการ
  - ถ้าเวลาเท่ากัน ให้คงลำดับรองแบบ deterministic ด้วย `market_name`, `market_id`
- เหตุผล:
  - หน้า frontend ต้องเรียงรายการหวยตามเวลาปิดรับใกล้สุดก่อน เพื่อช่วยตัดสินใจแทงได้เร็วขึ้น

## 2026-04-04 — `BetService` Container Binding Must Include `LottoPackageResolver` Before `WalletTransactionService` (APPROVED)

- แก้ binding ของ `Gametech\Lotto\Services\BetService` ใน `LottoServiceProvider`
- constructor dependency order ที่ถูกต้อง:
  - `ExposureService`
  - `LottoConfigResolver`
  - `LottoPackageResolver`
  - `WalletTransactionService`
- เพิ่ม regression test ให้ container resolve `BetService` ได้จริง
- เหตุผล:
  - ปิด bug ที่ทำให้ `POST /api/v1/lotto/bet` ล้มตั้งแต่ service construction
  - ฝั่ง `FrontendApi` จะกลบ exception นี้เป็นข้อความ generic ทำให้ production debug ยาก ถ้าไม่มี test คุม binding

## 2026-04-04 — Frontend API v1 Adds Authenticated Member Change-Password Without Current Password (APPROVED)

- เพิ่ม endpoint `POST /api/v1/member/change-password`
- request รับเฉพาะ:
  - `password`
  - `password_confirmation`
- รองรับ alias `password_confirm`
- ไม่ต้องส่งรหัสผ่านเดิม เพราะ route นี้ถูกป้องกันด้วย Bearer token middleware อยู่แล้ว
- implementation จะ update ทั้ง:
  - `members.password` เป็น hash
  - `members.user_pass` เพื่อรักษา legacy compatibility ชั่วคราว
- เหตุผล:
  - ลด friction ฝั่ง client ที่ login อยู่แล้ว
  - คง compatibility กับ flow เกม/legacy ที่ยังอ้าง `user_pass`

## 2026-04-04 — `selected-package` Returns Bet Settings for Client Betting Preview (APPROVED)

- ปรับ `GET /api/v1/lotto/groups/{groupId}/selected-package` ให้คืนข้อมูล package ที่เลือกพร้อม:
  - `group_id`
  - `package_id`
  - `name`
  - `image`
  - `bet_settings[]` โดยแต่ละรายการมี `bet_type`, `payout`, `discount_percent`
- เหตุผล:
  - ให้หน้า client แสดง preview ก่อนส่งโพยได้ใน call เดียวหลังผู้ใช้เลือก package
  - ลดการต้อง merge ข้อมูลจาก `selected-package` กับ `packages` เองทุกครั้ง
  - ยังรักษา boundary เดิม: submit bet จริงต้องยึด `package_id` แล้ว resolve server-side ใหม่เสมอ

## 2026-04-04 — Single-Page Admin Async Menu Pattern Reuses Dashboard Root Vue Bootstrap (APPROVED)

- สำหรับเมนู admin แบบหน้าเดียวที่ต้อง render UI แบบ Vue component ทั้งหน้าและ fetch ข้อมูล async ในหน้าเดิม:
  - ให้ Blade วาง custom component tag ใน `@section('content')` โดยตรง
  - ให้ layout ทั้งหน้าอยู่ใน `script type="text/x-template"`
  - ให้ register component ผ่าน `Vue.component(...)` ใน `script type="module"`
  - ให้ helper functions ที่ component ใช้จริงอยู่ใน module scope เดียวกัน ห้ามแยกไว้คนละ script ถ้า component ต้องเรียกตรง
  - ให้แยก root bootstrap เป็น `new Vue({ el: '#app' ... })` อีก script ตาม pattern ของ dashboard
- สำหรับ input ที่เปลี่ยนค่าแล้วต้องยิง fetch ทันที:
  - ให้ handler รับค่าจาก `$event.target.value` โดยตรง แทนการอ้าง state ที่เพิ่ง bind เพื่อหลีกเลี่ยงจังหวะ model ยังไม่ sync
- scope exclusion:
  - ไม่รวมเมนูที่ใช้ DataTables เป็นแกนหลักของหน้า เพราะหน้าเหล่านั้นมี lifecycle/filter/reload คนละแบบและต้องใช้ pattern ของ DataTables โดยตรง
- เหตุผล:
  - ทำให้ custom component ถูก compile แน่นอนใน layout admin ปัจจุบัน
  - ลดเคส component หา helper ไม่เจอเพราะอยู่คนละ script scope
  - ลดเคส change event ยิงด้วยค่าเก่าหรือไม่ยิง request ใหม่ตามค่าที่ผู้ใช้เพิ่งเลือก

## 2026-04-03 — Results-by-Date Page Auto-Fetches Initial Date on Mount (APPROVED)

- หน้า `admin/lotto/reports/results-by-date` จะ trigger `GET /lotto/reports/results-by-date/loaddata` ตอน mount ทันที
- policy การเลือกวันที่เริ่มต้น:
  - ถ้า URL มี `?draw_date=YYYY-MM-DD` ที่ valid ให้ใช้ค่านั้น
  - ถ้าไม่มี ให้ใช้วันที่ปัจจุบันของ browser
- เมื่อ mounted แล้ว input `draw_date` และข้อมูลในตารางต้องอิงวันเดียวกันเสมอ
- เหตุผล:
  - ปิดช่อง mismatch ที่หน้าแสดง input เป็นวันปัจจุบัน แต่ข้อมูลในตารางยังเป็น payload เดิมก่อน mount
  - คงความสามารถเปิดลิงก์พร้อม `draw_date` เพื่อดูย้อนหลังได้โดยไม่ถูกทับเป็นวันปัจจุบัน

## 2026-04-03 — Split Results-by-Date Page Route and LoadData Route (APPROVED)

- แยกหน้า `admin/lotto/reports/results-by-date` ออกเป็น 2 เส้นชัดเจน:
  - `GET /lotto/reports/results-by-date` = render หน้า report
  - `GET /lotto/reports/results-by-date/loaddata` = คืนข้อมูล JSON ตาม `draw_date`
- หน้า Vue ของ report จะเรียกเส้น `loaddata` โดยตรงตอนเลือกวันที่ และอัปเดตตารางแบบ async
- เหตุผล:
  - ให้โครง route สอดคล้อง pattern เมนู dashboard (หน้าแสดงผลแยกจาก data endpoint)
  - ลดความสับสนในการ debug ว่า request ไหนคือ page render และ request ไหนคือ data load

## 2026-04-03 — Results-by-Date Admin Report Uses Vue x-template Async Search (APPROVED)

- หน้า `admin/lotto/reports/results-by-date` refactor เป็น Vue component (`script type="text/x-template"`)
- กดค้นหา (`draw_date`) แล้วเรียก endpoint เดิมแบบ AJAX (`Accept: application/json`) และอัปเดตตารางในหน้าเดิม
- URL query (`?draw_date=...`) ยังถูกอัปเดตผ่าน `history.replaceState`
- เหตุผล:
  - ให้ UX ค้นหาเร็วขึ้นโดยไม่ reload หน้า และทำโครงหน้าให้ maintain ง่ายขึ้นใน pattern Vue

## 2026-04-03 — Telegram Result Message Uses Market `auto_result_time` and Adds Origin Line (APPROVED)

- ปรับ `SendDrawResultSummaryTelegramJob`:
  - บรรทัด `เวลาออกผล` ใน header ใช้ `lotto_markets.auto_result_time` เป็นหลัก
  - fallback เป็น `draw.result_at` เมื่อ market ไม่มี `auto_result_time`
- เพิ่มบรรทัดใหม่ในข้อความ:
  - `ออกผลโดยระบบออโต้ เวลา HH:mm`
  - หรือ `ออกผลโดยทีมงาน เวลา HH:mm`
- เหตุผล:
  - ให้ header สอดคล้องเวลาออกผลตามรอบตลาด
  - ให้ทีมงานแยกได้ทันทีว่าเป็นผลจากระบบอัตโนมัติหรือจากการดำเนินการโดยทีมงาน

## 2026-04-03 — Add `งดออกผล` Option in Draw Settle Mode Modal (APPROVED)

- ปรับ modal เลือกวิธีออกผลในหน้า `admin/lotto/draws` จากเดิม `Manual/Auto` ให้มีตัวเลือก `งดออกผล`
- เพิ่ม endpoint:
  - `POST /lotto/draws/mark-no-result`
- behavior:
  - อนุญาตเฉพาะงวดสถานะ `closed`
  - set draw เป็น `resulted` พร้อม `result_number.no_result=true` และ `result_number.status=no_result`
- เหตุผล:
  - ให้ทีมงานระบุเคสงดออกผลได้ทันทีจาก modal เดียวกับปุ่มหลัก `ออกผล` โดยไม่ต้องอ้อมหลายขั้นตอน

## 2026-04-03 — `GET /api/v1/lotto/markets/latest` Returns Group Images (APPROVED)

- ปรับ response ของ `markets/latest` ให้มีฟิลด์รูปในระดับกลุ่มหวย:
  - `group_logo`
  - `group_icon`
  - `group_image` (fallback `logo` -> `icon`)
- เหตุผล:
  - ให้ frontend แสดงรูปของกลุ่มหวยได้โดยไม่ต้องเรียก endpoint เพิ่ม

## 2026-04-03 — Results-by-Date Report Shows `งดออกผล` Instead of `-` (APPROVED)

- หน้า `admin/lotto/reports/results-by-date` ตรวจ `result_number.no_result` หรือ `result_number.status=no_result`
- เมื่อเป็นเคส `งดออกผล` ให้แสดงค่า `งดออกผล` ในคอลัมน์ผลรางวัลทั้งหมด (`รางวัลที่ 1`, `3 ตัวบน`, `2 ตัวบน`, `2 ตัวล่าง`) แทน `-`
- เหตุผล:
  - ทำให้ทีมงานแยกเคส “ไม่มีข้อมูลผล” ออกจาก “งดออกผล” ได้ชัดเจนในหน้ารายงานรวม

## 2026-04-03 — Add Auto Draw Mode `wed_sat_sun` for Markets (APPROVED)

- เพิ่มโหมดงวดใหม่ใน `lotto_markets.draw_mode`:
  - `wed_sat_sun` = ออกอัตโนมัติเฉพาะวันพุธ/เสาร์/อาทิตย์
- ปรับ command `lotto:generate-auto-draws` ให้รวมตลาดโหมดนี้และสร้างงวดเฉพาะ `dayOfWeekIso` = 3, 6, 7
- ปรับหน้า admin `/lotto/markets` และ label สรุปใน `/lotto/draws` ให้แสดงโหมดนี้
- เหตุผล:
  - รองรับ requirement ตลาดที่มีรอบออกงวดเฉพาะ พุธ/เสาร์/อาทิตย์ โดยไม่ต้องใช้ manual ทุกครั้ง

## 2026-04-03 — Fix V2 Fetch Context to Propagate `lookup_date` from Source Policy (APPROVED)

- ปรับ `V2ResultPipelineRunner` ให้ส่ง `lookup_date` และ `lookup_date_compact` เข้า `FetchExecutor` ทุกครั้ง
- เหตุผล:
  - ปิดช่องโหว่ที่ dry-run/auto-result บางเคส fallback ไปใช้ `expected_draw_date` ทำให้ query `{{lookup_date}}` ไม่เลื่อนวันตาม `lookup_date_mode`
  - รองรับตลาดที่ต้องยิง upstream ด้วยวันก่อนหน้า (เช่น `ROUND_DATE_MINUS_DAYS`)

## 2026-04-03 — Draws Cancel-All+Refund จำกัดเฉพาะ `resulted + งดออกผล` เท่านั้น (APPROVED)

- policy ล่าสุดของปุ่ม `ยกเลิกโพย+คืนเงิน`:
  - แสดงเฉพาะงวดสถานะ `resulted` ที่ผลเป็น `งดออกผล` (`no_result`)
- ถ้าระบบเคยคืนเงินทั้งงวดสำเร็จแล้วและตั้ง `result_number.manual_cancelled_all_tickets=true`
  - ต้องซ่อนปุ่ม `ยกเลิกโพย+คืนเงิน`
- backend guard ของ endpoint `POST /lotto/draws/cancel-all-refund`:
  - อนุญาตเฉพาะ `resulted` ที่ `result_number.no_result=true` หรือ `result_number.status=no_result`
- เหตุผล:
  - ตรงตาม requirement ทีมงานว่า action นี้ต้องใช้เฉพาะเคสงวดงดออกผลเท่านั้น
  - ป้องกันการกดซ้ำจาก UI หลังคืนเงินครบทั้งงวดแล้ว

## 2026-04-03 — Auto Result Supports “งดออกผล” and Admin Draws Can Cancel-All+Refund (APPROVED)

- ปรับ auto-result validation (legacy + v2 cutover) ให้ตรวจ marker ประเภท `งดออกผล`:
  - เมื่อพบ marker จะ normalize payload เป็นผลแบบ `no_result=true` พร้อม `no_result_reason`
  - apply จะข้าม settlement และบันทึก draw เป็น `resulted` พร้อม `result_number.status=no_result`
  - หน้า admin `งวดหวย` แสดงค่า `งดออกผล` ในคอลัมน์ผลรางวัล
- เพิ่ม endpoint ฝั่ง admin:
  - `POST /lotto/draws/cancel-all-refund`
- behavior ของ endpoint:
  - ยกเลิก ticket `active` ทั้งหมดใน draw
  - คืนเงินสมาชิกทุกรายการผ่าน `wallet_transactions` (`ref_type=LOTTO_CANCEL`)
  - ปรับ exposure ตามยอดที่ยกเลิก
  - mark draw เป็น `resulted` พร้อมผล `งดออกผล`
- เหตุผล:
  - รองรับเคส upstream ประกาศงดออกผลโดยไม่ให้ pipeline ติด `NOT_READY` ซ้ำ
  - ให้ทีมงานมีปุ่มงานเดียวสำหรับยกเลิกโพยทั้งงวดและคืนเงินครบถ้วน

## 2026-04-03 — Telegram Draw Result Message Adds Result Time Line (APPROVED)

- ปรับข้อความแจ้งผล Telegram ของ job `SendDrawResultSummaryTelegramJob`:
  - เดิม: แสดง `งวดวันที่ {date}`
  - ใหม่: แสดงทั้ง `งวดวันที่ {date}` และ `เวลาออกผล {HH:mm}`
- เหตุผล:
  - ให้ทีมงานเห็นเวลาประกาศผลในข้อความเดียวกับหัวข้อผลรางวัล โดยไม่ต้องเปิดหน้ารายละเอียดเพิ่ม

## 2026-04-03 — Add Admin Team Menu “ดูผลรางวัลทั้งหมด” Filtered by Draw Date (APPROVED)

- เพิ่มเมนูใหม่ใต้ `รายงาน Lotto`:
  - `ดูผลรางวัลทั้งหมด` (`admin.lotto.reports.results_by_date`)
- เพิ่มหน้า report ทีมงานที่ filter ตาม `draw_date` และแสดงผลแบบ grouped:
  - group = `lotto_groups`
  - market = `lotto_markets` ที่มี draw ตรงวันที่เลือกและ `status = resulted`
- เพิ่ม ACL key:
  - `lotto_reports.results_by_date`
- เหตุผล:
  - ให้ทีมงานดูผลรางวัลรวมทั้งระบบในวันเดียวได้จากหน้าเดียว โดยไม่ต้องไล่ทีละรายการหวย

## 2026-04-03 — Add Frontend API v1 Lotto Results-by-Date Grouped Endpoint (APPROVED)

- เพิ่ม endpoint:
  - `GET /api/v1/lotto/results/by-date?draw_date=YYYY-MM-DD`
- behavior:
  - คืนผลรางวัลทั้งหมดของวันที่เลือกแบบ grouped ตามกลุ่มหวย (`lotto_groups`)
  - ในแต่ละกลุ่มจะแสดงเฉพาะ market ที่มี draw วันนั้นและสถานะ `resulted`
  - รองรับ localization ของชื่อกลุ่ม/ชื่อรายการหวยตาม `language`
- response มี summary:
  - `group_count`, `market_count`, `result_count`
- เหตุผล:
  - รองรับหน้าแสดงผล “ทั้งระบบตามวันที่เดียว” โดยไม่ต้องเรียกทีละ market

## 2026-04-03 — Add Frontend API v1 Member History Endpoints from Wallet `/member/history` (APPROVED)

- เพิ่ม endpoints:
  - `GET /api/v1/member/history`
  - `GET /api/v1/member/history/{type}`
- รองรับประเภทประวัติ:
  - `deposit`, `withdraw`, `transfer`, `spin`, `money`, `cashback`, `memberic`, `bonus`, `other`
- รองรับ query filter:
  - `date_start`, `date_stop`
- response contract:
  - `{ type, date_start, date_stop, items }` + envelope มาตรฐาน (`success`, `message`)
- เหตุผล:
  - ให้ frontend เรียกประวัติธุรกรรมที่อ้างอิงจากหน้า wallet เดิม `/member/history` ผ่าน `FrontendApi v1` โดยตรง

## 2026-04-03 — Add Frontend API v1 `GET /member/contributor` for Referral Overview (APPROVED)

- เพิ่ม endpoint ใหม่ใน Frontend API v1:
  - `GET /api/v1/member/contributor` (ต้องใช้ Bearer token)
- ล็อกโครงข้อมูลหลักที่ต้องคืน:
  - `summary.referred_members` = จำนวนสมาชิกที่แนะนำ
  - `summary.referral_code` = รหัสแนะนำ 8 หลักของสมาชิก
  - `summary.referral_income` = รายได้แนะนำจาก `members.faststart`
  - `summary.promotion_bonus_income` และ `summary.promotion_bonus_count` = สรุปโบนัสแนะนำจาก `payments_promotion`
- เพิ่ม metadata กติกาแนะนำจากโปรโมชั่นระบบ `pro_faststart`:
  - `rule.length_type`, `rule.bonus_percent`, `rule.bonus_price`, `rule.display_value`
- เพิ่มรายการผู้ถูกแนะนำใน response:
  - `referrals[]` มี `username`, `name`, `regis_date`, `first_deposit_amount`, `first_deposit_date`
- เหตุผล:
  - รองรับหน้าแนะนำเพื่อนบน frontend ใหม่ให้เรียกข้อมูลในเส้นเดียวผ่าน `/api/v1`
  - ลดการพึ่ง endpoint เก่าจาก wallet module (`/member/contributor/api`)

## 2026-04-03 — Frontend Register Adds Unique 8-Char Referral Code and Upline Mapping (APPROVED)

- ปรับ `POST /api/v1/auth/register`:
  - สร้าง `members.referral_code` อัตโนมัติทุกสมาชิกใหม่
  - รูปแบบรหัส: 8 ตัวอักษร, ตัวพิมพ์ใหญ่+ตัวเลข, ไม่ใช้ `O`
  - บังคับ unique ด้วย DB unique index
- เพิ่มการ map upline จากรหัสแนะนำ:
  - รับ input field `referral_code` (alias: `invite_code`, `recommend_code`)
  - normalize input เป็น uppercase และแทน `O` -> `0`
  - ถ้าพบรหัสตรงกับสมาชิกเดิม ให้ตั้ง `members.upline_code` ของสมาชิกใหม่เป็น `members.code` ของเจ้าของรหัสนั้น
- เหตุผล:
  - เพิ่มระบบอ้างอิงแนะนำสมาชิกที่อ่านง่ายและลดความสับสนระหว่างตัวอักษรคล้ายกัน

## 2026-04-03 — Add Backfill Command for Existing Members Referral Code (APPROVED)

- เพิ่มคำสั่ง `php artisan member:backfill-referral-codes`
  - default เป็น dry-run
  - ใช้ `--apply` เพื่อเขียน `members.referral_code` จริงให้สมาชิกที่ยังไม่มีรหัส
  - รองรับ `--chunk`, `--limit`, `--member-code=*`
- policy ของการ generate ใน command ต้องใช้กติกาเดียวกับ register:
  - ความยาว 8 ตัวอักษร
  - ตัวพิมพ์ใหญ่ + ตัวเลข
  - ไม่ใช้ตัว `O`
- เหตุผล:
  - รองรับการ backfill ข้อมูลสมาชิกเดิมหลังเปิดระบบ referral code ใหม่

## 2026-04-03 — Consolidate External Lotto API Paths to `/api/v1` Only (APPROVED)

- ปรับเส้น external lotto API ให้เหลือ canonical prefix เดียวคือ `/api/v1/lotto/*`
- ย้าย critical path routes เดิมจาก `/api/frontend/lotto/*` ไปอยู่ที่:
  - `GET /api/v1/lotto/markets/{marketId}/betting-context`
  - `GET /api/v1/lotto/markets/{marketId}/results`
  - `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- ยกเลิก legacy member routes ใน Lotto module เดิมที่ prefix `/api/lotto/*`
- ข้อยกเว้นที่ยังคงไว้:
  - integration route `/api/rb7lotto/*`
  - internal endpoints `/internal/lottery/results/*`
- เหตุผล:
  - ลด route duplication และ ambiguity ของ client integration
  - บังคับ contract ฝั่ง frontend ให้ยึด `/api/v1` เส้นเดียว

## 2026-04-03 — Frontend API v1 Exposes Lotto Group Package Helper Endpoints (APPROVED)

- เพิ่ม route ใน `FrontendApi` ชุด `/api/v1/lotto/groups/{groupId}/*`:
  - `GET /api/v1/lotto/groups/{groupId}/packages`
  - `POST /api/v1/lotto/groups/{groupId}/select-package`
  - `GET /api/v1/lotto/groups/{groupId}/selected-package`
- implementation ของ v1 routes ใช้ flow เดียวกับ `Gametech\Lotto\Http\Controllers\Api\PackageController`
  เพื่อให้ response contract/error mapping ของ package helper คงพฤติกรรมเดียวกัน
- เหตุผล:
  - แก้ mismatch ระหว่างเอกสาร Frontend API v1 กับ route ที่ deploy จริง ซึ่งเคยมีเฉพาะ `/api/lotto/*`
  - ลดกรณี `404 Resource not found` เมื่อลูกค้าเรียก endpoint ตามคู่มือ v1

## 2026-04-02 — Number Block Add Modal Uses Latest Non-Closed Draw per Market (APPROVED)

- ปรับหน้า admin `/lotto/number-blocks`:
  - ช่อง `งวดหวย` ใน modal `เพิ่มเลขอั้น` ใช้เฉพาะงวดล่าสุดของแต่ละรายการหวย
  - เลือกจากสถานะที่ยังไม่ปิดเท่านั้น (`draft`, `open`)
- เหตุผล:
  - ลดกรณี dropdown ว่างและลดการเลือกงวดที่ปิดรับแล้วโดยไม่ตั้งใจ

## 2026-04-01 — Frontend Package APIs Include Package Image Field (APPROVED)

- ปรับ API:
  - `GET /api/lotto/groups/{groupId}/packages` เพิ่ม field `image` ในแต่ละ package
  - `GET /api/lotto/groups/{groupId}/selected-package` เพิ่ม `name` และ `image` ใน `data`
- ปรับเอกสาร public API ให้ตรง response contract ล่าสุดของ package endpoints
- เหตุผล:
  - frontend ต้องใช้ภาพแพกเกจจาก API โดยตรงเพื่อลดการเรียกข้อมูลซ้ำจาก endpoint อื่น

## 2026-04-01 — Add Command to Migrate Existing Exphuay Source Rows to `get_lottery.php` (APPROVED)

- เพิ่ม command `lotto:migrate-exphuay-sources-to-get-lottery`
  - รองรับ dry-run / apply
  - รองรับ filter ด้วย `--source-id` และ `--market-code`
  - resolve `type` จาก endpoint เดิมหรือ query config ที่มีอยู่
- ตอน apply ระบบจะ rewrite แถว exphuay เดิมให้:
  - `endpoint_url` -> `http://203.146.127.170/~anan/get_lottery.php`
  - `request_query_template_json` -> `type={type}`, `date={{lookup_date}}`, `page=1`
  - parser -> JSON_PATH ตาม schema ของ `get_lottery.php`
  - ตั้งค่า default `priority=1` และ `is_active=1` (override ได้ด้วย option)
- เหตุผล:
  - รองรับเคสที่มี source แถวเดิมอยู่แล้วและต้องย้ายแบบ bulk โดยไม่ต้องแก้ทีละรายการใน admin

## 2026-04-01 — Insert-Internal Generator Supports External `get_lottery.php` for All Exphuay-Mapped Markets (APPROVED)

- ปรับ `lotto:insert-internal-result-source-mappings` สำหรับทุก market ที่ map เป็น `exphuay:{type}`:
  - ใช้ endpoint `http://203.146.127.170/~anan/get_lottery.php`
  - query template: `type={type}`, `date={{lookup_date}}`, `page=1`
  - parser fields:
    - `draw_date_raw` -> `$.date`
    - `first_prize_raw_1` -> `$.results[0].lottosNumber`
    - `last_2_raw_1` -> `$.results[0].lottosUnder`
  - บังคับค่าแถวใหม่เป็น `priority=1` และ `is_active=true` (override ค่า option ปกติของ command สำหรับกลุ่มนี้)
- เหตุผล:
  - ให้ auto source ทุกตลาดที่อิง exphuay ดึงข้อมูลจาก endpoint กลางที่ทีมงานกำหนดได้ทันที โดยไม่ต้องแก้มือหลัง generate

## 2026-04-01 — Lotto Group Package Admin Modal Supports Package Image Upload (APPROVED)

- ปรับหน้า admin `/lotto/group-packages`:
  - modal `เพิ่มแพกเกจ` และ `แก้ไขแพกเกจ` รองรับอัปโหลดไฟล์รูป (`image_file`)
  - ฝั่ง frontend ส่งข้อมูลแบบ `multipart/form-data` และรองรับ nested payload (`bet_settings`) ผ่าน bracket notation
- ปรับ backend:
  - เพิ่มคอลัมน์ `lotto_group_packages.image` (nullable string)
  - `LottoGroupPackageController` รองรับ validate/upload รูป (`jpeg/png/gif/webp`, max 5MB)
  - เมื่ออัปโหลดใหม่จะเก็บไฟล์ใน `storage/app/public/lotto/media` และอัปเดต path เป็น `/storage/...`
- เหตุผล:
  - ให้ทีมงานกำหนดรูปภาพของแพกเกจได้ตรงจาก modal เพิ่ม/แก้ไข โดยไม่ต้องแก้ข้อมูลผ่าน DB

## 2026-04-01 — Exphuay Adds Python `curl_cffi` Worker Fallback Before Browser Runtime (APPROVED)

- ปรับ fallback chain ใน `ExphuayResultDriver` เป็น:
  - HTTP ปกติ
  - ถ้าเจอ Cloudflare challenge (`HTTP 403` หรือ body บ่งชี้ `Just a moment/cf-mitigated`) ให้ลอง Python worker (`curl_cffi`) ก่อน
  - ถ้า Python worker ไม่สำเร็จ ค่อย fallback ไป browser runtime (`performRuntimeFetch`)
- เพิ่ม config/env policy สำหรับ Python worker:
  - `LOTTO_EXPHUAY_PYTHON_WORKER_ENABLED`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_BINARY`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_SCRIPT`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_TIMEOUT_SECONDS`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_IMPERSONATE`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_WARMUP`
  - `LOTTO_EXPHUAY_PYTHON_WORKER_WARMUP_URL`
- เหตุผล:
  - เพิ่มทางเลือก fetch ที่ stable กว่า plain HTTP ในเคส Cloudflare challenge
  - คง browser runtime เป็น fallback ชั้นสุดท้ายเพื่อรักษา compatibility

## 2026-04-01 — Exphuay Driver Uses Browser Runtime Fallback on Cloudflare Challenge (APPROVED)

- ปรับ `ExphuayResultDriver`:
  - ยิง HTTP ปกติก่อน
  - ถ้าเจอ Cloudflare challenge (`HTTP 403` หรือ body บ่งชี้ `Just a moment/cf-mitigated`) ให้ fallback ไป browser runtime (`performRuntimeFetch`) แบบ sync ใน driver
- env policy:
  - `LOTTO_EXPHUAY_COOKIE` สำหรับ upstream cookie
  - `LOTTO_EXPHUAY_USER_AGENT` สำหรับ UA override
  - `LOTTO_EXPHUAY_BROWSER_FALLBACK` สำหรับเปิด/ปิด fallback (default=true)
  - `LOTTO_EXPHUAY_BROWSER_FALLBACK_TIMEOUT_SECONDS`, `LOTTO_EXPHUAY_BROWSER_WAIT_UNTIL`, `LOTTO_EXPHUAY_BROWSER_TIMEOUT_MS` สำหรับควบคุม browser fallback runtime behavior
- เหตุผล:
  - ลดกรณี `REQUIRED_FIELD_MISSING @ VALIDATE` ที่ root cause มาจาก upstream ถูก Cloudflare block

## 2026-04-01 — Move Exphuay Headers/Cookie to Upstream Driver; Keep Inserted Source JSON Clean (APPROVED)

- ยกเลิก policy ที่ให้ command `lotto:insert-internal-result-source-mappings` ฝัง browser headers/cookie ลง JSON หลัก
- policy ล่าสุด:
  - source ที่ generate ใหม่คง `request_headers_json=[]` และ `fetch_config_json.headers=[]`
  - การส่ง header/cookie สำหรับ exphuay ให้ทำใน `ExphuayResultDriver` ตอนเรียก upstream เท่านั้น
  - cookie อ่านจาก env `LOTTO_EXPHUAY_COOKIE` (ถ้าไม่ตั้งไม่แนบ)
  - user-agent override ได้ผ่าน env `LOTTO_EXPHUAY_USER_AGENT`
- เหตุผล:
  - แก้ปัญหา Cloudflare challenge ที่เกิดที่ upstream driver โดยตรง
  - ลดความเสี่ยงเก็บ cookie ใน DB/log ของ source config
- สถานะเอกสาร:
  - decision `2026-04-01 — Insert-Internal Mapping Generates Browser-like Headers + Cookie by Default` ถูก superseded

## 2026-04-01 — Insert-Internal Mapping Generates Browser-like Headers + Cookie by Default (APPROVED)

- ปรับ command `lotto:insert-internal-result-source-mappings` ให้แถวที่ generate ใหม่มี header ใน JSON หลักอัตโนมัติ
- policy ที่ล็อก:
  - inject header ลงทั้ง `request_headers_json` และ `fetch_config_json.headers`
  - header baseline: `Accept`, `Accept-Language`, `Referer`, `User-Agent`, `x-sveltekit-invalidated`, `Cookie`
  - `Referer` ของ exphuay ใช้ pattern `https://exphuay.com/backward/{type}`
  - `Cookie` รองรับ override ผ่าน env `LOTTO_INTERNAL_RESULT_SOURCE_COOKIE`
- เหตุผล:
  - ให้ source ที่สร้างจาก command พร้อมใช้งานกับ upstream ที่ต้องการ browser header/cookie โดยไม่ต้องแก้ทีละแถวใน admin

## 2026-04-01 — Telegram Result Message Shows Top-3 as Right(3) of First Prize (APPROVED)

- ปรับข้อความแจ้งผล Telegram ตอน draw เป็น `resulted`:
  - บรรทัด `3 ตัวบน` ต้องแสดงค่า `right(3)` ของ `first_prize`
  - ไม่แสดงเลข `first_prize` เต็มในบรรทัด `3 ตัวบน`
- เหตุผล:
  - ให้ข้อความสอดคล้องกับรูปแบบผลที่ทีมงานใช้งาน (`3 ตัวบน / 2 ตัวล่าง`)

## 2026-04-01 — Revert Manual Draw Mode Guard on Auto Result Fetch/Apply (APPROVED)

- ยกเลิก policy ที่เคยบล็อก auto-result เมื่อ `draw_mode=manual`
- policy ล่าสุด:
  - `draw_mode` ใช้กำหนดการสร้างงวดอัตโนมัติเท่านั้น (manual = ทีมงานเพิ่มงวดเอง)
  - auto-result fetch/apply ให้ยึดสถานะ source และกติกาคำนวณ (`auto_settle_on_result`) ตามเดิม
  - ถ้ามี source active และงวดเข้าเงื่อนไข ระบบต้องดึงผลอัตโนมัติได้แม้ market เป็น `draw_mode=manual`
- สถานะเอกสาร:
  - decision `2026-04-01 — Manual Draw Mode Disables Auto Result Fetch/Apply Regardless of Source/Auto Settle` ถูก superseded

## 2026-04-01 — Manual Draw Mode Disables Auto Result Fetch/Apply Regardless of Source/Auto Settle (APPROVED)

- เพิ่ม policy บังคับสำหรับตลาดที่ `draw_mode=manual`:
  - ห้าม auto-result fetch ผลเข้าระบบ
  - ห้าม auto-result apply/settle อัตโนมัติ
- policy นี้มีผลแม้:
  - มี source config ผูกไว้แล้ว
  - ตั้ง `auto_settle_on_result=true`
- เหตุผล:
  - โหมด manual ต้องให้ทีมงานควบคุมการออกผลและการคำนวณเองทั้งหมด
  - ป้องกันระบบอัตโนมัติรันข้ามเจตนาการตั้งค่าโหมดงวด

## 2026-04-01 — Internal Result Endpoints Use API Domain as Single Canonical Host (APPROVED)

- route `/internal/lottery/results/*` ถูก bind ให้รับ request เฉพาะ API host เท่านั้น
- กติกา resolve host:
  - ถ้าตั้ง `APP_API_DOMAIN_URL`: ใช้ `APP_API_URL + APP_API_DOMAIN_URL`
  - ถ้าไม่ตั้ง `APP_API_DOMAIN_URL`: fallback ใช้ `APP_API_URL + APP_ADMIN_DOMAIN_URL`
- canonical internal endpoint URL ต้องเป็น `api.*` เพียงเส้นเดียว (ไม่เปิดผ่าน `admin.*`)
- เหตุผล:
  - ลดความสับสนของ source config ที่มี endpoint ได้หลาย host
  - ลดความเสี่ยง config drift ระหว่าง environment

## 2026-04-01 — Lotto Admin ACL Split by CRUD Actions with Backward Compatibility (APPROVED)

- ปรับ `packages/Gametech/Lotto/src/Config/acl.php` ให้รองรับ permission key แบบแยก action (`index/create/update/delete`) ในเมนูที่มี route รองรับจริง
- คง permission key เดิมระดับเมนูไว้ทั้งหมด เพื่อไม่ให้ role เดิมที่ผูก key เก่าใช้งานพังทันที
- ขอบเขตที่เพิ่ม key แยก action:
  - `lotto_settings.draws.*`
  - `lotto_settings.auto_result_sources.*`
  - `lotto_settings.number_blocks.*`
  - `lotto_settings.groups.*`
  - `lotto_settings.markets.*`
  - `lotto_settings.group_packages.*`
  - `lotto_settings.payout_settings.*`
  - `lotto_settings.bet_limit_settings.*`
- เหตุผล:
  - ให้ Lotto ACL อยู่มาตรฐานเดียวกับโมดูลที่แยกสิทธิ์ CRUD ชัดเจน
  - ลดความคลุมเครือของสิทธิ์ “เห็นเมนู” กับสิทธิ์ “เพิ่ม/แก้ไข/ลบ”

## 2026-03-31 — Lotto Group Package Contract + Helper Boundary + Snapshot Ownership (APPROVED)

- ล็อก contract ของ package helper APIs:
  - `POST /api/lotto/groups/{groupId}/select-package`
    - success: `HTTP 200`
    - idempotent เมื่อเลือก package เดิมซ้ำ
  - `GET /api/lotto/groups/{groupId}/selected-package`
    - ถ้ายังไม่เลือก: `HTTP 200` + `data=null` + `selected=false`
- ล็อก boundary:
  - helper API เป็น non-authoritative state สำหรับ UI เท่านั้น
  - betting runtime ต้อง validate จาก `package_id` ที่ส่งมาใน bet request เท่านั้น
  - ห้ามใช้ helper state เป็น auth/permission gate
- ล็อก betting package error mapping:
  - `PACKAGE_REQUIRED` -> `400`
  - `PACKAGE_NOT_IN_GROUP` -> `400`
  - `PACKAGE_INACTIVE` -> `409`
  - `BET_TYPE_NOT_CONFIGURED` -> `422`
- ล็อก snapshot ownership:
  - authoritative snapshot เก็บที่ `lotto_ticket_items`
  - ต้องมี `package_id_at_time`, `package_name_at_time`, `calculated_values_at_bet_time`
  - `calculated_values_at_bet_time` อย่างน้อยมี `bet_amount`, `discount_amount`, `net_amount`, `payout_amount`
- ล็อก admin package management:
  - เพิ่ม admin endpoints สำหรับ `group-packages` และ `group-package-bet-settings`
  - package ที่ถูกใช้งานแล้วห้าม hard delete และต้อง disable แทน
- ล็อก deprecate market-level payout override:
  - ปิดการแก้ `payout/discount_percent` ผ่าน `default-settings`
  - ถ้าพบการส่ง field นี้ให้ reject ด้วย `DEPRECATED_PAYOUT_OVERRIDE`

## 2026-03-30 — Internal Result Endpoints Bypass Fixture Gate in Local/Testing (APPROVED)

- source config ที่ชี้ endpoint ภายในระบบหลัก (`/internal/lottery/results/*`) ไม่ต้องถูกบังคับ fixture gate ตอน save/validate cutover ใน `local|testing`
- เหตุผล:
  - เป็น first-party integration ภายในระบบเดียวกัน
  - fixture gate เดิมออกแบบมาสำหรับ external source/parser validation เป็นหลัก

## 2026-03-30 — V2 Fetch Runtime Renders Query/Header/Body Placeholders (APPROVED)

- V2 fetch executor ต้อง render placeholders ไม่เฉพาะ `endpoint_url` แต่รวมถึง `query`, `headers`, `body`
- policy ที่ล็อก:
  - รองรับ `{{lookup_date}}` และ `{{expected_draw_date}}` ใน request config
  - ถ้า runtime context ไม่มี `lookup_date` ให้ fallback ใช้ `expected_draw_date`

## 2026-03-30 — Dowjones `digit5` Derives `bottom_2` from Leading Two Digits (APPROVED)

- สำหรับ `dowjones-midnight` และ `dowjones-extra` เมื่อ business rule ของ source นั้นใช้เลข 5 หลักเป็น canonical result
- policy ที่ล็อก:
  - `first_prize` = `digit5`
  - `top_3` = 3 หลักท้ายของ `digit5`
  - `top_2` = 2 หลักท้ายของ `digit5`
  - `bottom_2` = 2 หลักหน้าของ `digit5`
- เหตุผล:
  - payload ของ source กลุ่มนี้บางช่วงไม่มี field `bottom_2` แยก แต่ business rule ต้อง derive จากเลข 5 หลักโดยตรง

## 2026-03-30 — Dowjones Extra Uses `result` for Today and `history` for Past Dates (APPROVED)

- สำหรับ `dowjones-extra`
- policy ที่ล็อก:
  - ถ้าขอผลของวันปัจจุบัน ให้เรียก `https://api.dowjonesextra.com/result` และไม่ส่ง `date`
  - ถ้าขอผลย้อนหลัง ให้เรียก `https://api.dowjonesextra.com/history`
  - หลังได้ payload จาก `history` ต้อง select record จาก `lotto_date` ให้ตรงวันที่ขอเอง
  - ถ้าไม่พบวันที่ที่ขอ ต้องคืน `DRAW_DATE_NOT_FOUND`

## 2026-03-30 — Exphuay Date Selection Uses Local Draw Date from Payload (APPROVED)

- ปรับ internal result handling ของ `exphuay` ให้ไม่เชื่อว่า upstream `date` query จะ filter งวดให้ตรงเสมอ
- policy ที่ล็อก:
  - ต้อง parse payload หลายงวดจาก upstream แล้ว select record เอง
  - การเทียบวันที่ใช้ local draw date `Asia/Bangkok` ที่ derive จาก `lottosDate`
  - เมื่อเลือก record สำเร็จ:
    - `first_prize` = `lottosNumber`
    - `top_3` = 3 หลักท้ายของ `lottosNumber`
    - `top_2` = 2 หลักท้ายของ `lottosNumber`
    - `bottom_2` = `lottosUnder`
  - `draw_date` ของ canonical response ต้องมาจาก record ที่ match จริง ไม่ใช่ echo input date อย่างเดียว

## 2026-03-30 — Insert-Only Canonical Mapping Command for Result Sources (APPROVED)

- เพิ่ม command `lotto:insert-internal-result-source-mappings`
- วัตถุประสงค์:
  - เพิ่ม canonical internal mapping ต่อ market แบบ insert-only
  - ไม่ update/overwrite แถว `lotto_result_sources` เดิมที่มีอยู่แล้ว
- command รองรับ:
  - dry-run (default)
  - apply (`--apply`)
  - จำกัดตลาด (`--market-id=*`, `--market-code=*`)
  - กำหนด priority แถวใหม่ (`--priority=...`)
  - เปิด active เฉพาะแถวใหม่ (`--activate-new`)
- policy ที่ล็อก:
  - ถ้า market นั้นมี endpoint canonical เดียวกันอยู่แล้ว ต้อง `skip(exists)`
  - แถวเดิมทั้งหมดต้องไม่ถูกแก้ไขโดย command นี้
  - default แถวใหม่เป็น `is_active=false` เพื่อไม่กระทบ runtime ทันที

## 2026-03-30 — Bootstrap Missing Result Sources as Safe Placeholder Rows (APPROVED)

- เพิ่ม command `lotto:bootstrap-missing-result-sources` เพื่อเติม `lotto_result_sources` ให้ครบสำหรับ market ที่ยังไม่มี source
- command รองรับ:
  - dry-run (default)
  - apply (`--apply`)
  - จำกัดตลาด (`--market-id=*`)
- policy ที่ล็อก:
  - แถวที่ bootstrap ใหม่ต้องเป็น `is_active=false` (safe placeholder)
  - ไม่บังคับเปิดใช้งานทันที เพื่อลดความเสี่ยงกระทบ auto-result runtime
  - market code `downjone-midnight` และ `downjone-extra` จะชี้ไป internal endpoints ใหม่โดยตรง
- evidence local run:
  - apply สำเร็จ: markets=60, sources=60, missing=0

## 2026-03-30 — Internal Result Sources Migration Command + Optional-Date Upstream Mode (APPROVED)

- เพิ่ม command `lotto:migrate-internal-result-endpoints` สำหรับ PR-13:
  - dry-run/report (`--report-only`)
  - apply backfill (`--apply`)
  - filter ราย source (`--source-id=*`)
- command จะเขียน report ที่:
  - `storage/app/lotto/internal_result_migration/migration_report_*.json`
- lock mapping rules:
  - `exphuay.com/backward/{type}/__data.json` -> `/internal/lottery/results/exphuay/{type}`
  - `api.dowjones-midnight.com/result` -> `/internal/lottery/results/dowjones-midnight`
  - `api.dowjonesextra.com/result` -> `/internal/lottery/results/dowjones-extra`
- ระหว่าง migrate ถ้ามี `fetch_config_json` ให้ sync ทั้ง:
  - `endpoint_url`, `request.url`, `request.query`, `query`
- ถ้าเปิด shared-key จะ inject header ตาม config ไปที่ `fetch_config_json` เพื่อไม่ให้ internal auth block รันจริง
- ปรับ internal result service ให้ `date` เป็น optional จริง:
  - ถ้าไม่ส่ง `date` จะไม่บังคับส่ง query `date` ไป upstream
  - `draw_date` จะ resolve จาก upstream payload ก่อน (fallback วันนี้เมื่อไม่พบ)
- เหตุผล: รักษา compatibility ของ mode “latest” และทำ migration/backfill แบบตรวจสอบได้

## 2026-03-30 — Internal Lotto Result Sources API Baseline Implementation (APPROVED)

- เพิ่ม internal endpoints ตาม contract freeze:
  - `GET /internal/lottery/results/exphuay/{type}?date=&page=`
  - `GET /internal/lottery/results/dowjones-midnight?date=`
  - `GET /internal/lottery/results/dowjones-extra?date=`
- ล็อก date adapter สำหรับ input `Y-m-d`, `d/m/Y`, `d-m-Y` และ output `draw_date=Y-m-d`
- บังคับ canonical response key คงที่:
  - `success`, `source`, `type`, `draw_date`, `raw_result`, `normalized_result`, `meta`, `errors`
  - `normalized_result` คง key ชุดเดียว (ค่าว่างเป็น `null`)
  - `errors` เป็น array เสมอ
- ล็อก policy field เสริม Dowjones:
  - `start_spin`, `show_result`, `now`, `update` ต้องอยู่ที่ `meta.dowjones_supplemental`
  - ห้าม map field เสริมเข้า `normalized_result`
- เพิ่ม middleware `lotto.internal_results`:
  - เมื่อกำหนด `LOTTO_INTERNAL_RESULT_SHARED_KEY` จะบังคับตรวจ shared-key header
  - เมื่อไม่กำหนด key จะ allow เพื่อรองรับ migration/transition window

## 2026-03-30 — Internal Result Sources Contract Freeze Baseline (APPROVED)

- ล็อก baseline integration สำหรับ `lottery-php`, `dowjones-midnight`, `dowjones-extra` จาก zip evidence
- ล็อก internal endpoint targets:
  - `GET /internal/lottery/results/exphuay/{type}?date=YYYY-MM-DD&page=1`
  - `GET /internal/lottery/results/dowjones-midnight?date=YYYY-MM-DD`
  - `GET /internal/lottery/results/dowjones-extra?date=YYYY-MM-DD`
- ล็อก date policy:
  - input รองรับ `Y-m-d`, `d/m/Y`, `d-m-Y`
  - output canonical `draw_date` เป็น `Y-m-d` เท่านั้น
- ล็อก canonical response keys บังคับ:
  - `success`, `source`, `type`, `draw_date`, `raw_result`, `normalized_result`, `meta`, `errors`
  - field ที่ derive ไม่ได้ให้ `null` และ `errors` ต้องเป็น array
- ล็อกว่า supplemental fields ของ Dowjones (`start_spin`, `show_result`, `now`, `update`) ต้องผ่าน policy ownership ที่ชัดเจนใน metadata/raw (ห้ามปะปนกับผลรางวัลโดยไม่มี rule)
- ล็อก migration intent:
  - production path ใหม่ต้องเป็น service-first integration ภายในระบบหลัก
  - CLI runtime เดิมใช้เป็น reference/transition เท่านั้น
- เอกสาร source of truth:
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-contract-freeze.md`

## 2026-03-29 — Draws DataTable Disables Search on withCount Alias Columns (APPROVED)

- ปรับคอลัมน์ `blocked_numbers_count` และ `tickets_count` ใน `LottoDrawDataTable` ให้ `searchable=false`
- แก้ปัญหา SQL error `Unknown column 'lotto_draws.blocked_numbers_count' in 'where clause'` ที่เกิดจาก DataTables พยายามสร้าง `WHERE` บน alias จาก `withCount(...)`

## 2026-03-29 — Result Telegram Uses One Async Summary Message Per Resulted Draw (APPROVED)

- เปลี่ยนเส้นแจ้งผล Telegram ให้ trigger ตอน `draw.status` เปลี่ยนเป็น `resulted` เท่านั้น
- เพิ่ม queue job `SendDrawResultSummaryTelegramJob` สำหรับ:
  - คำนวณ summary (`บิลรวม/ชนะ/แพ้/ยอดชนะ/ยอดแพ้/กำไรสุทธิ`)
  - ยิงข้อความผ่าน `SendTelegramBot` แบบ async
- ปรับ format ข้อความเป็น short + impact-first และเน้น `กำไร/ขาดทุนสุทธิ`
- เพิ่ม idempotency กันยิงซ้ำด้วยฟิลด์ `lotto_draws.telegram_sent_at`
- ยกเลิกการยิงข้อความสถานะ fetched ที่ยังไม่ resulted จาก `ResultApplier`

## 2026-03-29 — Settlement Normalization Accepts Short First Prize for Auto Result (APPROVED)

- ปรับ `SettlementService::normalizeResultNumber` ให้รองรับ `first_prize` ความยาว `3|4|5|6` หลัก (เดิมรับเฉพาะ `5|6`)
- สำหรับเคส `first_prize=3 หรือ 4 หลัก` + `last_2_digits=2 หลัก`:
  - ยอมรับผลและ normalize ต่อได้
  - derive `top_3/top_2/bottom_2` ตามเดิมเพื่อไม่กระทบการคำนวณรางวัลของ bet types เดิม
- เหตุผล: ป้องกันกรณี dry-run ผ่านแต่ apply จริงล้มที่ settlement สำหรับตลาดหุ้น/VIP/4D ที่ใช้ผลรางวัลไม่ใช่ 5-6 หลัก

## 2026-03-29 — Dry-run By Date Supports Single-Click Async Polling in Popup (APPROVED)

- ปรับ response ของ `test_fetch_by_date` ให้ส่ง `receipt_key`, `selected_driver`, `polling_required` เมื่ออยู่เส้น async (`FETCH_DEFERRED`)
- ปรับหน้า popup ทั้ง `lotto/markets` และ `lotto/auto-result-sources`:
  - กด dry-run ครั้งเดียวได้
  - ถ้าได้ `FETCH_DEFERRED` จะ polling `browser_test_status` อัตโนมัติใน popup เดิม
  - เมื่อ worker จบแล้ว frontend จะยิง dry-run ซ้ำอัตโนมัติรอบสรุปผล เพื่อให้ได้ผล pipeline สุดท้าย
- เป้าหมาย: ลดขั้นตอนมือ (ไม่บังคับกด Browser Test ก่อนทุกครั้ง) และคง flow async-only ของ browser runtime

## 2026-03-29 — FetchExecutor Supports endpoint_url Placeholder expected_draw_date (APPROVED)

- เพิ่มการแทนค่า runtime placeholder ใน `fetch_config_json.endpoint_url` ก่อนยิง request:
  - รองรับ `{{expected_draw_date}}` และ `{expected_draw_date}`
- ใช้ค่าจาก `runtimeContext.expected_draw_date` ในรอบ execute ของ pipeline
- แก้ปัญหาเคส endpoint แบบ dynamic date เช่น `/between-dates/null/{{expected_draw_date}}/1` ที่เดิมไม่ถูก interpolate

## 2026-03-29 — Logs Detail Modal Shows Trace Only (APPROVED)

- ปุ่ม `ดู` ในหน้า logs ตามวันที่ (ทั้ง popup `/lotto/markets` และหน้า `/lotto/auto-result-sources`) ปรับให้แสดงเฉพาะ `trace_json`
- ตัดการแสดง payload อื่นใน modal รายละเอียด log เพื่อลด noise ตอนวิเคราะห์ pipeline trace

## 2026-03-29 — Main JSON Is Save-Time Source of Truth for Auto Result Source Forms (APPROVED)

- ทั้งหน้า `/lotto/markets` popup และ `/lotto/auto-result-sources` ปรับให้ตอน save/preview/validate ยึด `JSON หลัก` (`unified_pipeline_json`) เป็น source of truth โดยตรง
- ตัดพฤติกรรมที่เอา quick setup ไป regenerate/overwrite ค่าใน `JSON หลัก` อัตโนมัติตอน submit/edit flow
- กรณีผู้ใช้วาง `selection_stage` ไว้ระดับ top-level ของ `JSON หลัก` ระบบจะ normalize ไปที่ `selection_config_json.selection_stage` เพื่อกัน fallback ผิดไป `PRE_MAPPING`

## 2026-03-29 — Markets Table: Result Mode Toggle + Source Light Indicator (APPROVED)

- หน้า `lotto/markets` เพิ่มคอลัมน์ `ออกผล` (หลัง `ลิงก์ออกผล`) เป็นปุ่ม toggle `Auto/Manual`
- ปุ่มดังกล่าวผูกกับฟิลด์ `auto_settle_on_result` และใช้ endpoint toggle เดิม (`edit`) ผ่าน `method=auto_settle_on_result`
- ปรับชื่อคอลัมน์ `Auto Source` เป็น `Source`
- สถานะผูก source เปลี่ยนจาก badge ข้อความ เป็นไอคอนไฟ:
  - ผูกแล้ว = ไฟเขียวมี pulse effect + แสดงจำนวน source
  - ยังไม่ผูก = ไฟสีเทา

## 2026-03-29 — Dry-run By Date Persists Full Fetch Log Snapshot (APPROVED)

- ปรับ endpoint `test_fetch_by_date` ให้ persist log ลง `lotto_result_fetch_logs` แบบใกล้เคียง production run
- บันทึกเพิ่ม: `request_url`, `request_meta_json`, `response_http_status`, `response_body`, `parsed_payload_json`, `normalized_result_json`, `selection_debug_json`, `trace_json`, `duration_ms`
- สำหรับ dry-run by date ให้ใช้ `draw_id = null` และอ้างอิงด้วย `run_id` เป็นหลัก

## 2026-03-29 — Quick Setup First Prize Right-Digits Supports Zero (APPROVED)

- ตั้งค่า default ของ `เก็บท้ายกี่หลัก (รางวัลที่ 1)` เป็น `0`
- semantics ใหม่: ถ้าค่าเป็น `0` ระบบจะไม่ generate `right` transform ให้ `first_prize`
- ใช้กติกาเดียวกันทั้งหน้า `/lotto/markets` popup และ `/lotto/auto-result-sources`

## 2026-03-29 — Per-Market Auto Settle Toggle + Result Telegram Notify (APPROVED)

- เพิ่มค่าระดับ `lotto_markets`:
  - `auto_settle_on_result` (default `true`)
  - `notify_result_telegram` (default `true`)
- นโยบาย apply ผลอัตโนมัติ:
  - ถ้า `auto_settle_on_result=true` ระบบทำงานเดิม: settle ทันทีและเปลี่ยน draw เป็น `resulted`
  - ถ้า `auto_settle_on_result=false` ระบบจะบันทึกผลที่ดึงได้ไว้ใน draw แต่คงสถานะ `closed` ให้ทีมงานกดประกาศผลเอง
- เมื่อ draw เปลี่ยนเป็น `resulted` จะส่ง Telegram ไป `notify/send` ตาม pattern เดียวกับ observer ฝั่ง payment
- การส่ง Telegram ถูกควบคุมรายตลาดด้วย `notify_result_telegram`
- เปลี่ยนเส้น exhausted alert ของ auto-result ให้ส่งผ่าน `TelegramBot` (queue job `SendTelegramBot`) แทน `SendTelegramAlert/TelegramFailedBot`
- ปรับข้อความแจ้งเตือนเป็นภาษาธุรกิจ:
  - exhausted: `หวย{ชื่อ} งวดวันที่ {date} เวลาออกผล {time} ไม่สามารถดึงผลรางวัลได้`
  - resulted/fetched: แสดงเลขผล + สถานะ `คำนวนเงินรางวัลแล้ว` หรือ `รอทีมงานอนุมัติการคำนวน`

## 2026-03-29 — Auto Result Per-Source Retry Exhaustion Fallback Chain (APPROVED)

- ปรับ pipeline ให้รองรับ fallback chain เมื่อมีหลาย source ใน market เดียวกัน
- retry policy ถูกประเมินแบบราย `draw + source` โดยดูสถานะ `NOT_READY` จาก fetch logs
- เมื่อ source แรกครบ retry limit (`max_attempts`) ระบบจะ mark exhausted เฉพาะ source และเลื่อนไปลอง source ถัดไปตาม priority
- ถ้า source ยังอยู่ใน backoff window และยังไม่ exhausted ระบบยังคงรอ source เดิม (ไม่สลับ source ก่อนเวลา)
- draw จะถูก mark `EXHAUSTED` ก็ต่อเมื่อ source ที่ active ทั้งหมด exhausted แล้ว

## 2026-03-29 — Markets Popup Quick Setup Aligns to Effective JSON Contract (APPROVED)

- popup `/lotto/markets` ของ `Auto Result Sources` ปรับ label `ตลาด` เป็น `รายการหวย` ทั้งโหมดเพิ่มและแก้ไข
- แท็บ `ตั้งค่าด่วน` เพิ่มตัวเลือกที่ผูกกับ JSON จริง: `fetch_strategy`, `parser_type`, `selection_stage`
- เพิ่ม dependency reset ใน quick setup ให้สอดคล้องกับ runtime policy:
  - โหมด lookup ที่ไม่ใช้ offset บังคับ offset = 0
  - `http_only` ปิด `allow_dom_fallback` และปิด `requires_browser` เมื่อไม่ใช้ rendered browser
  - `RENDERED_BROWSER` บังคับเปิด browser capability ที่เกี่ยวข้อง
- แท็บ `JSON หลัก` ระบุโครงสร้าง key หลักที่ต้องมีอย่างชัดเจน
- เอาปุ่ม preset `ตั้งค่าอัตโนมัติ` ออกจาก quick setup เพื่อลดความสับสน
- `Browser Worker Settings` ในแท็บ quick setup ถูกเปลี่ยนเป็น auto-generated defaults (ไม่ให้ปรับรายช่อง)
- ปุ่ม `Dry Run ตามวันที่` ใน popup `/lotto/markets` รองรับ draw สถานะ `open/closed/resulted` (ไม่จำกัดเฉพาะ `closed/resulted`)
- ปรับโหมดทดสอบตามวันที่ให้ไม่ต้องพึ่งงวดจริง: ถ้าไม่พบ draw ของวันนั้น ระบบจะใช้ virtual draw context แทนทั้ง Dry Run และ Browser Test

## 2026-03-29 — Number Blocks Table Splits Draw/Market Columns with Market Logo (APPROVED)

- ตาราง `lotto/number-blocks` แยกคอลัมน์ `งวด` และ `รายการหวย` ออกจากกัน
- คอลัมน์ `รายการหวย` แสดงโลโก้หน้าชื่อรายการเมื่อ market มี `logo`/`icon`
- โครงสร้าง filter เดิม (draw_date/market/group/bet_type/number_search) ไม่เปลี่ยน

## 2026-03-29 — Number Blocks Market Filter Supports Whole Group Selection (APPROVED)

- ใน filter `รายการหวย` ของหน้า `lotto/number-blocks` เพิ่มตัวเลือก `ทั้งกลุ่ม: {group}`
- เมื่อเลือกทั้งกลุ่ม ระบบจะส่งและใช้ `group_id` filter กับ query แทน `market_id`
- ยังคงรองรับการเลือกแบบราย `market_id` ตามเดิมใน select เดียวกัน

## 2026-03-29 — Number Blocks Filter Uses Draw Date + Grouped Market (APPROVED)

- หน้า `lotto/number-blocks` เปลี่ยน filter จาก `งวดหวย (draw_id)` เป็น `วันที่งวด (draw_date)`
- เพิ่ม filter `รายการหวย (market_id)` แบบ grouped options ตามกลุ่มหวย
- filter เดิม `ประเภทเดิมพัน` และ `ค้นหาเลข` ยังคงเดิม

## 2026-03-29 — AutoResultV2 CI Guardrail Workflow (APPROVED)

- เพิ่ม GitHub Actions workflow `lotto-autoresultv2-unit`
- รัน test scope `tests/Unit/Lotto/AutoResultV2` ในทุก push/pull_request ที่กระทบโค้ดหลัก
- บังคับเก็บ test artifacts (`autoresultv2-unit.log`, `junit-autoresultv2.xml`) เพื่อ debug regression

## 2026-03-29 — Browser Runtime Incident Runbook Adoption (APPROVED)

- เพิ่ม runbook มาตรฐานสำหรับ on-call ที่ `docs/internal/03_DOMAINS/lotto-browser-runtime-incident-runbook.md`
- ล็อกให้ triage อิง `reason_code` + trace fields (`selected_driver`, `phase_timing`, `payload_origin`, `selected_capture`, `artifact_refs`)
- ล็อก rollback policy ตาม capability (`prefer_browser_runtime` fallback allowlist only, `require_browser_runtime` no HTTP fallback)

## 2026-04-06 — Auto Result Global Retry Backoff = 5 Minutes (APPROVED)

- ปรับค่า default retry policy ของ auto-result ให้เป็นทั้งระบบ
- `max_attempts=5`
- หน่วงการ retry ครั้งละ 5 นาที (ตั้ง `base_backoff_seconds=300`, `max_backoff_seconds=300`)

## 2026-04-06 — Auto Result Logs Only When Endpoint Is Called (APPROVED)

- ถ้าอยู่ใน backoff window และยังไม่ได้ยิง endpoint จริง: ไม่ต้องบันทึก `lotto_result_fetch_logs`
- ไม่บันทึก `lotto_result_fetch_logs` สำหรับกรณีที่ยังไม่ยิง endpoint (rate limit, no-source, template error, exhausted decision)
- บันทึกเฉพาะรอบที่มีการเรียก endpoint จริงเท่านั้น

## 2026-03-29 — Browser Runtime Artifact Retention Cleanup Scheduling (APPROVED)

- เพิ่ม command `lotto:cleanup-browser-runtime-artifacts` สำหรับลบ artifact ที่เกิน retention
- command รองรับ `--days` override และ `--dry-run` เพื่อ rollout อย่างปลอดภัย
- เพิ่ม scheduler รันทุกวันเวลา `03:55` แบบ non hot-path (`withoutOverlapping`)
- retention default ยังคงอิง `lotto_auto_result.browser_runtime.artifacts.retention_days`

## 2026-03-29 — Browser Runtime Phase 2 Implementation Alignment (APPROVED)

- เพิ่ม runtime policy ที่บังคับใช้จาก source config (`fetch_config_json.meta.runtime`):
  - `fetch_capability`: `http_only|prefer_browser_runtime|require_browser_runtime`
  - `allow_dom_fallback`
  - optional `http_fallback_strategy` สำหรับเส้นทาง `http_only`
- เพิ่ม global runtime config สำหรับ rollout + budget + artifact:
  - whitelist source ids
  - global/per-source/per-domain concurrency caps
  - overall timeout cap
  - artifact max bytes + preview truncate
- ล็อก fallback classifier ให้ `prefer_browser_runtime` fallback ได้เฉพาะ allowlist reason codes
- เพิ่ม trace/debug visibility ฝั่ง fetch/runtime:
  - `selected_driver`, `payload_origin`, `phase_timing`, `selected_capture`, `artifact_refs`
- เพิ่ม Node worker script baseline (`scripts/lotto/browser_runtime_worker.js`) สำหรับ Playwright execution contract (JSON in/out)

## 2026-03-29 — Browser Runtime Phase 2 Locked Decisions (APPROVED)

- Runtime baseline ล็อกเป็น `Playwright Node Worker`
- Capability policy ล็อกเป็น `http_only|prefer_browser_runtime|require_browser_runtime`
- Transport เฟสแรกล็อกเป็น:
  - PHP queue job เรียก local Node process
  - input/output JSON
  - บันทึก `exit_code` และ `stderr_summary`
- Fallback ของ `prefer_browser_runtime` อนุญาตเฉพาะ:
  - `BROWSER_RUNTIME_UNAVAILABLE`
  - `BROWSER_LAUNCH_FAILED`
  - `BROWSER_EXECUTOR_TIMEOUT`
  - `BROWSER_EXECUTOR_IO_ERROR`
- ห้าม fallback ไป HTTP ในเคส:
  - `NO_NETWORK_MATCH` (เมื่อ source declare network capture เป็นหลัก)
  - `DOM_SELECTOR_NOT_FOUND` (เมื่อ source declare browser path เป็นหลัก)
  - invalid capture/wait/predicate config
- ล็อกว่า PHP เป็น runtime schema authority:
  - Node worker ต้อง emit ตาม schema/version ที่ PHP กำหนด
  - schema change ต้อง bump version
- `selection_mode=best` ต้อง deterministic tie-break:
  1) exact URL > wildcard  
  2) exact method > any  
  3) exact content-type > generic  
  4) rule priority สูงกว่า  
  5) latest response  
  6) ถ้ายัง tie ให้ reject `CAPTURE_AMBIGUOUS_MATCH`
- DOM fallback เป็น optional เท่านั้น (`allow_dom_fallback=true`) และต้องระบุ `payload_origin` ใน trace
- Browser runtime test ใน admin ล็อกเป็น async only (dispatch + polling) ห้าม sync execution ใน request lifecycle
- Artifact policy ล็อก:
  - deterministic storage path
  - redaction, truncation, retention
  - size cap ต่อ run
- Rollout policy ล็อก:
  - source เดิม default = `http_only`
  - browser runtime ใช้แบบ opt-in + whitelist
  - มี global feature flag ปิด browser runtime ได้ทั้งระบบ
- Concurrency/time budget ล็อกตั้งแต่ implementation แรก:
  - global / per-source / per-domain concurrency caps
  - overall runtime timeout cap
  - artifact write cap ต่อ run

## 2026-03-28 — Browser Worker Hardening for Auto Result JS-delayed Sources (APPROVED)

- ยืนยัน runtime model เป็น `Async + Retry` บน dedicated worker สำหรับ `RENDERED_BROWSER`
- เพิ่ม deterministic `receipt_key` (normalized config + stable context) และตัด volatile fields ออกจาก hash
- dispatch ป้องกันงานซ้ำด้วย atomic lock (`SETNX + TTL`) key ตาม `receipt_key`
- กำหนด structured cache payload สำหรับ browser fetch result:
  - `status` (`success|failed|app_shell_only`)
  - `response_body`, `selected_endpoint`, `error_code`, `meta`
- กำหนดลำดับเลือกผลแบบ strict:
  1) captured endpoint JSON
  2) rendered HTML
  3) `APP_SHELL_ONLY`
- ปรับ cutover semantics:
  - `FETCH_DEFERRED`/network-class errors -> `NOT_READY` (retryable)
  - `APP_SHELL_ONLY` -> terminal reject (no retry)
- เพิ่ม Browser Worker settings ใน Auto Source form และ serialize/deserialize ผ่าน `fetch_config_json.meta.browser_worker`
- เพิ่ม async browser test endpoints:
  - `POST /lotto/auto-result-sources/browser-test-dispatch`
  - `GET /lotto/auto-result-sources/browser-test-status`

## 2026-03-28 — Number Blocks Table Supports Filters + Bulk Delete (APPROVED)

- หน้า `lotto/number-blocks` เพิ่มคอลัมน์ checkbox เป็นคอลัมน์แรกสุด
- เพิ่ม filter บนหน้า index สำหรับ `งวดหวย`, `ประเภทเดิมพัน`, และ `ค้นหาเลข`
- เปิด DataTables `searching=true` สำหรับตารางเลขอั้น
- เพิ่มปุ่มลบรายรายการในคอลัมน์ `จัดการ`
- เพิ่มปุ่มลบแบบกลุ่มเมื่อมีการเลือก checkbox หลายรายการ
- เพิ่ม endpoint:
  - `POST /lotto/number-blocks/delete`
  - `POST /lotto/number-blocks/bulk-delete`

## 2026-03-28 — Markets Status Toggle Label + Auto Source Delete in Modal (APPROVED)

- ตาราง `lotto/markets` ปรับปุ่มคอลัมน์ `สถานะ` ให้แสดงคำ `ถูก/ผิด` พร้อม icon และกดสลับสถานะได้เหมือนเดิม
- คอลัมน์ `จัดการ` ของ `lotto/markets` คงปุ่ม `แก้ไข` + `Auto` (ไม่เพิ่มปุ่มลบตลาดในตารางหลัก)
- เพิ่มปุ่ม `ลบ` เฉพาะใน modal รายการ `Auto Result Sources` ของ market
- เพิ่ม endpoint `POST /lotto/auto-result-sources/delete` สำหรับลบ source
- guard การลบ: ถ้า source ถูกอ้างอิงโดย `lotto_draws.result_source_id` จะ reject

## 2026-03-28 — Markets/Auto Source Action Layout Refinement (APPROVED)

- ปุ่มคอลัมน์ `สถานะ` ของ `lotto/markets` เปลี่ยนเป็น icon-only (`check/times`) โดยยังคงกดสลับสถานะได้
- modal `Auto Result Sources` แสดงชื่อรายการหวยบนหัว modal (ไม่แสดงแค่ id)
- คอลัมน์ `สถานะ` ในตาราง modal ถูกย้ายมาไว้ก่อน `จัดการ` และใช้ปุ่ม icon-only เป็นจุดกดสลับสถานะ
- คอลัมน์ `จัดการ` ของ modal เหลือเฉพาะ `แก้ไข/ลบ` พร้อม icon+ข้อความ และสีมาตรฐาน (`info/danger`)
- ตัดปุ่ม `เปิดใช้งาน/ปิดใช้งาน` ออกจากคอลัมน์ `จัดการ` ของ modal

## 2026-03-28 — Markets Auto Sources Uses Native Modal (No iframe) (APPROVED)

- ปรับ modal `Auto` ในหน้า `lotto/markets` ให้จัดการ source แบบ native ทั้งหมด (ไม่ใช้ iframe/embed)
- modal แสดงรายการ source ของ market นั้นในตารางเดียวกัน พร้อมปุ่มเพิ่ม/แก้ไข/เปิด-ปิดใช้งาน
- ฟอร์มแก้ไข source ใน modal รองรับการทดสอบตามวันที่และดู logs ได้ในหน้าเดียวกัน
- ใช้ endpoint เดิมของ `auto-result-sources` และใช้ `GET /lotto/auto-result-sources/list` สำหรับโหลดรายการ
- หมายเหตุ: แนวทางนี้แทนที่ decision `Markets Auto Button Restored to In-Page Modal` เฉพาะส่วนที่อ้าง iframe

## 2026-03-28 — Markets Auto Button Restored to In-Page Modal (APPROVED)

- ปรับปุ่ม `Auto` ในหน้า `lotto/markets` กลับมาเปิด modal ในหน้าเดิม (ไม่เปลี่ยนหน้า)
- modal ยังคงใช้ iframe โหมด embed (`embed=1`) พร้อม `market_id` + `lock_market=1`
- เหตุผล: ผู้ใช้ต้องการ workflow ที่ไม่ออกจากหน้ารายการหวย
- หมายเหตุ: ถูกแทนที่ภายหลังด้วย decision `Markets Auto Sources Uses Native Modal (No iframe)`

## 2026-03-28 — Markets Auto Button Switches to Direct Page Navigation (APPROVED)

- ยกเลิกการเปิด `Auto Result Sources` ผ่าน iframe modal จากหน้า `lotto/markets`
- ปุ่ม `Auto` เปลี่ยนเป็นนำทางไปหน้า `auto-result-sources` โดยตรง พร้อม `market_id` และ `lock_market=1`
- เหตุผล: ลดความเสี่ยงปัญหา iframe ถูกบล็อกจากนโยบาย web server/security header และลดปัญหา layout ซ้อนใน modal
- หมายเหตุ: แนวทางนี้ถูกแทนที่ภายหลังด้วย decision `Markets Auto Button Restored to In-Page Modal`

## 2026-03-28 — Embed Mode for Markets Auto Result Sources Modal (APPROVED)

- ปรับลิงก์ที่ปุ่ม `Auto` ในหน้า `lotto/markets` ให้ส่ง `embed=1`
- หน้า `auto-result-sources` เมื่ออยู่โหมด embed จะซ่อน layout ส่วน global (sidebar/topbar/footer/breadcrumb) เพื่อให้แสดงผลถูกต้องใน iframe modal
- แก้ปัญหา modal แสดงหน้าเต็มผิดสัดส่วนและอ่านยาก
- หมายเหตุ: แนวทางนี้ถูกแทนที่ภายหลังด้วยการนำทางตรง (ไม่ใช้ iframe) ใน decision `Markets Auto Button Switches to Direct Page Navigation`

## 2026-03-28 — Add Status Filter on Admin Draws Menu (APPROVED)

- เพิ่มตัวกรอง `สถานะ` ในหน้า `lotto/draws`
- รองรับค่า `draft`, `open`, `closed`, `resulted`
- filter นี้ทำงานร่วมกับตัวกรองเดิม (`กลุ่มหวย`, `รายการหวย`, `วันงวด`) และส่งผลถึง query ฝั่ง DataTable จริง

## 2026-03-28 — Draw Status Column Uses Button + Styled Text (APPROVED)

- คอลัมน์ `สถานะ` ในหน้า `lotto/draws` เลิกใช้ badge แบบเดิม
- สถานะที่สลับได้ (`open/closed` เมื่อมีสิทธิ์) แสดงเป็นปุ่มเพื่อกดสลับสถานะโดยตรง
- สถานะที่กดไม่ได้ แสดงเป็นข้อความตกแต่งสีพร้อมไอคอน เพื่อให้อ่านชัดกว่า badge เดิม

## 2026-03-28 — Draw Settle Action Uses Manual/Auto Selector Modal (APPROVED)

- ปุ่ม `ออกผล` ในหน้า `lotto/draws` ถูกปรับให้เปิด modal ขนาดเล็กเพื่อเลือกโหมดการทำงาน
- โหมดใน modal:
  - `Manual` เปิดฟอร์ม settle เดิมเพื่อกรอกผลเองและคำนวณรางวัล
  - `Auto` เรียก flow `Retry` เดิม (`auto_result_manual_retry`)
- ปุ่ม `Retry` แยกจาก action column ถูกยุบเข้าโหมด `Auto` เพื่อลดความซ้ำซ้อนของ action
- การมองเห็น/การกดแต่ละโหมดใน modal ยังคงเช็กสิทธิ์ ACL ตามเดิม (`lotto_draws.settle`, `lotto_draws.auto_result_manual_retry`)

## 2026-03-28 — Increase Draw Row Tint Contrast + Middle Alignment (APPROVED)

- ปรับสีพื้นหลังแถวในหน้า `lotto/draws` ให้เข้มขึ้นจากเดิม เพื่อแยกสถานะ (`draft/open/closed/resulted`) ได้ชัดเจนขึ้น
- บังคับการจัดแนวข้อมูลในตารางให้เป็นแนวตั้งกึ่งกลาง (`vertical-align: middle`) และจัดข้อความส่วนข้อมูลให้อยู่กึ่งกลาง

## 2026-03-28 — Draw Status Toggle via Status Cell + Remove Open Action Button (APPROVED)

- หน้า `lotto/draws` เอาปุ่ม `เปิดรับ/ปิดรับ` ออกจากคอลัมน์ `ดำเนินการ`
- ช่อง `สถานะ` สำหรับงวดที่เป็น `open/closed` ถูกปรับเป็น clickable badge เพื่อสลับสถานะได้ตรงจากคอลัมน์สถานะ
- ก่อนสลับสถานะต้องแสดง popup ยืนยันทุกครั้ง
- การคลิกสลับสถานะยังคงดักสิทธิ์ตาม ACL เดิม:
  - `open -> closed` ต้องมีสิทธิ์ `lotto_draws.close`
  - `closed -> open` ต้องมีสิทธิ์ `lotto_draws.open`

## 2026-03-28 — Soft Row Tint by Draw Status on Admin Draws Table (APPROVED)

- หน้า `lotto/draws` เพิ่มสีพื้นหลังแถวแบบโทนอ่อนตามสถานะงวดเพื่ออ่านสถานะได้เร็วขึ้น
- mapping สี:
  - `draft` เทาอ่อน
  - `open` เขียวอ่อน
  - `closed` เหลืองอ่อน
  - `resulted` ฟ้าอ่อน
- ใช้การ tint ที่ฝั่ง DataTable render (ไม่เปลี่ยน domain data/schema)
- หมายเหตุ: ความเข้มสีถูกปรับเพิ่มภายหลังใน decision `Increase Draw Row Tint Contrast + Middle Alignment`

## 2026-03-28 — Lotto Markets Inline Auto Result Sources Management (APPROVED)

- เพิ่มปุ่ม `Auto` ในเมนู `lotto/markets` (วางหลังปุ่ม `แก้ไข`) และดักสิทธิ์ด้วย ACL `lotto_settings.auto_result_sources`
- ปุ่ม `Auto` เปิด modal เพื่อจัดการ `Auto Result Sources` ของตลาดนั้นโดยตรง (filter lock ตาม `market_id`)
- เพิ่มความสามารถทดสอบจากหน้าแก้ไข source:
  - เลือก `draw_date` แล้วกด dry-run โดย resolve draw จาก `market_id + draw_date` (ไม่ต้องไปกดจากเมนูงวดหวย)
  - มีปุ่มดู logs ของผลทดสอบจากวันที่ที่เลือกใน modal เดียวกัน
- เพิ่มคอลัมน์ในเมนู `lotto/markets` หลัง `ลิงก์ออกผล` เพื่อแสดงสถานะว่า market นี้ผูก Auto Result Source แล้วหรือยัง

## 2026-03-28 — Hide Dry-run/Logs Actions on Admin Lotto Draws (APPROVED)

- ปรับหน้าเมนู `lotto/draws` (action column) ให้ซ่อนปุ่ม `Dry-run` และ `Logs`
- คงปุ่ม action อื่นไว้ตามสิทธิ์เดิม (เช่น edit/open/close/settle/retry)

## 2026-03-28 — Frontend Lotto Critical Path Endpoints (`/api/frontend`) (APPROVED)

- เพิ่ม endpoint public สำหรับ frontend หน้าแทงโดยตรงที่ `/api/frontend/lotto/markets/{marketId}/betting-context`
- payload ต้องรวม market/current draw/blocked numbers/limits/number exposure/version/server_time ในเส้นเดียว
- เพิ่ม endpoint ผลย้อนหลัง:
  - `GET /api/frontend/lotto/markets/{marketId}/results`
  - `GET /api/frontend/lotto/markets/{marketId}/draws/{drawId}/result`
- เป้าหมาย: ลดการเรียกหลายเส้นใน critical path ของหน้าแทงและทำให้ผลย้อนหลังมี contract ชัดเจนแบบ pagination-friendly

## 2026-03-28 — Frontend API v1 Game List Warmup Before Proxy Read (APPROVED)

- สำหรับ endpoint `GET /api/v1/games/{type}/{provider}` ให้ trigger provider `gamelist` ก่อนทุกครั้ง
- หลัง warmup แล้วให้คืนผลจาก `GameListProxy` เป็นหลักตาม contract v1 เดิม
- วัตถุประสงค์: ลดเคสข้อมูลค่ายเกมใน v1 ไม่อัปเดต/ไม่ครบเมื่อ cache/proxy ยังไม่ทัน sync

## 2026-03-27 — Document Standardization (LOCKED)

- รวมเอกสารเข้าโครงสร้างมาตรฐานเดียวภายใต้ `docs/`
- แยก internal/public ชัดเจน
- ย้ายเอกสารกระจัดกระจายจาก root/.github/packages/public/vendor เข้า `docs/internal`
- ตั้ง source-of-truth หลัก:
  - `docs/internal/00_RULES/agent_rules.md`
  - `docs/internal/01_SYSTEM/system_current_state.md`
  - `docs/internal/02_DECISIONS/decision_log.md`
- เอกสารซ้ำและเวอร์ชันเก่าถูกย้ายไป `docs/internal/05_ARCHIVE/`

## 2026-03-27 — Lotto Draw Lifecycle Hardening (LOCKED)

- ล็อก `open/close` ให้รับ `source` แบบ explicit (`scheduled|manual`)
- ล็อก settle idempotency แบบ reject เมื่อ `status=resulted`
- ล็อก `result_at` ให้ใช้ server time ใน service เท่านั้น
- เพิ่มฟิลด์ audit transition ของ draw (`opened_at`, `closed_at`, `open_mode`, `close_mode`)

## 2026-03-27 — Open Draw Date Editable (APPROVED)

- อนุญาตให้แก้ `draw_date` ได้ในหน้าแก้ไขงวด เมื่อสถานะงวดเป็น `open`
- คงหลัก allowlist ของ update ไว้ โดยเพิ่ม `draw_date` เข้า allowlist ของสถานะ `open`
- ฝั่ง UI และ backend ต้องสอดคล้องกัน (เปิด field + validate/persist ได้จริง)

## 2026-03-27 — Draw Actions Permission Gate (APPROVED)

- เพิ่มการเช็กสิทธิ์รายปุ่มในหน้า `draws` action column ผ่าน `bouncer()->hasPermission(...)`
- map ACL key ตาม action (`edit/open/close/settle/dry-run/retry/logs`)
- กำหนดให้สถานะ `resulted` ยังแสดงปุ่ม `Logs` และ `Dry-run` ได้เมื่อมีสิทธิ์
- ยืนยันว่า `superadmin` ผ่านทุกสิทธิ์ตาม bouncer behavior เดิม

## 2026-03-27 — Resulted Dry-run Visibility (APPROVED)

- เพิ่มการแสดงปุ่ม `Dry-run` ในสถานะงวด `resulted` เมื่อผู้ใช้มีสิทธิ์ `lotto_draws.auto_result_test_fetch`
- ปรับ command `lotto:fetch-auto-results` ให้ manual dry-run แบบระบุ `draw_id` รองรับสถานะ `closed` และ `resulted`

## 2026-03-27 — Auto Result Sources Table Sorting (APPROVED)

- ยกเลิกการ lock ลำดับข้อมูลด้วย `orderBy(priority,id)` ตายตัวใน query ของ DataTable
- กำหนด default initial sort ที่ฝั่ง DataTables แทน (`priority ASC`, `id DESC`)
- เป้าหมายคือให้ผู้ใช้กด sort คอลัมน์อื่นได้จริงตามพฤติกรรมตารางมาตรฐาน

## 2026-03-27 — Auto Result Dry-run Sync Execution (APPROVED)

- เปลี่ยน endpoint admin `Dry-run` ให้รัน `lotto:fetch-auto-results` แบบ synchronous แทน queue dispatch
- เหตุผล: production อาจไม่มี worker queue ทำให้ขึ้นข้อความว่าส่งคำสั่งแล้วแต่ไม่เกิดการประมวลผลจริง
- กำหนดให้ UI แสดง error message จาก backend เมื่อ dry-run/retry ล้มเหลว เพื่อลด silent failure

## 2026-03-27 — Draw Window Overnight Normalization (APPROVED)

- ในฟอร์ม admin `draws/addedit` ให้รองรับการกรอกเวลาข้ามวันโดยไม่ต้องเปลี่ยนวันที่เองทุกครั้ง
- ถ้า `close_at` น้อยกว่า `open_at` ให้ normalize `close_at` เป็นวันถัดไป
- ถ้า `result_at` น้อยกว่า `close_at` ให้ normalize `result_at` เป็นวันถัดไป
- ถ้าเวลาที่กรอกน้อยกว่าค่าอ้างอิง ระบบให้ normalize ไปวันถัดไปจนได้ลำดับเวลาที่ถูกต้อง
- เมนู `รายการหวย` ใช้กติกาเวลาเดียวกัน และ command `lotto:generate-auto-draws` ต้องคำนวณข้ามวันให้ตรงกับ config

## 2026-03-27 — Auto Result Parser v2 Strict Context (APPROVED)

- เพิ่ม parser pipeline v2 แบบ candidate/record-scoped เพื่อกัน cross-block mismatch
- ล็อกความรับผิดชอบ layer:
  - parser = extract candidate/raw fields
  - selector = choose/reject candidate
  - mapper = transform chain
  - validator = canonical validation + expected context
- default strategy ของ v2 คือ `strict_single_match` และไม่ fallback แบบเงียบเมื่อ ambiguous
- score-based strategy เป็น opt-in เท่านั้น และต้อง reject เมื่อ tie
- เพิ่ม runtime debug field `selection_debug_json` ใน `lotto_result_fetch_logs` (execution metadata)
- รองรับส่ง `expected_draw_date` จาก command/admin action เข้า pipeline โดยตรง

## 2026-03-27 — Auto Result Skip When Source Config Missing (APPROVED)

- ใน command `lotto:fetch-auto-results` โหมด auto sweep ให้เช็กก่อนว่า market นั้นมี source config ใน `lotto_result_sources` หรือยัง
- ถ้ายังไม่มีให้ `skip` โดยไม่เรียก pipeline, ไม่เพิ่ม retry attempts, และไม่ปล่อยให้วิ่งจน `EXHAUSTED`
- ใช้เพื่อกันเคส noise alert ประเภท exhausted จาก draw ที่ยังไม่ได้ onboard source

## 2026-03-27 — Lotto Result Pipeline v2 Enum/Trace/Shadow Governance (APPROVED)

- เพิ่ม fixed enum/value sets สำหรับ pipeline orchestration:
  - `pipeline_version`: `LEGACY|V2_SHADOW|V2_CUTOVER`
  - `fetch_strategy`: `JSON_HTTP|HTML_HTTP|RENDERED_BROWSER|EMBEDDED_JSON|MANUAL_INPUT`
  - `selection_stage`: `PRE_MAPPING|POST_MAPPING`
  - `shadow_compare_status`: `MATCH|MISMATCH|ERROR|SKIPPED`
- เพิ่ม schema/config storage สำหรับ source v2:
  - `fetch_config_json`, `selection_config_json`, `readiness_config_json`
  - flags: `supports_partial`, `requires_browser`, `shadow_enabled`, `cutover_enabled`
- เพิ่ม structured trace/error storage ใน `lotto_result_fetch_logs`:
  - `trace_json`, `error_code`, `error_stage`
  - `legacy_result_json`, `v2_result_json`, `shadow_diff_json`, `shadow_compare_status`
- เพิ่ม source revision table `lotto_result_source_revisions` พร้อม metadata:
  - `changed_by`, `reason`, `config_hash`
- บังคับ trace normalization ก่อน persist (minimum required keys + shape normalization)
- บังคับ deterministic mismatch policy ใน shadow compare โดยเทียบ canonical outcome set เท่านั้น
- บังคับ `RenderedBrowserFetchDriver` เป็น async worker/runtime path เท่านั้น (ไม่ block main fetch path)
- เพิ่ม admin preview/validate config และ validate cutover ก่อนเปิด cutover

## 2026-03-27 — Cutover Validation Production Readiness (APPROVED)

- ปรับ `validate cutover` ให้เหมาะกับ production:
  - `production` ใช้ live validation โดยรัน pipeline กับ `endpoint_url` จริง
  - ไม่บังคับให้ผู้ใช้ admin จัดการไฟล์ fixture เอง
  - เพิ่ม fallback deterministic: หากไม่ส่ง `expected_draw_date` และเจอ `NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE` ให้ retry live validate แบบไม่ผูก expected date 1 ครั้ง
- คง fixture gate ไว้เฉพาะ `local/testing` เพื่อรองรับ regression test ของทีมพัฒนา
- ตอนบันทึก source ที่เปิด `cutover_enabled=true`:
  - production ไม่บล็อกด้วย fixture gate
  - local/testing ยังบล็อกจนกว่าจะมี fixture ตาม source

## 2026-03-27 — Auto Result Source Form V2-Only Mode (APPROVED)

- ลดความสับสนจากการมี field legacy+v2 ซ้ำซ้อนในฟอร์มเดียว
- ฟอร์ม `admin/lotto/auto-result-sources` แสดงและเน้นการตั้งค่าแบบ V2 config เป็นหลัก
- ก่อน `preview/validate/save` ระบบจะ derive ค่า field ที่ backend legacy ยังต้องใช้จาก JSON config อัตโนมัติ:
  - `endpoint_url`, `http_method`, `parser_type`, `fetch_strategy`, `selection_stage`
- ตั้ง default ฝั่งฟอร์มเป็น `pipeline_version=V2_CUTOVER` เพื่อให้ flow การใช้งานสอดคล้องกับ runtime ใหม่

## 2026-03-27 — Auto Result Latest-Only Runtime (APPROVED)

- ปรับ runtime ให้ใช้ V2 cutover path เท่านั้น (`latest-only`)
- ปิดการใช้งาน shadow/legacy path ใน `AutoResultPipelineService`
- นโยบายตรวจวันงวดยังคง strict (ห้าม fallback ข้าม expected_draw_date)

## 2026-03-27 — Auto Result Form Single JSON Input UX (APPROVED)

- ฟอร์ม `admin/lotto/auto-result-sources` ให้ผู้ใช้กรอก config หลักผ่าน `Pipeline Config JSON` ช่องเดียว
- ช่อง JSON ย่อย (fetch/parser/mapping/selection/validation/readiness/retry/headers/query/body) ถูกซ่อนจากหน้า form หลัก
- ก่อน preview/validate/save ระบบยัง split/derive ไป field ย่อยอัตโนมัติเพื่อคง backend contract เดิม
- เพิ่มแท็บ `Quick Setup` สำหรับ generate config อัตโนมัติจาก input สั้น ๆ และมี preset สำเร็จรูป
- ยกเลิกแท็บ `Pipeline` ในหน้า UI เพื่อลด field ซ้ำซ้อนและลด cognitive load ของผู้ใช้
- ปรับ label/tab/action เป็นภาษาไทย และจัด layout ของ `ตั้งค่าด่วน` เป็น 2 คอลัมน์สมมาตร พร้อมปรับปุ่ม action ให้มองเห็นชัดขึ้น
- ย้ายฟิลด์แก้ไขหลักทั้งหมดไปที่ `ตั้งค่าด่วน` และให้แท็บ `ทั่วไป` เป็น read-only summary
- โหมดแก้ไข source (`update`) ถูกล็อกไม่ให้เปลี่ยน `market_id` ทั้งใน UI และ backend เพื่อกันย้าย source ข้ามตลาดโดยไม่ตั้งใจ
