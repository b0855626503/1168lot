แผนงานปรับระบบงวดหวยตามระบบจริง
เป้าหมาย

ปรับ flow งวดหวยให้แน่นขึ้น โดย:

ไม่เปลี่ยน status หลักเดิม
ไม่เปลี่ยน route/action เดิมโดยไม่จำเป็น
ยึด UI และ service เดิมเป็นฐาน
เพิ่ม guard ฝั่ง backend ให้แน่น
จำกัด field ที่แก้ได้ตามสถานะ
รองรับกรณี “เปิดรับก่อนเวลา” แบบ override ที่ audit ได้
1) ขอบเขตที่ต้องแก้
   ไฟล์หลักที่ต้องอิง
   src/Services/DrawService.php
   src/Http/Controllers/Admin/LottoDrawController.php
   src/Resources/views/admin/module/lotto/draws/datatables_actions.blade.php
   src/Resources/views/admin/module/lotto/draws/addedit.blade.php
   src/Models/LottoDraw.php
   migration ของ lotto_draws
   ถ้ามี policy/acl ที่เกี่ยวข้อง ให้เพิ่มตามโครง ACL เดิมของ package นี้
   สิ่งที่ห้ามทำ
   ห้ามเปลี่ยน status หลักจาก draft/open/closed/resulted
   ห้ามเปลี่ยน route/action หลักเดิม ถ้าไม่จำเป็น
   ห้ามรื้อฟอร์มประกาศผลให้กลับไปใช้ top_3/bottom_2
   ห้ามให้ UI ตัดสิน rule เองโดยไม่มี backend guard
2) Decision lock ที่ต้องยึด
   2.1 สถานะหลัก

ใช้สถานะเดิมเท่านั้น:

draft
open
closed
resulted

ไม่ต้องเพิ่ม settling/settled ตอนนี้ เพราะของจริงยัง settle จบใน action เดียว

2.2 ผลรางวัล

คงแนวปัจจุบัน:

input = first_prize
input = last_2_digits

ให้ derive ใน SettlementService เหมือนเดิม:

top_3
top_2
bottom_2
2.3 การเปิดรับก่อนเวลา

ให้รองรับได้แบบ override
แต่:

ห้าม overwrite open_at
ต้องเก็บเวลาเปิดจริงแยก
ต้องแยกว่าเปิดตาม schedule หรือเปิดมือ
2.4 การแก้ไขงวด
draft แก้ได้เต็ม
open แก้ได้จำกัด
closed แก้ได้เฉพาะ metadata/remark หรือ correction ที่ไม่กระทบการเดิมพัน
resulted ห้ามแก้ไขงวด
3) Database changes
   เพิ่ม field ใน lotto_draws

ให้ agent เพิ่ม migration ใหม่แบบ additive เท่านั้น

field ที่ต้องเพิ่ม
opened_at nullable datetime
closed_at nullable datetime
open_mode nullable string/enum
ค่าใช้ scheduled
ค่าใช้ manual

ถ้าจะเพิ่มให้ครบฝั่งปิดด้วยก็ทำได้:

close_mode nullable string/enum
scheduled
manual

แต่ถ้าจะคุม scope ให้เล็กก่อน:

อย่างน้อยต้องมี opened_at
อย่างน้อยต้องมี open_mode
ข้อกำหนด
ห้ามแก้ความหมายของ open_at เดิม
open_at ยังเป็น “เวลาเปิดตามแผน”
opened_at เป็น “เวลาเปิดจริง”
result_at เดิมให้เก็บเป็น “เวลาประกาศผลจริง” ต่อไป
4) ปรับ model LottoDraw

ให้ agent:

เพิ่ม casts ของ field ใหม่เป็น datetime/array ให้ครบ
ถ้า model มี fillable/guarded ให้รองรับ field ใหม่
ไม่เปลี่ยน accessor/relationship เดิม

อย่างน้อยต้อง cast:

draw_date
open_at
close_at
opened_at
closed_at
result_at
result_number
5) ปรับ DrawService ให้เป็น source of truth

ตอนนี้ DrawService มี:

syncScheduledStatuses()
createDraft()
openDraw()
closeDraw()
snapshotBetSettings()
assertCanOpen()

ให้ agent ปรับตรงนี้ ไม่กระจาย logic ไป controller

