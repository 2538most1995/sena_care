-- ============================================================
-- Upgrade existing installation: many surveys per enrollment
-- Run this ONCE on a database that still has the old
-- student_surveys table (PRIMARY KEY on enrollment_id).
--
-- Back up first:
--   mysqldump -h127.0.0.1 -P8889 -uroot -p sena_care student_surveys > backup.sql
-- ============================================================

ALTER TABLE student_surveys
    DROP PRIMARY KEY,
    ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD PRIMARY KEY (id),
    ADD KEY idx_survey_enrollment (enrollment_id),
    ADD COLUMN cancelled_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN cancelled_by INT UNSIGNED NULL,
    ADD CONSTRAINT fk_survey_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL;
