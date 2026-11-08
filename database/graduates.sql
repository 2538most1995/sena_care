-- ============================================================
-- Graduate tracking (ผู้สำเร็จการศึกษา)
-- Graduates are synced from the SDL_school "graduates" endpoint.
-- Outcome follow-ups are append-only; an image is downscaled on upload.
-- ============================================================

CREATE TABLE IF NOT EXISTS graduates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_code VARCHAR(30) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    level_id TINYINT UNSIGNED DEFAULT NULL,
    level_label VARCHAR(100) DEFAULT NULL,
    group_code VARCHAR(30) DEFAULT NULL,
    group_name VARCHAR(255) DEFAULT NULL,
    graduation_term VARCHAR(20) NOT NULL,
    learning_center_id INT UNSIGNED DEFAULT NULL,
    synced_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_graduates_code (student_code),
    KEY idx_graduates_term (graduation_term),
    KEY idx_graduates_group (group_code),
    KEY idx_graduates_center (learning_center_id),
    CONSTRAINT fk_graduates_center FOREIGN KEY (learning_center_id) REFERENCES learning_centers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS graduate_trackings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    graduate_id INT UNSIGNED NOT NULL,
    categories JSON NOT NULL,
    other_detail TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    tracked_by INT UNSIGNED NOT NULL,
    tracked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tracking_graduate (graduate_id),
    CONSTRAINT fk_tracking_graduate FOREIGN KEY (graduate_id) REFERENCES graduates (id) ON DELETE CASCADE,
    CONSTRAINT fk_tracking_user FOREIGN KEY (tracked_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
