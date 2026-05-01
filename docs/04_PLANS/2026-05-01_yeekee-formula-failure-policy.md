> สถานะ: DONE
> วันที่: 2026-05-01
> โดเมน/เรื่อง: lotto / yeekee
> แทนแผนเก่า: -
> อ้างอิง: BOA-191

# Yeekee Formula Failure Policy (BOA-191)

## Objective
ล็อกพฤติกรรมเมื่อสูตรคำนวณ Yeekee ไม่สามารถคำนวณผลได้จาก input ไม่ครบ โดยไม่กระทบ flow หวยปกติ

## Code Source of Truth
- Command: `packages/Gametech/Lotto/src/Console/Commands/SettleYeekeeRoundsCommand.php`
- Formula: `packages/Gametech/Lotto/src/Services/Yeekee/Formulas/Presets/ShootsSumMinusPositionFormula.php`
- Exception: `packages/Gametech/Lotto/src/Services/Yeekee/Exceptions/YeekeeFormulaInputException.php`
- Test anchor:
  - `tests/Feature/Lotto/SettleYeekeeRoundsCommandTest.php`
  - `tests/Feature/Lotto/YeekeeResultEngineServiceTest.php`

## Runtime Policy Lock

### 1) Formula input recoverable failure
เมื่อสูตร `SHOOTS_SUM_MINUS_POSITION` หาเลขยิงตำแหน่งที่ต้องลบไม่พบ:
- throw `YeekeeFormulaInputException`
- `failure_code` ต้องเป็น `FORMULA_INPUT_INSUFFICIENT`

### 2) Draw has active tickets + formula input insufficient
ต้องยกเลิกและคืนเงิน active ticket ทั้งหมด แล้วปิด draw:
- `draw.status = resulted`
- `draw.result_number = null`
- `draw.result_fetch_status = VOID_REFUND_FORMULA_INPUT_INSUFFICIENT`
- `yeekee_round.status = voided`

### 3) Draw has no active tickets + formula input insufficient
ไม่ต้อง refund แต่ต้องปิด draw:
- `draw.status = resulted`
- `draw.result_number = null`
- `draw.result_fetch_status = NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT`
- `yeekee_round.status = voided`

### 4) Non-recoverable config error
กรณี config สูตรผิด (เช่น `subtract_position <= 0`) ถือเป็น hard error:
- command นับใน `errors`
- draw/round ต้องไม่ถูก mutate เป็น resulted/voided จาก path นี้

### 5) Existing path (no shoot) must stay unchanged
- มี active ticket แต่ไม่มี shoot: `VOID_REFUND`
- ไม่มี active ticket และไม่มี shoot: `NO_ACTIVITY`

## Logging Contract
เมื่อเกิด recoverable formula input failure ต้อง log:
- event: `yeekee.formula_failure_policy.recoverable`
- context ขั้นต่ำ:
  - `yeekee_round_id`
  - `lotto_draw_id`
  - `formula_preset`
  - `failure_code`
  - `result_fetch_status`
  - `ticket_count`
  - `shoot_count`
  - `message`

## DB Contract
ต้องรองรับค่าใหม่ใน `lotto_draws.result_fetch_status`:
- `VOID_REFUND_FORMULA_INPUT_INSUFFICIENT`
- `NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT`

Migration reference:
- `packages/Gametech/Lotto/src/Database/Migrations/2026_05_01_000100_expand_result_fetch_status_for_yeekee_formula_failure_policy.php`

## Non-goals
- ไม่เปลี่ยน logic สูตรหวยปกติ
- ไม่เปลี่ยน wallet domain contract
- ไม่เพิ่ม external dependency
