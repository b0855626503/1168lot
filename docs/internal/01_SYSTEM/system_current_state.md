# System Current State

อัปเดตล่าสุด: 2026-04-04

## ภาพรวมระบบ

- Framework: Laravel 8
- Architecture: Modular (Konekt Concord)
- หลัก domain สำคัญ: Admin, Wallet, Payment, API, Lotto
- Lotto ใช้สถานะงวดหลัก: `draft -> open -> closed -> resulted`

## นโยบายแก้ไขงวดหวย (Admin)

- สถานะ `draft`: แก้ไขฟิลด์หลักของงวดได้
- สถานะ `open`: อนุญาตแก้ `draw_date` และ `close_at` (รวม metadata ที่ระบบ allowlist ไว้)
- สถานะ `resulted`: ไม่อนุญาตให้แก้ไข
- ฟิลด์เวลาในฟอร์ม admin รองรับเคสข้ามวัน:
  - ถ้า `close_at` < `open_at` จะตีความ `close_at` เป็นวันถัดไป
  - ถ้า `result_at` < `close_at` จะตีความ `result_at` เป็นวันถัดไป
  - ถ้าเวลาที่กรอกน้อยกว่าค่าอ้างอิง ระบบจะ normalize ไปวันถัดไปจนได้ลำดับเวลาที่ถูกต้อง
- เมนู `รายการหวย` รองรับเวลาอัตโนมัติแบบข้ามวันเช่นกัน (`auto_open_time > auto_close_time`)
- โหมดงวด (`draw_mode`) ของตลาดรองรับ:
  - `manual` (ทีมงานสร้างงวดเอง)
  - `daily` (ทุกวัน)
  - `weekdays` (จันทร์-ศุกร์)
  - `wed_sat_sun` (พุธ/เสาร์/อาทิตย์)

## นโยบายสิทธิ์ปุ่มงวดหวย (Admin UI)

- ปุ่มในตาราง `draws` ถูกเช็กสิทธิ์รายปุ่มด้วย `bouncer()->hasPermission(...)`
- ผู้ใช้ `superadmin` ผ่านการตรวจสิทธิ์ทั้งหมดตามกลไก bouncer เดิม
- ปัจจุบันหน้าเมนู `lotto/draws` ซ่อนปุ่ม `Dry-run` และ `Logs` ออกจาก action column
- ปุ่ม `เปิดรับ/ปิดรับ` ถูกถอดออกจากคอลัมน์ `ดำเนินการ` และย้ายพฤติกรรมสลับสถานะไปที่ช่อง `สถานะ` แทน
- ปุ่ม `ออกผล` (สถานะ `closed`) เปิด modal ขนาดเล็กให้เลือกโหมด `Manual` หรือ `Auto`
  - `Manual` = เปิดฟอร์มกรอกผลและคำนวณรางวัลด้วยมือ
  - `Auto` = เรียก flow เดียวกับ `Retry` (`lotto_draws.auto_result_manual_retry`)
- modal `ออกผล` รองรับปุ่ม `งดออกผล` เพิ่มเติม
  - เรียก `POST /lotto/draws/mark-no-result`
  - ใช้ได้เฉพาะงวดสถานะ `closed`
  - เมื่อสำเร็จ ระบบ set งวดเป็น `resulted` พร้อม `result_number.no_result=true`
- ปุ่ม `Retry` แยกใน action column ถูกยุบเข้าในโหมด `Auto` ของ modal `ออกผล`
- ช่อง `สถานะ` ของงวด `open/closed` แสดงเป็นปุ่ม (button) เพื่อสลับ `เปิดรับ <-> ปิดรับ` ได้เมื่อผู้ใช้มีสิทธิ์ที่เกี่ยวข้อง (`lotto_draws.open`/`lotto_draws.close`) และต้องยืนยันผ่าน popup ก่อนทุกครั้ง
- สถานะที่กดไม่ได้ (`draft`/`resulted` หรือรายการที่ไม่มีสิทธิ์) แสดงเป็นข้อความตกแต่งสีพร้อมไอคอน (ไม่ใช้ badge เดิม)
- ตาราง `lotto/draws` แสดงสีพื้นหลังแยกตามสถานะงวดด้วยโทนที่เห็นชัดขึ้น:
  - `draft` = เทาอ่อน
  - `open` = เขียวอ่อน
  - `closed` = เหลืองอ่อน
  - `resulted` = ฟ้าอ่อน
- ตาราง `lotto/draws` จัดข้อมูลใน cell เป็นแนวตั้งกึ่งกลาง (`vertical-align: middle`) และจัดข้อความกึ่งกลางในส่วนข้อมูลแถว
- หน้า `lotto/draws` รองรับ filter เพิ่มเติมด้วย `สถานะ` (`draft/open/closed/resulted`) ร่วมกับ group/market/draw_date

## นโยบาย ACL แยกสิทธิ์ CRUD (Admin Lotto)

- ACL ของเมนูตั้งค่า Lotto รองรับ key แยก action ระดับ `index/create/update/delete` ตาม route ที่มีจริง (pattern เดียวกับโมดูลที่แยกสิทธิ์แบบ CRUD)
- key เดิมระดับเมนู (เช่น `lotto_settings.markets`, `lotto_settings.number_blocks`) ยังคงอยู่เพื่อรักษา backward compatibility
- กลุ่มที่มีการแยก action เพิ่มแล้ว:
  - `lotto_settings.draws.*` (`index/create/update`)
  - `lotto_settings.auto_result_sources.*` (`index/create/update/delete`)
  - `lotto_settings.number_blocks.*` (`index/create/update/delete`)
  - `lotto_settings.groups.*` (`index/create/update`)
  - `lotto_settings.markets.*` (`index/create/update`)
  - `lotto_settings.group_packages.*` (`index/create/update/delete`)
  - `lotto_settings.payout_settings.*` (`index/update`)
  - `lotto_settings.bet_limit_settings.*` (`index/update`)

## นโยบายการเรียงข้อมูลแหล่งผลอัตโนมัติ (Admin UI)

- ตาราง `/lotto/auto-result-sources` รองรับ interactive sorting จากการกดหัวคอลัมน์
- ระบบใช้ default initial sort เป็น `priority ASC` แล้วตามด้วย `id DESC`
- ห้าม lock ลำดับด้วย `orderBy(...)` ตายตัวใน query หลัก เพราะจะทำให้ผู้ใช้ sort คอลัมน์อื่นไม่ได้จริง

## นโยบายหน้าเลขอั้น (Admin `/lotto/number-blocks`)

- ตารางมีคอลัมน์ checkbox เป็นคอลัมน์แรกสุดสำหรับเลือกหลายรายการ
- modal `เพิ่มเลขอั้น` แสดงตัวเลือก `งวดหวย` เป็นงวดล่าสุดของแต่ละรายการหวยที่สถานะยังไม่ปิด (`draft/open`) เท่านั้น
- หน้า index รองรับ filter ด้วย:
  - `วันที่งวด` (`draw_date`)
  - `รายการหวย` (`market_id`) แบบจัดกลุ่มตามกลุ่มหวย และเลือก filter ทั้งกลุ่มได้ (`group_id`)
  - `ประเภทเดิมพัน` (`bet_type`)
  - `ค้นหาเลข` (`number_search`)
