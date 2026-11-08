# Context.md — บริบทของโปรเจกต์ SENA Care

> เอกสารนี้สรุป "บริบท" ของระบบในมุมที่จำเป็นต่อการแก้ไข/พัฒนาต่อ
> สำหรับรายละเอียดการติดตั้งและฐานข้อมูลฉบับเต็ม ดูที่ [README.md](README.md)

---

## 1. ระบบนี้คืออะไร

**SENA Care** คือระบบติดตามและเยี่ยมบ้านผู้เรียนของ **สกร.** (สำนักงานส่งเสริมการเรียนรู้ประจำจังหวัด / ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา)
ใช้โดยครู ศกร.ตำบล และผู้บริหาร เพื่อติดตามผู้เรียนที่เสี่ยงหลุดจากระบบการศึกษา

- ที่มา/เป้าหมาย: จัดเก็บข้อมูลผู้เรียน + ที่อยู่อาศัย (พร้อมพิกัด GPS), บันทึกการลงพื้นที่เยี่ยมบ้านพร้อมหลักฐานรูปภาพและการเช็คอินพิกัด, ประเมินความเสี่ยง, และติดตามช่วยเหลือต่อเนื่อง
- กลุ่มผู้ใช้: ครู (teacher), ผู้บริหาร (executive), ผู้ดูแลระบบ (admin), ผู้ดูแลระบบสูงสุด (super_admin)
- ภาษา: UI ทั้งหมดเป็น **ภาษาไทย** (ฟอนต์ Sarabun)

---

## 2. สถาปัตยกรรมและเทคโนโลยี

| ชั้น | รายละเอียด |
|---|---|
| Runtime | PHP 8.2+ (native, ไม่ใช้ framework) |
| Pattern | MVC แบบ home-grown, Front Controller ที่ `public/index.php` |
| Database | MySQL 8 / MariaDB, InnoDB, `utf8mb4`, PDO prepared statements |
| UI | Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 + CSS design system เอง (`public/assets/css/style.css`) |
| Map | Leaflet.js + OpenStreetMap tiles |
| Charts | Chart.js 4 |
| JS | Vanilla ES6 (`public/assets/js/app.js`), Fetch API + CSRF header |

**สภาพแวดล้อมพัฒนา:** MAMP (Apache + MySQL) — โปรเจกต์อยู่ที่ `/Applications/MAMP/htdocs/sena_care`
URL ปกติ: `http://localhost:8888/sena_care` · MySQL port `8889` (user `root` / `root`) · DB ชื่อ `sena_care`
ค่าเหล่านี้อยู่ใน `app/config/config.php` (มี auto-detect `app_url`)

---

## 3. แผนผังโค้ด (สรุป)

```
public/index.php          Front controller + ตาราง route ทั้งหมด
app/config/               config.php, database.php (PDO singleton)
app/middleware/           AuthMiddleware, RoleMiddleware
app/helpers/              auth.php, csrf.php, response.php (e/url/redirect/format), validation.php
app/controllers/          จัดการ request แต่ละโดเมน
app/models/               data access (PDO)
app/services/             AuthService, SdlApiClient, SdlSyncService, VisitPhotoStorage, VisitReportPdf, TeacherVisitReport
app/views/layouts/        header.php, navbar.php, sidebar.php, footer.php
app/views/<domain>/       หน้าจอแต่ละส่วน
public/assets/css/        style.css (design system หลัก)
public/assets/js/         app.js (sidebar, search, toast, geolocation, lightbox)
public/uploads/           ไฟล์อัปโหลด (visits/, students/, avatars/, documents/)
scripts/                  CLI utilities (sync, migrate, repair)
tests/                    integration tests
storage/                  logs / private uploads
```

---

## 4. Route หลัก (ดูเพิ่มที่ `public/index.php`)

