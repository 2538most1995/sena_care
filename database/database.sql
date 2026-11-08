-- ============================================================
-- ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.
-- Student Care & Home Visit Management System
-- Database Schema - MySQL 8+ / utf8mb4 / InnoDB
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER_SET_CLIENT = utf8mb4;
SET CHARACTER_SET_RESULTS = utf8mb4;
SET COLLATION_CONNECTION = utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

-- Create and select database automatically
CREATE DATABASE IF NOT EXISTS `sena_care` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sena_care`;

-- ============================================================
-- 1. organizations - หน่วยงาน
-- ============================================================
CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_name` VARCHAR(255) NOT NULL,
    `short_name` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. learning_centers - ศกร.ระดับตำบล / ศูนย์การเรียนรู้
-- ============================================================
CREATE TABLE IF NOT EXISTS `learning_centers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `center_code` VARCHAR(20) DEFAULT NULL,
    `center_name` VARCHAR(255) NOT NULL,
    `subdistrict` VARCHAR(100) DEFAULT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `visit_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `visit_open_at` DATETIME DEFAULT NULL,
    `visit_close_at` DATETIME DEFAULT NULL,
    `survey_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `survey_open_at` DATETIME DEFAULT NULL,
    `survey_close_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_organization` (`organization_id`),
    KEY `idx_center_code` (`center_code`),
    CONSTRAINT `fk_lc_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. users - ผู้ใช้งาน / ครู / ผู้บริหาร
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `prefix` VARCHAR(30) DEFAULT NULL,
    `firstname` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('super_admin','admin','teacher','executive') NOT NULL DEFAULT 'teacher',
    `organization_id` INT UNSIGNED DEFAULT NULL,
    `learning_center_id` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_organization` (`organization_id`),
    KEY `idx_learning_center` (`learning_center_id`),
    CONSTRAINT `fk_user_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_user_learning_center` FOREIGN KEY (`learning_center_id`) REFERENCES `learning_centers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. login_attempts - ป้องกัน Brute Force
-- ============================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_username_ip` (`username`, `ip_address`),
    KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. academic_terms - ปีการศึกษา / ภาคเรียน
-- ============================================================
CREATE TABLE IF NOT EXISTS `academic_terms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academic_year` SMALLINT UNSIGNED NOT NULL COMMENT 'ปี พ.ศ. เช่น 2569',
    `term_no` TINYINT UNSIGNED NOT NULL COMMENT '1 หรือ 2',
    `term_name` VARCHAR(50) NOT NULL COMMENT 'เช่น 1/2569',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_year_term` (`academic_year`, `term_no`),
    KEY `idx_is_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. education_levels - ระดับการศึกษา
-- ============================================================
CREATE TABLE IF NOT EXISTS `education_levels` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level_code` VARCHAR(20) NOT NULL,
    `level_name` VARCHAR(100) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_level_code` (`level_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. students - ข้อมูลกลางผู้เรียน (1 คน = 1 Record)
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_code` VARCHAR(30) DEFAULT NULL,
    `citizen_id` VARCHAR(13) DEFAULT NULL,
    `prefix` VARCHAR(30) DEFAULT NULL,
    `firstname` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `nickname` VARCHAR(50) DEFAULT NULL,
    `gender` ENUM('male','female','other') DEFAULT NULL,
    `birthdate` DATE DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `student_status` ENUM('active','inactive','graduated','dropped','transferred') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_code` (`student_code`),
    KEY `idx_citizen_id` (`citizen_id`),
    KEY `idx_firstname` (`firstname`),
    KEY `idx_lastname` (`lastname`),
    KEY `idx_student_status` (`student_status`),
    KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. student_addresses - ที่อยู่ผู้เรียน
