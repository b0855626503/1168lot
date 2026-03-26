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
* docs/internal/01_SYSTEM/system-current-state.md

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