| เส้นทาง | หน้า |
|---|---|
| `/login`, `/logout` | เข้า/ออกระบบ |
| `/dashboard` (`/`) | แดชบอร์ด + API `/api/dashboard/stats` |
| `/students`, `/students/create`, `/students/{id}`, `/students/{id}/edit`, `/students/import` | จัดการผู้เรียน |
| `/surveys`, `/surveys/{studentId}`, `/surveys/view/{id}`, `/surveys/cancel/{id}` | แบบสำรวจผู้เรียนรายบุคคล (บันทึกได้หลายครั้ง มีประวัติ ดูย้อนหลัง/ยกเลิกได้) |
| `/map` + `/api/map/students` | แผนที่ GIS |
| `/visits`, `/visits/history`, `/visits/create/{studentId}`, `/visits/{id}`, `/visits/{id}/edit`, `/visits/{id}/cancel`, `/visits/{id}/pdf` | บันทึก/ดู/แก้/ยกเลิก (soft)/พิมพ์รายงานเยี่ยมบ้าน |
| `/followups`, `/followups/create`, `/followups/{id}/update-status` | งานติดตามช่วยเหลือ (ฟีเจอร์เดิม ยังใช้งานได้; เมนูหลักถูกแทนด้วยผู้สำเร็จการศึกษา) |
| `/graduates`, `/graduates/{id}`, `/graduates/{id}/track`, `/graduates/{id}/track/{trackingId}/edit`, `/graduates/sync` | ติดตามผู้สำเร็จการศึกษา: ซิงก์จาก API, กรองตามกลุ่มครู, บันทึก/แก้ไขผลลัพธ์ 3 หมวด + ข้อความ + รูปย่อ |
| `/reports`, `/reports/teachers`, `/reports/teachers/pdf`, `/reports/export` | รายงาน/ส่งออก CSV/PDF |
| `/terms`, `/terms/create`, `/terms/switch`, `/centers`, `/centers/create`, `/centers/windows` | ตั้งค่าภาคเรียน/ศกร. และช่วงเวลาเปิด–ปิดการเยี่ยม/การสำรวจ |
| `/users`, `/users/{id}/edit`, `/profile`, `/change-password` | ผู้ใช้/โปรไฟล์ |
| `/integrations/sdl` | เชื่อมต่อ API SDL_school (super_admin) |
| `/settings` | ตั้งค่าระบบ (super_admin) |
| `/notifications/read-all` | อ่านการแจ้งเตือนทั้งหมด |

หน้ารายชื่อ `/graduates` เปิดดู/บันทึก/แก้ไขผลการติดตามใน Bootstrap modal `#graduatePanelModal` ผ่าน `data-panel-url` และ `initGraduatePanel()` ใน `app.js` โดยใช้ route เดิม: คำขอ AJAX คืน JSON พร้อม HTML จาก view เดิม ส่วนการเข้า URL โดยตรงยังแสดงเต็มหน้าได้ ฟอร์มใช้ความกว้างเต็มพื้นที่ แบ่งสองคอลัมน์บน desktop และเรียงลงบนมือถือ

ปุ่มดูในรายชื่อ `/visits` เปิด `/visits/history?student_id=...&term_id=...` เพื่อเลือกบันทึกการเยี่ยมทุกครั้งของผู้เรียนในภาคเรียนนั้น ตัวกรองและ pagination คง `student_id` ไว้ การบันทึกใหม่เพิ่มแถวใน `home_visits` และเก็บคำตอบ/รูปเดิม ครูดูประวัติที่ตนบันทึกหรือของกลุ่มเรียนที่ได้รับสิทธิ์ได้

ประวัติแบบสำรวจในหน้าสำรวจและโปรไฟล์ผู้เรียนแสดงเฉพาะ `student_surveys.cancelled_at IS NULL` รายการที่ยกเลิกไม่นับในประวัติและเปิดรายละเอียดจาก URL เดิมไม่ได้ การยกเลิกยังใช้สถานะในฐานข้อมูลโดยไม่ลบข้อมูลจริง