-- ============================================================
CREATE TABLE IF NOT EXISTS `student_addresses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `address_type` ENUM('home','current','work') NOT NULL DEFAULT 'home',
    `house_no` VARCHAR(30) DEFAULT NULL,
    `moo` VARCHAR(10) DEFAULT NULL,
    `village` VARCHAR(100) DEFAULT NULL,
    `road` VARCHAR(100) DEFAULT NULL,
    `subdistrict` VARCHAR(100) DEFAULT NULL,
    `district` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `zipcode` VARCHAR(10) DEFAULT NULL,
    `latitude` DECIMAL(10,7) DEFAULT NULL,
    `longitude` DECIMAL(10,7) DEFAULT NULL,
    `map_url` VARCHAR(500) DEFAULT NULL,
    `landmark` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_lat_lng` (`latitude`, `longitude`),
    KEY `idx_subdistrict` (`subdistrict`),
    CONSTRAINT `fk_addr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. student_contacts - ผู้ปกครอง / บุคคลติดต่อ
-- ============================================================
CREATE TABLE IF NOT EXISTS `student_contacts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `note` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    CONSTRAINT `fk_contact_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. enrollments - การลงทะเบียน (เชื่อมผู้เรียน-ภาคเรียน)
-- ============================================================
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `academic_term_id` INT UNSIGNED NOT NULL,
    `learning_center_id` INT UNSIGNED DEFAULT NULL,
    `teacher_id` INT UNSIGNED DEFAULT NULL,
    `education_level` VARCHAR(20) DEFAULT NULL COMMENT 'FK to education_levels.level_code',
    `group_name` VARCHAR(50) DEFAULT NULL,
    `enrollment_status` ENUM('active','inactive','completed','dropped','transferred') NOT NULL DEFAULT 'active',
    `registered_at` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_term` (`student_id`, `academic_term_id`),
    KEY `idx_academic_term` (`academic_term_id`),
    KEY `idx_teacher` (`teacher_id`),
    KEY `idx_learning_center` (`learning_center_id`),
    KEY `idx_education_level` (`education_level`),
    KEY `idx_enrollment_status` (`enrollment_status`),
    CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_term` FOREIGN KEY (`academic_term_id`) REFERENCES `academic_terms` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_center` FOREIGN KEY (`learning_center_id`) REFERENCES `learning_centers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. visit_plans - แผนการเยี่ยมบ้าน
-- ============================================================
CREATE TABLE IF NOT EXISTS `visit_plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `enrollment_id` INT UNSIGNED DEFAULT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `visit_date` DATE NOT NULL,
    `visit_time` TIME DEFAULT NULL,
    `purpose` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `status` ENUM('planned','confirmed','completed','cancelled','rescheduled') NOT NULL DEFAULT 'planned',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_teacher` (`teacher_id`),
    KEY `idx_visit_date` (`visit_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_vp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vp_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_vp_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. home_visits - บันทึกการเยี่ยมบ้าน
