# Lotto Internal Result Sources — Compatibility & Legacy Contract Matrix (PR-12)

อัปเดตล่าสุด: 2026-03-30  
สถานะ: LOCKED

## Scope

ครอบคลุม behavior ที่มาจาก zip 3 ชุด:

- `lottery-php`
- `dowjones-midnight`
- `dowjones-extra`

## Matrix: keep / shim / drop

| legacy behavior | caller state | policy | implementation decision |
|---|---|---|---|
| `type=list` (surface ของ legacy API) | external-unknown | keep-via-shim | รองรับผ่าน `/internal/lottery/results/exphuay/{type}` โดยส่ง `type=list` ได้ |
| date input `Y-m-d` | known-required | keep | รองรับตรง |
| date input `d/m/Y` | compat-required | keep | normalize ที่ service (`DateInputNormalizer`) |
| date input `d-m-Y` | compat-required | keep | normalize ที่ service (`DateInputNormalizer`) |
| exphuay `page` query | known-required | keep | รองรับ `page` เฉพาะ route exphuay |
| path-style mini-project URL (`/lottery/{type}`) | no in-repo caller | drop | ไม่เปิด route นี้ในระบบหลัก |
| legacy CLI (`php lottery.php ...`) | no in-repo caller | drop | ไม่ใช้เป็น production path |

## Compatibility Policy Lock

- compatibility baseline = คงเฉพาะ API shape ที่ integration ฝั่งระบบหลักต้องใช้จริง
- ไม่แบก mini-project runtime เดิมเข้า production path
- contract กลางใช้ canonical schema คงที่:
  - `success`, `source`, `type`, `draw_date`, `raw_result`, `normalized_result`, `meta`, `errors`

## Traceability

1. zip evidence: `lottery-php`, `dowjones-midnight`, `dowjones-extra`
2. contract decision: keep/shim/drop table ด้านบน
3. implementation task:
   - `InternalResultService`
   - `DateInputNormalizer`
   - internal routes `/internal/lottery/results/*`
4. test evidence:
   - `tests/Feature/Lotto/InternalResultEndpointsTest.php`
   - `tests/Unit/Lotto/LottoApiRouteScaffoldTest.php`