- คอลัมน์ตารางแยก `งวด` และ `รายการหวย` ออกจากกัน และคอลัมน์ `รายการหวย` แสดงโลโก้หน้าชื่อรายการเมื่อมีข้อมูลโลโก้
- เปิด DataTables global searching (`searching=true`) และยังรองรับ filter ด้านบนผ่าน preXhr
- รองรับลบหลายรายการจาก checkbox ที่เลือกผ่านปุ่ม `ลบที่เลือก`
- คอลัมน์ `จัดการ` มีปุ่ม `แก้ไข` และ `ลบ` รายรายการ

## นโยบาย Auto Result Manual Action (Admin UI)

- ปุ่ม `Retry` และ `Dry-run` ในหน้า `draws` เรียก `lotto:fetch-auto-results` แบบ synchronous (`Artisan::call`)
- เป้าหมายคือไม่ผูกกับ worker queue ใน production เพื่อให้คำสั่งถูกประมวลผลทันทีและเห็นผล/log ได้จริง
- ฝั่ง UI ของปุ่มดังกล่าวต้องแสดงข้อความ error จาก backend เมื่อคำสั่งล้มเหลว (ห้าม silent failure)
- รองรับส่ง `expected_draw_date` จาก admin action ไปยัง pipeline เพื่อใช้ strict context validation
- pipeline v2 ส่ง `lookup_date` (และ `lookup_date_compact`) ต่อเข้า `FetchExecutor` โดยตรง
  - ถ้าตั้ง source เป็น `ROUND_DATE_MINUS_DAYS` ระบบ dry-run/retry จะยิง upstream ด้วยวันที่ที่เลื่อนแล้วจริง (ไม่ fallback เป็น `expected_draw_date`)

## นโยบาย Auto Result Apply/Settlement (Per Market)

- ถ้า market ตั้ง `auto_settle_on_result=true`:
  - เมื่อ source ดึงผลสำเร็จ ระบบจะ settle ทันทีและ draw เปลี่ยนเป็น `resulted`
- ถ้า market ตั้ง `auto_settle_on_result=false`:
  - ระบบจะบันทึกผลที่ดึงได้ไว้ใน draw แต่คงสถานะงวดเป็น `closed`
  - ทีมงานต้องกดประกาศผลเองจากหน้า admin เพื่อคำนวณยอดได้เสีย
- เมื่อ draw เปลี่ยนเป็น `resulted`:
  - ถ้า market เปิด `notify_result_telegram=true` ระบบ dispatch queue job แยกเพื่อสรุปผลและส่ง Telegram ผ่าน `notify/send`
  - ข้อความแจ้งผลเป็นแบบสั้น (impact-first) และมีสรุป `บิลทั้งหมด/ชนะ/แพ้/กำไรสุทธิ` ในข้อความเดียว
  - ส่วน header ของข้อความ Telegram แสดงทั้ง `งวดวันที่` และ `เวลาออกผล` โดยอ้างอิง `lotto_markets.auto_result_time` เป็นหลัก (fallback เป็น `draw.result_at`)
  - เพิ่มบรรทัด `ออกผลโดย... เวลา ...` เพื่อระบุที่มาของการออกผล (`ระบบออโต้` หรือ `ทีมงาน`)
  - บรรทัด `3 ตัวบน` ในข้อความ Telegram derive จาก `first_prize` แบบ `right(3)` เสมอ
  - policy บังคับ `1 message ต่อ 1 draw` โดยใช้ `lotto_draws.telegram_sent_at` กันยิงซ้ำ
  - trigger เฉพาะตอน status transition เป็น `resulted` เท่านั้น (ไม่ยิงตอนยัง `closed`)
- `SettlementService::normalizeResultNumber` รองรับ `first_prize` แบบ 3 หลักร่วมกับ `last_2_digits` 2 หลัก
  - ใช้ได้กับตลาดที่ผลประกาศเป็น 3 ตัวบน/2 ตัวล่าง (เช่น หุ้น/VIP)
  - ระบบยัง derive `top_3`, `top_2`, `bottom_2` เหมือนเดิมเพื่อให้ settlement bet types เดิมทำงานต่อเนื่อง
- ถ้า source ส่งข้อความลักษณะ `งดออกผล` (หรือ marker เทียบเท่า) ในฟิลด์ผล:
  - pipeline จะ normalize เป็นผลแบบ `no_result=true`
  - apply จะไม่ยิง settlement และบันทึก draw เป็น `resulted` พร้อม `result_number.status=no_result`
  - หน้า `งวดหวย` จะแสดงค่า `งดออกผล` ในคอลัมน์ผลรางวัลแทนตัวเลข

## นโยบาย Telegram Alert ของ Auto Result

- เส้นแจ้งเตือน `EXHAUSTED` ส่งผ่าน `TelegramBot` (job `SendTelegramBot`) ไม่ใช้ `TelegramFailedBot`
- รูปแบบข้อความ exhausted:
  - `หวย{ชื่อ} งวดวันที่ {date} เวลาออกผล {time} ไม่สามารถดึงผลรางวัลได้`
- `TelegramFailedBot` สงวนไว้สำหรับเส้น dev/failed monitoring

## นโยบาย Retry/Fallback หลาย Source (Auto Result)

- การเลือก source ยังคงเรียงตาม `priority ASC` (เลขน้อยสำคัญกว่า) แล้วตาม `id ASC`
- ระบบนับ retry แบบราย `draw + source` จาก fetch logs สถานะ `NOT_READY` (ไม่ใช้ draw-level attempts เพียงอย่างเดียว)
- เมื่อ source แรกครบ `max_attempts` แล้ว ระบบจะ mark ว่า source นั้น exhausted (เฉพาะ source) และ fallback ไป source ถัดไปอัตโนมัติ
- กรณี source ยังไม่ครบ max แต่ยังติด backoff window ระบบจะคงรอ source เดิม (ยังไม่ข้ามไป source ถัดไป)
- draw จะถูก mark `EXHAUSTED` เมื่อ source ที่ active ทั้งหมดในช่วงเวลานั้น exhausted ครบแล้วเท่านั้น

## นโยบาย Frontend API v1 Game List

- endpoint `GET /api/v1/games/{type}/{provider}` จะ trigger provider `gamelist` ก่อนทุกครั้ง
- จากนั้นระบบจะอ่านและคืนข้อมูลจาก `GameListProxy` เป็นหลัก (คง response contract v1 เดิม)

## นโยบาย Frontend API v1 Register Referral Code

- ตอนสมัครสมาชิกผ่าน `POST /api/v1/auth/register` ระบบจะสร้าง `members.referral_code` อัตโนมัติทุกบัญชี
  - format: ยาว 8 ตัวอักษร
  - charset: ตัวพิมพ์ใหญ่ + ตัวเลข (`A-Z`, `0-9`) โดยไม่ใช้ `O`
  - ต้องไม่ซ้ำกันทั้งระบบ (unique)