5.1 ปรับ openDraw()

แยก logic เป็น 2 แบบใน method เดิมหรือ method ใหม่ภายใน service:

open ปกติ
force open
behavior ที่ต้องได้
กรณี open ตามเวลา

ถ้า now >= open_at

อนุญาต
set status = open
set opened_at = now
set open_mode = scheduled ถ้ายังไม่มีค่า
กรณี open ก่อนเวลา

ถ้า now < open_at

ต้องถือเป็น override
set status = open
set opened_at = now
set open_mode = manual
ข้อห้าม
ห้ามแก้ open_at
ห้ามเปิดถ้า status = resulted
เพิ่ม guard

assertCanOpen() ต้องเช็คเพิ่ม:

อนุญาตเฉพาะ draft กับ closed
ถ้า resulted ให้ reject ชัดเจน
ถ้าไม่มี open_at ให้ reject หรือ handle ให้ชัด
5.2 ปรับ closeDraw()

ตอนนี้ปิดได้จาก open เท่านั้น ถือว่าถูกแล้ว

ให้เพิ่ม:

set closed_at = now
ถ้าใช้ close_mode ให้ set ตาม source
cron = scheduled
manual button = manual
5.3 ปรับ syncScheduledStatuses()

ตอนนี้ดีอยู่แล้ว แต่ต้องให้การเปิด/ปิดจาก cron เขียน field ใหม่ด้วยผ่าน service เดิม

สำคัญ:

ห้าม update status ตรงๆ ข้าม service
cron ต้องเรียก openDraw() / closeDraw() เท่านั้น
6) ปรับ Controller ให้ backend guard แน่น

ไฟล์: src/Http/Controllers/Admin/LottoDrawController.php

6.1 create()

คง flow เดิมได้
แต่ต้องชัดว่า:

default status = draft
ถ้าจะ allow create แล้วเปิดทันที ให้ใช้ transition ผ่าน service เท่านั้น
6.2 update()

อันนี้ต้องแก้หนักสุด เพราะตอนนี้ update field กว้างเกิน

ให้ agent แยก validation/allow-update ตาม status ปัจจุบันของ draw

rule ใหม่
ถ้า status = draft

แก้ได้:

market_id
draw_date
open_at
close_at
result_at ถ้าระบบใช้เป็น schedule ผล
remark/metadata ถ้ามี
ถ้า status = open

แก้ได้จำกัด:

close_at เฉพาะกรณี extend/correction
metadata ที่ไม่กระทบโพย
ห้ามแก้ market_id
ห้ามแก้ draw_date ถ้ากระทบความหมายงวด
ห้ามแก้โครง rule ที่มีผลกับ ticket ที่ซื้อไปแล้ว
ถ้า status = closed

แก้ได้เฉพาะ:

metadata / remark / correction บางจุด
ไม่ควรแก้ market_id
ไม่ควรแก้ draw_date
ไม่ควรแก้เวลาเปิด/ปิดแบบสุ่ม ถ้าจะแก้เพื่อ correction ต้องจำกัดสิทธิ์
ถ้า status = resulted
reject ทันที
message ชัดว่า “งวดที่ประกาศผลแล้วไม่อนุญาตให้แก้ไข”
สิ่งที่ต้องทำ
อย่าใช้ validator ชุดเดียวครอบทุกสถานะ
ให้ build validation rules ตาม status
ให้ build $updatePayload ตาม field ที่อนุญาตจริง
6.3 open()

ตอนนี้เรียก DrawService->openDraw($draw) ตรงๆ

ให้ agent เพิ่ม:

ถ้าจะมี permission แยก force open ให้ controller ส่ง context หรือ flag เข้า service
ถ้าไม่ทำ ACL ตอนนี้ อย่างน้อยให้ service ตัดสินจากเวลาแล้วตอบ error ชัด หรือเปิดเป็น manual ตาม rule ที่ตกลง

คำแนะนำ:

อย่าเพิ่ม route ใหม่ถ้ายังไม่จำเป็น
ใช้ route เดิม /open
แต่ service ต้องบอกได้ว่าเปิดแบบ scheduled หรือ manual
6.4 close()

คง route เดิม
เพิ่มแค่ให้ service เขียน closed_at

