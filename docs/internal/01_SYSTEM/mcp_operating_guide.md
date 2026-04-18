# MCP Operating Guide

อัปเดตล่าสุด: 2026-04-18

เอกสารนี้สรุปวิธีใช้งาน `octocode_mcp` และ `codebase_memory_mcp` สำหรับ repo นี้
รวมถึงขั้นตอน sync ข้อมูลให้เป็นล่าสุดหลังมีการเปลี่ยนแปลง

## หลักการสำคัญ

- `/docs` คือ source of truth
- MCP memory/graph คือ acceleration layer
- ถ้า docs กับ memory ไม่ตรง ให้ยึด `/docs` ก่อน
- จุดรวมหลักฐาน retrieval/memory/index อยู่ที่ `docs/internal/01_SYSTEM/retrieval_system_status.md`

## 1) วิธีใช้งาน `octocode_mcp`

ใช้สำหรับสำรวจโค้ด, trace, และอ่าน implementation แบบเร็ว

### Quick Flow (แนะนำ)

1. `localSearchCode` หา symbol และ `lineHint`
2. `lspGotoDefinition` กระโดดไป definition
3. `lspFindReferences` หา usage ทั้งหมด
4. `lspCallHierarchy` ไล่ call graph (เริ่มจาก `outgoing`)
5. `localGetFileContent` อ่านช่วงโค้ดสุดท้ายก่อนแก้จริง

### ข้อกำหนดสำคัญ

- LSP tools ต้องใช้ `uri` แบบ absolute path เสมอ
  - ตัวอย่าง: `/home/boat/projects/1168lot/app/Providers/AppServiceProvider.php`
- เวลาหา references ให้ลด noise ด้วย pattern
  - include: `**/*.php`
  - exclude: `**/public/assets/**`, `**/node_modules/**`

### Known Caveats (environment ปัจจุบัน)

- `localFindFiles` อาจ fail ด้วย `find: unknown predicate '-O3'`
- `lspCallHierarchy` ฝั่ง `incoming` อาจไม่เสถียรบางครั้ง

### Preset Queries (ใช้งานซ้ำได้ทันที)

#### Preset A: Laravel App Core

1. `localSearchCode`
   - path: `/home/boat/projects/1168lot/app`
   - type: `php`
2. `lspGotoDefinition`
   - uri: absolute path ของไฟล์จากผลลัพธ์ข้อ 1
3. `lspFindReferences`
   - includePattern: `["**/*.php"]`
   - excludePattern: `["**/public/assets/**", "**/node_modules/**"]`

#### Preset B: Gametech Package Domain

1. `localSearchCode`
   - path: `/home/boat/projects/1168lot/packages/Gametech`
   - type: `php`
2. `lspGotoDefinition`
   - uri: absolute path
3. `lspCallHierarchy` (outgoing ก่อน)
   - direction: `outgoing`
4. `localGetFileContent`
   - อ่านช่วง implementation สุดท้ายก่อนแก้จริง

#### Preset C: Route-to-Handler Trace

1. `localSearchCode` หา route (`routes/web.php`, `routes/api.php`)
2. `lspGotoDefinition` ที่ controller action
3. `lspCallHierarchy` outgoing เพื่อไล่ service/repository chain
4. `lspFindReferences` เฉพาะไฟล์ PHP เพื่อประเมิน impact

### Fallback Playbook (แก้ caveat ที่เจอบ่อย)

#### กรณี `localFindFiles` ใช้ไม่ได้

ใช้ flow นี้แทน:
1. `localViewStructure` เพื่อดูโฟลเดอร์
2. `localSearchCode` แบบ `filesOnly=true` หรือค้น pattern กว้าง
3. ค่อย `localGetFileContent` เฉพาะไฟล์ที่เจอ

#### กรณี `lspCallHierarchy incoming` ล้ม

