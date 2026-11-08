# ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. (SENA Care)
## Student Care & Home Visit Tracking Management System

> ระบบสารสนเทศสำหรับติดตามและเยี่ยมบ้านผู้เรียน สกร. ระดับอำเภอเสนา จังหวัดพระนครศรีอยุธยา พัฒนาด้วยสถาปัตยกรรม **PHP Native (MVC) + MySQL + Bootstrap 5 + Leaflet Map + Chart.js** ออกแบบตามมาตรฐานความปลอดภัยทางไซเบอร์และการจัดการศึกษาตลอดชีวิต

---

### สารบัญ (Table of Contents)
1. [ภาพรวมระบบ (System Overview)](#1-ภาพรวมระบบ-system-overview)
2. [เทคโนโลยีและสถาปัตยกรรม (Architecture & Tech Stack)](#2-เทคโนโลยีและสถาปัตยกรรม-architecture--tech-stack)
3. [โครงสร้างไดเรกทอรี (Directory Structure)](#3-โครงสร้างไดเรกทอรี-directory-structure)
4. [ข้อกำหนดและการติดตั้งบน MAMP (Installation Guide)](#4-ข้อกำหนดและการติดตั้งบน-mamp-installation-guide)
5. [บัญชีผู้ใช้งานเริ่มต้นสำหรับทดสอบ (Default Accounts)](#5-บัญชีผู้ใช้งานเริ่มต้นสำหรับทดสอบ-default-accounts)
6. [โครงสร้างฐานข้อมูล (Database Schema)](#6-โครงสร้างฐานข้อมูล-database-schema)
7. [ฟังก์ชันการทำงานหลัก (Core Features)](#7-ฟังก์ชันการทำงานหลัก-core-features)
8. [ความปลอดภัยของระบบ (Cybersecurity & Best Practices)](#8-ความปลอดภัยของระบบ-cybersecurity--best-practices)
9. [การสำรองและกู้คืนข้อมูล (Backup & Restore Guide)](#9-การสำรองและกู้คืนข้อมูล-backup--restore-guide)
10. [การทดสอบระบบ (Automated Tests & Quality Assurance)](#10-การทดสอบระบบ-automated-tests--quality-assurance)
11. [เอกสารเพิ่มเติมและ UI Design System](#11-เอกสารเพิ่มเติมและ-ui-design-system)

---

### 1. ภาพรวมระบบ (System Overview)
ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. ถูกออกแบบขึ้นเพื่อแก้ปัญหาการติดตามผู้เรียนที่เสี่ยงต่อการหลุดออกจากระบบการศึกษา โดยมีเป้าหมายหลัก:
- **จัดเก็บข้อมูลผู้เรียนอย่างเป็นระบบ**: ข้อมูลส่วนตัว ที่อยู่พร้อมพิกัด GPS ข้อมูลผู้ติดต่อฉุกเฉิน และประวัติการลงทะเบียนเรียนรายภาคเรียน
- **วางแผนและบริหารการเยี่ยมบ้าน**: กำหนดตารางนัดหมาย บันทึกการลงพื้นที่จริง พร้อมระบบตรวจสอบพิกัดเช็คอินเทียบกับพิกัดบ้านผู้เรียน (Distance & Haversine calculation)
- **ระบบแผนที่พิกัด GIS เชิงโต้ตอบ**: แสดงบ้านผู้เรียนบนแผนที่ Leaflet.js แบบแยกสีตามระดับความเสี่ยง พร้อมลิงก์เปิด Google Maps นำทาง
- **แบบประเมินสภาพความเป็นอยู่และวิเคราะห์ความเสี่ยง**: บันทึกคำตอบแบบสอบถาม คำนวณคะแนนความเสี่ยง (Low, Medium, High, Critical) อัตโนมัติ
- **ระบบติดตามต่อเนื่อง (Follow-up Tasks)**: มอบหมายงานช่วยเหลือ ส่งต่อปัญหา กำหนดวันครบกำหนด และติดตามผล
- **รายงานเชิงสถิติและส่งออกข้อมูล**: กราฟแสดงสถิติ Chart.js, รายงานผลการเยี่ยมบ้านราย ศกร.ตำบล, และส่งออกไฟล์ CSV พร้อม UTF-8 BOM สำหรับเปิดใน Microsoft Excel ภาษาไทยได้ทันที

---

### 2. เทคโนโลยีและสถาปัตยกรรม (Architecture & Tech Stack)

| ส่วนประกอบ | เทคโนโลยีที่ใช้ | รายละเอียด |
|---|---|---|
| **Backend Runtime** | PHP 8.2+ Native | MVC Pattern (Model-View-Controller) ปราศจาก Heavy Frameworks |
| **Database** | MySQL 8.0+ / MariaDB | InnoDB Engine, `utf8mb4_unicode_ci`, Foreign Key Constraints |
| **Database Access** | PDO (PHP Data Objects) | Singleton Connection, Prepared Statements 100%, Transactions |
| **Frontend UI** | Bootstrap 5.3.3 + Bootstrap Icons | Responsive UI (Mobile-First), Thai Typography (Sarabun font) |
| **Interactive Map** | Leaflet.js 1.9.4 | OpenStreetMap Tiles, Custom DivIcon Markers, Popup Preview |
| **Data Visualization** | Chart.js 4.4 | Doughnut & Bar Charts แสดงความก้าวหน้าและการกระจายตัวของความเสี่ยง |
| **Frontend Scripting** | Vanilla ES6 JavaScript | Fetch API with CSRF token auto-injection, HTML5 Geolocation API |
| **Web Server** | Apache (MAMP Environment) | Mod_rewrite, Front Controller Routing (`public/index.php`) |

---

### 3. โครงสร้างไดเรกทอรี (Directory Structure)

```
sena_care/
├── .htaccess                       # Root URL Rewrite ไปยัง public/
├── index.php                       # Root Fallback Redirector
├── manifest.json                   # PWA Manifest Configuration
├── README.md                       # เอกสารคู่มือระบบ
│
├── app/                            # Application Core (MVC)
│   ├── config/                     # ไฟล์ตั้งค่าระบบ
│   │   ├── config.php              # App Settings, URLs, Session, Security
│   │   └── database.php            # PDO Database Singleton
│   ├── controllers/                # Controller Handlers
│   │   ├── AuthController.php      # จัดการ Login, Logout
│   │   ├── DashboardController.php # หน้าภาพรวมและ API Stats
│   │   ├── StudentController.php   # บันทึก ค้นหา แก้ไข นำเข้าผู้เรียน
│   │   ├── MapController.php       # หน้าแผนที่ GIS และ API พิกัด
│   │   ├── VisitController.php     # แผนเยี่ยม บันทึกเช็คอิน ประเมินผล
│   │   ├── FollowupController.php  # จัดการงานติดตามช่วยเหลือ
│   │   ├── ReportController.php    # สรุปสถิติและส่งออกรายงาน CSV
│   │   ├── UserController.php      # จัดการบัญชีผู้ใช้งานระบบ
│   │   └── SettingController.php   # ตั้งค่าภาคเรียน และ ศกร.ตำบล
│   ├── helpers/                    # ฟังก์ชันช่วยเหลือส่วนกลาง
│   │   ├── auth.php                # ตรวจสอบสิทธิ์ผู้ใช้, Role, Session
│   │   ├── csrf.php                # CSRF Token Generator & Validator
│   │   ├── response.php            # Sanitization (e), JSON, Flash, Badges
│   │   └── validation.php          # Request Input Validation Engine
│   ├── middleware/                 # มิดเดิลแวร์คัดกรองคำขอ
│   │   ├── AuthMiddleware.php      # บังคับการล็อกอิน
│   │   └── RoleMiddleware.php      # คัดกรองบทบาทและสิทธิ์ผู้ใช้
│   ├── models/                     # โมเดลฐานข้อมูล (Data Access Layer)
│   │   ├── User.php                # ผู้ใช้งานและระบบล็อกอิน
│   │   ├── AcademicTerm.php        # ภาคเรียนการศึกษา
│   │   ├── Organization.php        # สถานศึกษา/หน่วยงาน สกร.
│   │   ├── LearningCenter.php      # ศกร.ระดับตำบล
│   │   ├── Student.php             # ข้อมูลผู้เรียน
│   │   ├── StudentAddress.php      # ที่อยู่และพิกัด GPS ผู้เรียน
│   │   ├── StudentContact.php      # ข้อมูลผู้ติดต่อและผู้ปกครอง
│   │   ├── Enrollment.php          # การลงทะเบียนเรียนรายภาคเรียน
│   │   ├── VisitPlan.php           # แผนการเยี่ยมบ้าน
│   │   ├── HomeVisit.php           # บันทึกผลการเยี่ยมบ้าน
│   │   └── Followup.php            # งานติดตามช่วยเหลือต่อเนื่อง
│   ├── services/                   # Business Logic Layer
│   │   └── AuthService.php         # การตรวจสอบรหัสผ่านและ Audit Log
│   └── views/                      # การแสดงผล (View Templates)
│       ├── layouts/                # Template แม่แบบ (Header, Sidebar, Navbar, Footer)
│       ├── auth/                   # หน้า Login
│       ├── dashboard/              # หน้าภาพรวม (Dashboard + Charts)
│       ├── students/               # หน้ารายชื่อ, รายละเอียด, เพิ่ม, แก้ไข, Import
│       ├── map/                    # หน้าแผนที่แสดงตำแหน่งบ้านผู้เรียน (GIS Map)
│       ├── visits/                 # รายการเยี่ยมบ้าน, วางแผน, ฟอร์มบันทึกเช็คอิน
│       ├── followups/              # หน้ารายการงานติดตามช่วยเหลือ
│       ├── reports/                # หน้ารายงานสรุปและส่งออก
│       ├── users/                  # หน้าจัดการผู้ใช้งานระบบ
│       ├── settings/               # หน้าจัดการภาคเรียน และ ศกร.ตำบล
│       └── errors/                 # หน้าข้อผิดพลาด 403, 404, 500
│
├── database/                       # โครงสร้างฐานข้อมูลและข้อมูลเริ่มต้น
│   ├── database.sql                # DDL สคริปต์สร้างตารางทั้ง 22 ตาราง
│   └── seed.sql                    # ไม่มีข้อมูลบุคคลจำลอง ใช้ API เท่านั้น
│
├── public/                         # Public Document Root
│   ├── .htaccess                   # Front Controller Routing
│   ├── index.php                   # Entry Point หลักของโปรแกรม
│   ├── assets/                     # Static Assets
│   │   ├── css/style.css           # สไตล์ชีทตกแต่งแบบโมเดิร์น
│   │   ├── js/app.js               # สคริปต์กลาง Geolocation, CSRF, Map
│   │   └── images/                 # ไอคอนและรูปภาพประกอบ
│   └── uploads/                    # ไดเรกทอรีสำหรับอัปโหลดไฟล์ (Write Permission)
│       ├── avatars/                # รูปโปรไฟล์ผู้ใช้งาน
│       ├── students/               # รูปถ่ายผู้เรียน
│       ├── visits/                 # ภาพถ่ายการลงพื้นที่เยี่ยมบ้าน
│       └── documents/              # เอกสารแนบ
│
└── storage/                        # ไดเรกทอรีสำหรับข้อมูลระบบภายใน
    ├── backups/                    # ไฟล์สำรองฐานข้อมูล SQL
    ├── logs/                       # ไฟล์บันทึก Error Logs
    └── private_uploads/            # ไฟล์ส่วนตัวที่มีการจำกัดสิทธิ์เข้าถึง
```

---

### 4. ข้อกำหนดและการติดตั้งบน MAMP (Installation Guide)

#### ความต้องการของระบบ (System Requirements)
- **เว็บเซิร์ฟเวอร์**: Apache 2.4+ (มี `mod_rewrite` เปิดใช้งาน)
- **ภาษา PHP**: PHP 8.2 หรือสูงกว่า (เปิด Extension: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`)
- **ฐานข้อมูล**: MySQL 8.0+ หรือ MariaDB 10.4+
- **สภาพแวดล้อมจำลอง**: MAMP / MAMP PRO สำหรับ macOS หรือ XAMPP / Laragon

#### ขั้นตอนการติดตั้งบน MAMP (macOS)
1. **คัดลอกโฟลเดอร์โครงการ**:
   นำโฟลเดอร์ `sena_care` ไปวางไว้ที่ Document Root ของ MAMP:
   ```bash
   /Applications/MAMP/htdocs/sena_care
   ```

2. **เปิดบริการ MAMP**:
   - เริ่มการทำงานของ Apache Server (Port 8888)
   - เริ่มการทำงานของ MySQL Server (Port 8889)

3. **สร้างฐานข้อมูลและนำเข้าข้อมูลเริ่มต้น**:
   เปิด Terminal และรันคำสั่ง MySQL เพื่อสร้างฐานข้อมูลและนำเข้าตารางพร้อมข้อมูล Seed:
   ```bash
   /Applications/MAMP/Library/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 -e "CREATE DATABASE IF NOT EXISTS sena_care CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   /Applications/MAMP/Library/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 sena_care < /Applications/MAMP/htdocs/sena_care/database/database.sql
   /Applications/MAMP/Library/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 sena_care < /Applications/MAMP/htdocs/sena_care/database/seed.sql
   ```

4. **ตรวจสอบการตั้งค่าฐานข้อมูลใน `app/config/config.php`**:
   ```php
   'db_host'     => '127.0.0.1',
   'db_port'     => 8889,            // พอร์ต MySQL ของ MAMP
   'db_name'     => 'sena_care',
   'db_user'     => 'root',
   'db_password' => 'root',
   'app_url'     => 'http://localhost:8888/sena_care',
   ```

5. **กำหนดสิทธิ์ไดเรกทอรีอัปโหลด**:
   ```bash
   chmod -R 775 /Applications/MAMP/htdocs/sena_care/public/uploads
   chmod -R 775 /Applications/MAMP/htdocs/sena_care/storage
   ```

6. **เปิดใช้งานระบบผ่านเบราว์เซอร์**:
   เข้าสู่ URL: [http://localhost:8888/sena_care](http://localhost:8888/sena_care) หรือ [http://localhost:8888/sena_care/login](http://localhost:8888/sena_care/login)

---

### 5. บัญชีผู้ใช้งาน

ระบบใช้ข้อมูลครูและผู้เรียนจริงจาก SDL_school API เท่านั้น ไม่มีการสร้างบัญชีครูหรือนักศึกษาจำลอง บัญชี super admin ของระบบเดิมคงอยู่และแก้ไขข้อมูลส่วนตัว ชื่อผู้ใช้ และรหัสผ่านได้ที่ ผู้ใช้งาน → แก้ไขบัญชีของฉัน ครูใช้รหัสผ่านเดียวกับ SDL_school และจัดการข้อมูลบัญชีที่ต้นทาง

ไม่ต้องรัน `database/seed.sql` ไฟล์นี้ไม่มีข้อมูลตัวอย่างแล้ว สำหรับการติดตั้งให้ใช้โครงสร้างฐานข้อมูลและการซิงก์ API ที่ได้รับอนุญาต

---

### 6. โครงสร้างฐานข้อมูล (Database Schema)

ฐานข้อมูลประกอบด้วยตารางมาตรฐานจำนวน 22 ตาราง มีการกำหนด Primary Keys, Foreign Keys (Cascading / Restrict), และ Indexes เพื่อประสิทธิภาพในการค้นหา:

1. **`organizations`**: ข้อมูลหน่วยงานสถานศึกษา (สกร.ระดับอำเภอเสนา)
2. **`academic_terms`**: ภาคเรียนการศึกษา (เช่น 1/2569, 2/2569) มีสถานะ `is_current`
3. **`learning_centers`**: ศูนย์การเรียนรู้ระดับตำบล (ศกร.ระดับตำบล) ในเขตอำเภอเสนา
4. **`users`**: ผู้ใช้งานระบบ (ครู, ผู้บริหาร, เจ้าหน้าที่, แอดมิน)
5. **`roles`**: สิทธิ์และคำนิยามบทบาทผู้ใช้
6. **`user_roles`**: ความสัมพันธ์ระหว่างผู้ใช้และบทบาท
7. **`user_sessions`**: จัดการเซสชันและติดตามการเข้าสู่ระบบ
8. **`audit_logs`**: บันทึกประวัติการกระทำสำคัญในระบบ (Audit Trail)
9. **`login_attempts`**: บันทึกการพยายามเข้าสู่ระบบเพื่อป้องกัน Brute-Force
10. **`students`**: ข้อมูลทะเบียนประวัติผู้เรียน (รหัสนักศึกษา, เลขบัตรประชาชน, วันเกิด, เบอร์โทร)
11. **`student_addresses`**: ข้อมูลที่อยู่บ้านผู้เรียน พร้อมพิกัด `latitude`, `longitude` และจุดสังเกต
12. **`student_contacts`**: ข้อมูลผู้ปกครองและผู้ติดต่อฉุกเฉิน
13. **`enrollments`**: การลงทะเบียนเรียนของผู้เรียนในแต่ละภาคเรียน ระดับการศึกษา และครูผู้รับผิดชอบ
14. **`visit_plans`**: แผนนัดหมายการเยี่ยมบ้านล่วงหน้า
15. **`home_visits`**: บันทึกผลการเยี่ยมบ้านจริง พิกัดเช็คอิน ระยะห่าง สรุปผล และคำแนะนำ
16. **`assessment_forms`**: แบบฟอร์มประเมินการเยี่ยมบ้าน
17. **`assessment_questions`**: ข้อคำถามประเมินด้านครอบครัว เศรษฐกิจ ที่อยู่อาศัย และพฤติกรรม
18. **`assessment_answers`**: คำตอบและคะแนนจากการประเมินในแต่ละครั้ง
19. **`student_risks`**: สรุปผลระดับความเสี่ยงของผู้เรียน (low, medium, high, critical)
20. **`followups`**: งานติดตามช่วยเหลือผู้เรียนต่อเนื่อง และสถานะการดำเนินการ
21. **`visit_files`**: ไฟล์รูปภาพและหลักฐานการลงพื้นที่เยี่ยมบ้าน
22. **`notifications`**: ระบบแจ้งเตือนงานและการนัดหมายสำหรับผู้ใช้

---

### 7. ฟังก์ชันการทำงานหลัก (Core Features)

#### 1. แดชบอร์ดอัจฉริยะ (Smart Executive & Teacher Dashboard)
- แสดงตัวเลขสถิติสำคัญแบบเรียลไทม์: ผู้เรียนทั้งหมด, เยี่ยมบ้านแล้ว, ยังไม่เยี่ยม, กลุ่มเสี่ยงสูง, งานติดตามค้าง
- กราฟแสดงสัดส่วนความก้าวหน้าการเยี่ยมบ้าน (Doughnut Chart)
- กราฟแสดงจำนวนผู้เรียนแยกตามระดับการศึกษา (Bar Chart)
- กราฟแสดงการกระจายตัวของระดับความเสี่ยง (Risk Distribution Chart)
- รายการแผนเยี่ยมบ้านวันนี้ พร้อมปุ่มคลิกโทรออกและปุ่มเปิด Google Maps นำทาง
- กล่องแสดงงานติดตามช่วยเหลือเร่งด่วนที่ใกล้ถึงกำหนด

#### 2. ทะเบียนและประวัติผู้เรียน (Student Care Profile)
- ค้นหาผู้เรียนตามชื่อ รหัส เบอร์โทร ศกร.ตำบล ระดับชั้น หรือสถานะความเสี่ยง
- แสดงโปรไฟล์ผู้เรียนแบบครบวงจร: ข้อมูลส่วนตัว, ที่อยู่และพิกัด, ผู้ปกครอง, ประวัติการเรียน
- Timeline แสดงประวัติการเยี่ยมบ้านในอดีตทุกครั้งพร้อมภาพถ่าย
- ระบบนำเข้าข้อมูลผู้เรียนจากไฟล์ CSV เพื่อความสะดวกในการเริ่มต้นภาคเรียนใหม่

#### 3. ระบบแผนที่พิกัดบ้านผู้เรียน (GIS Interactive Map)
- แสดงหมุดตำแหน่งบ้านผู้เรียนบนแผนที่ Leaflet.js
- ปักหมุดสีตามระดับความเสี่ยงของผู้เรียน:
  - 🟢 **สีเขียว**: ผู้เรียนปกติ/เยี่ยมแล้ว (เสี่ยงต่ำ)
  - 🟡 **สีเหลือง**: เสี่ยงปานกลาง
  - 🟠 **สีส้ม**: เสี่ยงสูง
  - 🔴 **สีแดง**: วิกฤต/ยังไม่เยี่ยม
- ระบบกรองข้อมูลบนแผนที่ตาม ศกร.ตำบล, ระดับชั้น, ระดับความเสี่ยง และสถานะการเยี่ยม
- เมื่อคลิกที่หมุด แสดงข้อมูลสรุปของผู้เรียน ระยะทาง และปุ่มเปิดระบบนำทาง Google Maps

#### 4. บันทึกผลการเยี่ยมบ้านและเช็คอิน GPS (Home Visit Recording & Geolocation)
- ระบบดึงพิกัดปัจจุบันผ่าน Browser HTML5 Geolocation แบบความแม่นยำสูง (High Accuracy)
- คำนวณระยะห่างระหว่างจุดที่ครูยืนเช็คอิน กับพิกัดบ้านที่ลงทะเบียนไว้ (Haversine Formula) แจ้งเตือนระยะเป็นเมตร
- แผนที่ย่อแสดงจุดเช็คอินเปรียบเทียบกับพิกัดบ้านผู้เรียน
- บันทึกภาพถ่ายการลงพื้นที่ได้หลายภาพพร้อมกัน (Multi-photo upload)
- ทำแบบประเมินสภาพความเป็นอยู่รายด้าน (ครอบครัว, เศรษฐกิจ, การเดินทาง, ความพร้อมในการเรียน)
- ระบุระดับความเสี่ยงและสร้างงานติดตามช่วยเหลือ (Follow-up) อัตโนมัติ

#### 5. งานติดตามช่วยเหลือต่อเนื่อง (Intervention & Follow-up Tasks)
- มอบหมายงานติดตามผู้เรียน เช่น "มอบทุนการศึกษา", "ประสานสาธารณสุข", "แนะแนวการเรียน"
- กำหนดระดับความเร่งด่วนและวันครบกำหนด (Due Date)
- ปรับเปลี่ยนสถานะงานได้ง่าย (รอดำเนินการ ➔ กำลังดำเนินการ ➔ เสร็จสิ้น)

#### 6. รายงานสรุปและส่งออกข้อมูล (Reports & Export)
- รายงานสรุปสถิติผลการเยี่ยมบ้านแยกตาม ศกร.ตำบล เปอร์เซ็นต์ความสำเร็จ
- รายชื่อผู้เรียนกลุ่มเสี่ยงสูงและวิกฤตที่ต้องการการดูแลเป็นพิเศษ
- ส่งออกไฟล์ CSV แยกประเภท (รายชื่อผู้เรียน, กลุ่มเสี่ยง, สรุปรายตำบล) พร้อมรองรับ UTF-8 BOM เปิดใน Microsoft Excel ภาษาไทยได้ทันทีไม่เพี้ยน
- รองรับการสั่งพิมพ์รายงาน (Print View) ที่ซ่อนเมนูและจัดหน้าเอกสารให้สวยงาม

#### 7. การตั้งค่าระบบ (Settings & Configurations)
- สลับภาคเรียนการศึกษาที่ต้องการดูข้อมูลได้สะดวกรวดเร็ว
- บริหารจัดการศูนย์การเรียนรู้ระดับตำบล (ศกร.ตำบล)
- จัดการบัญชีผู้ใช้งาน เพิ่ม แก้ไข กำหนดบทบาท และระงับการใช้งาน

---

### 8. ความปลอดภัยของระบบ (Cybersecurity & Best Practices)

1. **CSRF Protection (Cross-Site Request Forgery)**:
   ทุกคำขอ POST, PUT, DELETE ต้องส่ง `csrf_token` ที่สร้างจาก `random_bytes(32)` และตรวจสอบผ่านมิดเดิลแวร์ สำหรับ Ajax/Fetch มีการดึง Token จาก `<meta name="csrf-token">` และส่งใน Header `X-CSRF-TOKEN` อัตโนมัติ

2. **SQL Injection Prevention**:
   ใช้ PDO Prepared Statements พร้อม Parameter Binding 100% ในทุกคำสั่ง SQL ปราศจากการต่อ String ตัวแปรเข้ากับ Query

3. **XSS Protection (Cross-Site Scripting)**:
   ฟังก์ชันช่วย `e($string)` ทำการครอบ `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')` ในทุกจุดที่มีการ Render ข้อมูลออกสู่ HTML

4. **IDOR Defense (Insecure Direct Object Reference)**:
   ครูผู้สอนจะถูกตรวจสอบสิทธิ์ผ่านฟังก์ชัน `canAccessStudent($studentId)` เพื่อป้องกันไม่ให้ครูต่างตำบลเข้าถึงหรือแก้ไขข้อมูลผู้เรียนที่ตนเองไม่ได้เป็นผู้รับผิดชอบ

5. **Brute-Force Attack Prevention**:
   ระบบบันทึกความพยายามล็อกอินใน `login_attempts` หากระบุรหัสผ่านผิดเกิน 5 ครั้ง จะทำการระงับการล็อกอินชั่วคราวเป็นเวลา 15 นาที

6. **Session Fixation Defense**:
   ใช้คำสั่ง `session_regenerate_id(true)` ทันทีเมื่อผู้ใช้ล็อกอินสำเร็จ เพื่อทำลาย Session ID เดิมและสร้างใหม่

7. **Secure File Upload**:
   ตรวจสอบนามสกุลไฟล์ MIME Type และขนาดไฟล์ จำกัดเฉพาะนามสกุลภาพที่ปลอดภัย (`jpg`, `jpeg`, `png`, `webp`) และเปลี่ยนชื่อไฟล์แบบสุ่มด้วย `uniqid()` ป้องกันการรันโค้ดอันตราย

---

### 9. การสำรองและกู้คืนข้อมูล (Backup & Restore Guide)

#### การสำรองฐานข้อมูล (Backup)
รันคำสั่ง `mysqldump` ผ่าน Terminal เพื่อส่งออกฐานข้อมูลเป็นไฟล์ `.sql`:
```bash
/Applications/MAMP/Library/bin/mysqldump -u root -proot -h 127.0.0.1 -P 8889 sena_care > /Applications/MAMP/htdocs/sena_care/storage/backups/backup_$(date +%Y%m%d_%H%M%S).sql
```

#### การกู้คืนฐานข้อมูล (Restore)
นำเข้าไฟล์สำรองเพื่อกู้คืนข้อมูล:
```bash
/Applications/MAMP/Library/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 sena_care < /Applications/MAMP/htdocs/sena_care/storage/backups/backup_20261009.sql
```

---

### 10. การทดสอบระบบ (Automated Tests & Quality Assurance)

ระบบผ่านการทดสอบแบบ End-to-End ครบทุกโมดูล:
- **Syntax Lint**: ตรวจสอบความถูกต้องของไวยากรณ์ PHP ทั้ง 54 ไฟล์ (`php -l`) ผ่าน 100%
- **Endpoint Test**: ตรวจสอบการทำงานของ Controller ทุกตัว (Dashboard, Students, Map, Visits, Followups, Reports, Users, Settings) ให้ผลลัพธ์ `[PASS]` และสร้าง HTML ได้สมบูรณ์
- **Database Integrity**: ตรวจสอบ Foreign Keys, Referential Integrity, และการรองรับโหมด `ONLY_FULL_GROUP_BY` ของ MySQL 8.0 ผ่าน 100%

---
*ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. (SENA Care) — พัฒนาเพื่อการศึกษาไทยที่ทั่วถึงและเท่าเทียม*

### เชื่อมต่อ SDL_school (ข้อมูลจริง)

ผู้ดูแลระบบสูงสุดเปิดเมนู **เชื่อมต่อ SDL_school** (`/integrations/sdl`) เพื่อบันทึก API token ของ `https://krumost.com/SDL_school/` และกดดึงข้อมูลล่าสุด ระบบนำเข้ารายชื่อผู้เรียนในภาคเรียนปัจจุบัน กลุ่มเรียน และครูผู้รับผิดชอบตาม `assigned_groups` พร้อมสร้างบัญชีครูสำหรับ SENA Care โดยตรวจรหัสผ่านกับต้นทางทุกครั้งที่ล็อกอิน ไม่เก็บรหัสผ่านจริงหรือค่าแฮชของต้นทาง

- ติดตั้งตารางเชื่อมต่อด้วย `database/sdl_integration.sql` ก่อนเปิดใช้
- ซอร์สตัวติดตั้ง API ต้นทางและคู่มืออยู่ใน `deployment/sdl-school-api/` และ `deployment/sdl-school-api/README.md` ไฟล์ ZIP ที่สร้างจากซอร์สเก็บแยกจาก Git
- ค่าเชื่อมต่อจริงเก็บใน `app/config/sdl.local.php` ซึ่งปฏิเสธ HTTP และไม่ควรนำเข้า version control; ดูตัวอย่าง `app/config/sdl.example.php`
- ซิงก์ผ่าน CLI ได้ด้วย `php scripts/sdl-sync.php` การซิงก์ซ้ำใช้รหัสต้นทางเดิม ไม่สร้างนักศึกษา/กลุ่มซ้ำ และไม่เขียนทับที่อยู่ โทรศัพท์ หรือประวัติเยี่ยมบ้านที่บันทึกใน SENA Care
- ชื่อบัญชี/อีเมลที่ชนกับบัญชีเดิมจะยกเลิกทั้งรอบเพื่อให้ตรวจสอบก่อน นักศึกษาที่พ้นจากรายชื่อปัจจุบันจะถูกปิดสถานะในภาคเรียนที่ซิงก์ ส่วนประวัติภาคเรียนก่อนยังเก็บไว้
- กลุ่มที่มีครูรับผิดชอบมากกว่าหนึ่งคนจะเชื่อมครูทุกคนและให้สิทธิ์เข้าถึงผู้เรียนตามต้นทาง โดยไม่เลือกครูหลักแทนผู้ดูแล
- หากต้นทางเชื่อมต่อไม่ได้ บัญชีที่เชื่อมจาก SDL_school จะเข้าสู่ระบบไม่ได้จนกว่าการเชื่อมต่อกลับมา ไม่ย้อนกลับไปใช้รหัสผ่านสำรองในเครื่อง
- โหมดทดสอบฐานข้อมูล MySQL เดิมในเครื่องมี gateway เฉพาะ backend ที่ `sdl-api/` ซึ่งปิดอยู่เมื่อเลือกเว็บจริง ไม่ใช้ฐานข้อมูลสำเนาแทนเว็บจริงโดยอัตโนมัติ

ทดสอบปลายทาง: `php tests/sdl-integration.php` ใช้ฐานข้อมูลทดสอบชื่อสุ่มแยกจากข้อมูลจริงและลบทิ้งเมื่อจบ ส่วนชุดทดสอบ API ต้นทางอยู่ใน SDL_school/laravel-app/tests/Feature

### ใช้รายชื่อนักศึกษาจาก API เท่านั้น

ตั้งค่า `students_api_only` เป็น true แล้ว ระบบปิดการเพิ่มผู้เรียนเองและนำเข้า CSV/Excel ให้ใช้รายชื่อจาก SDL_school เป็นข้อมูลกลาง ข้อมูลตัวอย่าง 30 คนถูกนำออกหลังสำรองฐานข้อมูล เมื่อวันที่ 19 ตุลาคม 2569 เหลือนักศึกษาจาก API จำนวน 631 คน ณ รอบซิงก์นั้น

Token ของการเชื่อมต่อจริงชื่อ sena-care ในอำเภอเสนาถูกเปลี่ยนเป็นไม่มีวันหมดอายุ (`expires_at = NULL`) และยังใช้ token เดิม คู่มือการสร้างใหม่ใช้ `--no-expiry` แทน `--expires-in-days=365` และผู้ดูแลต้นทางยังเพิกถอน token ได้ตามปกติ แพ็กเกจอัปเดตและตัวติดตั้งอยู่ใน `deployment/sdl-no-expiry/`

### แบบประเมินเยี่ยมบ้านและรายงาน PDF รายครู

- แบบประเมินใช้งาน 18 ข้อ แสดงเฉพาะคำถามและแบบฟอร์มสถานะ active สคริปต์สร้างฐานข้อมูลเรียกซ้ำได้โดยไม่เพิ่มคำถามชุดเดิม
- ซ่อมคำถามซ้ำด้วย `php scripts/repair-survey-duplicates.php` หลังสำรองฐานข้อมูล สคริปต์ปิดใช้เฉพาะข้อที่มีนิยามเหมือนกันทุกส่วน ไม่ลบคำตอบย้อนหลัง คำตอบเหมือนกันแสดงครั้งเดียว ส่วนคำตอบเดิมที่ต่างกันแสดงพร้อมคำอธิบาย
- การแนบรูปบันทึก file_path, MIME, ขนาดและผู้แนบ ตรวจชนิดภาพจริง รองรับ JPG/PNG/WebP ไม่เกิน 10 MB ต่อรูป หากขั้นตอนใดผิดพลาดจะ rollback ฐานข้อมูลและไฟล์ พร้อมคืนข้อมูลที่กรอกไว้ (ต้องเลือกรูปใหม่)
- เปิด **รายงานเยี่ยมบ้านรายครู** หรือ `/reports/teachers` เลือกครู ภาคเรียน และชื่อผู้อำนวยการถ้าต้องการ แล้วดาวน์โหลด PDF ได้ทันที ครูดูรายงานของตนเอง ผู้ดูแลและผู้บริหารเลือกครูได้
- ผู้ดูแลระบบสูงสุดกำหนดชื่อ/ตำแหน่งผู้อำนวยการและผู้รายงาน ข้อความผู้รับรายงาน รวมถึงเลือกแสดงช่องลงนามและวันที่ได้ใน **ตั้งค่า** (`/settings`) ใช้กับ PDF รายบุคคลและรายครู เว้นชื่อผู้รายงานว่างเพื่อใช้ครูเจ้าของบันทึก ชื่อผู้อำนวยการในหน้ารายงานรายครูยังเปลี่ยนเฉพาะรายงานนั้นได้
- ค่าผู้ลงนามเก็บใน `app/config/report.local.php` ซึ่ง Git ไม่ติดตาม ไม่ต้องเพิ่มตารางหรือ migration ให้ PHP เขียนโฟลเดอร์ `app/config` ได้ และสำรองไฟล์นี้เมื่อย้ายเซิร์ฟเวอร์ ห้ามเปิดโฟลเดอร์ config ผ่าน HTTP (มี `.htaccess` ป้องกันอยู่แล้ว)
- ทดสอบค่ารายงานและ PDF โดยไม่ใช้ฐานข้อมูลจริง: `php tests/report-settings.php`
- PDF ขนาด A4 ฝังฟอนต์ TH Sarabun New มีสรุปจำนวนคน/ครั้งที่เยี่ยม ความเสี่ยงล่าสุดต่อคน รายการเยี่ยม คำตอบแบบประเมิน แนวทางช่วยเหลือ งานติดตาม รูปแนบทุกรูป และช่องลงนามผู้รายงาน/ผู้อำนวยการ บันทึกที่ไม่มีรูปจะแจ้งตามจริง
- ติดตั้งไลบรารีด้วย `composer install --no-dev --prefer-dist` (ใช้ PHP ที่มี mbstring และ gd) ให้ PHP เขียน `storage/pdf-cache` และ `public/uploads/visits` ได้ ไฟล์ `resources/fonts` ต้องติดตั้งพร้อมแอป รายงานสร้างภายในเครื่องเซิร์ฟเวอร์
- หน้าแบบประเมินและรายงานรองรับมือถือ เปิดการซูม และมีเมนูรายงาน PDF ในเมนูเพิ่มเติม
- ทดสอบผ่าน `php tests/visit-report.php`: ใช้ฐานข้อมูลชั่วคราวและ HTTP server เฉพาะ localhost ตรวจคำถามซ้ำ/ประวัติ คำตอบบังคับ การบันทึก multipart รูปจริง การ rollback ไฟล์ สิทธิ์รายงาน และ PDF ภาษาไทย ไฟล์ PDF ทดสอบสร้างเฉพาะในโฟลเดอร์ชั่วคราวและลบเมื่อทดสอบเสร็จ ไม่เพิ่มข้อมูลจำลองในระบบจริง

รายงานรายครูอิง `enrollments.teacher_id` และ `sdl_enrollment_teachers` ของภาคเรียนที่เลือก ไม่ใช้ชื่อบัญชีผู้บันทึกเป็นเงื่อนไขจัดกลุ่มรายงาน เช่น ผู้ดูแลบันทึกเยี่ยมนักศึกษาในกลุ่มครูสมชาย รายการจะปรากฏในรายงานครูสมชาย โดยหน้ารายละเอียดและ PDF ยังแสดงผู้บันทึกจริง

### ปรับปรุงทุกเมนูและสำรวจรายบุคคล (5 พฤศจิกายน 2569)

- `/surveys`: บันทึกได้หลายครั้งต่อ `enrollment_id` (หนึ่งคนต่อภาคเรียน) — ทุกครั้งที่บันทึกจะ **เพิ่มรายการใหม่ (append-only history)** ไม่ทับของเดิม พร้อมครั้งที่ ผู้บันทึก วันที่ และหมายเหตุ
- ดูคำตอบย้อนหลังได้ที่ `/surveys/view/{surveyId}` และประวัติทุกครั้งแสดงในหน้าแบบสำรวจ หน้า roster (นับจำนวนครั้ง + วันที่ล่าสุด) และแท็บ "ประวัติการสำรวจ" ของหน้าผู้เรียน
- ยกเลิกการสำรวจได้ที่ `/surveys/cancel/{surveyId}` (soft delete ด้วย `cancelled_at`/`cancelled_by`) รายการยังอยู่ในประวัติแต่ไม่ถูกนับว่า "สำรวจแล้ว"
- แบบสำรวจนำคำตอบจากครั้งล่าสุดมาเป็นค่าเริ่มต้น หากไม่เคยสำรวจ การเยี่ยมครั้งใหม่อ่านคำตอบจากการเยี่ยมล่าสุดให้ตรวจยืนยันก่อนบันทึก ประวัติแต่ละครั้งของการเยี่ยมยังเป็นหลักฐานแยกตามวันที่ ไม่เพิ่มจำนวนการเยี่ยมเพียงเพราะบันทึกแบบสำรวจ
- อัปเกรดฐานข้อมูลเดิม: รัน `database/migrate_survey_history.sql` ครั้งเดียว (เปลี่ยน `student_surveys` ให้มี `id` เป็น PK แทน `enrollment_id` พร้อม `cancelled_at`/`cancelled_by`) — ติดตั้งใหม่ใช้ `database/student_surveys.sql` หรือ `database/database.sql` ได้เลย
- `/users/{id}/edit`: ผู้ดูแลแก้ข้อมูลตนเองและ user/password ได้ บทบาทไม่เปลี่ยนจากข้อมูลที่ส่งมา บัญชีเชื่อม API จัดการที่ต้นทางเท่านั้น ปิดการสร้างครูด้วยมือทั้งหน้าจอและ POST
- `scripts/remove-demo-users.php` นำออกเฉพาะบัญชี seed ที่ชื่อและบทบาทตรงเดิม ไม่มีลิงก์ API และไม่มีประวัติงานจริง ต้องสำรองข้อมูลก่อนใช้งาน ไม่มีการลบครูที่ API ส่งมาเพียงเพราะชื่อดูเหมือนบัญชีทดสอบ
- แก้เลขแบ่งหน้าชน routing, ตั้งค่า/โปรไฟล์/เปลี่ยนรหัสผ่านที่เคยไม่พบหน้า, ค้นหาจากแถบบน, สลับภาคเรียน, รายการแจ้งเตือน/อ่านทั้งหมด, ขอบเขตครูในรายการเยี่ยม, ตรวจสิทธิ์วางแผนและติดตาม, รายงาน/CSV ใช้ความเสี่ยงล่าสุดและไม่เพิ่มแถวเพราะมีหลายครั้งเยี่ยม

### Current API roster and group teacher labels

Run `php scripts/migrate-source-membership.php` once for existing installations, then `php scripts/sdl-sync.php`. The complete successful snapshot marks `sdl_links.source_present` for imported students; missing students remain in historical storage but no longer appear in current student/survey/map rosters. Returning students reuse their existing IDs. Status alone does not determine source membership: an inactive student explicitly returned by the API remains in the API roster. Failed/incomplete syncs do not update membership.

Visit teacher labels use primary and joint teachers assigned to the enrollment. The account that entered the record remains in `home_visits.teacher_id` for audit and is labeled separately as ผู้บันทึกข้อมูล on the detail/PDF. Admin entry never substitutes the admin name for the group teacher.

### Direct home-visit recording

The visit-planning workflow is retired. `/visits` lists current API students (teachers see primary/joint assignments; district admins see all groups), with group, level, term and visit-status filters. Each student appears once with direct record/view/edit actions; `/visits/history` preserves individual dated visit records. Old planning GET links redirect to the roster and planning POST returns 410 without writing. Historical plan foreign keys are retained, but new records do not require or create appointments. Dashboard “เยี่ยมวันนี้” counts completed visits on today's date rather than appointments.

### พิมพ์รายงานรายบุคคลรูปแบบทางการ

ปุ่ม **พิมพ์รายงาน** ในหน้ารายละเอียดเยี่ยมเปิด `/visits/{id}/pdf` เป็น PDF A4 ในแท็บใหม่ ใช้รูปแบบหัวรายงานสีน้ำเงินเข้ม ตารางพื้นอ่อน ฟอนต์ TH Sarabun New และช่องลงนามครูประจำกลุ่ม/ผู้อำนวยการ รายงานรวมรายครูใช้รายละเอียดรูปแบบเดียวกันหลังหน้าสรุป

- ภาพแนบทุกรูปแสดงเต็มภาพ รักษาสัดส่วน กว้างไม่เกิน 82 มม. สูงไม่เกิน 78 มม. เรียงครั้งละสองรูปและเพิ่มหน้าเมื่อจำเป็น
- ผู้บันทึกข้อมูลแสดงแยกจากครูผู้เยี่ยม คำตอบย้อนหลังที่ต่างกันยังแสดงในช่องคำถามเดิมพร้อมคำอธิบาย ไม่แก้ไขข้อมูลจริงเพื่อจัดหน้ารายงาน
- GPS ใช้พิกัดที่บันทึกจริง พร้อมลิงก์/QR เปิดแผนที่ ไม่ดึงแผนที่ผ่านเครือข่ายขณะสร้าง PDF หากไม่มีพิกัดหรือไฟล์ภาพจะแจ้งตามจริง
- ติดตั้งตาม `composer.lock` ด้วย `composer install --no-dev --prefer-dist` รวม `mpdf/qrcode` สำหรับ QR แผนที่ รายงานเข้าถึงผ่านสิทธิ์ผู้ใช้งานและส่ง `Cache-Control: private, no-store`

---

### 11. เอกสารเพิ่มเติมและ UI Design System

#### เอกสารสำหรับทีมพัฒนาและ AI Agent

| ไฟล์ | ใช้ทำอะไร |
|---|---|
| [Context.md](Context.md) | บริบทระบบ, โดเมน, route, design tokens, ข้อจำกัด |
| [AGENTS.md](AGENTS.md) | คู่มือสำหรับ AI coding agent (กฎเหล็ก, helper, checklist) |
| [CONTRIBUTING.md](CONTRIBUTING.md) | ตั้งค่า, มาตรฐานโค้ด, git workflow, PR checklist |
| [SKILL.md](SKILL.md) | skill งาน UI/UX (tokens, component patterns, a11y, responsive) |

#### UI Design System (สรุป)

ระบบใช้ light theme และแบรนด์สีน้ำเงินเข้ม ปรับแต่งจากไฟล์เดียวคือ `public/assets/css/style.css`

- **สีหลัก:** `#04395e` (hover `#032c49`, subtle `#edf3f7`) · semantic success `#70a288`, info `#04395e`, warning `#dab785`, danger `#d5896f`
- **พื้นหลัง/การ์ด/ขอบ:** `#f5f7fa` / `#ffffff` / `#e7ecf2`
- **ตัวอักษร:** `#0f172a` หัวข้อ, `#334155` เนื้อหา, `#64748b` รอง — ฟอนต์ Sarabun
- **Radius:** การ์ด 16px · ฟอร์ม/ปุ่ม 10–12px · badge/avatar pill
- **Bootstrap re-theme:** override `--bs-*` ใน `:root` ให้ทั้งแอปใช้โทนน้ำเงินเข้มเดียวกัน
- **Motion:** 150–250ms, ease-out, รองรับ `prefers-reduced-motion`
- **Responsive:** desktop sidebar + top navbar / mobile bottom nav 5 ช่อง (หน้าแรก · ผู้เรียน · สำรวจผู้เรียน · เยี่ยมบ้าน · เพิ่มเติม)
- **Lightbox:** คลิกรูปหลักฐานการเยี่ยมบ้านเพื่อดูเต็มจอในหน้าเดิม (`data-lightbox` + `app.js`) ไม่เปิดแท็บใหม่
- **Form controls:** checkbox/radio มีเส้นขอบเข้ม 2px มองเห็นชัดทุกพื้นหลัง

> หลีกเลี่ยง: hardcode สีฟ้า/ม่วงของ Bootstrap, ตั้งสีตายตัวบน `h1..h6` (จะทับ `.text-white` บนแบนเนอร์), เปิดรูปด้วย `target="_blank"`

#### Frontend ของกลาง

- `public/assets/css/style.css` — design system + component styles + responsive rules
- `public/assets/js/app.js` — sidebar toggle, global search (Cmd/Ctrl+K), toast, geolocation helpers, lightbox

---

### ช่วงเวลาเปิด–ปิดการเยี่ยมบ้าน/การสำรวจ (Activity Windows)

- อำเภอ (`super_admin` / `admin`) กำหนดช่วงเวลาเปิด–ปิดได้ที่ **`/centers/windows`** (ปุ่ม "ช่วงเวลาเปิด–ปิด" ในหน้าศกร.) — ตั้งต่อศกร. พร้อมปุ่มเปิด/ปิดทั้งหมด หรือเปิด/ปิดเฉพาะการเยี่ยมบ้าน/การสำรวจ
- โครงสร้าง: `learning_centers.visit_enabled`, `visit_open_at`, `visit_close_at`, `survey_enabled`, `survey_open_at`, `survey_close_at`
- ครูตำบล (`teacher`) บันทึก/สำรวจได้เฉพาะเมื่อหน้าต่างของศกร.นั้นเปิด แต่ยังดูข้อมูลย้อนหลังได้เสมอ; อำเภอทำงานได้เสมอ
- ยกเลิกบันทึกเยี่ยมบ้าน: `POST /visits/{id}/cancel` (soft → `status='cancelled'` และลบ `student_risks` ที่ผูกกับ visit นั้น)
- ยกเลิกการสำรวจ: `POST /surveys/cancel/{id}` (soft → `cancelled_at`/`cancelled_by`)
- **สิทธิ์ยกเลิก = อำเภอ (`admin`/`super_admin`) เท่านั้น** ครูตำบลไม่เห็นปุ่มและถูกปฏิเสธที่เซิร์ฟเวอร์
- อัปเกรดฐานข้อมูลเดิม: รัน `database/migrate_activity_windows.sql`

### กำหนดวันเวลาแบบ "ทั้งหมด" และปฏิทินไทย พ.ศ.

- หน้า `/centers/windows` มีการ์ด **"กำหนดทั้งหมด (ใช้กับทุกศกร.)"** — ตั้งเวลาเปิด/ปิดของการเยี่ยมบ้านและ/หรือการสำรวจครั้งเดียว มีผลกับทุกศกร. (`action=apply_all_schedule`); ช่องที่เว้นว่างจะไม่ถูกแก้ไข และประเภทที่มีการกำหนดเวลาจะถูกเปิดใช้งานอัตโนมัติ
- ยังปรับรายศกร.ได้ (เลือกหลายศกร.) และมีปุ่มเปิด/ปิดทั้งหมดหรือเฉพาะประเภท
- ช่องวันเวลาใช้ **ปฏิทินภาษาไทย พ.ศ.** (เดือนไทย + ปี พ.ศ. + เวลา) ผ่าน component `.thai-datetime` ใน `app.js` — ไม่ใช้ `<input type="datetime-local">` ของเบราว์เซอร์ที่แสดง ค.ศ./อังกฤษ

### ติดตามผู้สำเร็จการศึกษา (Graduate Tracking)

เมนู "ติดตามช่วยเหลือ" ถูกแทนที่ด้วย **"ติดตามผู้สำเร็จการศึกษา"** (`/graduates`)

- ดึงข้อมูลจาก SDL API endpoint **`graduates`** (รองรับ `term` เช่น `1/2569`) ผ่าน `GraduateSyncService` → ตาราง `graduates`
- ปุ่ม **ดึงข้อมูลจาก API** ซิงก์ทุกภาคเรียนตั้งแต่ **1/2564 ถึงภาคเรียนล่าสุดที่ API มีข้อมูล** โดยอ่านรายการจาก `meta.terms` และดึงทุกหน้าของแต่ละภาคเรียนให้ครบก่อนเขียนใน transaction เดียว หาก API ขัดข้องจะไม่เปลี่ยนข้อมูลปลายทาง การซิงก์ซ้ำใช้รหัสนักศึกษาเดิมและไม่แตะประวัติการติดตาม
- CLI: สำรองฐานข้อมูลตามหัวข้อ 9 ก่อน แล้วรัน `php scripts/sync-graduates.php` เพื่อซิงก์ช่วงเดียวกับปุ่มบนเว็บ
  - ปุ่ม **"ดึงข้อมูลจาก API"** (ผู้ดูแล/ซุปเปอร์แอดมิน) อยู่ที่หน้าผู้สำเร็จการศึกษา
  - แมป `group_code` → `learning_centers` เพื่อกรองตามกลุ่มเรียน
- ครูตำบลเห็นเฉพาะผู้สำเร็จการศึกษาในกลุ่มที่รับผิดชอบ (จาก `sdl_links` snapshot ของครู) · ผู้ดูแล/ผู้บริหารเห็นทั้งหมด
- ฟอร์มบันทึกผลการติดตาม (`POST /graduates/{id}/track`):
  - 3 หมวด: `study` (การศึกษาต่อ), `career` (การประกอบอาชีพ), `development` (การพัฒนาตนเองและสังคม/ชุมชน)
  - รายละเอียดเพิ่มเติม (ไม่เกิน 5,000 ตัวอักษร)
  - รูปหลักฐาน 1 รูป → ย่อขนาดอัตโนมัติด้วย GD (กว้างสุด `graduate_image_max_width` = 1000px, JPEG quality 70) เก็บที่ `public/uploads/graduates/`
- ประวัติการติดตามเก็บแบบ append-only ในตาราง `graduate_trackings` (ดูย้อนหลัง/ดูรูปได้)
- หน้ารายละเอียดแสดงตามสถานะ: **ยังไม่ติดตาม** → ฟอร์มบันทึกเต็มหน้า (ไม่มีประวัติ); **ติดตามแล้ว** → ประวัติอย่างเดียว + ปุ่ม **แก้ไข** แต่ละรายการ (`/graduates/{id}/track/{trackingId}/edit`) ซึ่งแก้หมวด/รายละเอียด/เปลี่ยนหรือลบรูปได้
- หมวดผลลัพธ์แสดงเป็น tiles เลือกได้ 3 หมวด: `study`, `career`, `development`
- โครงสร้างฐานข้อมูล: `database/graduates.sql` (รวมใน `database/database.sql` แล้ว)
