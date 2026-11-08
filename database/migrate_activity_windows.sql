-- ============================================================
-- Upgrade existing installation: per-center activity windows
-- Adds open/close controls for home visits and individual surveys.
-- Run ONCE on a database created before this feature.
--
-- Back up first:
--   mysqldump -h127.0.0.1 -P8889 -uroot -p sena_care learning_centers > backup.sql
-- ============================================================

ALTER TABLE learning_centers
    ADD COLUMN visit_enabled TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN visit_open_at DATETIME NULL,
    ADD COLUMN visit_close_at DATETIME NULL,
    ADD COLUMN survey_enabled TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN survey_open_at DATETIME NULL,
    ADD COLUMN survey_close_at DATETIME NULL;
