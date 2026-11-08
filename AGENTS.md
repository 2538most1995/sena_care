# AGENTS.md — คู่มือสำหรับ AI Coding Agent

> อ่านไฟล์นี้ก่อนแก้โค้ดใด ๆ ใน repo นี้ ทุกครั้ง
> บริบทของระบบอยู่ที่ [Context.md](Context.md) · คู่มือติดตั้ง/ฐานข้อมูลอยู่ที่ [README.md](README.md)

---

## 0. TL;DR

- Stack: **PHP 8.2+ native MVC** + **MySQL** + **Bootstrap 5.3** (light theme) + Leaflet + Chart.js
- Front controller: `public/index.php` (แก้ route ที่นี่)
- Design system: `public/assets/css/style.css` (source of truth ของ UI)
- JS กลาง: `public/assets/js/app.js`
- รันผ่าน MAMP: `http://localhost:8888/sena_care` · DB `127.0.0.1:8889` (root/root) · DB name `sena_care`
- ตรวจ syntax ก่อนส่งงาน: `php -l <file>` และ `node --check public/assets/js/app.js`

---

## 1. กฎเหล็ก (Do Not Break)

1. **ภาษาไทยคือ UI หลัก** — ข้อความผู้ใช้ต้องเป็นไทย อย่าแปลเป็นอังกฤษโดยไม่ได้รับอนุญาต
2. **อย่าเปลี่ยน route slug / ชื่อฟิลด์ฟอร์ม / primary nav labels** โดยไม่จำเป็น (กระทบ analytics, autofill, SEO/ความเคยชิน)
3. **ทุก POST ต้องมี CSRF** — ใช้ `csrfField()` ในฟอร์ม และ `csrfMeta()` + `apiFetch()` ใน JS
4. **SQL ต้องเป็น prepared statement เท่านั้น** — ห้ามต่อ string จาก input ผู้ใช้
5. **Escape output ทุกครั้ง** — ใช้ `e()` กับข้อความที่มาจาก DB/ผู้ใช้
6. **ตรวจสิทธิ์ (role) ทุก endpoint** — ใช้ `requireLogin()`, `requireRole(...)`, `hasRole(...)`, `isAdmin()`
7. **สีแบรนด์**: น้ำเงินเข้ม `#04395e` — ห้ามใช้สีฟ้า/ม่วงค่าเริ่มต้นของ Bootstrap; ใช้ token ใน `:root` หรือ `--bs-*` ที่ re-theme ไว้แล้ว
8. **ห้ามแตะ** `app/views/reports/*-pdf.php` ด้วย CSS ของเว็บ — เป็น mPDF + THSarabunNew คนละบริบท
9. **`visit_plans` ถูกยกเลิก** — อย่าสร้าง flow วางแผนเยี่ยมใหม่
10. **Light theme เท่านั้น** — ไม่ต้องเพิ่ม dark mode เว้นแต่สั่ง
11. **ผู้สำเร็จการศึกษา**: เมนู "ติดตามผู้สำเร็จการศึกษา" = `/graduates` (ข้อมูลจาก API endpoint `graduates`) — ฟีเจอร์ followups เดิมอยู่ที่ `/followups`; รูปที่อัปโหลดต้องย่อขนาดผ่าน `ImageUploader` เสมอ (ห้ามเก็บรูปเต็มความละเอียด)

---

## 2. โครงสร้างที่ต้องรู้

```
public/index.php        → route dispatch (path → controller)
app/controllers/*.php   → handler; โหลด model/service, แล้ว include view
app/views/layouts/      → header (head+begin), navbar, sidebar, footer (scripts+end)
app/models/*.php        → PDO queries
app/services/*.php      → business logic (auth, SDL sync, PDF, photo storage)
app/helpers/*.php       → e(), url(), redirect(), thaiDate(), riskBadge(), statusBadge(), getFlash(), ...
public/assets/css/style.css → design system
public/assets/js/app.js → sidebar, search shortcut, toast, geolocation, lightbox
```

**รูปแบบการเพิ่มหน้าใหม่:**
1. เพิ่ม route ที่ `public/index.php` (เช่น `if ($path === '/x') { (new XController())->index(); exit; }`)
2. สร้าง controller method ที่ตรวจสิทธิ์ + เตรียมข้อมูล
3. สร้าง `app/views/x/index.php` (หน้าเนื้อหา)
4. ครอบด้วย layout ใน controller:
   ```php
   $pageTitle = '...'; $currentPage = 'x';
   include APP_ROOT.'/app/views/layouts/header.php';
   include APP_ROOT.'/app/views/x/index.php';
   include APP_ROOT.'/app/views/layouts/footer.php';
   ```
5. เพิ่มเมนูใน `app/views/layouts/sidebar.php` (desktop) และถ้าจำเป็นใน `footer.php` (mobile bottom nav / more menu)

> `$currentPage` คือค่าที่ใช้ไฮไลต์เมนู active (ต้องตรงกับ `page` ใน `sidebar.php`)

---

## 3. Design System Rules (สำหรับงาน UI)

รายละเอียดเต็มอยู่ที่ [SKILL.md](SKILL.md) — สรุปสั้น:

