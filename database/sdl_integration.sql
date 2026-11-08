CREATE TABLE IF NOT EXISTS sdl_links (
    entity VARCHAR(20) NOT NULL,
    source_key VARCHAR(190) NOT NULL,
    local_id INT UNSIGNED NOT NULL,
    snapshot JSON DEFAULT NULL,
    source_present TINYINT(1) NOT NULL DEFAULT 1,
    synced_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (entity, source_key),
    UNIQUE KEY uk_sdl_local (entity, local_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS sdl_sync_runs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    summary JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS sdl_enrollment_teachers (
    enrollment_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    PRIMARY KEY(enrollment_id,teacher_id),
    KEY idx_sdl_teacher(teacher_id),
    FOREIGN KEY(enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
