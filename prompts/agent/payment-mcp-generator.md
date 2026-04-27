# Agent Prompt: Payment Provider MCP Generator

คุณกำลังทำงานใน repo `b0855626503/1168lot`

## Startup

อ่านไฟล์เหล่านี้ก่อน:

1. `docs/START_HERE.md`
2. `docs/internal/00_RULES/agent_rules.md`
3. `docs/internal/01_SYSTEM/system_current_state.md`
4. `docs/internal/02_DECISIONS/decision_log.md`
5. `docs/04_PLANS/2026-04-27_payment-provider-mcp-generator.md`
6. `docs/internal/03_DOMAINS/payment-mcp-generator.md`

## Task

สร้าง MCP tools สำหรับ generate payment provider ใหม่ โดยอิงจาก `smkpay`

## Reference Files

- `packages/Gametech/Payment/src/Libraries/SmkPay.php`
- `packages/Gametech/Payment/src/Http/Controllers/SmkPayController.php`
- `packages/Gametech/Auto/src/Jobs/PaymentOutSmkPay.php`
- `packages/Gametech/Auto/src/Jobs/UpdateBalanceSmkPay.php`
- `database/migrations/2026_02_01_000000_create_payment_provider_accounts_table.php`

## Required MCP Tools

1. `payment.providers.list`
2. `payment.provider.inspect`
3. `payment.api_doc.analyze`
4. `payment.provider.plan`
5. `payment.provider.generate`
6. `payment.provider.validate`
7. `payment.provider.package`

## Hard Rules

- ห้ามเปลี่ยน interface เดิม
- ห้ามลบไฟล์
- ห้ามแก้ `.env`
- ห้าม deploy
- ห้าม migrate production
- ห้าม git push
- default generate ต้องเป็น `dry_run`
- write จริงต้องใช้ `mode=write_files`
- ถ้าแตะ route/config/provider registry ต้องรายงานไฟล์ที่กระทบก่อน

## Output

- สร้างไฟล์เต็ม พร้อมวางทับ
- อัปเดต docs
- มี manifest
- มี validation report
- มี package zip output
