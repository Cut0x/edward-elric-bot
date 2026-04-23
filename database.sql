-- =============================================
--   EDWARD ELRIC BOT - Schéma base de données
--   Version 1.6.0
-- =============================================

CREATE DATABASE IF NOT EXISTS `edwardbot`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `edwardbot`;

-- Utilisateurs (1 compte = 1 ID Discord)
CREATE TABLE IF NOT EXISTS `users` (
    `id`               BIGINT UNSIGNED  NOT NULL COMMENT 'ID Discord',
    `username`         VARCHAR(255)     NOT NULL,
    `global_name`      VARCHAR(255)     DEFAULT NULL,
    `avatar`           VARCHAR(255)     DEFAULT NULL,
    `banner`           VARCHAR(255)     DEFAULT NULL,
    `is_owner`         TINYINT(1)       NOT NULL DEFAULT 0,
    `is_card_author`   TINYINT(1)       NOT NULL DEFAULT 0,
    `is_banned`        TINYINT(1)       NOT NULL DEFAULT 0,
    `rolls_remaining`  INT              NOT NULL DEFAULT 10,
    `last_roll_reset`  DATE             DEFAULT NULL,
    `total_rolls`      INT              NOT NULL DEFAULT 0,
    `xp`               INT              NOT NULL DEFAULT 0,
    `level`            INT              NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_xp` (`xp`),
    INDEX `idx_level` (`level`),
    INDEX `idx_roles` (`is_owner`, `is_card_author`),
    INDEX `idx_banned` (`is_banned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Propositions communautaires de cartes
CREATE TABLE IF NOT EXISTS `card_proposals` (
    `id`             INT             NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(255)    NOT NULL,
    `character_name` VARCHAR(255)    NOT NULL,
    `serie`          VARCHAR(255)    NOT NULL DEFAULT 'Fullmetal Alchemist',
    `rarity`         ENUM('commune','peu_commune','rare','epique','legendaire') DEFAULT NULL,
    `description`    TEXT            DEFAULT NULL,
    `image_file`     VARCHAR(500)    DEFAULT NULL,
    `status`         ENUM('open','accepted','rejected') NOT NULL DEFAULT 'open',
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_cp_user` (`user_id`),
    INDEX `idx_cp_status` (`status`),
    CONSTRAINT `fk_cp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `card_proposal_likes` (
    `proposal_id` INT             NOT NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`proposal_id`, `user_id`),
    CONSTRAINT `fk_cpl_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `card_proposals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `card_proposal_replies` (
    `id`          INT             NOT NULL AUTO_INCREMENT,
    `proposal_id` INT             NOT NULL,
    `parent_id`   INT             DEFAULT NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `content`     TEXT            NOT NULL,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_cpr_proposal` (`proposal_id`, `created_at`),
    INDEX `idx_cpr_parent` (`parent_id`),
    CONSTRAINT `fk_cpr_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `card_proposals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpr_parent` FOREIGN KEY (`parent_id`) REFERENCES `card_proposal_replies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cpr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File d'activité du site pour le bot Alphonse
CREATE TABLE IF NOT EXISTS `activity_events` (
    `id`           INT          NOT NULL AUTO_INCREMENT,
    `type`         VARCHAR(64)  NOT NULL,
    `user_id`      BIGINT UNSIGNED DEFAULT NULL,
    `title`        VARCHAR(255) NOT NULL,
    `message`      TEXT         NOT NULL,
    `url`          VARCHAR(500) DEFAULT NULL,
    `metadata_json` JSON        DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `delivered_at` TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_activity_delivery` (`delivered_at`, `created_at`),
    INDEX `idx_activity_type` (`type`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cartes disponibles
CREATE TABLE IF NOT EXISTS `cards` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(255) NOT NULL COMMENT 'Nom de la carte',
    `character_name` VARCHAR(255) NOT NULL COMMENT 'Nom du personnage',
    `description`    TEXT         DEFAULT NULL,
    `serie`          VARCHAR(255) NOT NULL DEFAULT 'Fullmetal Alchemist',
    `rarity`         ENUM('commune','peu_commune','rare','epique','legendaire') NOT NULL DEFAULT 'commune',
    `rarity_weight`  INT          NOT NULL DEFAULT 6000 COMMENT 'Plus grand = plus commun',
    `image_file`     VARCHAR(500) NOT NULL COMMENT 'Nom du fichier dans /cards/',
    `author_id`      BIGINT UNSIGNED DEFAULT NULL,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_rarity`    (`rarity`),
    INDEX `idx_active`    (`is_active`),
    INDEX `idx_character` (`character_name`),
    INDEX `idx_author`    (`author_id`),
    CONSTRAINT `fk_card_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection des utilisateurs
CREATE TABLE IF NOT EXISTS `user_cards` (
    `id`          INT             NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `card_id`     INT             NOT NULL,
    `obtained_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_card` (`user_id`, `card_id`),
    CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uc_card` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique des rolls
CREATE TABLE IF NOT EXISTS `roll_history` (
    `id`           INT             NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `card_id`      INT             NOT NULL,
    `was_duplicate` TINYINT(1)     NOT NULL DEFAULT 0,
    `xp_gained`    INT             NOT NULL DEFAULT 0,
    `rolled_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_date` (`user_id`, `rolled_at`),
    CONSTRAINT `fk_rh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rh_card` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
