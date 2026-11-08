-- Survey history: many surveys per enrollment. Every save appends a new record.
-- Cancel uses cancelled_at/cancelled_by (soft delete) so history is preserved.
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
