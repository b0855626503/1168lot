# MCP Operating Guide

อัปเดตล่าสุด: 2026-07-02

เอกสารนี้สรุปวิธีใช้งาน `boat` MCP server สำหรับ repo นี้
Boat แทนที่ `codebase-memory-mcp`, `serena` (octocode), และ `mem0` ด้วย MCP server ตัวเดียว

## หลักการสำคัญ

- `/docs` คือ source of truth
- Boat MCP คือ acceleration layer (code intelligence + memory)
- ถ้า docs กับ memory ไม่ตรง ให้ยึด `/docs` ก่อน
- จุดรวมหลักฐาน retrieval/memory/index อยู่ที่ `docs/internal/01_SYSTEM/retrieval-system-status.md`

## วิธีใช้งาน `boat`

### 90% ของงานใช้แค่ `boat_ask`

| ผู้ใช้ถามว่า | ใช้ |
|------------|-----|
| หาโค้ด / functions / classes | `boat_ask` |
| อธิบาย flow / architecture | `boat_ask` |
| ดู impact ก่อน refactor | `boat_ask` |
| debug / หาบัค | `boat_ask` |
| review PR | `boat_ask` |
| ออกแบบระบบ | `boat_ask` |
| security audit | `boat_ask` |
| library ใช้ยังไงในโปรเจค | `boat_ask` |
| ทำความเข้าใจ codebase ใหม่ | `boat_ask` |
| migration impact | `boat_ask` |

Boat เลือก workflow ให้เองจากภาษาธรรมชาติ (ไทย/อังกฤษ) — ไม่ต้องบอกว่าอยากได้ workflow ไหน

### Verify Mode — ใช้กับงานสำคัญ

```bash
# งานสำคัญที่ต้องการความมั่นใจสูง
boat_ask(question="หา SQL injection ในโค้ด", verify=true, thoroughness="thorough")
```

**3 ระดับ:**
- `quick` — ตรวจสอบ correctness (1 lens)
- `medium` — correctness + security (2 lenses)
- `thorough` — ทั้ง 3 lenses + loop-until-dry (หาจนไม่เจออะไรใหม่)

### 10 Workflows ที่ Boat เลือกให้อัตโนมัติ

| Intent | ตัวอย่างคำถาม | Boat เรียกอะไรภายใน |
|--------|-------------|-------------------|
| **search** | "หาโค้ดที่เรียกใช้ PaymentGateway" | CBM + Serena |
| **explain** | "deposit flow ทำงานยังไง" | CBM + mem0 |
| **refactor** | "WalletService แก้แล้วอะไรพัง" | CBM + Serena |
| **bug** | "debug null pointer ใน OrderService" | CBM + mem0 + Serena |
| **review** | "review PR #123" | CBM + Serena |
| **design** | "ออกแบบ notification system" | CBM + mem0 |
| **audit** | "ตรวจสอบความปลอดภัย PaymentService" | CBM + Serena |
| **library** | "axios ใช้ในโปรเจคยังไง" | CBM + Serena |
| **onboard** | "ทำความเข้าใจ codebase นี้" | CBM + Serena + mem0 |
| **migrate** | "upgrade Laravel 10 → 11" | CBM + Serena |

## วิธีอัปเดตข้อมูลให้ล่าสุด (Latest Sync)

ใช้ทุกครั้งเมื่อมีการเปลี่ยน behavior, architecture, หรือ boundary สำคัญ

1. อัปเดต `/docs` ก่อน หรืออย่างน้อยพร้อมกัน
2. ใช้ `boat_ask` สอบถาม impact และ verify ว่าโค้ดกับ doc ตรงกัน
3. อัปเดต memory layer (`memory/`) ให้สะท้อนสถานะล่าสุด

## Unified Sync Policy (Doc + Memory)

เมื่อมีการเปลี่ยนโค้ดที่กระทบ behavior/structure ต้องอัปเดตพร้อมกัน:

1. `.md` ใน `/docs` (source of truth)
2. memory layer (`memory/`)
3. ใช้ `boat_ask(verify=true)` เพื่อตรวจสอบ consistency

- ตรวจด้วย `bash scripts/docs-validation/run.sh`
- ถ้า 2 ชั้นนี้ไม่สอดคล้องกัน ให้ถือเป็น invalid state

## Definition of Done สำหรับงาน MCP/Memory

- มี doc ใน `/docs` รองรับ decision ล่าสุด
- Boat memory อัปเดตแล้วและอ่านกลับได้
- ผ่าน `bash scripts/docs-validation/run.sh`
- ทีมสามารถกลับมา query ต่อใน session ถัดไปได้ทันที

## Retrieval Rule (Memory First)

ลำดับการอ่าน context ที่บังคับ:

1. `boat_ask` — quick retrieval สำหรับ code intelligence + memory
2. `memory/<domain>.md`
3. docs เฉพาะ section ที่จำเป็นต่อ task

ห้ามเปิด doc ใหญ่ก่อนโดยยังไม่อ่าน memory layer

## Quick Checklist ก่อนเริ่มงานด้วย `boat`

1. `boat_status()` — เช็ค backends พร้อมไหม
2. ใช้ `boat_ask` เป็น tool แรกเสมอ (ไม่ใช่ `grep` หรือ `Read`)
3. งานสำคัญเปิด `verify=true`
