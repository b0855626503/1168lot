# Frontend API: Lotto Market Content

สถานะ: Draft for implementation (canonical)
อัปเดต: 2026-05-01

## Endpoint
`GET /api/v1/lotto/markets/{marketId}/content`

## Purpose
ดึงเนื้อหา “กติกา/รายละเอียดหวย” ของตลาดหวยตาม locale ที่ร้องขอ โดยรองรับ fallback

## Language Resolution
ใช้ language จาก middleware เดิม (`ResolveFrontendLanguage`) แล้ว normalize เพิ่มเติม:
- `la`, `laos` -> `lo`
- `kh`, `khmer` -> `km`

## Behavior Contract
1. ตรวจ market exists + enabled
2. ถ้า market ไม่เจอ: `404`
3. หา content ตาม normalized locale และ `is_enabled=true`
4. ไม่เจอ -> fallback `th`
5. ถ้าไม่เจอทั้งคู่ -> success และ `content` เป็น object ว่างแบบคงรูป

## Success Response (Shape Lock)
```json
{
  "market_id": 1,
  "locale": "lo",
  "fallback_locale": "th",
  "content": {
    "title": null,
    "summary": null,
    "rules_content": null,
    "schedule_content": null,
    "prize_content": null,
    "formula_content": null,
    "seo_title": null,
    "seo_description": null
  }
}
```

หมายเหตุ:
- `fallback_locale` เป็น `null` เมื่อใช้ locale ตรงโดยไม่ fallback
- `content` ต้องเป็น object เสมอ (ไม่สลับเป็น `null`)

## Error Response
- `404` เมื่อ market ไม่เจอหรือ market ไม่ enabled
- รูปแบบ envelope ให้ตาม pattern เดิมของ FrontendApi BaseController

## Cache
- key: `lotto:market-content:{market_id}:{normalized_locale}`
- read-through cache: miss -> query -> set
- invalidate หลัง DB commit เท่านั้น

## Security
ข้อมูล content ต้องผ่าน sanitizer ตอน save ก่อน
- block `script`, inline `on*`, `javascript:` URL, iframe
- limited HTML whitelist

## Backward Compatibility
- endpoint ใหม่นี้เป็น additive
- ห้ามเปลี่ยน key เดิมของ endpoint อื่นใน `LottoController`