6.5 settle()

ของเดิมถูกแล้ว:

ออกผลได้เฉพาะ closed
validate first_prize = 5-6 หลัก
validate last_2_digits = 2 หลัก

ให้คงไว้
แต่เพิ่ม guard เล็กน้อย:

ถ้า resulted แล้ว ห้ามซ้ำ
ถ้ามี result อยู่แล้ว ให้ reject ชัด
7) ปรับ UI action visibility ให้ตรง business rule

ไฟล์: src/Resources/views/admin/module/lotto/draws/datatables_actions.blade.php

ตอนนี้:

edit แสดงทุก status ยกเว้น resulted
open แสดง draft/closed
close แสดง open
settle แสดง closed

ให้ agent ปรับเป็นดังนี้

7.1 ปุ่มแก้ไข

แสดงเมื่อ:

draft
open
closed

ไม่แสดงเมื่อ:

resulted

อันนี้คงเดิมได้

แต่ต้องเปลี่ยนความหมายใน backend ว่า “กดได้ ไม่ได้แปลว่าแก้ได้ทุก field”

7.2 ปุ่มเปิดรับ

แสดงเมื่อ:

draft
closed

คงเดิมได้

แต่ใน modal/confirm ควรมีข้อความเพิ่ม:

ถ้ายังไม่ถึง open_at จะเป็นการเปิดรับก่อนเวลา
ระบบจะบันทึกเป็น manual open
จะไม่แก้เวลา open_at

ถ้าไม่อยากทำ confirm ซับซ้อนตอนนี้ อย่างน้อย backend ต้อง handle ถูก

7.3 ปุ่มปิดรับ

แสดงเมื่อ:

open

คงเดิม

7.4 ปุ่มประกาศผล

แสดงเมื่อ:

closed

คงเดิม

7.5 สถานะ resulted

ไม่แสดง action ใดๆ
คงเดิม

8) ปรับฟอร์ม add/edit modal ให้ตรง flow จริง

ไฟล์: src/Resources/views/admin/module/lotto/draws/addedit.blade.php

จากที่มีอยู่จริง ตอนนี้ฟอร์มประกาศผลใช้

first_prize
last_2_digits

อันนี้ไม่ต้องรื้อ

สิ่งที่ต้องเพิ่มคือ behavior ฝั่ง edit

8.1 เมื่อเปิด modal edit

ให้ frontend รู้ status ปัจจุบัน แล้ว disable field ตาม rule

ตัวอย่าง
draft

เปิดแก้ได้หมด

open

disable:

market_id
draw_date
field โครงสร้างหลักที่ไม่ควรเปลี่ยน

allow:

close_at
remark/metadata
closed

disable เพิ่มอีก

เกือบทั้งหมด
เหลือเฉพาะ metadata ที่อนุญาต
resulted

จริงๆ ไม่ควรเปิด modal edit แล้ว

8.2 อย่าให้ frontend เป็นตัว enforce หลัก

frontend disable เพื่อ UX
backend ต้อง validate ซ้ำเสมอ

9) เพิ่ม business rule เรื่องเวลา
   9.1 ความหมายของเวลา
   open_at = เวลาเปิดตามแผน
   opened_at = เวลาเปิดจริง
   close_at = เวลาปิดตามแผน
   closed_at = เวลาปิดจริง
   result_at = เวลาประกาศผลจริง
   9.2 กรณีเปิดรับก่อนเวลา

อนุญาตได้ แต่ต้อง:

ไม่แก้ open_at
บันทึก opened_at = now
บันทึก open_mode = manual
9.3 กรณี cron เปิดตามเวลา

ต้อง:

opened_at = now
open_mode = scheduled
10) Permission / ACL

ถ้าระบบ ACL ของ package นี้พร้อมขยาย ให้ agent เพิ่ม subject/action ใหม่
แต่ถ้า scope งานนี้อยากเบา ให้ทำ backend rule ก่อนและ defer ACL ละเอียดไว้ phase ถัดไป

แนะนำถ้าจะเพิ่ม
lotto_draws.edit
lotto_draws.open
lotto_draws.force_open
lotto_draws.close
lotto_draws.settle

อย่างน้อย force_open ควรแยกจาก open

ถ้ายังไม่ทำ ACL รอบนี้:

