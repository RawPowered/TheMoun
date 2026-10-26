-- 2026-04-12 Moun investor gate: schema + idempotent seed (utf8mb4, binary-safe hashes as CHAR(64) hex)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `nda_versions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version_label` VARCHAR(64) NOT NULL,
  `body_html` MEDIUMTEXT NOT NULL,
  `effective_at` DATETIME NOT NULL,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_reaccept` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nda_versions_label` (`version_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARBINARY(255) NULL,
  `display_name` VARCHAR(120) NULL,
  `legal_first_name` VARCHAR(120) NOT NULL DEFAULT '',
  `legal_last_name` VARCHAR(120) NOT NULL DEFAULT '',
  `role` ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
  `legal_identity_locked_at` DATETIME NULL,
  `invite_token_hash` CHAR(64) NULL,
  `invite_expires_at` DATETIME NULL,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_invite` (`invite_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nda_acceptances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `nda_version_id` INT UNSIGNED NOT NULL,
  `signature_text` VARCHAR(500) NOT NULL,
  `signed_at` DATETIME NOT NULL,
  `signer_declared_date` DATE NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(512) NOT NULL,
  `geo_country` VARCHAR(2) NULL,
  `geo_region` VARCHAR(120) NULL,
  `geo_city` VARCHAR(120) NULL,
  `geo_lat` DECIMAL(10,7) NULL,
  `geo_lon` DECIMAL(10,7) NULL,
  `content_snapshot_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_na_user` (`user_id`),
  KEY `idx_na_version` (`nda_version_id`),
  CONSTRAINT `fk_na_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_na_version` FOREIGN KEY (`nda_version_id`) REFERENCES `nda_versions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(512) NOT NULL,
  `geo_country` VARCHAR(2) NULL,
  `geo_region` VARCHAR(120) NULL,
  `geo_city` VARCHAR(120) NULL,
  `geo_lat` DECIMAL(10,7) NULL,
  `geo_lon` DECIMAL(10,7) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_le_user_time` (`user_id`, `occurred_at`),
  CONSTRAINT `fk_le_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Single current NNN template (idempotent by version_label)
INSERT INTO `nda_versions` (`version_label`, `body_html`, `effective_at`, `is_current`, `requires_reaccept`)
VALUES (
  '2026-04-15-nnn-v1',
  '<p>This Non-Disclosure, Non-Use, and Non-Circumvention Agreement (&quot;NNN Agreement&quot;) is entered into between <strong>[[BRAND_LEGAL_NAME]]</strong> and <strong>[[PARTY_FULL_NAME]]</strong> (&quot;Recipient&quot;).</p><p>Recipient acknowledges that all information, materials, specifications, prototypes, and business details accessed through this platform are confidential and proprietary. Recipient will not disclose, use outside the authorized purpose, or circumvent <strong>[[BRAND_SHORT_NAME]]</strong> in connection with such information, directly or indirectly, except as expressly authorized in writing.</p><p>Recipient understands that this acceptance is executed electronically and constitutes Recipient''s legally binding digital signature.</p><p><em>Version [[VERSION_LABEL]] — effective [[EFFECTIVE_AT]]</em></p>',
  NOW(),
  1,
  1
)
ON DUPLICATE KEY UPDATE `id` = `id`;

UPDATE `nda_versions` SET `is_current` = 0 WHERE `version_label` <> '2026-04-15-nnn-v1';
UPDATE `nda_versions` SET `is_current` = 1 WHERE `version_label` = '2026-04-15-nnn-v1';

-- --- 2026-04-13 — Investor interest analytics (same as deploy/sync.sql) ---

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
