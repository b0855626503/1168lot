# Decision: Lotto Market Content Storage

วันที่: 2026-05-01
สถานะ: Accepted

## Context
`lotto_markets` มีข้อมูล config การทำงานและชื่อหลายภาษา แต่ยังไม่มีที่เก็บเนื้อหา long-form สำหรับกติกา/รายละเอียดหวยที่ต้องแสดงฝั่ง frontend

## Decision
เพิ่มตาราง `lotto_market_contents` แยกจาก `lotto_markets` โดยผูกด้วย `market_id` และ `locale`

## Rationale
- แยก content ออกจาก betting config ชัดเจน
- รองรับหลายภาษาแบบ normalized
- เพิ่ม locale ใหม่ในอนาคตได้โดยไม่บวม schema หลัก
- cache ต่อ market+locale ได้ตรง use case

## Data Constraints
- unique `(market_id, locale)`
- FK `market_id -> lotto_markets.id` พร้อม `onDelete(cascade)`
- locale เก็บเป็น lower-case เสมอ
- index `locale`, `is_enabled`
- charset/collation align กับ policy ของ migration เดิมใน repo

## Locale Normalization
- accepted v1: `th`, `en`, `lo`, `km`
- mapping:
  - `la`, `laos` -> `lo`
  - `kh`, `khmer` -> `km`
- normalize ก่อน read/write/cache key ทุกครั้ง

## API Behavior Lock
- market ไม่เจอ -> 404
- content ไม่เจอ -> success + empty content object
- `fallback_locale` เป็น `null` ถ้าไม่ fallback

## Security Decision
- รอบนี้ไม่เพิ่ม package ใหม่
- ใช้ helper กลางสำหรับ sanitize ตอน save
- ไม่อนุญาต script/event handler/javascript URL/iframe

## Consequences
- ต้องเพิ่ม migration/model/relation และเส้น admin/frontend API ใหม่
- ต้องมี cache invalidation หลัง DB commit เพื่อกัน race
- ถ้าภายหลัง whitelist ไม่พอ ค่อยเสนอ package sanitization แบบแยก PR
