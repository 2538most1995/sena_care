# SKILL.md — SENA Care UI Skill

> Skill สำหรับงานออกแบบ/ปรับ UI ของระบบ SENA Care
> ใช้เมื่อ: "ปรับ UI", "ทำหน้าใหม่", "แก้หน้าจอ", "เพิ่มฟอร์ม/ตาราง/ปุ่ม", "ทำ responsive", "แก้คอนทราสต์/สี"
> ไฟล์ดีไซน์หลัก: [`public/assets/css/style.css`](public/assets/css/style.css) · JS กลาง: [`public/assets/js/app.js`](public/assets/js/app.js)

---

## เมื่อไหร่ควรใช้ skill นี้

- สร้างหน้าใหม่ (view) หรือ component ใหม่ในระบบ
- ปรับหน้าตา/ระยะห่าง/สี/ไทโปกราฟี ของหน้าจอที่มีอยู่
- แก้ปัญหา responsive (มือถือ/แท็บเล็ต) หรือ bottom nav
- ตรวจ accessibility เบื้องต้น (คอนทราสต์, ฟอร์ม, ปุ่ม)
- เพิ่ม interaction: lightbox, toast, modal, collapse

ไม่ใช้กับ: หน้า PDF (`app/views/reports/*-pdf.php`) และงาน backend ล้วน ๆ

---

## 1. Design Tokens (ใช้จาก `:root` ใน style.css)

| Token | ค่า | ใช้กับ |
|---|---|---|
| `--primary-color` | `#04395e` | พื้นปุ่มหลักและสถานะ active |
| `--primary-ink` | `#04395e` | ลิงก์ ตัวอักษร และไอคอนบนพื้นอ่อน |
| `--on-primary` | `#ffffff` | ตัวอักษรบนพื้นสีหลัก |
| `--primary-hover` | `#032c49` | hover ของ primary |
| `--primary-subtle` | `#edf3f7` | พื้นอ่อน (active nav, badge) |
| `--primary-soft` | `#f7f9fb` | hover อ่อน ๆ |
| `--palette-ink` | `#031d44` | ตัวอักษรหลักและตัวอักษรบนพื้น accent |
| `--success/info/warning/danger` | `#70a288 / #04395e / #dab785 / #d5896f` | สถานะจากพาเลตเดียวกัน |
| `--bg` | `#f5f7fa` | พื้นหน้า |
| `--surface` | `#ffffff` | การ์ด |
| `--border` | `#e7ecf2` | เส้นขอบ |
| `--text` | `#0f172a` | หัวข้อ |
| `--text-body` | `#334155` | เนื้อหา |
| `--text-muted` | `#64748b` | ข้อความรอง |
| `--radius-lg` | `16px` | การ์ด |
| `--radius` | `12px` | กลุ่มย่อย |
| `--radius-sm` | `9px` | ปุ่ม/ฟอร์ม |

**Radius rule:** การ์ด 16 · ฟอร์ม/ปุ่ม 10–12 · badge/avatar/pill 999
**Theme:** light เท่านั้น · **Brand:** น้ำเงินเข้มเดียว ใช้สีสถานะเฉพาะความหมาย

สีหลักเป็นน้ำเงินเข้ม ใช้ตัวอักษรขาว `--on-primary` บนพื้นทึบ ส่วนลิงก์และไอคอนบนพื้นอ่อนใช้ `--primary-ink` ปุ่ม hover ใช้ `--primary-hover` กับตัวอักษรขาว

ใช้พาเลต 5 สี `#031D44 / #04395E / #70A288 / #DAB785 / #D5896F` ทั้งปุ่ม badge กราฟและแผนที่ พื้นเขียว/ครีม/ส้มอิฐใช้ `--on-accent` สีน้ำเงินเข้ม ห้ามใช้ตัวอักษรขาวบนพื้นสีอ่อนเหล่านี้ ข้อความสถานะบนพื้นขาวใช้สีเข้ม `--bs-*-text-emphasis`

---

## 2. Component Patterns

### การ์ดเนื้อหา
```html
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-xxx text-primary me-2"></i>หัวข้อ</h5>
  </div>
  <div class="card-body p-4">...</div>
</div>
```

### สถิติ (dashboard)
```html
<div class="stat-card stat-card-success">
  <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
  <div class="stat-value">128</div>
  <div class="stat-label">เยี่ยมแล้ว</div>
</div>
```

### ปุ่ม
```html
<button class="btn btn-primary">บันทึก</button>          <!-- หลัก: น้ำเงินเข้มทึบ -->
<a class="btn btn-outline-secondary">ยกเลิก</a>          <!-- รอง -->
<button class="btn btn-success">ยืนยัน</button>
```
> ปุ่มต้องมีข้อความบรรทัดเดียว (มี `white-space: nowrap`) ห้ามตัดคำ

