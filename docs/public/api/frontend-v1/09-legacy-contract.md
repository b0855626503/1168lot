# Frontend API V1 - Legacy Contract Notes

อัปเดตล่าสุด: 2026-04-30

รายการ legacy/custom shape ที่ยังมีอยู่ (เพื่อ backward compatibility):

- บาง endpoint ตอบ `{ success, message }` โดยไม่มี `data`
- reward บางเส้นตอบ root payload แบบ custom
- บาง list endpoint ใช้ `items` ที่ root payload

คำแนะนำ frontend:
- แยก parser ตาม endpoint critical list
- ทำ adapter layer กลางไว้ก่อนจนกว่าจะ migrate contract ครบ