หน้ารายละเอียดการเยี่ยมแสดงปุ่มขนาดเล็กเฉพาะพิมพ์รายงาน/แก้ไข/บันทึกใหม่ตามสิทธิ์ ปุ่มย้อนกลับในหน้าเยี่ยมใช้ `data-back-link` และ `initBackLinks()` เพื่อย้อนประวัติเบราว์เซอร์หนึ่งขั้น หากเปิดตรงหรือมาจากภายนอกระบบจะใช้ URL สำรองใน `href`

ป๊อปอัปดูผลกว้างสูงสุด 820px ปรับความสูงตามเนื้อหา; ฟอร์มบันทึก/แก้ไขใช้คลาส `graduate-panel-form` ขยายได้ถึง 960px ข้อมูลบุคคลใช้ partial `_profile.php` ร่วมกัน และประวัติจัดรูปหลักฐานไว้ข้างรายละเอียดเพื่อลดพื้นที่ว่าง มือถือเว้นขอบจอเล็กน้อย ปรับความสูงตามเนื้อหาและเลื่อนภายในเมื่อข้อมูลยาว

เมื่อมีฟอร์มในป๊อปอัป `initGraduatePanel()` ย้ายชุดปุ่มบันทึก/ยกเลิกไปที่ `.modal-footer` และเชื่อมปุ่ม submit กับฟอร์มด้วย attribute `form` แถบล่างซ่อนเมื่อดูประวัติหรือกำลังโหลด ปิดหน้าต่างด้วยกากบาทด้านบนหรือ Esc

ปุ่มซิงก์ผู้สำเร็จการศึกษาเรียก `GraduateSyncService::syncHistory()` เพื่อดึงทุกภาคเรียนจาก `meta.terms` ตั้งแต่ 1/2564 ถึงล่าสุดที่ API มีข้อมูล (ไม่จำกัดภาคเรียนตามตัวกรอง) ดึงครบทุกหน้าก่อน upsert ใน transaction เดียว และคง ID/ประวัติการติดตามเดิมไว้ CLI ใช้ `scripts/sync-graduates.php` หลังสำรองฐานข้อมูล

Tooltip ของ `[data-bs-toggle="tooltip"]` ใช้ `initTooltips()` ส่วนกลางใน `app.js` ควบคุมแบบ manual ผ่าน pointer และการ Tab ด้วยคีย์บอร์ด เพื่อไม่ให้ focus หลังคลิก/ปิด modal ค้างคำอธิบายไว้ ซ่อนเมื่อเมาส์ออก คลิก เลื่อนหน้า เปิด/ปิด modal เปลี่ยนแท็บ หรือกด Esc และรองรับปุ่มที่เพิ่มภายหลังผ่าน event delegation

หน้าผู้เรียนที่เชื่อม SDL อ่านข้อมูลส่วนตัวจาก `/students/{code}/profile?level={1|2|3}` หลังตรวจสิทธิ์ ใช้ `SdlStudentProfile` เลือกเฉพาะวันเกิด เพศ อาชีพ อีเมล โทรศัพท์ สัญชาติ ศาสนา ที่อยู่ และข้อมูลติดต่อครอบครัวเพื่อแสดงผล ไม่รับ/เก็บเลขบัตรประชาชนและไม่เขียนทับข้อมูลท้องถิ่น จำกัด timeout 5 วินาที ใช้ endpoint ใหม่จาก SDL_school โดยตรง (ไม่ต้องติดตั้งแพ็ก `deployment/sdl-student-profile` แบบเก่า)

