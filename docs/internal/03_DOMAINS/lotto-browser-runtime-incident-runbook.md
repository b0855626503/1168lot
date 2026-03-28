# Lotto Browser Runtime Incident Runbook

อัปเดตล่าสุด: 2026-03-29

## วัตถุประสงค์

- ใช้เป็นแนวทาง on-call เมื่อ auto result browser runtime ล้มเหลว
- ลด MTTR ด้วยการ triage ตาม `reason_code`
- ควบคุม rollback แบบไม่กระทบ source ที่ยังทำงานปกติ

## ข้อมูลที่ต้องเก็บก่อนเริ่ม triage

1. `source_id`, `draw_id`, `receipt_key`
2. `selected_driver`, `payload_origin`, `selected_capture`
3. `error_code`, `error_stage`, `phase_timing`
4. `artifact_refs` (path และไฟล์ที่เกี่ยวข้อง)
5. เวลาที่เกิดเหตุ (timezone `Asia/Bangkok`)

## Reason Code → First Response

### `BROWSER_RUNTIME_UNAVAILABLE`

1. ตรวจ runtime feature flag (`lotto_auto_result.browser_runtime.enabled`)
2. ตรวจ rollout whitelist ของ source (`browser_runtime.rollout.whitelist_source_ids`)
3. ถ้าเป็น `prefer_browser_runtime` ให้ยืนยันว่า HTTP fallback ทำงานแทน

### `BROWSER_LAUNCH_FAILED`

1. ตรวจ dependency/playwright install ของ environment
2. เปิด artifact/log เพื่อดู stderr summary ของ worker
3. ถ้ายังไม่จบ incident ภายใน SLA ให้ rollback เป็น HTTP (เฉพาะ source ที่ capability = `prefer_browser_runtime`)

### `BROWSER_EXECUTOR_TIMEOUT`

1. ดู `phase_timing` ว่าค้างที่ navigation/readiness/capture
2. ตรวจ `timeouts.overall_seconds` และ config wait strategy ของ source
3. ถ้า timeout เกิดซ้ำ ให้ลด blast radius โดยเอา source ออกจาก whitelist ชั่วคราว

### `BROWSER_EXECUTOR_IO_ERROR`

1. ตรวจ worker script path และ permission (`scripts/lotto/browser_runtime_worker.js`)
2. ตรวจ node binary ใน runtime config
3. ถ้าเป็น infra outage ให้เข้าสู่ rollback path ทันที

### `NO_NETWORK_MATCH`

1. ตรวจ capture rules (`capture_url_patterns`, method/content-type)
2. ตรวจว่า endpoint ที่ source ต้องจับยังถูกเรียกจริงในรอบนั้น
3. ห้าม fallback เป็น HTTP สำหรับ source ที่ declare network capture เป็นหลัก

### `DOM_SELECTOR_NOT_FOUND`

1. ตรวจ selector readiness ว่ายังตรงกับหน้าเป้าหมาย
2. ยืนยันว่า source นั้นเปิด `allow_dom_fallback=true` หรือไม่
3. ห้าม fallback เป็น HTTP เมื่อ source declare browser path เป็นหลัก

### `CAPTURE_AMBIGUOUS_MATCH`

1. ตรวจว่า selection mode เป็น `best` และ rules มี tie ที่แตกไม่ได้
2. ปรับ capture rules ให้ deterministic มากขึ้น (url/method/content-type/priority)
3. หลังแก้ config ให้ dry-run อีกครั้งก่อนเปิดใช้งานจริง

## Rollback Decision Tree

1. ตรวจขอบเขต incident:
   - ถ้าเกิดทั้งระบบหรือข้ามหลาย source: ปิด global runtime (`browser_runtime.enabled=false`) ชั่วคราว
   - ถ้าเกิดเฉพาะบาง source: เอา source นั้นออกจาก whitelist ก่อน
2. ตรวจ capability:
   - `prefer_browser_runtime`: อนุญาต fallback ไป HTTP ตาม allowlist reason codes
   - `require_browser_runtime`: ไม่ fallback ไป HTTP, ต้องแก้ runtime/config แล้ว rerun
3. หลัง rollback:
   - ยืนยันว่า draw ใหม่ไม่สะสมสถานะ error เดิม
   - บันทึกเวลาเริ่ม rollback และเวลา restore

## Post-Incident Evidence Checklist

1. timeline เหตุการณ์ (เริ่ม, detect, mitigate, restore)
2. reason code หลัก + secondary signals (`phase_timing`, `artifact_refs`)
3. root cause ที่ยืนยันแล้ว (config/runtime/infra/data)
4. action ที่ทำจริง (flag toggle, whitelist change, config update)
5. ผลลัพธ์หลังแก้ (dry-run + production sample)
6. follow-up ticket พร้อม owner และ due date

## Verification หลังแก้

1. รัน unit tests กลุ่ม `tests/Unit/Lotto/AutoResultV2`
2. ทดสอบ async browser test dispatch/status จากหน้า admin
3. ตรวจ log ล่าสุดว่ามี `selected_driver`, `payload_origin`, `selected_capture`, `artifact_refs` ครบ
