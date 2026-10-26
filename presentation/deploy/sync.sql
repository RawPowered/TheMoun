-- ============================================================================
-- Sync SQL — append-only idempotent patches (utf8mb4). Run via Admin → Update patches
-- or: mysql ... < deploy/sync.sql
-- ============================================================================

-- --- 2026-04-13 — Investor interest analytics (section + image dwell) ---

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `analytics_sessions` (
  `id` CHAR(36) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` VARCHAR(512) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_as_user_started` (`user_id`, `started_at`),
  CONSTRAINT `fk_as_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analytics_dwell_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `session_id` CHAR(36) NOT NULL,
  `kind` ENUM('section','image') NOT NULL,
  `target_key` VARCHAR(191) NOT NULL,
  `section_context` VARCHAR(64) NULL,
  `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ade_user_kind_target` (`user_id`, `kind`, `target_key`),
  KEY `idx_ade_user_session` (`user_id`, `session_id`),
  KEY `idx_ade_started` (`started_at`),
  CONSTRAINT `fk_ade_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ade_session` FOREIGN KEY (`session_id`) REFERENCES `analytics_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