- ฝั่ง request รองรับรหัสแนะนำใน field `referral_code` (alias: `invite_code`, `recommend_code`)
  - ระบบ normalize เป็นตัวพิมพ์ใหญ่ และแทน `O` เป็น `0` ก่อนเทียบ
  - ถ้าเทียบแล้วตรงกับ `members.referral_code` ของสมาชิกใด ให้ set `members.upline_code` ของผู้สมัครเป็น `members.code` ของสมาชิกนั้น
- สำหรับสมาชิกเก่าที่ยังไม่มี `referral_code` ใช้คำสั่ง:
  - `php artisan member:backfill-referral-codes` (dry-run)
  - `php artisan member:backfill-referral-codes --apply` (เขียนจริง)

## นโยบาย Frontend API v1 Member Contributor

- เพิ่ม endpoint `GET /api/v1/member/contributor` (ต้องใช้ token)
- response จะรวมข้อมูลสำคัญสำหรับหน้าแนะนำเพื่อน:
  - จำนวนสมาชิกที่แนะนำ (`referred_members`)
  - รหัสแนะนำของสมาชิก (`referral_code`)
  - รายได้จากการแนะนำในกระเป๋าสมาชิก (`referral_income` จาก `members.faststart`)
  - ยอดโบนัสแนะนำสะสมและจำนวนรายการจาก `payments_promotion` (`promotion_bonus_income`, `promotion_bonus_count`)
- response มีข้อมูลกติกาโปรโมชั่นแนะนำจาก `promotions.id = pro_faststart`:
  - `length_type`, `bonus_percent`, `bonus_price`, `display_value`
- response มีรายการผู้ถูกแนะนำใน field `referrals`:
  - `username`
  - `name`
  - `regis_date` (`Y-m-d`)
  - `first_deposit_amount`
  - `first_deposit_date` (`Y-m-d H:i:s`, nullable)

## นโยบาย Frontend API v1 Member History

- เพิ่ม endpoints สำหรับอ้างอิงหน้า wallet เดิม `/member/history`:
  - `GET /api/v1/member/history`
  - `GET /api/v1/member/history/{type}`
- รองรับ query filter:
  - `date_start`, `date_stop`
- รองรับ `type` ดังนี้:
  - `deposit`, `withdraw`, `transfer`, `spin`, `money`, `cashback`, `memberic`, `bonus`, `other`
- response รูปแบบ:
  - `type`, `date_start`, `date_stop`, `items`
- policy:
  - mapping ฟิลด์รายการต้องคง semantics เดียวกับ `Wallet HistoryController@store`

## นโยบาย Frontend API v1 Member Change Password

- เพิ่ม endpoint `POST /api/v1/member/change-password` (ต้องใช้ Bearer token)
- request รับเฉพาะ:
  - `password`
  - `password_confirmation`
- รองรับ alias `password_confirm` โดย normalize เป็น `password_confirmation` ก่อน validate
- ไม่ต้องส่งรหัสผ่านเดิม เพราะถือว่า endpoint นี้เรียกหลังผ่าน token auth แล้ว
- validation:
  - `password` ต้องยาว `6-10` ตัวอักษร
  - `password_confirmation` ต้องตรงกับ `password`
- implementation ปัจจุบัน update ทั้ง:
  - `members.password` (hash)
  - `members.user_pass` (legacy plain text)
- policy:
  - การเก็บ `user_pass` ยังจำเป็นในช่วงนี้เพื่อรักษา compatibility กับ flow เดิมที่ยังพึ่ง password แบบ legacy

## นโยบาย Frontend Lotto Critical Path API

- `GET /api/v1/lotto/draws`
  - คืน “งวดล่าสุดต่อรายการหวย” ที่ `status != draft`
  - ถ้า market มีทั้ง `draft` และ `open/closed/resulted` พร้อมกัน ให้ข้าม `draft` และเลือก non-draft ล่าสุดแทน
- `GET /api/v1/lotto/markets/latest`
  - ฟิลด์ `latest_draw` ของแต่ละ market ต้องเลือกตามลำดับความสำคัญ:
    - `open` ล่าสุด
    - ถ้าไม่มี `open` ค่อยใช้ non-draft ล่าสุด
  - ห้ามชี้ไปที่ `draft`
  - `latest_draw.status/status_label` สำหรับหน้าเลือกหวยใช้ mapping:
    - `open` -> `แทงหวย`
    - `closed` -> `รอผล`
    - `resulted` -> `ออกผล`
    - `no_result` -> `ยกเลิก`
    - `refunded` -> `ยกเลิก`
  - ถ้า draw มี `result_number.no_result=true` หรือ `result_number.status=no_result`
    - ต้อง map เป็น `no_result`
  - ถ้า draw มี `result_number.manual_cancelled_all_tickets=true`
    - ต้อง map เป็น `refunded`
