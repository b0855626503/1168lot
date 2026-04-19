# Codebase Memory Summary

อัปเดตล่าสุด: 2026-04-19

ใช้ไฟล์นี้เป็น compressed context สำหรับ memory layer

## Retrieval Rule

- memory-first: อ่าน `.codebase-memory/SUMMARY.md` และ `memory/<domain>.md` ก่อน
- docs-on-demand: เปิด `/docs` เฉพาะ section ที่จำเป็นเมื่อ memory ไม่พอ
- ห้ามคัดลอก full doc มาไว้ใน memory

## Sync Rule

เมื่อมีการเปลี่ยน behavior/structure ของโค้ด:
- อัปเดต docs (`/docs`)
- อัปเดต memory (`.codebase-memory/*.md` หรือ `memory/*.md`)
- อัปเดต octocode index marker (`.ai/mcp/INDEX_SYNC.md`)

ถ้าชั้นข้อมูล 3 ส่วนไม่ตรงกัน ให้ถือว่าเป็น invalid state

## Domain Coverage

- `memory/auth.md`
- `memory/payment.md`
- `memory/wallet.md`
- `memory/game.md`

## Recent Retrieval-Relevant Updates

- Frontend API เพิ่ม reward flow สำหรับลูกค้า:
  - `GET /api/v1/reward/list`
  - `POST /api/v1/reward/redeem`
  - `GET /api/v1/reward/history`
- โค้ดหลัก:
  - `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/RewardController.php`
  - `packages/Gametech/FrontendApi/src/Routes/api.php`
