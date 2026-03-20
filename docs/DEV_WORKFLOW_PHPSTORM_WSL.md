# Developer Workflow (PHPStorm + WSL)

ใช้เอกสารนี้เพื่อเช็คไฟล์, ค้นหา, และแก้ไขโค้ดให้เร็วขึ้นในโปรเจค `1168lot`.

## 1) Workspace Baseline
- เปิดโปรเจคจาก path WSL: `/home/boat/Projects/1168lot`
- ใน PHPStorm ให้ใช้ WSL interpreter (PHP/Composer) เพื่อหลีกเลี่ยง path mismatch
- ตั้ง Terminal shell เป็น `wsl.exe` และเริ่มที่ `/home/boat/Projects/1168lot`

## 2) Search Scope ที่แนะนำ
- Include (งานหลัก):
  - `app/`
  - `config/`
  - `routes/`
  - `packages/Gametech/`
  - `tests/`
- Exclude (noise/high-volume):
  - `vendor/`
  - `public/vendor/`
  - `storage/`
  - `bootstrap/cache/`

ไฟล์ `.rgignore` ใน root ถูกเพิ่มไว้ให้ `rg`/search tools ลด noise อัตโนมัติ

## 3) Daily Loop (เช็ค -> ค้นหา -> แก้ไข -> verify)

```bash
cd /home/boat/Projects/1168lot
php artisan optimize:clear
php artisan route:list | grep lotto
```

```bash
cd /home/boat/Projects/1168lot
rg "BetService|ExposureService|DrawService" packages/Gametech/Lotto/src
rg "lotto\.admin|lotto\.api" packages/Gametech/Lotto/src/Routes
```

```bash
cd /home/boat/Projects/1168lot
php artisan test --filter=Lotto
```

## 4) Quick Patterns เฉพาะโปรเจคนี้
- Module-first: ฟีเจอร์ใหม่ควรเริ่มใน `packages/Gametech/<Module>/src`
- Lotto route ไฟล์อยู่ที่ `packages/Gametech/Lotto/src/Routes`, แต่ต้องเช็คว่า provider มี `loadRoutesFrom(...)` แล้ว
- Concord model mapping อยู่ที่ `packages/Gametech/Lotto/src/Providers/ModuleServiceProvider.php`
- Bet types เป็น fixed enum ที่ `packages/Gametech/Lotto/src/Enums/BetType.php` (ห้าม dynamic)

## 5) Checklist ก่อนส่งงาน
- route ที่แตะยัง resolve ได้ (`php artisan route:list | grep <keyword>`)
- migration/model/service สอดคล้องกัน
- ไม่แก้ข้าม context โดยไม่จำเป็น (admin/wallet/api)
- มีบันทึกการเปลี่ยนในเอกสาร handover เมื่อแก้ logic สำคัญ

อ้างอิงเพิ่ม: `docs/LOTTO_SYSTEM_HANDOVER_TH.md`