- เพิ่ม public routes ชุด `/api/v1/lotto/markets/*` สำหรับหน้าแทงและผลย้อนหลังโดยตรง:
  - `GET /api/v1/lotto/markets/latest`
  - `GET /api/v1/lotto/markets/{marketId}/betting-context`
  - `GET /api/v1/lotto/markets/{marketId}/results`
  - `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- endpoint `GET /api/v1/lotto/markets/latest` ส่งรูประดับกลุ่มหวยเพิ่ม:
  - `group_logo`, `group_icon`, `group_image` (fallback logo -> icon)
- เพิ่ม route รวมผลรางวัลตามวันที่:
  - `GET /api/v1/lotto/results/by-date?draw_date=YYYY-MM-DD`
  - แสดงผลแบบ grouped ตาม `lotto_groups`
  - แสดงเฉพาะ market ที่มี draw ของวันที่เลือกและสถานะ `resulted`
- `betting-context` คืนข้อมูลรวมสำหรับหน้าแทงในเส้นเดียว: market/draw/blocked numbers/limits/exposure/version/server_time
- `results` รองรับ `limit` และ `page` เพื่อให้ frontend ทำ pagination ได้
- `POST /api/v1/lotto/bet` ผ่าน `FrontendApi` ไปยัง `Gametech\Lotto\Services\BetService`
  - container binding ของ `BetService` ต้อง inject 4 dependencies ตามลำดับ:
    - `ExposureService`
    - `LottoConfigResolver`
    - `LottoPackageResolver`
    - `WalletTransactionService`
  - ถ้า bind ผิดลำดับ route จะตอบ generic error `ไม่สามารถส่งโพยได้ในขณะนี้` จาก `FrontendApi` แม้ข้อมูล bet ถูกต้อง

## นโยบาย Lotto Group Package (Frontend + Betting)

- เพิ่มตารางระดับกลุ่มสำหรับ package:
  - `lotto_group_packages`
  - `lotto_group_package_bet_settings`
- เพิ่ม API สำหรับ package flow assist:
  - `GET /api/v1/lotto/groups/{groupId}/packages` (frontend contract)
  - `POST /api/v1/lotto/groups/{groupId}/select-package` (frontend contract)
  - `GET /api/v1/lotto/groups/{groupId}/selected-package` (frontend contract)
  - response ของ package endpoints มี field `image` สำหรับใช้งานหน้า frontend
  - `selected-package` คืน `bet_settings[]` ของ package ที่ถูกเลือกด้วย
    - แต่ละรายการมี `bet_type`, `payout`, `discount_percent`
    - ใช้เพื่อให้หน้าแทงคำนวณ preview ยอดลด/ยอดจ่ายจริงได้ก่อน submit
- policy ของ helper API:
  - ใช้เพื่อ UI flow assist เท่านั้น (non-authoritative)
  - ห้ามใช้แทน betting validation/auth gate
  - ตอน submit bet ต้องยึด `package_id` ใน request เท่านั้น
- `POST /api/v1/lotto/bet` บังคับรับ `package_id` ทุกครั้ง
- เพิ่ม Admin endpoints สำหรับจัดการ package ระดับ group:
  - `POST /lotto/group-packages/list|create|edit|update|delete`
  - `POST /lotto/group-package-bet-settings/list|create|edit|update|delete`
- หน้า Admin `/lotto/group-packages` ใช้ flow เดียวกับเมนู `rate-plans`:
  - เลือก `group` ก่อน
  - แสดงปุ่ม `เพิ่มแพกเกจ` เมื่ออยู่ใน group นั้น
  - ถ้า group ไม่มี package จะไม่แสดง panel รายละเอียดเพิ่มเติม
  - ถ้ามี package จะแสดง tab package และเมื่อเลือก tab จะแสดงตารางแบบ `rate-plans`:
    - แถว = รายการหวยใน group
    - คอลัมน์ = bet types
    - โหมดแสดงผลเลือกได้ `อัตราจ่าย | ส่วนลด(%) | ทั้งคู่`
    - ไม่มีคอลัมน์จัดการในตารางแสดงผล
  - modal `เพิ่มแพกเกจ` ต้องกรอก `อัตราจ่าย/ส่วนลด` ราย `bet_type` และบันทึก package + bet settings ใน transaction เดียว
  - มีปุ่ม `แก้ไขแพกเกจ` สำหรับแก้ชื่อ/คำอธิบาย/สถานะ พร้อม `อัตราจ่าย/ส่วนลด` ราย `bet_type` ใน modal เดียว
  - modal `เพิ่ม/แก้ไขแพกเกจ` รองรับอัปโหลดรูปภาพแพกเกจ (`image_file`) และบันทึก path ลง `lotto_group_packages.image`
  - modal `เพิ่ม/แก้ไขแพกเกจ` ไม่มีตัวเลือกเปิด-ปิดราย bet type และระบบตั้งค่า `is_enabled=true` ให้ทุกประเภทโดยอัตโนมัติ
  - endpoint `group-packages/update` รองรับ sync `bet_settings` ใน transaction เดียวกับ package update
- policy package deletion:
  - package ที่เคยถูกใช้ใน `lotto_ticket_items.package_id_at_time` ห้าม hard delete
  - ระบบจะ disable (`is_active=false`) แทน
- error mapping สำหรับ package flow:
  - `PACKAGE_REQUIRED` -> `400`
  - `PACKAGE_NOT_IN_GROUP` -> `400`
  - `PACKAGE_INACTIVE` -> `409`
  - `BET_TYPE_NOT_CONFIGURED` -> `422`
- snapshot package ที่ authoritative อยู่ที่ `lotto_ticket_items`:
  - `package_id_at_time`
  - `package_name_at_time`
  - `calculated_values_at_bet_time` (อย่างน้อยมี `bet_amount`, `discount_amount`, `net_amount`, `payout_amount`)

## นโยบายรายงานผลรางวัลทั้งหมด (Admin ทีมงาน)

- เพิ่มเมนูรายงานในหน้า `รายงาน Lotto`:
  - `ดูผลรางวัลทั้งหมด` (`admin.lotto.reports.results_by_date`)
- หน้า report รองรับ filter `วันที่งวด` (`draw_date`) และแสดงผลแบบ grouped:
  - ระดับกลุ่มหวย (`lotto_groups`)
  - ภายในกลุ่มแสดงรายการหวย (`lotto_markets`) ที่มีงวดตรงวันที่เลือกและสถานะ `resulted`
- ข้อมูลที่แสดงต่อรายการหวย:
  - `draw_date`, `result_at`, `first_prize`, `top_3`, `top_2`, `bottom_2`
- กรณีงวดมีผลแบบ `งดออกผล` (`result_number.no_result=true` หรือ `status=no_result`)
  - ตารางรายงานจะแสดงคำว่า `งดออกผล` ในคอลัมน์ผลรางวัลแทน `-`
- หน้า `/lotto/reports/results-by-date` ใช้ Vue (`x-template`) สำหรับค้นหาแบบ async
  - route หน้าแสดงผล (`GET /lotto/reports/results-by-date`) แยกจาก route โหลดข้อมูล (`GET /lotto/reports/results-by-date/loaddata`)
  - ตอนเปิดหน้า ถ้า URL ไม่มี `draw_date` ระบบจะตั้ง input เป็นวันที่ปัจจุบันของ browser และเรียก `loaddata` อัตโนมัติ
  - ถ้า URL มี `?draw_date=...` ที่ valid ระบบจะคงวันที่นั้นไว้และเรียก `loaddata` ของวันนั้นตอนเปิดหน้า
  - เมื่อเลือก `draw_date` ระบบจะเรียก `loaddata` และอัปเดตผลลัพธ์ในหน้าเดิมโดยไม่ reload ทั้งหน้า
  - pattern implementation มาตรฐานนี้ใช้เฉพาะเมนู admin แบบหน้าเดียวที่ render component และ fetch ข้อมูลในหน้าเดียว:
    - ใน Blade วาง custom tag ของหน้าไว้ตรง `@section('content')` โดยตรง เช่น `<result-app ref="resultApp"></result-app>`
    - วาง layout ทั้งหน้าไว้ใน `script type="text/x-template"`
    - register component ด้วย `Vue.component(...)` ภายใน `script type="module"` และให้ helper functions ที่ component ใช้ (`validate/filter/init`) อยู่ใน module scope เดียวกัน
    - bootstrap `new Vue({ el: '#app' ... })` แยกอีก script ตาม pattern เดียวกับ dashboard เพื่อให้ root Vue compile custom component หลัง register เสร็จ
    - event ที่ต้อง fetch ตามค่าจากฟอร์มทันที ให้ส่งค่าจริงจาก `$event.target.value` เข้า method โดยตรง แทนการพึ่ง state ที่อาจยัง sync ไม่ทัน
    - ไม่ใช้กับเมนูที่ขับด้วย DataTables (`window.LaravelDataTables`, `preXhr`, server-side table actions`) เพราะเมนูกลุ่มนั้นต้องใช้ pattern ของ DataTables แยกต่างหาก

## นโยบายยกเลิกโพยทั้งงวดและคืนเงิน (Admin Draws)

- เพิ่ม action ในเมนู `งวดหวย`:
  - `POST /lotto/draws/cancel-all-refund`
