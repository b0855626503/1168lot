# Lotto Internal Result Sources — Dowjones Extra-Fields Policy (PR-14)

อัปเดตล่าสุด: 2026-03-30  
สถานะ: LOCKED

## Problem

Dowjones sources มี field เสริมที่ไม่ใช่ผลรางวัลตรง เช่น:

- `start_spin`
- `show_result`
- `now`
- `update`

ต้อง lock ownership ให้แน่น เพื่อไม่ให้ parser/consumer ตีความไม่ตรงกัน

## Field Ownership Policy

| field | owner | keep/drop | rule |
|---|---|---|---|
| `start_spin` | `meta.dowjones_supplemental.start_spin` | keep | metadata เท่านั้น |
| `show_result` | `meta.dowjones_supplemental.show_result` | keep | metadata เท่านั้น |
| `now` | `meta.dowjones_supplemental.now` | keep | metadata เท่านั้น |
| `update` | `meta.dowjones_supplemental.update` | keep | metadata เท่านั้น |
| `digit5` | `normalized_result.digit_5` (+ `first_prize` fallback) | keep | ใช้เป็นผลรางวัล |

## Hard Rules

- ห้าม map field เสริมเข้า `normalized_result` (ยกเว้น field ผลรางวัลจริง)
- ห้าม drop field เสริมแบบเงียบ ๆ: ถ้า upstream มี ให้เก็บใน `meta.dowjones_supplemental`
- `raw_result` เก็บ payload ต้นทางเพื่อ trace/debug

## Traceability

1. zip evidence:
   - `dowjones-midnight/src/lottery.service.php`
   - `dowjones-extra/src/lottery.service.php`
2. contract decision: ownership table ด้านบน
3. implementation task: `InternalResultService` (`meta.dowjones_supplemental`)
4. test evidence: `tests/Feature/Lotto/InternalResultEndpointsTest.php` (assert supplemental path)

