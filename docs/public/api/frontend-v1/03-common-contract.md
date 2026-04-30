# Frontend API V1 - Common Contract

อัปเดตล่าสุด: 2026-04-30

## Current Contract (Runtime ปัจจุบัน)

- Standard ส่วนใหญ่: `{ success, message, data }`
- บาง endpoint: `{ success, message }`
- บาง endpoint เป็น custom payload (legacy)

## Target Contract (สำหรับ normalize)

- Standard: `{ success, message, data }`
- List: `data.items`
- Pagination: `data.meta`
- Error: `{ success: false, message, errors? }`

หมายเหตุ: Target ยังไม่ใช่การเปลี่ยน runtime ทันที
