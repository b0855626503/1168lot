# Frontend API: Lotto Market Content

เอกสารนี้เป็น mirror ของ canonical doc:
- `docs/05_API/frontend-lotto-market-content.md`

## Endpoint
`GET /api/v1/lotto/markets/{marketId}/content`

## Contract ย่อ
- market ไม่เจอ -> `404`
- content ไม่เจอ -> success + `content` object ว่าง
- มี `fallback_locale` เสมอ (`null` ถ้าไม่ fallback)
- `content` เป็น object เสมอ

## Locale Normalize
- `la`, `laos` -> `lo`
- `kh`, `khmer` -> `km`

## ตัวอย่าง Success
```json
{
  "market_id": 1,
  "locale": "km",
  "fallback_locale": null,
  "content": {
    "title": "...",
    "summary": "...",
    "rules_content": "...",
    "schedule_content": "...",
    "prize_content": "...",
    "formula_content": "...",
    "seo_title": "...",
    "seo_description": "..."
  }
}
```