หน้ารายชื่อผู้เรียนโหลดเบอร์จาก `GET /api/students/{id}/phone?term_id=...` ด้วย `initStudentPhones()` เฉพาะแถวใกล้หน้าจอและพร้อมกันสูงสุด 2 รายการ endpoint ตรวจสิทธิ์ในภาคเรียนก่อนเรียก profile API และคืนเฉพาะเบอร์ที่จัดรูปแบบแล้ว `SdlStudentPhone` แคชเฉพาะเบอร์ 5 นาทีใน `storage/cache/student-phones` แยกตามต้นทาง/token/รหัส/ระดับ ไม่เขียนฐานข้อมูลและไม่แคช payload ข้อมูลส่วนตัว หากไม่มีเบอร์หรือ API ขัดข้องใช้ค่าเดิมในระบบ

ลิงก์ Google Maps ใช้ `googleMapsUrl()` ใน `response.php` ตรวจคู่ละติจูด/ลองจิจูดและเข้ารหัส URL ก่อนเปิด `api=1&travelmode=driving&dir_action=navigate` โดยไม่กำหนดต้นทางตายตัว ปุ่มดูจุดเช็คอิน/ตำแหน่งใช้โหมด search แยกจากการนำทาง API แผนที่คืน `navigation_url` ของแต่ละบ้านให้ popup ใช้โดยตรง; พิกัดไม่ครบหรืออยู่นอกช่วงจะไม่สร้างลิงก์นำทาง

---

## 5. โดเมนข้อมูลและคำศัพท์

| คำ | ความหมาย |
|---|---|
| **สกร.** | สำนักงานส่งเสริมการเรียนรู้ (ต้นสังกัด) |
| **ศกร.ตำบล** | ศูนย์ส่งเสริมการเรียนรู้ระดับตำบล (1 อำเภอมีหลายศกร.) |
| **ภาคเรียน (term)** | `academic_terms` — มี 1/2569, 2/2569 ฯลฯ และมี "ภาคเรียนปัจจุบัน" ใน session |
| **กลุ่มเรียน / enrollment** | การลงทะเบียนของผู้เรียนในภาคเรียนหนึ่ง (`enrollments`) |
| **เยี่ยมบ้าน (home visit)** | การลงพื้นที่จริง บันทึกวันที่, GPS check-in, ระยะห่างจากบ้าน, รูปหลักฐาน, ผลประเมิน, ความเสี่ยง |
| **ติดตามช่วยเหลือ (followup)** | งานต่อเนื่องที่เกิดจากการเยี่ยม เช่น ประสานทุน, ติดตามการส่งงาน |
| **ระดับความเสี่ยง** | `low` / `medium` / `high` / `critical` |

ตารางสำคัญ: `users`, `roles`, `user_roles`, `academic_terms`, `learning_centers`, `organizations`, `students`, `student_addresses`, `student_contacts`, `enrollments`, `home_visits`, `visit_files`, `visit_plans` (เลิกใช้แล้ว), `followups`, `graduates`, `graduate_trackings`, `student_risks`, `assessment_questions`, `student_surveys`, `notifications`, `user_sessions`, `login_attempts`

> หมายเหตุ: ข้อมูลครู/ผู้เรียนจริงมาจาก API `SDL_school` (`/integrations/sdl`) ไม่ได้สร้างบัญชีจำลอง ระบบจึงไม่เก็บรหัสผ่านจริงของต้นทาง

---

## 6. Design System (สรุป)

ไฟล์หลัก: `public/assets/css/style.css` — เป็น single source of truth ของ UI

