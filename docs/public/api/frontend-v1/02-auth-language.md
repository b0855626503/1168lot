# Frontend API V1 - Auth & Language

อัปเดตล่าสุด: 2026-04-30

## Auth

- Public routes: ไม่ต้องใช้ token
- Authenticated routes: ใช้ `Authorization: Bearer <access_token>`
- ถ้าไม่มี token: `401` + `ไม่พบ Bearer token`
- ถ้า token ไม่ถูกต้อง/หมดอายุ: `401` + `token ไม่ถูกต้องหรือหมดอายุ`

## Language

รองรับ `th|en|kh|la`

ลำดับการ resolve:
1. body/query: `language` หรือ `lang` หรือ `locale`
2. header: `X-Language`
3. header: `Accept-Language`
4. fallback: `th`
