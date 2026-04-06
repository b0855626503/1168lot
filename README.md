# Project Overview

โปรเจกต์นี้เป็นระบบ Laravel + Frontend (SPA) สำหรับเกมออนไลน์ พร้อมระบบหวย (Lotto), Wallet, Payment และ Admin Panel

---

## 🚀 Getting Started

### Requirements

* PHP 8.2+
* Composer
* Node.js
* MySQL / MariaDB

### Install

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run dev
```

---

## 📂 โครงสร้างเอกสาร

เอกสารทั้งหมดอยู่ที่ `/docs`

* internal → ใช้ภายใน (system, decision, plan)
* public → ใช้เปิดผ่าน URL (API docs)

---

## 📖 Documentation

เริ่มอ่านที่:

* docs/README.md
* docs/internal/01_SYSTEM/system_current_state.md

---

## 🤖 สำหรับ Agent

Agent ต้องเริ่มจาก:

* docs/START_HERE.md

---

## ⚠️ หมายเหตุ

* ห้ามแก้ logic โดยไม่อัปเดตเอกสาร
* เอกสารถือเป็น source of truth ของระบบ

## 🧠 Development Workflow

1. อ่านเอกสารใน /docs ก่อนเสมอ
2. ยึด SYSTEM_CURRENT_STATE เป็นหลัก
3. ห้ามแก้ logic โดยไม่อัปเดตเอกสาร
4. ใช้ WORK_PLAN ในการทำงานทุกครั้ง
5. เวลาตอบ ให้ตอบสั้นๆ ไม่ต้องอธิบายเยอะ
6. ทุกอย่างให้ minimal output เพื่อลดการใช้ Token
7. ถ้าให้แก้ Code แล้วมี จุดอื่นที่เกี่ยวข้อง ที่ควรแก้ด้วย ก็ถามมาเลย อย่ารอให้บอกก่อน
8. ไม่ต้องรอให้บอกให้แก้ก่อน ถ้าเห็นว่าควรแก้ก็แก้ไปเลย แต่ต้องอัปเดต doc ให้ตรงกันด้วย
9. ไม่ต้องอธิบายเยอะ ถ้าไม่จำเป็น ให้ตอบสั้นๆ ตรงประเด็น และอัปเดต doc ให้ตรงกันด้วย
10. ถ้าเจอปัญหา หรืออะไรที่ ต้องเลือกหรือต้องตัดสินใจ ให้ถามมาเลย 
11. พยายามให้ แต่ละครั้ง ใช้ token น้อยที่สุด ในส่วนของการที่ใช้สื่อสารกัน เพราะ token ที่ใช้ในการสื่อสารกัน ก็มีผลต่อค่าใช้จ่ายด้วย