- เงื่อนไข:
  - ใช้ได้เฉพาะ draw สถานะ `resulted` ที่เป็น `งดออกผล` (`result_number.no_result=true` หรือ `result_number.status=no_result`)
- behavior:
  - ยกเลิก ticket สถานะ `active` ทั้งหมดของงวด
  - คืนเงินตาม `total_net_amount` (fallback `total_amount`) ให้สมาชิกแต่ละโพย
  - ปรับ exposure (`lotto_number_exposures.sold_amount`) ลงตามจำนวนที่ยกเลิก
  - mark draw เป็น `resulted` พร้อมผล `งดออกผล` (`result_number.no_result=true`)
  - หลังคืนเงินสำเร็จ ระบบบันทึก marker `result_number.manual_cancelled_all_tickets=true`
- policy การแสดงปุ่มในหน้า `งวดหวย`:
  - ปุ่ม `ยกเลิกโพย+คืนเงิน` แสดงเฉพาะสถานะ `resulted+งดออกผล`
  - ถ้าเคยคืนเงินทั้งงวดแล้ว (`manual_cancelled_all_tickets=true`) ปุ่มต้องไม่แสดงซ้ำ

## นโยบาย Deprecate Payout Override ระดับ Market

- endpoint จัดการ `lotto_market_bet_settings` (`default-settings`) ไม่อนุญาตให้แก้ `payout/discount_percent` อีกต่อไป
- ถ้าพบ field ดังกล่าวใน request จะ reject ด้วยข้อความ `DEPRECATED_PAYOUT_OVERRIDE`
- คงไว้เฉพาะการตั้งค่า limits (`min_bet`, `max_bet`, `max_per_number`) และ toggle `is_enabled`

## นโยบาย Internal Lotto Result Sources API

- เพิ่ม internal endpoints สำหรับรวม source จาก mini projects เดิม:
  - `GET /internal/lottery/results/exphuay/{type}?date=&page=`
  - `GET /internal/lottery/results/dowjones-midnight?date=`
  - `GET /internal/lottery/results/dowjones-extra?date=`
- date input รองรับ 3 format:
  - `Y-m-d`
  - `d/m/Y`
  - `d-m-Y`
- เมื่อไม่ส่ง `date` ระบบจะไม่บังคับใส่ query `date` ไป upstream (คงโหมด latest)
- output `draw_date` ถูก normalize เป็น `Y-m-d` เสมอ
- สำหรับ exphuay:
  - upstream อาจคืน payload หลายงวดแม้ส่ง `date`
  - ระบบหลักต้อง select record จาก payload เองตาม local draw date (`Asia/Bangkok`) ที่ derive จาก `lottosDate`
  - เมื่อ match record แล้วจะ derive:
    - `first_prize` จาก `lottosNumber`
    - `top_3` จาก 3 หลักท้ายของ `lottosNumber`
    - `top_2` จาก 2 หลักท้ายของ `lottosNumber`
    - `bottom_2` จาก `lottosUnder`
- canonical response บังคับ key คงที่:
  - `success`, `source`, `type`, `draw_date`, `raw_result`, `normalized_result`, `meta`, `errors`
  - `normalized_result` คง key: `first_prize`, `top_3`, `top_2`, `bottom_2`, `digit_4`, `digit_5` (ไม่มีค่าใช้ `null`)
  - `errors` เป็น array เสมอ
- policy field เสริม Dowjones:
  - `start_spin`, `show_result`, `now`, `update` ถูกเก็บใน `meta.dowjones_supplemental`
  - ห้าม map field เสริมเหล่านี้เข้า `normalized_result`
  - เมื่อ `dowjones-midnight` upstream ให้มาเพียง `digit5`:
    - `top_3` = 3 หลักท้ายของ `digit5`
    - `top_2` = 2 หลักท้ายของ `digit5`
    - `bottom_2` = 2 หลักหน้าของ `digit5`
  - `dowjones-extra`:
    - ถ้าขอ “วันปัจจุบัน” ให้ใช้ `https://api.dowjonesextra.com/result` โดยไม่ส่ง `date`
    - ถ้าขอย้อนหลัง ให้ใช้ `https://api.dowjonesextra.com/history` แล้ว select `lotto_date` ให้ตรงวันที่ขอ
    - เมื่อได้ `digit5` แล้ว:
      - `top_3` = 3 หลักท้ายของ `digit5`
      - `top_2` = 2 หลักท้ายของ `digit5`
      - `bottom_2` = 2 หลักหน้าของ `digit5`
- security policy:
  - route ชุดนี้ bind domain เฉพาะ API host เท่านั้น:
    - `APP_API_URL + APP_API_DOMAIN_URL` (ถ้าตั้ง `APP_API_DOMAIN_URL`)
    - fallback เป็น `APP_API_URL + APP_ADMIN_DOMAIN_URL` (กรณีไม่ได้ตั้ง `APP_API_DOMAIN_URL`)
  - canonical URL สำหรับ internal result endpoints ต้องเรียกผ่าน `api.*` เท่านั้น (ไม่เปิดผ่าน `admin.*`)
  - route ชุดนี้ใช้ middleware `lotto.internal_results`
- นโยบาย generated config จากคำสั่ง `lotto:insert-internal-result-source-mappings`:
  - JSON หลักที่สร้างใหม่ใช้ baseline แบบ internal endpoint:
    - `request_headers_json = []`
    - `fetch_config_json.headers = []`
  - ห้ามฝัง cookie ของ upstream ไว้ใน JSON หลักที่บันทึกลงฐานข้อมูล
  - สำหรับทุก market ที่ map เป็น `exphuay:{type}`:
    - generator จะชี้ endpoint เป็น `http://203.146.127.170/~anan/get_lottery.php`
    - query template เป็น `type={type}`, `date={{lookup_date}}`, `page=1`
    - parser ใช้ JSON_PATH จาก `$.date`, `$.results[0].lottosNumber`, `$.results[0].lottosUnder`
    - บังคับค่า row ที่สร้างใหม่เป็น `priority=1` และ `is_active=true`
