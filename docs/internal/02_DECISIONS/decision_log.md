# Decision Log

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

## 2026-03-29 — Settlement Normalization Accepts 3-digit First Prize for Auto Result (APPROVED)

- ปรับ `SettlementService::normalizeResultNumber` ให้รองรับ `first_prize` ความยาว `3|5|6` หลัก (เดิมรับเฉพาะ `5|6`)
- สำหรับเคส `first_prize=3 หลัก` + `last_2_digits=2 หลัก`:
  - ยอมรับผลและ normalize ต่อได้
  - derive `top_3/top_2/bottom_2` ตามเดิมเพื่อไม่กระทบการคำนวณรางวัลของ bet types เดิม
- เหตุผล: ป้องกันกรณี dry-run ผ่านแต่ apply จริงล้มที่ settlement สำหรับตลาดหุ้น/VIP ที่ใช้ผล 3 ตัวบน

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