ใช้ flow นี้แทน:
1. `lspCallHierarchy` แบบ `outgoing` จาก symbol เดียวกัน
2. `lspFindReferences` ของ method/function เดิม
3. กรองผลลัพธ์เฉพาะ PHP แล้วตามด้วย `lspGotoDefinition` ทีละจุด
4. สรุป callers เชิงปฏิบัติจากตำแหน่งเรียกจริงที่ยืนยันได้

## 2) วิธีใช้งาน `codebase_memory_mcp`

ใช้สำหรับ knowledge graph, impact analysis, และ ADR memory ข้าม session

### Quick Flow (แนะนำ)

1. เช็กโปรเจกต์: `list_projects`
2. เช็กสถานะ index: `index_status`
3. ถ้ายังไม่มี index ให้ทำ: `index_repository`
4. ดูภาพรวมสถาปัตย์: `get_architecture(aspects=['all'])`
5. วิเคราะห์เฉพาะจุด: `search_graph`, `search_code`, `trace_path`
6. จัดการ ADR memory: `manage_adr(mode='get'|'update'|'sections')`

## 3) วิธีอัปเดตข้อมูลให้ล่าสุด (Latest Sync)

ใช้ทุกครั้งเมื่อมีการเปลี่ยน behavior, architecture, หรือ boundary สำคัญ

1. อัปเดต `/docs` ก่อน หรืออย่างน้อยพร้อมกัน
2. ตรวจการเปลี่ยนแปลงโค้ดด้วย `detect_changes`
3. refresh index ถ้าจำเป็นด้วย `index_repository`
4. ทบทวนภาพรวมด้วย `get_architecture`
5. อัปเดต ADR memory (`manage_adr(mode='update')`) ให้สะท้อนสถานะล่าสุด
6. ตรวจซ้ำ sections (`manage_adr(mode='sections')`) ว่าครบ

## 3.1) Unified Sync Policy (Doc + Memory + Index)

เมื่อมีการเปลี่ยนโค้ดที่กระทบ behavior/structure ต้องอัปเดตพร้อมกัน 3 ชั้น:

1. `.md` ใน `/docs` (source of truth)
2. memory layer (`.codebase-memory/` หรือ `memory/`)
3. octocode index/search layer (`.ai/mcp/index-build.json` แบบ machine-verifiable)

ถ้า 3 ชั้นนี้ไม่สอดคล้องกัน ให้ถือเป็น invalid state

- ตรวจด้วย `bash scripts/docs-validation/check-unified-sync.sh`
- ปรับโหมดด้วย `UNIFIED_SYNC_MODE=warn|error`
- สร้าง index artifact ด้วย `bash scripts/docs-validation/rebuild-octocode-index-artifact.sh --changed-only`

## 4) Definition of Done สำหรับงาน MCP/Memory

- มี doc ใน `/docs` รองรับ decision ล่าสุด
- graph/index พร้อมใช้งานกับโค้ดชุดล่าสุด
- ADR memory อัปเดตแล้วและอ่านกลับได้
- ผ่าน `check-unified-sync.sh` เมื่อมี code change
- ทีมสามารถกลับมา query ต่อใน session ถัดไปได้ทันที

## 4.1) Retrieval Rule (Memory First)

ลำดับการอ่าน context ที่บังคับ:

1. `.codebase-memory/SUMMARY.md`
2. `memory/<domain>.md`
3. docs เฉพาะ section ที่จำเป็นต่อ task

ห้ามเปิด doc ใหญ่ก่อนโดยยังไม่อ่าน memory layer

## 5) Quick Checklist ก่อนเริ่มงานด้วย `octocode_mcp`

1. ใช้ absolute path กับ LSP ทุกครั้ง
2. เริ่มจาก `localSearchCode` เพื่อเอา `lineHint`
3. เปิด references ด้วย include/exclude pattern เพื่อลด noise
4. ถ้า incoming hierarchy fail ให้ใช้ fallback playbook ทันที