- นโยบาย exphuay upstream headers/cookie:
  - ให้ส่งที่ `ExphuayResultDriver` ตอนเรียก upstream โดยตรง (ไม่ส่งผ่าน source JSON หลัก)
  - header baseline: `Accept`, `Accept-Language`, `Referer`, `User-Agent`, `x-sveltekit-invalidated`
  - cookie อ่านจาก env `LOTTO_EXPHUAY_COOKIE` (ถ้าไม่ตั้งจะไม่แนบ `Cookie`)
  - user-agent override ได้ผ่าน `LOTTO_EXPHUAY_USER_AGENT`
  - เมื่อ HTTP fetch เจอ Cloudflare challenge (`403` หรือ body แนว `Just a moment/cf-mitigated`) ให้ fallback แบบเป็นลำดับ:
    - ลอง Python worker (`curl_cffi`) ก่อน
    - ถ้าไม่สำเร็จค่อย fallback ไป browser runtime แบบ sync ใน driver
  - env สำหรับ Python worker:
    - `LOTTO_EXPHUAY_PYTHON_WORKER_ENABLED` (default = false)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_BINARY` (default = `python3`)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_SCRIPT` (default = `scripts/lotto/exphuay_curl_cffi_worker.py`)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_TIMEOUT_SECONDS` (default = 20)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_IMPERSONATE` (default = `chrome124`)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_WARMUP` (default = true)
    - `LOTTO_EXPHUAY_PYTHON_WORKER_WARMUP_URL` (default = `https://exphuay.com/`)
  - toggle ผ่าน `LOTTO_EXPHUAY_BROWSER_FALLBACK` (default = true)
  - timeout ของ fallback ผ่าน `LOTTO_EXPHUAY_BROWSER_FALLBACK_TIMEOUT_SECONDS` (default = 60)
  - browser goto/wait controls ผ่าน:
    - `LOTTO_EXPHUAY_BROWSER_WAIT_UNTIL` (default = `domcontentloaded`)
      - `LOTTO_EXPHUAY_BROWSER_TIMEOUT_MS` (default = `60000`)
  - ถ้าตั้งค่า `LOTTO_INTERNAL_RESULT_SHARED_KEY` ระบบบังคับตรวจ header (`LOTTO_INTERNAL_RESULT_SHARED_HEADER`, default `X-Lotto-Internal-Key`)
  - ถ้าไม่ตั้ง shared key จะ allow เพื่อรองรับช่วง transition ภายในระบบ
  - source config ที่ชี้ internal endpoints (`/internal/lottery/results/*`) จะไม่ถูกบล็อกด้วย fixture gate ใน local/testing ตอน save/validate cutover
- migration/backfill policy:
  - ใช้ command `lotto:migrate-internal-result-endpoints` เพื่อ map endpoint เดิม -> internal endpoints
  - ใช้ command `lotto:migrate-exphuay-sources-to-get-lottery` เพื่อ migrate แถว exphuay ที่มีอยู่แล้วให้ชี้ `http://203.146.127.170/~anan/get_lottery.php`
    - resolve `type` จาก endpoint เดิม (`/internal/lottery/results/exphuay/{type}` หรือ `exphuay.com/backward/{type}/__data.json`) หรือจาก query template/config
    - rewrite parser/query ให้ตรง schema ของ `get_lottery.php`
    - default จะตั้ง `priority=1` และ `is_active=1` (override ได้ผ่าน option)
  - command จะ generate report ทุกครั้งที่รันสำหรับ traceability
  - มี command bootstrap สำหรับ market ที่ยังไม่มี source row:
    - `lotto:bootstrap-missing-result-sources`
    - policy เริ่มต้นจะสร้างเป็น `is_active=false` (safe placeholder) เพื่อไม่กระทบ runtime ทันที
  - มี command insert-only สำหรับ canonical mapping โดยไม่ทับแถวเดิม:
    - `lotto:insert-internal-result-source-mappings`
    - policy: insert เฉพาะ endpoint canonical ที่ยังไม่มีใน market นั้น (skip ถ้ามี endpoint เดิมอยู่แล้ว)
  - V2 fetch runtime ต้อง render placeholders ใน `endpoint_url`, `query`, `headers`, `body` ก่อนยิง request
    - ถ้าไม่มี `lookup_date` ใน runtime context ให้ fallback ใช้ `expected_draw_date`

## นโยบาย UI รายการหวย (Admin `/lotto/markets`)

- เพิ่มปุ่ม `Auto` ต่อแถว (หลังปุ่ม `แก้ไข`) เพื่อเปิด modal จัดการ `Auto Result Sources` ของตลาดนั้นแบบ inline
- ปุ่ม `Auto` ถูกดักสิทธิ์ด้วย ACL `lotto_settings.auto_result_sources`
- ฟอร์มตลาดรองรับตัวเลือกเพิ่ม:
  - `ออกผลแล้วคำนวณยอดได้เสียอัตโนมัติทันที` (`auto_settle_on_result`)
  - `ส่งแจ้งเตือน Telegram เมื่อหวยนี้ออกผล` (`notify_result_telegram`)
- ปุ่ม `Auto` ของหน้า `lotto/markets` เปิด modal ในหน้าเดิมแบบ native (ไม่ใช้ iframe) และดึงข้อมูลผ่าน API:
  - `GET /lotto/auto-result-sources/list?market_id={id}`
  - `POST /lotto/auto-result-sources/loaddata|create|update|edit`
- ตาราง `รายการหวย` เพิ่มคอลัมน์สถานะผูก source (`ผูกแล้ว` / `ยังไม่ผูก`) ถัดจากคอลัมน์ `ลิงก์ออกผล`
- เพิ่มคอลัมน์ `ออกผล` (หลัง `ลิงก์ออกผล`) เป็นปุ่มสลับ `Auto/Manual` สำหรับ `auto_settle_on_result`
- คอลัมน์สถานะผูก source ปรับ UI เป็นไอคอนไฟ:
  - ผูกแล้ว = ไฟเขียวมี effect + แสดงจำนวน
  - ยังไม่ผูก = ไฟสีเทา
- ใน modal แก้ไข source มีโหมดทดสอบตามวันที่:
  - เลือก `draw_date` แล้วกด dry-run ได้แม้ไม่มีงวดจริงของวันนั้น (ระบบสร้าง virtual draw context ให้ทดสอบ)
  - Browser test dispatch รองรับโหมดเดียวกัน (ใช้ virtual draw context เมื่อไม่พบงวดจริง)
  - ปุ่ม `Dry Run ตามวันที่` รองรับ async orchestration ใน popup:
    - ผู้ใช้กด dry-run ครั้งเดียวได้
    - ถ้ารอบแรกได้ `FETCH_DEFERRED` และมี `receipt_key` ระบบ frontend จะ polling `browser_test_status` อัตโนมัติใน popup เดิม
    - เมื่อ worker จบแล้ว ระบบจะยิง dry-run ซ้ำอัตโนมัติเพื่อเดิน parse/select/map/validate ต่อให้ครบ
  - ดู fetch logs ของวันทดสอบได้ใน modal เดียวกัน
  - dry-run by date จะ persist log แบบเต็มคล้าย production run และใช้ `run_id` เป็นหลัก (`draw_id=null`)
  - ปุ่ม `ดู` ในรายการ logs-by-date แสดงเฉพาะข้อมูล `trace_json` ใน modal รายละเอียด
- คอลัมน์ `สถานะ` ในตาราง `lotto/markets` แสดงเป็นปุ่ม icon-only (`check/times`) และกดสลับ `is_enabled` ได้โดยตรง (มี confirm)
- คอลัมน์ `จัดการ` ของตาราง `lotto/markets` คงปุ่ม `แก้ไข` และ `Auto`
- ปุ่ม `ลบ` ของ `Auto Result Source` แสดงเฉพาะใน modal รายการ source ของ market นั้น และเรียก endpoint `POST /lotto/auto-result-sources/delete`
- ตารางใน modal `Auto Result Sources`:
  - คอลัมน์ `สถานะ` ถูกวางไว้ก่อน `จัดการ` และแสดงเป็นปุ่ม icon-only สำหรับกดสลับสถานะ
  - คอลัมน์ `จัดการ` แสดงเฉพาะปุ่ม `แก้ไข/ลบ` แบบมาตรฐาน (`btn-info`/`btn-danger`) พร้อม icon+ข้อความ
  - หัว modal แสดงชื่อรายการหวยร่วมกับ `market id`