- ใช้ **class ที่มีอยู่** ก่อนสร้างใหม่: `card`, `dashboard-card`, `stat-card`, `btn btn-primary`, `form-control`, `form-select`, `table`, `badge`, `empty-state`
- **Radius**: การ์ด 16px · ฟอร์ม/ปุ่ม 10–12px · badge/avatar เป็น pill
- **Spacing**: ใช้ระบบ Bootstrap (`p-4`, `g-3`, `mb-4`) อย่าใส่ px มั่ว ๆ
- **Headings**: ห้าม set สีตายตัวใน `h1..h6` (จะทับ `.text-white` บนแบนเนอร์) — ปล่อยให้ inherit
- **Icons**: Bootstrap Icons (`bi bi-*`) เท่านั้น อย่าเขียน SVG เอง
- **Forms**: label อยู่เหนือ input, ใช้ `.form-label`; checkbox/radio ใช้ `.form-check-input` (มีสไตล์ขอบชัดแล้ว)
- **รูปภาพคลิกดูเต็มจอ**: ใส่ `<a href="..." data-lightbox data-alt="...">` แล้ว wrap `<img>` — `app.js` จัดการให้ (ไม่มีหน้าใหม่)
- **วันเวลา**: ใช้ component `.thai-datetime` (ช่องแสดงผลไทย พ.ศ.) + hidden input ค่า `YYYY-MM-DDTHH:mm` — **ห้ามใช้ `<input type="datetime-local">`** เพราะเบราว์เซอร์แสดง ค.ศ./อังกฤษ
- **Motion**: 150–250ms, `ease-out`; ต้องไม่กระทบ `prefers-reduced-motion`

---

## 4. Helper ที่ใช้บ่อย

| Helper | ใช้ทำอะไร |
|---|---|
| `e($str)` | escape HTML |
| `url('path')` | สร้าง URL เต็มจาก base |
| `redirect(url('...'))` | redirect |
| `thaiDate($d)`, `thaiDateTime($dt)` | วันที่แบบไทย |
| `riskBadge($level)`, `statusBadge($status, $type)`, `priorityBadge($p)` | badge มาตรฐาน |
| `currentUser()`, `currentUserId()`, `currentRole()` | ผู้ใช้ปัจจุบัน |
| `hasRole('admin','teacher')`, `isAdmin()`, `requireRole(...)`, `requireLogin()` | ตรวจสิทธิ์ |
| `getFlash()` / `flash($type,$msg)` | ข้อความแจ้งเตือน |
| `csrfField()`, `csrfMeta()`, `requireCsrf()` | CSRF |
| `educationLevelName()`, `truncate()`, `number_format(...)` | รูปแบบข้อความ |
| `currentTermId()`, `currentTermName()` | ภาคเรียนปัจจุบัน |
| `canCreateVisitForCenter($centerId)`, `canCreateSurveyForCenter($centerId)` | เช็กว่าผู้ใช้ปัจจุบันบันทึกเยี่ยม/สำรวจได้หรือไม่ (ครูตำบลขึ้นกับช่วงเวลาเปิด–ปิดของศกร.; อำเภอผ่านเสมอ; executive ดูได้อย่างเดียว) |
| `canCancelRecords()` | ยกเลิกบันทึก/การสำรวจ = อำเภอ (`admin`/`super_admin`) เท่านั้น |
| `centerActivityState($centerId, 'visit'|'survey')` | สถานะหน้าต่างเปิด–ปิดของศกร. (`enabled`, `open`, `open_at`, `close_at`) |

---

## 5. คำสั่งที่ใช้บ่อย

```bash
# ตรวจ syntax
php -l app/controllers/XController.php
php -l app/views/x/index.php
node --check public/assets/js/app.js

# ตรวจ CSS ว่าวงเล็บสมดุล (macOS/Linux)
python3 -c "s=open('public/assets/css/style.css').read(); print(s.count('{')==s.count('}'))"

# ตรวจว่าเว็บตอบ 200 (ถ้า MAMP เปิดอยู่)
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8888/sena_care/login

# tests (อ่านก่อนรัน — บางตัวสร้าง/ลบ DB ชั่วคราว)
php tests/visit-report.php
php tests/sdl-integration.php
```

---

## 6. Checklist ก่อนส่งงาน

- [ ] `php -l` ผ่านทุกไฟล์ที่แตะ
- [ ] `node --check public/assets/js/app.js` ผ่าน (ถ้าแตะ JS)
- [ ] CSS วงเล็บสมดุล (ถ้าแตะ CSS)
- [ ] ไม่มี hardcoded สีที่ขัดกับแบรนด์
- [ ] ข้อความไทยอ่านง่ายบนพื้นหลังจริง (คอนทราสต์พอ)
- [ ] checkbox/radio/ฟอร์มมองเห็นชัด
- [ ] ปุ่ม/ตารางไม่ล้นจอ (ตรวจทั้ง desktop และ mobile)
- [ ] ทุก POST มี CSRF + ตรวจสิทธิ์
- [ ] คลิกรูปแล้วเปิด lightbox ไม่เปิดแท็บใหม่ (ถ้าเกี่ยวกับรูป)

---

## 7. สิ่งที่ห้ามทำ

- ห้าม `git commit` / push เอง เว้นแต่ผู้ใช้สั่ง
- ห้ามลบ/แก้ข้อมูลในฐานข้อมูลจริงหรือไฟล์อัปโหลดของผู้ใช้
- ห้ามรัน `scripts/remove-demo-users.php` หรือ migration บนข้อมูลจริงโดยไม่สำรองก่อน
- ห้าม `cat` ไฟล์ทั้งก้อนถ้าใช้ `read` ได้ (ประหยัด context)
- ห้ามเดา path — ใช้ `glob`/`grep` ค้นก่อน