- **Brand navy**: `#04395e` (hover `#032c49`, light `#04395e`, subtle `#edf3f7`)
- **พาเลตประกอบ:** `#031d44` ตัวอักษรเข้ม, `#70a288` เขียว, `#dab785` ครีม, `#d5896f` ส้มอิฐ ใช้ร่วมกันทั้งปุ่ม badge กราฟและแผนที่ สี accent อ่อนต้องใช้ตัวอักษร `#031d44`; ข้อความสีบนพื้นขาวใช้เฉดเข้มจาก `--bs-*-text-emphasis`
- **Semantic**: success `#70a288`, info `#04395e`, warning `#dab785`, danger `#d5896f`
- **Neutral**: bg `#f5f7fa`, surface `#fff`, border `#e7ecf2`, text `#0f172a`/`#334155`/`#64748b`
- **Radius**: card 16px, control 10–12px, pill 999px
- **Theme**: light เท่านั้น (`<html data-bs-theme="light">`)
- Bootstrap ถูก re-theme ผ่าน `--bs-*` ให้ทั้งแอปเป็นน้ำเงินเข้มเดียวกัน (ห้าม hardcode สีฟ้า Bootstrap)
- ฟอนต์: Sarabun (Google Fonts) — และ THSarabunNew สำหรับ PDF
- Motion: สั้น ๆ (150–250ms), มี `prefers-reduced-motion` รองรับ
- รายละเอียดการใช้งาน/แนวทางเขียน UI: ดู [SKILL.md](SKILL.md)

---

## 7. หลักการทำงานของ UI (ต้องรักษาไว้)

- **Desktop (≥992px)**: sidebar ซ้าย 264px + top navbar 64px
- **Mobile (<992px)**: ซ่อน sidebar → bottom nav 5 ช่อง
  `หน้าแรก · ผู้เรียน · สำรวจผู้เรียน · เยี่ยมบ้าน · เพิ่มเติม`
  (ปุ่ม "เพิ่มเติม" เปิด offcanvas ที่รวม `แผนที่ผู้เรียน`, ติดตาม, รายงาน PDF และเมนูแอดมิน)
- **Lightbox**: คลิกรูปหลักฐานการเยี่ยมบ้าน → เปิดดูเต็มจอในหน้าเดิม (ไม่เปิดแท็บใหม่) ผ่าน `[data-lightbox]` ใน `app.js`
- **Flash message**: ผ่าน `getFlash()` แสดงใน `layouts/header.php`
- **Toast**: `showToast(type, message)` ใน `app.js`
- **CSRF**: ทุกฟอร์ม POST ต้องมี `csrfField()`; fetch ใช้ meta token
- **Responsive tables**: ตาราง `.records-table` เปลี่ยนเป็นการ์ดบนจอเล็ก (ดู CSS section 11)

---

## 7.1 ช่วงเวลาเปิด–ปิดกิจกรรม และสิทธิ์

- **อำเภอ (super_admin / admin)** เท่านั้นที่กำหนดช่วงเวลาเปิด–ปิดได้ ที่ `/centers/windows`
  - **กำหนดทั้งหมด (ใช้กับทุกศกร.)**: ตั้งเวลาเปิด/ปิดครั้งเดียวมีผลทุกศกร. (`action=apply_all_schedule`)
  - **ปรับรายศกร.**: เลือกหลายศกร.แล้วกำหนดแยกได้ (`action=save_selected`) พร้อมปุ่มเปิด/ปิดทั้งหมด หรือเปิด/ปิดเฉพาะประเภท
  - ช่องวันเวลาใช้ **ปฏิทินไทย พ.ศ.** (component `.thai-datetime` + hidden input) ไม่ใช้ `datetime-local` ของเบราว์เซอร์
- คอลัมน์ใน `learning_centers`: `visit_enabled`, `visit_open_at`, `visit_close_at`, `survey_enabled`, `survey_open_at`, `survey_close_at`
- **ครูตำบล (teacher)** บันทึก/สำรวจได้เฉพาะเมื่อหน้าต่างของศกร.นั้นเปิด (helper `canCreateVisitForCenter()` / `canCreateSurveyForCenter()`) แต่ยังดูข้อมูลย้อนหลังได้เสมอ
- **ยกเลิกบันทึก/การสำรวจ = อำเภอเท่านั้น** (`canCancelRecords()`) ครูจะไม่เห็นปุ่มยกเลิกและถูกปฏิเสธที่เซิร์ฟเวอร์
- การยกเลิกเป็นการ soft delete: เยี่ยมบ้าน → `home_visits.status='cancelled'` (ลบ `student_risks` ที่ผูกกับ visit นั้น), การสำรวจ → `student_surveys.cancelled_at/by`
- helper ที่เกี่ยวข้อง: `centerActivityState()`, `centerActivityOpen()`, `canCreateVisitForCenter()`, `canCreateSurveyForCenter()`, `canCancelRecords()`