- ฟอร์มแก้ไข source ใน popup `/lotto/markets`:
  - label `ตลาด` ถูกปรับเป็น `รายการหวย` เพื่อให้ตรงชื่อเมนูจริง
  - แท็บ `ตั้งค่าด่วน` รองรับฟิลด์ที่มีผลจริงต่อ `Pipeline Config JSON` ได้แก่ `fetch_strategy`, `parser_type`, `selection_stage`, `lookup_date_mode/offset`, `runtime capability`
  - ฟิลด์ `เก็บท้ายกี่หลัก (รางวัลที่ 1)` รองรับค่า `0` = ไม่ใส่ `right` transform (ไม่ตัดท้าย)
  - ส่วน `Browser Worker Settings` ใน quick setup เป็น auto-generated (ไม่เปิดให้ปรับค่าละเอียดใน UI)
  - เมื่อผู้ใช้แก้ `JSON หลัก` แล้วบันทึก ระบบยึด `JSON หลัก` เป็น source of truth โดยตรง และไม่ให้ quick setup ทับค่า JSON อัตโนมัติระหว่าง save/edit
  - กรณีมี `selection_stage` อยู่ top-level ของ `JSON หลัก` แต่ยังไม่มีใน `selection_config_json` ระบบจะ normalize ใส่ใน `selection_config_json.selection_stage` อัตโนมัติ
  - มี dependency-reset อัตโนมัติสำหรับค่าที่ไม่เกี่ยวข้อง เช่น:
    - โหมดวันงวดที่ไม่ใช้ offset จะบังคับ `lookup_date_offset_days=0`
    - `fetch_capability=http_only` จะปิด `allow_dom_fallback` และถ้าไม่ได้ใช้ browser strategy จะปิด `requires_browser`
    - `fetch_strategy=RENDERED_BROWSER` จะบังคับเปิด browser capability ที่สัมพันธ์
  - แท็บ `JSON หลัก` ระบุชุด key บังคับที่ต้องมีใน config ก้อนหลัก
  - เอาปุ่ม preset `ตั้งค่าอัตโนมัติ` ออกจากแท็บ `ตั้งค่าด่วน`

## นโยบาย Auto Result Parser/Selector v2

- parser v2 เป็น `context-aware` และแยกบทบาทชัดเจน:
  - `parser` = extract candidate + raw fields เท่านั้น
  - `selector` = เลือกหรือ reject candidate
  - `mapper` = normalize/transform chain
  - `validator` = validate canonical output + expected context
- strategy default สำหรับ source v2 คือ `strict_single_match` (โดยเฉพาะ HTML/text)
- reject แบบ strict เมื่อ:
  - ไม่มี candidate ตรง `expected_draw_date`
  - มีหลาย candidate ตรง `expected_draw_date`
  - candidate ที่ถูกเลือกไม่ผ่าน required fields
  - candidate ที่ถูกเลือกไม่ตรง `expected_draw_date`
  - tie score ในกรณี strategy ที่อิง score
- transform chain อยู่ใน `mapping_config_json` เป็นหลัก (`trim`, `digits_only`, `left:n`, `right:n`, `{op:"date"...}`)
- debug runtime เก็บใน `lotto_result_fetch_logs.selection_debug_json` เท่านั้น (ไม่เก็บใน master state)

## นโยบาย Lotto Result Pipeline v2 (Config-Driven)

- รองรับ pipeline เวอร์ชันต่อ source ผ่าน `pipeline_version`:
  - `LEGACY`
  - `V2_SHADOW`
  - `V2_CUTOVER`
- รองรับ `fetch_strategy` แบบ fixed set:
  - `JSON_HTTP`, `HTML_HTTP`, `RENDERED_BROWSER`, `EMBEDDED_JSON`, `MANUAL_INPUT`
- รองรับ placeholder ใน `fetch_config_json.endpoint_url` สำหรับ runtime context:
  - `{{expected_draw_date}}` หรือ `{expected_draw_date}`
  - ใช้ค่า `expected_draw_date` จาก runtime context ของรอบทดสอบ/รันจริงก่อนยิง fetch
- รองรับ `selection_stage` แบบ fixed set:
  - `PRE_MAPPING`, `POST_MAPPING`
- มี shadow compare status แบบ fixed set:
  - `MATCH`, `MISMATCH`, `ERROR`, `SKIPPED`
- เพิ่ม config storage ฝั่ง source:
  - `fetch_config_json`, `selection_config_json`, `readiness_config_json`
  - flags: `supports_partial`, `requires_browser`, `shadow_enabled`, `cutover_enabled`
- เพิ่ม structured trace/error ใน fetch logs:
  - `trace_json`, `error_code`, `error_stage`
  - `legacy_result_json`, `v2_result_json`, `shadow_diff_json`, `shadow_compare_status`
- เพิ่ม revision history สำหรับ source config:
  - ตาราง `lotto_result_source_revisions` เก็บ `changed_by`, `reason`, `config_hash` และ snapshot ต่อ revision
- เพิ่ม admin actions ใหม่ในเมนู `/lotto/auto-result-sources`:
  - preview config, validate config, validate cutover
- ฟอร์ม admin ของ `auto-result-sources` ใช้โหมด V2-only:
  - มีแท็บ `Quick Setup` สำหรับผู้ใช้ทั่วไป กรอก URL + path หลัก แล้วระบบ generate pipeline JSON อัตโนมัติ
  - ฟิลด์ที่แก้ได้จริงถูกรวมไว้ที่แท็บ `Quick Setup` (เช่น market/priority/timeout/lookup/offset/effective)
  - แท็บ `ทั่วไป` ถูกปรับเป็นมุมมองสรุปแบบ read-only
  - มีช่องหลัก `Pipeline Config JSON (Single Source of Truth)` สำหรับตั้งค่ารวม
  - ตัดแท็บ `Pipeline` ออกจากหน้าแก้ไข เพื่อไม่ให้สับสนกับแท็บตั้งค่าหลัก
  - ปรับ label/tab/action ใน modal เป็นภาษาไทยและจัด layout 2 คอลัมน์ในโหมดตั้งค่าด่วนเพื่อให้อ่านง่ายขึ้น
  - ระบบ sync/แตกค่าไป field ย่อยอัตโนมัติก่อน preview/validate/save
  - ซ่อนช่อง JSON ย่อยจากหน้า form หลักเพื่อลดการกรอกซ้ำและลดความสับสนของผู้ใช้
  - derive ค่า legacy-required fields (`endpoint_url`, `http_method`, `parser_type`, `fetch_strategy`, `selection_stage`) จาก JSON config อัตโนมัติก่อน preview/validate/save
  - ลดความสับสนจากการกรอกค่า legacy และ v2 ซ้ำซ้อน
