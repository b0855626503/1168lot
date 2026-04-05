# FrontendApi Domain Note

อัปเดตล่าสุด: 2026-04-06

## ใช้อ่านเมื่อ

- ทำ route ใหม่ใน `FrontendApi`
- แก้ payload ฝั่งลูกค้า
- ทำ auth/member/wallet/game/lotto API

## กติกาหลัก

- `FrontendApi` เป็น BFF ของลูกค้า
- ห้ามเรียก controller ของ package อื่นโดยตรง
- อนุญาตให้ reuse ผ่าน repository, model, query, domain service
- response contract ของลูกค้าต้องอยู่ใน `FrontendApi`

## เส้นสำคัญที่มีอยู่

- `auth/*`
- `member/profile|balance|contributor|history`
- `wallet/transactions|withdraw|claim`
- `coupon/*`
- `games/*`
- `lotto/*`
- `wheel/*`

## ข้อควรจำ

- ลูกค้าห้ามใช้ admin realtime channel
- ประวัติการเงินรวมยึด `wallet_transactions`
- ถ้าเพิ่ม endpoint ใหม่ ต้องอัปเดต public API doc

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- route/payload ปัจจุบัน -> `docs/public/api/api-frontend-v1.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision_log.md`
- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