## 7.2 ติดตามผู้สำเร็จการศึกษา (เมนูเดิม "ติดตามช่วยเหลือ")

- เมนูหลักเปลี่ยนเป็น **"ติดตามผู้สำเร็จการศึกษา"** (`/graduates`) — ฟีเจอร์ followups เดิมยังอยู่ที่ `/followups` (เข้าจาก dashboard)
- ดึงข้อมูลจาก SDL API endpoint **`graduates`** (มี `term` filter เช่น `1/2569`) ผ่าน `GraduateSyncService` แล้วเก็บในตาราง `graduates` (แมป `group_code` → `learning_centers`)
- ครูตำบลเห็นเฉพาะผู้สำเร็จการศึกษาใน `assigned_groups` ของตน (จาก snapshot ของครู), ผู้ดูแล/ผู้บริหารเห็นทั้งหมด
- ฟอร์มบันทึกผล: 3 หมวด (`study`, `career`, `development`) + ข้อความเพิ่มเติม (≤5,000 ตัวอักษร) + รูป 1 รูป
- รูปถูกย่อด้วย `ImageUploader` (GD, กว้าง ≤ `graduate_image_max_width` = 1000px, JPEG quality 70) เก็บที่ `public/uploads/graduates/`
- ประวัติการติดตามเป็น append-only ใน `graduate_trackings`
- หน้า `/graduates/{id}` แสดงผลตามสถานะ: **ยังไม่ติดตาม** → ฟอร์มบันทึกเต็มหน้า (ไม่แสดงประวัติ); **ติดตามแล้ว** → แสดงประวัติอย่างเดียว พร้อมปุ่มแก้ไขแต่ละรายการ (`/graduates/{id}/track/{trackingId}/edit`)
- หมวดผลลัพธ์แสดงเป็น tiles เลือกได้ (checkbox ซ่อน + label) — `study`, `career`, `development`

## 8. ข้อจำกัด/สิ่งที่ควรรู้

- เป็น light theme เท่านั้น (ไม่มี dark mode)
- ภาษาไทย: หลีกเลี่ยงการตัดคำที่ไม่เหมาะสม และคำนึงถึงความยาวข้อความบน bottom nav / ปุ่ม
- `visit_plans` ถูกยกเลิกแล้ว — ห้ามสร้าง flow วางแผนใหม่ ให้บันทึกเยี่ยมจากรายชื่อผู้เรียน (`/visits`)
- หน้าจอ PDF (`app/views/reports/*-pdf.php`) ใช้ mPDF + THSarabunNew — ห้ามเอา CSS ของเว็บไปใช้ปนกัน
- ข้อมูลส่วนตัวผู้เรียนเป็นข้อมูลอ่อนไหว: อย่า log/ส่งออกเกินจำเป็น

---

## 9. เอกสารที่เกี่ยวข้อง

| ไฟล์ | ใช้ทำอะไร |
|---|---|
| [README.md](README.md) | คู่มือติดตั้ง/สถาปัตยกรรม/ฐานข้อมูล/ฟีเจอร์ฉบับเต็ม |
| [AGENTS.md](AGENTS.md) | คู่มือสำหรับ AI agent ที่เข้ามาแก้โค้ด |
| [CONTRIBUTING.md](CONTRIBUTING.md) | วิธี contribute, มาตรฐานโค้ด, PR checklist |
| [SKILL.md](SKILL.md) | skill สำหรับงาน UI/UX ของโปรเจกต์นี้ |
