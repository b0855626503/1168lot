> สถานะ: ACTIVE
> วันที่: 2026-05-01
> โดเมน/เรื่อง: lotto / market-content
> แทนแผนเก่า: -
> อ้างอิง: BOA-187

# Lotto Market Content / Rules System

## Objective
เพิ่มระบบจัดการเนื้อหา “กติกา/รายละเอียดหวย” แยกจาก betting config โดยรองรับหลายภาษา และไม่กระทบ flow แทงหวยเดิม

## Requirement Source
- BOA-187 comment: Production Spec
- Final Locked Plan v2 (execution lock)
- ไฟล์นี้ + docs ที่เกี่ยวข้องใน `/docs` คือ source of truth สำหรับ implementation

## Current Code Context
- Market model: `packages/Gametech/Lotto/src/Models/LotteryMarket.php`
- Admin market controller: `packages/Gametech/Lotto/src/Http/Controllers/Admin/LotteryMarketController.php`
- Admin market UI: `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/markets/addedit.blade.php`
- Frontend lotto API controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/LottoController.php`
- Frontend routes: `packages/Gametech/FrontendApi/src/Routes/api.php`

## Schema Decision
- เพิ่มตารางใหม่ `lotto_market_contents`
- Locale support v1: `th`, `en`, `lo`, `km`
- Locale mapping (normalize ก่อน query/cache เสมอ):
  - `la`, `laos` -> `lo`
  - `kh`, `khmer` -> `km`

## API Contract Lock
Endpoint ใหม่:
- `GET /api/v1/lotto/markets/{marketId}/content`

Behavior lock:
- market ไม่เจอ -> `404`
- content ไม่เจอ (รวม fallback แล้ว) -> `success` และ `content` เป็น object เสมอ
- `fallback_locale` ต้องมีเสมอ (เป็น `null` เมื่อไม่ fallback)
- ห้ามเปลี่ยน key เดิมของ endpoint อื่นใน `LottoController`

## Admin Flow
- save market เดิมยังทำงานเหมือนเดิม
- content เป็น optional ต่อ locale (locale ว่างได้, market ยัง save ได้)
- แก้ไขต่อ locale ได้พร้อม toggle `is_enabled`
- `loadData` ต้องคืน field เดิมทั้งหมด + เพิ่ม `contents` map by locale

## Cache Strategy
- key: `lotto:market-content:{market_id}:{locale}` (locale ที่ normalize แล้ว)
- read-through cache เท่านั้น: miss -> query -> set
- invalidate หลัง commit DB เท่านั้น

## Security Strategy
- ห้ามเพิ่ม dependency ใหม่ในรอบนี้
- ใช้ sanitizer helper กลาง
- sanitize ตอน save เป็นหลัก
- policy ขั้นต่ำ: block `script`, `on*`, `javascript:`, disallow iframe, limited HTML whitelist

## Rollout Plan
1. Docs + commit docs
2. DB migration + model + relation
3. Admin save/load + UI + invalidation
4. Frontend API + fallback + cache read
5. Security + tests + regression checks

## Validation Checklist
- [ ] docs ใน `/docs` ครบตามสเปก
- [ ] commit docs ก่อนเริ่ม production code
- [ ] migration + model + relation ทำงาน
- [ ] admin create/update/loadData รองรับ content
- [ ] API ทำงานครบ hit/fallback/empty/404
- [ ] cache hit/miss/invalidate ถูกต้อง
- [ ] sanitizer ป้องกัน XSS ตาม policy
- [ ] regression ไม่กระทบ betting/draw/result/wallet flow

## PR Checklist (อ้างอิงสเปกนี้)
- [ ] อ้างอิง BOA-187 Production Spec + docs canonical
- [ ] ระบุว่าไม่มี breaking change ต่อ endpoint เดิม
- [ ] แนบผลทดสอบไฟล์ที่เกี่ยวข้อง
- [ ] ระบุ fallback behavior (`fallback_locale`, empty content object)
- [ ] ระบุ sanitizer scope (no new dependency)
- [ ] ระบุ cache key + invalidation timing หลัง DB commit