-- ============================================================
CREATE TABLE IF NOT EXISTS `home_visits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `enrollment_id` INT UNSIGNED DEFAULT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `visit_plan_id` INT UNSIGNED DEFAULT NULL,
    `visit_date` DATE NOT NULL,
    `checkin_time` DATETIME DEFAULT NULL,
    `checkout_time` DATETIME DEFAULT NULL,
    `checkin_latitude` DECIMAL(10,7) DEFAULT NULL,
    `checkin_longitude` DECIMAL(10,7) DEFAULT NULL,
    `distance_from_home` INT UNSIGNED DEFAULT NULL COMMENT 'Distance in meters',
    `visit_type` ENUM('home_visit','phone','line','at_center','parent_meeting','online','other') NOT NULL DEFAULT 'home_visit',
    `purpose` VARCHAR(255) DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `problem` TEXT DEFAULT NULL,
    `student_need` TEXT DEFAULT NULL,
    `teacher_recommendation` TEXT DEFAULT NULL,
    `risk_level` ENUM('low','medium','high','critical') DEFAULT 'low',
    `followup_required` TINYINT(1) NOT NULL DEFAULT 0,
    `next_followup_date` DATE DEFAULT NULL,
    `status` ENUM('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_teacher` (`teacher_id`),
    KEY `idx_visit_date` (`visit_date`),
    KEY `idx_visit_type` (`visit_type`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_hv_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_hv_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_hv_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_hv_plan` FOREIGN KEY (`visit_plan_id`) REFERENCES `visit_plans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. assessment_forms - แบบประเมิน
-- ============================================================
CREATE TABLE IF NOT EXISTS `assessment_forms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_name` VARCHAR(255) NOT NULL,
    `version` VARCHAR(20) DEFAULT '1.0',
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. assessment_questions - คำถามในแบบประเมิน
-- ============================================================
CREATE TABLE IF NOT EXISTS `assessment_questions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id` INT UNSIGNED NOT NULL,
    `section_name` VARCHAR(100) DEFAULT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('text','textarea','number','radio','checkbox','select','date','yes_no','rating') NOT NULL DEFAULT 'text',
    `options_json` JSON DEFAULT NULL COMMENT 'JSON array of options for radio/checkbox/select',
    `required` TINYINT(1) NOT NULL DEFAULT 0,
    `risk_weight` TINYINT UNSIGNED DEFAULT 0 COMMENT 'Weight for risk calculation',
    `risk_trigger_value` VARCHAR(100) DEFAULT NULL COMMENT 'Value that triggers risk score',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    KEY `idx_form` (`form_id`),
    KEY `idx_sort_order` (`sort_order`),
    CONSTRAINT `fk_aq_form` FOREIGN KEY (`form_id`) REFERENCES `assessment_forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. assessment_answers - คำตอบแบบประเมิน
-- ============================================================
CREATE TABLE IF NOT EXISTS `assessment_answers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visit_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `answer_value` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_visit_question` (`visit_id`, `question_id`),
    KEY `idx_question` (`question_id`),
    CONSTRAINT `fk_aa_visit` FOREIGN KEY (`visit_id`) REFERENCES `home_visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aa_question` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. visit_files - ไฟล์ / รูปภาพเยี่ยมบ้าน
-- ============================================================
CREATE TABLE IF NOT EXISTS `visit_files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visit_id` INT UNSIGNED NOT NULL,
    `file_type` ENUM('image','document','other') NOT NULL DEFAULT 'image',
    `stored_name` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `file_size` INT UNSIGNED DEFAULT NULL COMMENT 'Size in bytes',
    `caption` VARCHAR(255) DEFAULT NULL,
    `uploaded_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_visit` (`visit_id`),
    CONSTRAINT `fk_vf_visit` FOREIGN KEY (`visit_id`) REFERENCES `home_visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vf_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. followups - การติดตามช่วยเหลือ
-- ============================================================
CREATE TABLE IF NOT EXISTS `followups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `home_visit_id` INT UNSIGNED DEFAULT NULL,
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    `due_date` DATE DEFAULT NULL,
    `status` ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `result` TEXT DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_assigned_to` (`assigned_to`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    CONSTRAINT `fk_fu_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_fu_visit` FOREIGN KEY (`home_visit_id`) REFERENCES `home_visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_fu_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. risk_rules - กฎคำนวณความเสี่ยง
-- ============================================================
CREATE TABLE IF NOT EXISTS `risk_rules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_name` VARCHAR(255) NOT NULL,
    `rule_description` TEXT DEFAULT NULL,
    `score` TINYINT NOT NULL DEFAULT 1,
    `category` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. student_risks - ผลประเมินความเสี่ยงรายผู้เรียน
-- ============================================================
CREATE TABLE IF NOT EXISTS `student_risks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `enrollment_id` INT UNSIGNED DEFAULT NULL,
    `home_visit_id` INT UNSIGNED DEFAULT NULL,
    `risk_level` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    `total_score` SMALLINT UNSIGNED DEFAULT 0,
    `risk_factors` JSON DEFAULT NULL COMMENT 'JSON array of matched rule IDs',
    `manual_override` TINYINT(1) NOT NULL DEFAULT 0,
    `override_reason` TEXT DEFAULT NULL,
    `assessed_by` INT UNSIGNED DEFAULT NULL,
    `assessed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_risk_level` (`risk_level`),
    CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_visit` FOREIGN KEY (`home_visit_id`) REFERENCES `home_visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_assessed_by` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20. notifications - การแจ้งเตือน
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `type` VARCHAR(50) DEFAULT 'info' COMMENT 'info, warning, danger, success',
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_read` (`user_id`, `is_read`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21. audit_logs - บันทึกการใช้งาน
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, create, update, delete, export, etc.',
    `table_name` VARCHAR(100) DEFAULT NULL,
    `record_id` INT UNSIGNED DEFAULT NULL,
    `old_data` JSON DEFAULT NULL,
    `new_data` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_table_record` (`table_name`, `record_id`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 22. import_logs - บันทึกการ Import
-- ============================================================
CREATE TABLE IF NOT EXISTS `import_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `academic_term_id` INT UNSIGNED DEFAULT NULL,
    `total_rows` INT UNSIGNED DEFAULT 0,
    `inserted` INT UNSIGNED DEFAULT 0,
    `updated` INT UNSIGNED DEFAULT 0,
    `skipped` INT UNSIGNED DEFAULT 0,
    `failed` INT UNSIGNED DEFAULT 0,
    `errors_json` JSON DEFAULT NULL,
    `imported_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_term` (`academic_term_id`),
    KEY `idx_imported_by` (`imported_by`),
    CONSTRAINT `fk_il_term` FOREIGN KEY (`academic_term_id`) REFERENCES `academic_terms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_il_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Default Data - ระดับการศึกษา
-- ============================================================
INSERT IGNORE INTO `education_levels` (`level_code`, `level_name`, `sort_order`) VALUES
('primary', 'ประถมศึกษา', 1),
('lower_secondary', 'มัธยมศึกษาตอนต้น', 2),
('upper_secondary', 'มัธยมศึกษาตอนปลาย', 3);

-- ============================================================
-- Default Data - กฎความเสี่ยง
-- ============================================================
INSERT IGNORE INTO `risk_rules` (`rule_name`, `rule_description`, `score`, `category`, `sort_order`) VALUES
('ขาดการติดต่อ', 'ไม่สามารถติดต่อผู้เรียนได้เป็นเวลานาน', 3, 'การติดต่อ', 1),
('ไม่มีอินเทอร์เน็ต', 'ผู้เรียนไม่มีอินเทอร์เน็ตใช้ในการเรียน', 1, 'อุปกรณ์', 2),
('ต้องทำงานเต็มเวลา', 'ผู้เรียนต้องทำงานเต็มเวลาจนไม่มีเวลาเรียน', 2, 'การทำงาน', 3),
('ขาดเรียนบ่อย', 'ผู้เรียนขาดเรียนบ่อยครั้งหรือไม่ส่งงาน', 3, 'การเรียน', 4),
('ปัญหาเศรษฐกิจ', 'ครอบครัวมีปัญหาทางเศรษฐกิจรุนแรง', 2, 'เศรษฐกิจ', 5),
('มีแนวโน้มออกกลางคัน', 'ผู้เรียนแสดงความตั้งใจจะลาออกหรือหยุดเรียน', 5, 'การเรียน', 6),
('ปัญหาครอบครัว', 'มีปัญหาครอบครัวที่กระทบต่อการเรียน', 2, 'ครอบครัว', 7),
('ปัญหาสุขภาพ', 'มีปัญหาสุขภาพที่กระทบต่อการเรียน', 2, 'สุขภาพ', 8),
('ไม่มีอุปกรณ์การเรียน', 'ขาดแคลนอุปกรณ์การเรียนที่จำเป็น', 1, 'อุปกรณ์', 9),
('สภาพที่อยู่ไม่เอื้อ', 'สภาพที่อยู่อาศัยไม่เอื้อต่อการเรียน', 2, 'ที่อยู่', 10);

-- ============================================================
-- Default Data - แบบประเมินเยี่ยมบ้าน
-- ============================================================
INSERT IGNORE INTO `assessment_forms` (`id`, `form_name`, `version`, `description`) VALUES
(1, 'แบบประเมินการเยี่ยมบ้านผู้เรียน สกร.', '1.0', 'แบบประเมินมาตรฐานสำหรับการเยี่ยมบ้านผู้เรียน');

-- Idempotent defaults: INSERT IGNORE alone cannot prevent duplicates without a unique key.

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'สภาพที่อยู่อาศัย', 'ลักษณะที่อยู่อาศัย', 'radio', '["บ้านตนเอง","บ้านเช่า","บ้านญาติ","หอพัก","อื่นๆ"]', 1, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'สภาพที่อยู่อาศัย' AND `question_text` <=> 'ลักษณะที่อยู่อาศัย' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["บ้านตนเอง","บ้านเช่า","บ้านญาติ","หอพัก","อื่นๆ"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 1);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'สภาพที่อยู่อาศัย', 'สภาพที่อยู่อาศัยโดยรวม', 'radio', '["ดี","พอใช้","ต้องปรับปรุง","ทรุดโทรม"]', 1, 2, 'ทรุดโทรม', 2
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'สภาพที่อยู่อาศัย' AND `question_text` <=> 'สภาพที่อยู่อาศัยโดยรวม' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["ดี","พอใช้","ต้องปรับปรุง","ทรุดโทรม"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 2 AND `risk_trigger_value` <=> 'ทรุดโทรม' AND `sort_order` <=> 2);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ครอบครัว', 'จำนวนสมาชิกในครอบครัว (คน)', 'number', NULL, 1, 0, NULL, 3
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ครอบครัว' AND `question_text` <=> 'จำนวนสมาชิกในครอบครัว (คน)' AND `question_type` <=> 'number' AND `options_json` <=> NULL AND `required` <=> 1 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 3);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ครอบครัว', 'ผู้เรียนอาศัยอยู่กับใคร', 'radio', '["บิดามารดา","บิดาหรือมารดาฝ่ายเดียว","ญาติ","คู่สมรส","อยู่คนเดียว","อื่นๆ"]', 1, 1, 'อยู่คนเดียว', 4
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ครอบครัว' AND `question_text` <=> 'ผู้เรียนอาศัยอยู่กับใคร' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["บิดามารดา","บิดาหรือมารดาฝ่ายเดียว","ญาติ","คู่สมรส","อยู่คนเดียว","อื่นๆ"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 1 AND `risk_trigger_value` <=> 'อยู่คนเดียว' AND `sort_order` <=> 4);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'เศรษฐกิจ', 'รายได้ครอบครัวต่อเดือน (โดยประมาณ)', 'radio', '["ต่ำกว่า 5,000 บาท","5,001-10,000 บาท","10,001-20,000 บาท","20,001-30,000 บาท","มากกว่า 30,000 บาท"]', 1, 2, 'ต่ำกว่า 5,000 บาท', 5
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'เศรษฐกิจ' AND `question_text` <=> 'รายได้ครอบครัวต่อเดือน (โดยประมาณ)' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["ต่ำกว่า 5,000 บาท","5,001-10,000 บาท","10,001-20,000 บาท","20,001-30,000 บาท","มากกว่า 30,000 บาท"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 2 AND `risk_trigger_value` <=> 'ต่ำกว่า 5,000 บาท' AND `sort_order` <=> 5);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'เศรษฐกิจ', 'อาชีพหลักของครอบครัว', 'text', NULL, 0, 0, NULL, 6
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'เศรษฐกิจ' AND `question_text` <=> 'อาชีพหลักของครอบครัว' AND `question_type` <=> 'text' AND `options_json` <=> NULL AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 6);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'การเดินทาง', 'ระยะทางจากบ้านถึง ศกร. (โดยประมาณ)', 'radio', '["ต่ำกว่า 5 กม.","5-10 กม.","11-20 กม.","มากกว่า 20 กม."]', 0, 0, NULL, 7
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'การเดินทาง' AND `question_text` <=> 'ระยะทางจากบ้านถึง ศกร. (โดยประมาณ)' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["ต่ำกว่า 5 กม.","5-10 กม.","11-20 กม.","มากกว่า 20 กม."]' AS JSON) AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 7);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'การเดินทาง', 'พาหนะในการเดินทาง', 'radio', '["รถยนต์ส่วนตัว","จักรยานยนต์","รถจักรยาน","รถสาธารณะ","เดินเท้า","อื่นๆ"]', 0, 0, NULL, 8
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'การเดินทาง' AND `question_text` <=> 'พาหนะในการเดินทาง' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["รถยนต์ส่วนตัว","จักรยานยนต์","รถจักรยาน","รถสาธารณะ","เดินเท้า","อื่นๆ"]' AS JSON) AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 8);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'การทำงาน', 'ผู้เรียนทำงานหรือไม่', 'radio', '["ไม่ทำงาน","ทำงานพาร์ทไทม์","ทำงานเต็มเวลา"]', 1, 2, 'ทำงานเต็มเวลา', 9
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'การทำงาน' AND `question_text` <=> 'ผู้เรียนทำงานหรือไม่' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["ไม่ทำงาน","ทำงานพาร์ทไทม์","ทำงานเต็มเวลา"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 2 AND `risk_trigger_value` <=> 'ทำงานเต็มเวลา' AND `sort_order` <=> 9);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'อุปกรณ์การเรียน', 'ผู้เรียนมีโทรศัพท์มือถือหรือไม่', 'yes_no', NULL, 1, 0, NULL, 10
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'อุปกรณ์การเรียน' AND `question_text` <=> 'ผู้เรียนมีโทรศัพท์มือถือหรือไม่' AND `question_type` <=> 'yes_no' AND `options_json` <=> NULL AND `required` <=> 1 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 10);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'อุปกรณ์การเรียน', 'ผู้เรียนมีคอมพิวเตอร์/แท็บเล็ตหรือไม่', 'yes_no', NULL, 1, 0, NULL, 11
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'อุปกรณ์การเรียน' AND `question_text` <=> 'ผู้เรียนมีคอมพิวเตอร์/แท็บเล็ตหรือไม่' AND `question_type` <=> 'yes_no' AND `options_json` <=> NULL AND `required` <=> 1 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 11);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'อุปกรณ์การเรียน', 'มีอินเทอร์เน็ตใช้งานที่บ้านหรือไม่', 'yes_no', NULL, 1, 1, 'ไม่ใช่', 12
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'อุปกรณ์การเรียน' AND `question_text` <=> 'มีอินเทอร์เน็ตใช้งานที่บ้านหรือไม่' AND `question_type` <=> 'yes_no' AND `options_json` <=> NULL AND `required` <=> 1 AND `risk_weight` <=> 1 AND `risk_trigger_value` <=> 'ไม่ใช่' AND `sort_order` <=> 12);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ปัญหา', 'ปัญหาด้านการเรียน', 'textarea', NULL, 0, 0, NULL, 13
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ปัญหา' AND `question_text` <=> 'ปัญหาด้านการเรียน' AND `question_type` <=> 'textarea' AND `options_json` <=> NULL AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 13);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ปัญหา', 'ปัญหาด้านเศรษฐกิจ', 'textarea', NULL, 0, 0, NULL, 14
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ปัญหา' AND `question_text` <=> 'ปัญหาด้านเศรษฐกิจ' AND `question_type` <=> 'textarea' AND `options_json` <=> NULL AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 14);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ปัญหา', 'ปัญหาด้านครอบครัว', 'textarea', NULL, 0, 0, NULL, 15
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ปัญหา' AND `question_text` <=> 'ปัญหาด้านครอบครัว' AND `question_type` <=> 'textarea' AND `options_json` <=> NULL AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 15);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ปัญหา', 'ผู้เรียนมีความเสี่ยงหลุดจากระบบการศึกษาหรือไม่', 'radio', '["ไม่เสี่ยง","เสี่ยงน้อย","เสี่ยงปานกลาง","เสี่ยงสูง","เสี่ยงสูงมาก"]', 1, 5, 'เสี่ยงสูงมาก', 16
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ปัญหา' AND `question_text` <=> 'ผู้เรียนมีความเสี่ยงหลุดจากระบบการศึกษาหรือไม่' AND `question_type` <=> 'radio' AND CAST(`options_json` AS JSON) <=> CAST('["ไม่เสี่ยง","เสี่ยงน้อย","เสี่ยงปานกลาง","เสี่ยงสูง","เสี่ยงสูงมาก"]' AS JSON) AND `required` <=> 1 AND `risk_weight` <=> 5 AND `risk_trigger_value` <=> 'เสี่ยงสูงมาก' AND `sort_order` <=> 16);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ความต้องการ', 'ความต้องการช่วยเหลือ', 'checkbox', '["ทุนการศึกษา","อุปกรณ์การเรียน","อินเทอร์เน็ต","อาหาร","ที่อยู่อาศัย","การแนะแนว","สุขภาพ","อื่นๆ"]', 0, 0, NULL, 17
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ความต้องการ' AND `question_text` <=> 'ความต้องการช่วยเหลือ' AND `question_type` <=> 'checkbox' AND CAST(`options_json` AS JSON) <=> CAST('["ทุนการศึกษา","อุปกรณ์การเรียน","อินเทอร์เน็ต","อาหาร","ที่อยู่อาศัย","การแนะแนว","สุขภาพ","อื่นๆ"]' AS JSON) AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 17);

INSERT INTO `assessment_questions` (`form_id`, `section_name`, `question_text`, `question_type`, `options_json`, `required`, `risk_weight`, `risk_trigger_value`, `sort_order`)
SELECT 1, 'ข้อเสนอแนะ', 'ข้อเสนอแนะของครูผู้เยี่ยม', 'textarea', NULL, 0, 0, NULL, 18
WHERE NOT EXISTS (SELECT 1 FROM `assessment_questions` WHERE `form_id` <=> 1 AND `section_name` <=> 'ข้อเสนอแนะ' AND `question_text` <=> 'ข้อเสนอแนะของครูผู้เยี่ยม' AND `question_type` <=> 'textarea' AND `options_json` <=> NULL AND `required` <=> 0 AND `risk_weight` <=> 0 AND `risk_trigger_value` <=> NULL AND `sort_order` <=> 18);

SET FOREIGN_KEY_CHECKS = 1;

-- Survey history: many surveys per enrollment. Every save appends a new record.
CREATE TABLE IF NOT EXISTS student_surveys (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    enrollment_id INT UNSIGNED NOT NULL,
    answers_json JSON NOT NULL,
    note TEXT DEFAULT NULL,
    surveyed_by INT UNSIGNED NOT NULL,
    surveyed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    cancelled_by INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY(id),
    KEY idx_survey_enrollment (enrollment_id),
    CONSTRAINT fk_survey_enrollment FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_user FOREIGN KEY(surveyed_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_survey_cancelled_by FOREIGN KEY(cancelled_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Graduate tracking (ผู้สำเร็จการศึกษา)
-- ============================================================
CREATE TABLE IF NOT EXISTS `graduates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_code` VARCHAR(30) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `level_id` TINYINT UNSIGNED DEFAULT NULL,
    `level_label` VARCHAR(100) DEFAULT NULL,
    `group_code` VARCHAR(30) DEFAULT NULL,
    `group_name` VARCHAR(255) DEFAULT NULL,
    `graduation_term` VARCHAR(20) NOT NULL,
    `learning_center_id` INT UNSIGNED DEFAULT NULL,
    `synced_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_graduates_code` (`student_code`),
    KEY `idx_graduates_term` (`graduation_term`),
    KEY `idx_graduates_group` (`group_code`),
    KEY `idx_graduates_center` (`learning_center_id`),
    CONSTRAINT `fk_graduates_center` FOREIGN KEY (`learning_center_id`) REFERENCES `learning_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `graduate_trackings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `graduate_id` INT UNSIGNED NOT NULL,
    `categories` JSON NOT NULL,
    `other_detail` TEXT DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `tracked_by` INT UNSIGNED NOT NULL,
    `tracked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tracking_graduate` (`graduate_id`),
    CONSTRAINT `fk_tracking_graduate` FOREIGN KEY (`graduate_id`) REFERENCES `graduates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tracking_user` FOREIGN KEY (`tracked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