ชุดปุ่มไอคอนในคอลัมน์จัดการที่ต้องอยู่แถวเดียว ใช้ `.action-buttons.action-buttons-nowrap.gap-2` เพื่อไม่ให้ปุ่มตกบรรทัดเมื่อคอลัมน์ตารางแคบ

### ฟอร์ม (label เหนือ input)
```html
<div class="mb-3">
  <label class="form-label fw-semibold">ชื่อ <span class="text-danger">*</span></label>
  <input type="text" class="form-control" required>
  <div class="form-text">คำอธิบาย (ถ้ามี)</div>
</div>
```
> checkbox/radio ใช้ `.form-check-input` — มีสไตล์ขอบเข้ม 2px ให้อัตโนมัติ

### ตาราง
```html
<div class="table-responsive">
  <table class="table table-hover align-middle records-table">
    <thead><tr><th>...</th></tr></thead>
    <tbody>...</tbody>
  </table>
</div>
```
> `.records-table` จะเปลี่ยนเป็นการ์ดอัตโนมัติบนมือถือ (ใช้ `data-label` จาก `app.js`)

### Empty state
```html
<div class="empty-state py-4">
  <i class="bi bi-inbox fs-1 text-muted"></i>
  <p class="text-muted mt-2 mb-0">ยังไม่มีข้อมูล</p>
</div>
```

### รูปภาพคลิกดูเต็มจอ (Lightbox)
```html
<a href="<?= $baseUrl ?>/public/uploads/visits/<?= e($file) ?>"
   data-lightbox data-alt="คำอธิบายรูป" title="คลิกเพื่อดูรูปขนาดเต็ม">
  <img src="..." alt="คำอธิบายรูป" class="img-fluid rounded-3 border" loading="lazy">
</a>
```
> `app.js` จัดการ overlay ให้ — **ห้าม** ใส่ `target="_blank"` อีก

### Thai date-time picker (ปฏิทินไทย พ.ศ.)
```html
<input type="text" class="form-control form-control-sm thai-datetime" readonly
       data-target="fieldId" value="<?= e($displayThai) ?>" placeholder="วว/ดด/ปปปป HH:mm">
<input type="hidden" id="fieldId" name="field" value="<?= e($isoValue) /* YYYY-MM-DDTHH:mm */ ?>">
```
> `app.js` (`initThaiDateTimePickers`) สร้างปฏิทินไทย (เดือนไทย + ปี พ.ศ.) + เวลา ให้อัตโนมัติ
> **ห้ามใช้ `<input type="datetime-local">`** ใน UI ไทย เพราะเบราว์เซอร์แสดง ค.ศ./ภาษาอังกฤษ
> ค่าใน hidden input เป็น `YYYY-MM-DDTHH:mm` (ค.ศ.) เพื่อส่งให้ backend — แสดงผลด้วย `thaiDateTime()`

### Toast / Flash
```js
showToast('success', 'บันทึกสำเร็จ');
showToast('danger', 'เกิดข้อผิดพลาด');
```
```php
flash('success', 'บันทึกสำเร็จ');  // แสดงใน layouts/header.php
```

### Tooltip
```html
<span class="badge bg-light text-dark border badge-tooltip"
      data-bs-toggle="tooltip" data-bs-placement="top"
      title="คำอธิบายเพิ่มเติม">2 คน</span>
```
> `app.js` init tooltip ให้อัตโนมัติ (`initTooltips`) — ใส่ `data-bs-toggle="tooltip"` + `title` แล้วจบ
> ใช้ `.badge-tooltip` (cursor: help) กับ badge/ไอคอนที่มีคำอธิบาย

---

## 3. Layout & Navigation

- **Desktop (≥992px):** sidebar 264px + top navbar 64px
- **Mobile (<992px):** bottom nav 5 ช่อง — `หน้าแรก · ผู้เรียน · สำรวจผู้เรียน · เยี่ยมบ้าน · เพิ่มเติม`
- "เพิ่มเติม" = offcanvas (`#mobileMoreMenu`) รวม แผนที่ผู้เรียน, ติดตาม, รายงาน PDF, และเมนูแอดมิน
- หน้าใหม่ที่ต้องการให้อยู่ในเมนู ต้องเพิ่ม:
  - desktop → `app/views/layouts/sidebar.php` (`$menuItems` + `roles`)
  - mobile → `app/views/layouts/footer.php` (bottom nav หรือ more menu)
- active state ใช้ `$currentPage` เทียบกับ `page`
- **อย่าให้ nav เกิน 1 บรรทัด** บน desktop และความสูง ≤ 80px

---

