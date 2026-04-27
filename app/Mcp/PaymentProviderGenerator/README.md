# MCP Registration Note

วาง MCP tool wrappers ไว้ในโฟลเดอร์นี้หลังติดตั้ง `laravel/mcp`

แนวทางที่แนะนำ:

- MCP wrapper ไม่ควรมี business logic โดยตรง
- ให้ wrapper เรียก service ใน `App\Services\PaymentProviderGenerator`
- ทุก write operation ต้องตรวจ `mode`
- `dry_run` ต้องเป็น default

Tool names:

- `payment.providers.list`
- `payment.provider.inspect`
- `payment.api_doc.analyze`
- `payment.provider.plan`
- `payment.provider.generate`
- `payment.provider.validate`
- `payment.provider.package`

เพราะ API ของ `laravel/mcp` อาจต่างตาม version ให้ Agent ใช้ไฟล์ service ที่ให้มาเป็น core logic แล้วค่อยสร้าง wrapper ตาม version ที่ติดตั้งในโปรเจกต์จริง
