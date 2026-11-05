CREATE DATABASE IF NOT EXISTS `stemmate_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stemmate_db`;

-- Users / Sign-in State (S1, S16)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'Facilitator',
  `pin_code` VARCHAR(10) DEFAULT '1234',
  `language` VARCHAR(20) DEFAULT 'English'
) ENGINE=InnoDB;

INSERT INTO `users` (`username`, `role`, `pin_code`, `language`) VALUES 
('Kasanga Leonel', 'Facilitator', '1234', 'English');

-- Activities & Filtering (S3, S4, S5)
CREATE TABLE `activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `level` VARCHAR(50) NOT NULL,
  `duration_mins` INT NOT NULL,
  `topic` VARCHAR(100) NOT NULL,
  `materials_required` TEXT NOT NULL,
  `safety_notes` TEXT NOT NULL,
  `download_size_mb` DECIMAL(4,2) DEFAULT 0.30
) ENGINE=InnoDB;

INSERT INTO `activities` (`title`, `level`, `duration_mins`, `topic`, `materials_required`, `safety_notes`, `download_size_mb`) VALUES
('Simple Circuit Challenge', 'Grade 5-7', 25, 'Electricity', 'Copper wire, 1.5V battery, Small LED bulb', 'Ensure bare wires do not short-circuit batteries. Pair learners inclusively.', 0.30);
-- Additional activities to populate S4 Results screen
INSERT INTO `activities` (`title`, `level`, `duration_mins`, `topic`, `materials_required`, `safety_notes`, `download_size_mb`) VALUES
('Magnet Magic', 'Grade 5-7', 20, 'Force & Motion', 'Bar magnet, Iron filings, Paper clips', 'Keep magnets away from digital devices and sensitive electronics.', 0.25);

-- Saved Offline Activities (S6, S7)
CREATE TABLE `saved_activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Session Plans & Drafts (S8, S9, S10, S11, S12, S13, S14, S15)
CREATE TABLE `session_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` INT NOT NULL,
  `step_1` TEXT NOT NULL,
  `step_2` TEXT NOT NULL,
  `step_3` TEXT NOT NULL,
  `step_4` TEXT NOT NULL,
  `step_5` TEXT NOT NULL,
  `safety_notes` TEXT NOT NULL,
  `status` ENUM('draft', 'queued_sync', 'synced', 'conflict') DEFAULT 'draft',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;