ให้ TODO ไว้ชัด
แต่ service ต้องรองรับ concept manual open ไว้ก่อน
11) Logging / audit

คุณบอกว่าระบบมี log กลางอยู่แล้ว ว่า emp ไหน update table ไหน เวลาไหน

งั้นรอบนี้ไม่ต้องเพิ่ม table audit ใหม่
แต่ agent ต้อง ensure ว่า action สำคัญยังวิ่งผ่าน model update ปกติ เพื่อให้ log กลางจับได้

action ที่ต้อง trace ได้
open draw
close draw
settle result
update draw
สิ่งที่อยากให้ log กลางเห็น
old status -> new status
open_at เดิมไม่ถูกเขียนทับตอน force open
opened_at / closed_at ถูกเขียนเมื่อ action เกิดจริง
12) Test plan ที่ agent ต้องทำ

ไม่ต้องปล่อยงานโดยไม่มี test matrix

12.1 create draft
สร้างงวด draft ได้
ค่าเวลา save ถูก
status default เป็น draft
12.2 auto open
draw draft ที่ open_at <= now ถูกเปิดเป็น open
มี opened_at
open_mode = scheduled
12.3 manual open before schedule
draw draft ที่ open_at > now กด open ได้ตาม rule ที่ตกลง
status เป็น open
opened_at = now
open_mode = manual
open_at เดิมต้องไม่เปลี่ยน
12.4 close draw
open -> closed ได้
closed_at ถูกเขียน
12.5 settle only on closed
open แล้ว settle ต้อง fail
closed แล้ว settle ได้
resulted แล้ว settle ซ้ำต้อง fail
12.6 edit restrictions
draft แก้ market/date/time ได้
open แก้ market ไม่ได้
resulted แก้ไม่ได้เลย
12.7 result validation
first_prize 5 หลักผ่าน
first_prize 6 หลักผ่าน
4 หรือ 7 หลัก fail
last_2_digits ไม่ใช่ 2 หลัก fail
13) ลำดับทำงานที่แนะนำ
    Phase 1 — database + model
    เพิ่ม migration field ใหม่
    ปรับ LottoDraw model cast/fillable
    Phase 2 — service
    ปรับ DrawService::openDraw()
    ปรับ DrawService::closeDraw()
    ปรับ syncScheduledStatuses() ให้ใช้ service flow เดิม
    Phase 3 — controller
    ปรับ update() ให้ล็อก field ตาม status
    ปรับ open() / close() / settle() guard ให้ชัด
    Phase 4 — UI
    ปรับ actions blade
    ปรับ form edit modal ให้ disable field ตาม status
    เพิ่มข้อความ warning ตอน open ก่อนเวลา ถ้าทำได้ในรอบนี้
    Phase 5 — test / verify
    เขียน test matrix ตามข้อ 12
    ทดสอบ manual จริงใน admin list + modal
    เช็คว่าคำสั่ง lotto:sync-draw-statuses ยังทำงานตรง
14) Acceptance criteria

งานนี้ถือว่าจบเมื่อครบทั้งหมด:

งวด draft/open/closed/resulted ยังทำงานได้ตามเดิม
cron เปิด/ปิดงวดอัตโนมัติยังไม่พัง
ออกผลได้เฉพาะ closed
เปิดรับก่อนเวลาได้แบบ manual โดยไม่แก้ open_at
มี opened_at และ open_mode
update() ไม่เปิดให้แก้ทุก field แบบเดิม
resulted ไม่มีปุ่ม action
ฟอร์มผลรางวัลยังใช้ first_prize + last_2_digits
หวย 5 หลัก และ 6 หลักยังประกาศผลได้
15) ข้อกำชับสำหรับ agent

ให้ยึดหลักนี้:

ห้าม refactor กว้างเกิน scope
ห้ามเปลี่ยนชื่อ status
ห้ามเปลี่ยน route/public interface ถ้าไม่จำเป็น
ห้าม revert logic ผลรางวัลจาก first_prize + last_2_digits กลับไปเป็น top_3 + bottom_2
ต้องแก้ที่ service/controller/model/view ให้สอดคล้องกันทั้งชุด
ต้องระวังไม่ให้ cron กับ manual action เขียนสถานะคนละแบบ
