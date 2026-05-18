-- Creates normalized travel idea schema with lookup tables, pivot relationships, and itinerary details.

CREATE TABLE IF NOT EXISTS `provinces` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `experience_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `travel_ideas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `province_id` INT UNSIGNED DEFAULT NULL,
  `province_slug` VARCHAR(150) DEFAULT NULL,
  `image_path` VARCHAR(500) DEFAULT NULL,
  `duration_days` INT UNSIGNED DEFAULT NULL,
  `nights` INT UNSIGNED DEFAULT NULL,
  `transport` VARCHAR(150) DEFAULT NULL,
  `accommodation` VARCHAR(150) DEFAULT NULL,
  `best_time` VARCHAR(150) DEFAULT NULL,
  `pro_tip` TEXT DEFAULT NULL,
  `difficulty` ENUM('Easy','Moderate','Challenging') DEFAULT NULL,
  `status` ENUM('draft','published') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`province_id`),
  CONSTRAINT `fk_travel_ideas_province` FOREIGN KEY (`province_id`) REFERENCES `provinces`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `travel_idea_experiences` (
  `idea_id` INT UNSIGNED NOT NULL,
  `experience_type_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`idea_id`,`experience_type_id`),
  CONSTRAINT `fk_idea_experience_idea` FOREIGN KEY (`idea_id`) REFERENCES `travel_ideas`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_idea_experience_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `itineraries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idea_id` INT UNSIGNED NOT NULL,
  `day_order` INT UNSIGNED NOT NULL,
  `day_title` VARCHAR(255) DEFAULT NULL,
  `morning` TEXT DEFAULT NULL,
  `afternoon` TEXT DEFAULT NULL,
  `evening` TEXT DEFAULT NULL,
  `image_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`idea_id`),
  CONSTRAINT `fk_itineraries_idea` FOREIGN KEY (`idea_id`) REFERENCES `travel_ideas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `travel_idea_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idea_id` INT UNSIGNED NOT NULL,
  `content` MEDIUMTEXT DEFAULT NULL,
  `highlights` JSON DEFAULT NULL,
  `logistics` JSON DEFAULT NULL,
  `hero_image` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX (`idea_id`),
  CONSTRAINT `fk_idea_details_idea` FOREIGN KEY (`idea_id`) REFERENCES `travel_ideas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `provinces` (`name`, `slug`) VALUES
  ('Koshi', 'koshi'),
  ('Madhesh', 'madhesh'),
  ('Bagmati', 'bagmati'),
  ('Gandaki', 'gandaki'),
  ('Lumbini', 'lumbini'),
  ('Karnali', 'karnali'),
  ('Sudurpashchim', 'sudurpaschim');

INSERT IGNORE INTO `experience_types` (`name`, `slug`) VALUES
  ('Trekking', 'trekking'),
  ('Culture', 'culture'),
  ('Wildlife', 'wildlife'),
  ('Pilgrimage', 'pilgrimage'),
  ('Adventure', 'adventure'),
  ('Nature', 'nature'),
  ('History', 'history');