## 4. Responsive Rules

- ใช้ breakpoint Bootstrap: `sm 640`, `md 768`, `lg 992`, `xl 1280`
- ใช้ **CSS Grid** ไม่ใช้ flex percent math
- ห้ามใช้ `h-screen` — ใช้ `min-h-[100dvh]` หรือ `min-height: 100vh/100dvh`
- ใช้ `.container-fluid px-3 px-md-4` เป็น wrapper มาตรฐาน
- ตรวจ 2 ความกว้างขั้นต่ำ: **390px** (มือถือ) และ **1440px** (desktop)
- หลีกเลี่ยง `white-space: nowrap` กับข้อความไทยยาว ๆ (จะล้น)

---

## 5. Accessibility Checklist (ทำก่อนส่ง)

- [ ] ข้อความบนพื้นน้ำเงินเข้ม = **ขาว** (ห้ามหัวข้อกลายเป็นสีเข้มบนพื้นน้ำเงิน)
- [ ] checkbox / radio มองเห็นชัดทุกพื้นหลัง
- [ ] ฟอร์ม: label เหนือ input, placeholder ไม่ใช่ label, focus ring มองเห็น
- [ ] ปุ่ม: คอนทราสต์ ≥ 4.5:1, ข้อความไม่ตัดบรรทัด
- [ ] รูปทุกรูปมี `alt`
- [ ] ปุ่มที่ไม่มีข้อความต้องมี `aria-label`
- [ ] ใช้ `<label for>` ผูกกับ input
- [ ] keyboard: Tab ใช้งานได้, Esc ปิด lightbox/modal
- [ ] `prefers-reduced-motion` ไม่ถูกทำลาย

---

## 6. Motion Rules

- ความเร็ว UI: **150–250ms**
- easing: `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out) สำหรับ enter/hover
- อย่าใช้ `transition: all` — ระบุ property
- อย่าเริ่มจาก `scale(0)` — ใช้ `scale(0.96)` + `opacity`
- ปุ่มต้องมี `:active` feedback (เช่น `translateY(1px) scale(0.99)`)
- ต้องครอบด้วย `@media (prefers-reduced-motion: reduce)`

---

## 7. ขั้นตอนทำงาน (Workflow)

1. **อ่าน** หน้าเดิม + `style.css` ส่วนที่เกี่ยวข้อง
2. **ตรวจ class ที่มีอยู่** ก่อนสร้างใหม่ (grep ใน CSS)
3. แก้ที่ `style.css` ถ้าเป็น design token/component กลาง; แก้ view ถ้าเป็นโครงหน้า
4. ตรวจ:
   ```bash
   php -l app/views/<...>.php
   node --check public/assets/js/app.js   # ถ้าแตะ JS
   ```
5. เปิดดูจริง (ถ้า MAMP เปิด): `http://localhost:8888/sena_care/<page>`
6. ตรวจ 390px + 1440px
7. อัปเดตเอกสารนี้/`Context.md` ถ้าเพิ่ม component ใหม่

---

## 8. Anti-patterns (ห้าม)

- ❌ ตั้ง `color` ตายตัวบน `h1..h6` (จะทับ `.text-white` บนแบนเนอร์)
- ❌ hardcode สีฟ้า/ม่วง Bootstrap (`#0d6efd`, purple glow)
- ❌ ใช้ emoji แทน icon
- ❌ เปิดรูปด้วย `target="_blank"` (ต้องใช้ lightbox)
- ❌ การ์ดซ้อนการ์ดหลายชั้น + เงาหนัก
- ❌ ใส่ inline style ยกเว้นจำเป็น (เช่น ความสูงแผนที่)
- ❌ ใช้ `!important` พร่ำเพรื่อ
- ❌ สร้าง CSS/JS ไฟล์ใหม่ ถ้าใส่ใน `style.css`/`app.js` ได้

---

## 9. ตัวอย่างงานที่พบบ่อย

### เพิ่มหน้าใหม่
1. route ใน `public/index.php`
2. controller method (ตรวจสิทธิ์)
3. `app/views/<domain>/index.php` ใช้โครง `container-fluid` + `card` ตาม pattern
4. include layout (header → view → footer) พร้อม `$pageTitle`, `$currentPage`
5. เพิ่มเมนู (sidebar/footer) ถ้าต้องเข้าถึงบ่อย

### เพิ่มรูปที่กดดูเต็มจอ
ตามหัวข้อ 2 (Lightbox) — ใช้ `data-lightbox` เท่านั้น

### แก้ Mobile bottom nav
แก้ที่ `app/views/layouts/footer.php` — ต้องมี 5 item พอดี และ label ไทยสั้นพอ (≤ ~59px ที่ 0.7rem)