- runtime `AutoResultPipelineService` ใช้ V2 cutover path เท่านั้น (latest-only) และไม่วิ่ง legacy/shadow path แล้ว
- policy ของ `validate cutover`:
  - `local/testing`: บังคับ fixture gate ต่อ source (สำหรับ regression test)
  - `production`: ใช้ live validation จาก `endpoint_url` จริงผ่าน pipeline runner (ไม่บังคับให้ผู้ใช้ admin สร้างไฟล์ fixture)
  - strict date-check ตาม `expected_draw_date` (ไม่ fallback แบบข้ามเงื่อนไขวันที่)
- `RenderedBrowserFetchDriver` ถูกออกแบบเป็น async worker/runtime path เท่านั้น:
  - main fetch path ห้าม block รอ browser execution แบบ synchronous

## นโยบาย Browser Worker (Auto Result JS-delayed Sources)

- ใช้ deterministic `receipt_key` จาก normalized config + stable context (`source_id`, `draw_id`, `endpoint_url`, `strategy`, `parser_type`, `expected_draw_date`)
- ตัด volatile runtime fields ออกจาก receipt hashing เพื่อให้ input เดิมได้ key เดิมเสมอ
- dispatch gate ใช้ atomic lock (`SETNX + TTL`) ต่อ `receipt_key` เพื่อกัน dispatch ซ้ำ
- cache payload มาตรฐาน key `lotto:auto-result:browser-fetch:{receipt_key}`:
  - `status`: `success|failed|app_shell_only`
  - `response_body`
  - `selected_endpoint`
  - `error_code`
  - `meta` (`duration_ms`, `content_type`, `captured_count`, etc.)
- fetch selection priority แบบ strict:
  1. captured endpoint JSON (valid)
  2. rendered HTML
  3. `APP_SHELL_ONLY`
- retry policy:
  - retry เฉพาะ deferred/network-class failures
  - `APP_SHELL_ONLY` = terminal reject (no retry)
- หน้า admin auto source มี Browser Worker config fields และ async test API:
  - `wait_for_selector`, `wait_until`, `timeout_ms`, `capture_url_patterns`, `block_resource_types`
  - serialize/deserialize ผ่าน `fetch_config_json.meta.browser_worker`

## นโยบาย Browser Runtime Phase 2 (Locked)

- Runtime baseline:
  - ใช้ `Playwright Node Worker` เป็น browser executor หลัก
  - transport เฟสแรก: PHP queue job เรียก local Node process (JSON in/out + exit/stderr summary)
- Capability policy ต่อ source:
  - `http_only`
  - `prefer_browser_runtime`
  - `require_browser_runtime`
- Fallback policy (`prefer_browser_runtime`) แบบ allowlist เท่านั้น:
  - `BROWSER_RUNTIME_UNAVAILABLE`
  - `BROWSER_LAUNCH_FAILED`
  - `BROWSER_EXECUTOR_TIMEOUT`
  - `BROWSER_EXECUTOR_IO_ERROR`
- Non-fallback policy:
  - `NO_NETWORK_MATCH` (เมื่อ source declare network path เป็นหลัก)
  - `DOM_SELECTOR_NOT_FOUND` (เมื่อ source declare browser path เป็นหลัก)
  - invalid capture/wait/predicate config
- Schema governance:
  - PHP เป็น owner ของ runtime output schema
  - Node worker ต้อง emit ตาม version ที่ PHP กำหนด (ห้าม ad hoc shape)
- Selection determinism:
  - `selection_mode=best` ใช้ deterministic tie-break
  - tie ไม่แตกต้อง reject ด้วย `CAPTURE_AMBIGUOUS_MATCH`
- DOM fallback:
  - ใช้ได้เฉพาะ source ที่ `allow_dom_fallback=true`
  - trace ต้องระบุ `payload_origin` ชัด
- Admin/test fetch ของ browser runtime:
  - async only (dispatch + polling status)
  - ห้าม sync browser execution ใน request lifecycle
- Artifact governance:
  - deterministic storage path + filename convention
  - redaction policy สำหรับ secret/cookie/auth/token
  - truncation/retention/size cap ต่อ run
  - มี command cleanup ตาม retention policy: `lotto:cleanup-browser-runtime-artifacts` (รองรับ `--days`, `--dry-run`)
  - scheduler รัน cleanup รายวันเวลา `03:55` (non hot-path, `withoutOverlapping`)
- Rollout:
  - source เดิม default = `http_only`
  - browser runtime เปิดใช้แบบ opt-in + whitelist
  - มี global feature flag ปิดระบบ browser runtime ได้ทั้งระบบ
- Resource budget:
  - global/per-source/per-domain concurrency caps
  - overall timeout cap
  - artifact write cap ต่อ run
- Source capability/rollout config (ใช้งานจริงผ่าน `fetch_config_json.meta.runtime`):
  - `fetch_capability`: `http_only|prefer_browser_runtime|require_browser_runtime`
  - `allow_dom_fallback`: เปิด/ปิด DOM fallback ราย source
  - รองรับ `http_fallback_strategy` สำหรับ route แบบ `http_only`
- Admin Auto Source form รองรับตั้งค่า:
  - capability policy
  - DOM fallback
  - browser `selection_mode` (`first|best|all`)
- Browser test status/dispatch payload แสดง debug เพิ่ม:
  - `selected_driver`
  - `phase_timing`
  - `payload_origin`
  - `selected_capture`
  - `artifact_refs`
- มี CI guardrail สำหรับ AutoResultV2:
  - GitHub Actions workflow `lotto-autoresultv2-unit` รัน `tests/Unit/Lotto/AutoResultV2`
  - อัปโหลด artifact (`autoresultv2-unit.log`, `junit-autoresultv2.xml`) ทุกครั้ง
- มี incident runbook สำหรับ on-call:
  - `docs/internal/03_DOMAINS/lotto-browser-runtime-incident-runbook.md`
  - ครอบคลุม reason code triage + rollback decision tree + evidence checklist

## นโยบาย Internal Result Sources Integration (Contract Freeze)

- policy รายละเอียดของโดเมนนี้ถูกแยกเป็น source-of-truth ที่:
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-contract-freeze.md`
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-compatibility-matrix.md`
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-dowjones-extra-fields-policy.md`
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-migration-backfill.md`
  - `docs/internal/03_DOMAINS/lotto-internal-result-sources-rollout-deprecation.md`

## โครงสร้างเอกสาร

- Internal docs: `docs/internal/*`
- Public docs: `docs/public/*`
- Archive: `docs/internal/05_ARCHIVE/*`

## เอกสารระบบหลักที่ใช้งานจริง

- กฎ agent: `docs/internal/00_RULES/agent_rules.md`
- บันทึกการตัดสินใจ: `docs/internal/02_DECISIONS/decision_log.md`
- แผนงาน: `docs/04_PLANS/`
- Domain docs: `docs/internal/03_DOMAINS/`

## เอกสาร system เสริม

- `docs/internal/01_SYSTEM/dev-workflow-phpstorm-wsl.md`
- `docs/internal/01_SYSTEM/lotto-system-handover-th.md`
- `docs/internal/01_SYSTEM/laravel-echo-nextjs-install.md`
