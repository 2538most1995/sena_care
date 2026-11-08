# CONTRIBUTING.md — แนวทางการร่วมพัฒนา SENA Care

ขอบคุณที่สนใจร่วมพัฒนา 🙌 เอกสารนี้สรุปวิธีตั้งค่า มาตรฐานโค้ด และขั้นตอนส่งงาน
อ่าน [README.md](README.md) สำหรับการติดตั้งฉบับเต็ม และ [Context.md](Context.md) สำหรับบริบทระบบ

---

## 1. การเตรียมสภาพแวดล้อม

**สิ่งที่ต้องมี**
- MAMP (Apache + MySQL) หรือ PHP 8.2+ / MySQL 8+
- Composer (สำหรับ mPDF/QR)
- Node.js (ไม่บังคับ — ใช้ตรวจ JS)

**ขั้นตอน**
```bash
# 1) วางโปรเจกต์ใน MAMP
cd /Applications/MAMP/htdocs
# (โปรเจกต์อยู่ที่ sena_care/)

# 2) ติดตั้ง dependency สำหรับ PDF
composer install --no-dev --prefer-dist

# 3) ตั้งค่า DB ที่ app/config/config.php
#    db_host=127.0.0.1  db_port=8889  db_name=sena_care  db_user=root  db_password=root

# 4) เปิด Apache + MySQL ใน MAMP แล้วเข้า
#    http://localhost:8888/sena_care
```

> ค่า DB/URL ถูก auto-detect จาก `app/config/config.php` — ปรับได้ตามเครื่อง

---

## 2. มาตรฐานโค้ด

### PHP
- ใช้ PHP 8.2+ syntax ที่มีอยู่แล้วในโปรเจกต์ (`match`, typed properties, spread)
- ตามรูปแบบเดิม: 4 spaces, `<?php` เปิด, ไม่ปิด `?>`
- ทุกไฟล์ view เริ่มด้วย guard:
  ```php
  if (!defined('APP_ROOT')) die();
  ```
- Query ทุกครั้ง: **PDO prepared statements** (`?` หรือ named)
- Escape output ด้วย `e()` เสมอ
- ตรวจสิทธิ์ด้วย `requireLogin()` / `requireRole(...)` / `hasRole(...)`
- ฟังก์ชันใหม่: ตั้งชื่อ `camelCase`, คลาส `PascalCase`, ไฟล์ชื่อตรงคลาส

### CSS
- รวมไว้ที่ `public/assets/css/style.css`
- ใช้ CSS variables (`--primary-color`, `--radius`, ฯลฯ) ไม่ hardcode สี
- ใช้ selector ที่มีความหมาย (`.stat-card`, `.dashboard-card`) ไม่ใช้ `div > div > div`
- ระบุ transition property ให้ชัด ไม่ใช้ `transition: all`
- ครอบ motion ด้วย `@media (prefers-reduced-motion: reduce)`

### JavaScript
- Vanilla ES6, รวมที่ `public/assets/js/app.js`
- ใช้ `apiFetch()` สำหรับเรียก API (จัดการ CSRF ให้)
- ใช้ `showToast()` แทน `alert()`
- ห้ามใช้ `window.addEventListener('scroll')` เพื่อ animation — ใช้ CSS/IntersectionObserver
- ทุกฟังก์ชันใหม่ต้องตรวจ `document.addEventListener('DOMContentLoaded')` หรือถูกเรียกจากจุดที่ถูก

### ข้อความ / ภาษา
- UI เป็นภาษาไทย ใช้ถ้อยคำสุภาพ กระชับ
- วันที่: ใช้ `thaiDate()` / `thaiDateTime()`
- หลีกเลี่ยงการตัดคำไทยผิดที่

---

## 3. Git Workflow

- Branch: `feat/<topic>`, `fix/<topic>`, `docs/<topic>`, `ui/<topic>`
- Commit message (แนะนำ Conventional Commits):
  ```
  feat(visits): add photo lightbox
  fix(ui): restore white headings on green banner
  docs: add AGENTS.md and Context.md
  ```
- หนึ่ง commit = หนึ่งเรื่องที่เกี่ยวข้องกัน
- อย่า commit ไฟล์ที่ไม่ควรอยู่: `storage/logs/*`, `public/uploads/*`, `.DS_Store`, `app/config/sdl.local.php`

---

## 4. ขั้นตอนส่งงาน (Pull Request)

ก่อนเปิด PR ให้ตรวจ:

- [ ] `php -l` ผ่านทุกไฟล์ PHP ที่แก้
- [ ] `node --check public/assets/js/app.js` ผ่าน (ถ้าแตะ JS)
- [ ] CSS วงเล็บสมดุล (ถ้าแตะ CSS)
- [ ] ไม่มี hardcoded สี/ค่า magic ที่ควรเป็น token
- [ ] ไม่มีการเปลี่ยน route / ชื่อฟิลด์ / schema โดยไม่แจ้ง
- [ ] ฟอร์ม POST ทุกอันมี CSRF + ตรวจสิทธิ์
- [ ] ข้อความไทยไม่ล้นจอ (ตรวจ 390px และ 1440px)
- [ ] คอนทราสต์/checkbox/ปุ่ม มองเห็นชัด
- [ ] คลิกรูปเปิด lightbox ไม่เปิดแท็บใหม่ (ถ้าเกี่ยวกับรูป)
- [ ] อัปเดต `README.md` / `Context.md` / `SKILL.md` ถ้ามีของใหม่

**คำอธิบาย PR ควรมี:** สรุปสิ่งที่แก้, หน้าที่กระทบ, วิธีทดสอบ, ภาพก่อน/หลัง (ถ้าเป็น UI)

---

## 5. การทดสอบ

```bash
# syntax เท่านั้น
php -l app/controllers/VisitController.php
php -l app/views/visits/show.php

# integration tests (สร้าง/ลบ DB ชั่วคราว — อ่านสคริปต์ก่อนรัน)
php tests/visit-report.php
php tests/sdl-integration.php
```

> ⚠️ tests บางตัวแตะฐานข้อมูล ให้รันเฉพาะในเครื่องพัฒนาและอ่านโค้ดก่อนเสมอ

---

## 6. ข้อควรระวังด้านความปลอดภัย

- ห้าม commit ข้อมูลส่วนตัวผู้เรียนจริง / รูปจริง / token
- `app/config/sdl.local.php` เป็น secret — อยู่แล้วใน `.gitignore` ห้ามเปิดเผย
- ทุก action ที่แก้ข้อมูล (POST) ต้องมี CSRF
- หลีกเลี่ยง `echo $_GET[...]` — ใช้ `e()` ทุกครั้ง
- อย่า log ข้อมูลอ่อนไหว (เบอร์โทร/ที่อยู่/พิกัด)

---

## 7. การรายงานปัญหา (Issue)

ระบุ: หน้า/URL, สิ่งที่คาดหวัง, สิ่งที่เกิดขึ้นจริง, ขั้นตอนทำซ้ำ, เบราว์เซอร์/ขนาดจอ (ถ้าเป็น UI), และภาพหน้าจอ (ถ้ามี)
