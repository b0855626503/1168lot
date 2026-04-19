# Decision Log - Endpoints

อัปเดตล่าสุด: 2026-04-19

## API Contract Decisions

- FrontendApi ต้องเป็น owner ของ customer-facing contract
- endpoint ที่เพิ่ม/เปลี่ยนต้อง sync กับ public API docs เสมอ
- Lotto navbar config endpoint decision:
  - ใช้ `GET /api/v1/lotto/navbar-config`
  - `code` เป็น optional query และ default `mobile_bottom_nav`

## Related Docs

- `docs/public/api/frontend-v1/index.md`
- `docs/internal/03_DOMAINS/frontend_api.md`
