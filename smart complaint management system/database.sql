-- ============================================================================
-- Smart Complaint Management System - Database Schema & Seed Data
-- Database Name: smart_complaint_db
-- Server: MySQL / MariaDB (XAMPP)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `smart_complaint_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smart_complaint_db`;

-- Disable foreign key checks during table creation/reset
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `complaint_history`;
DROP TABLE IF EXISTS `complaint_responses`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 1. TABLE: users
-- ============================================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `account_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. TABLE: complaints
-- ============================================================================
CREATE TABLE `complaints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `location` VARCHAR(200) NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
  `status` ENUM('Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Submitted',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_complaints_user_id` (`user_id`),
  KEY `idx_complaints_status` (`status`),
  KEY `idx_complaints_priority` (`priority`),
  KEY `idx_complaints_category` (`category`),
  KEY `idx_complaints_created_at` (`created_at`),
  CONSTRAINT `fk_complaints_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. TABLE: complaint_responses
-- ============================================================================
CREATE TABLE `complaint_responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `response` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_responses_complaint_id` (`complaint_id`),
  KEY `idx_responses_admin_id` (`admin_id`),
  CONSTRAINT `fk_responses_complaint`
    FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_responses_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. TABLE: complaint_history
-- ============================================================================
CREATE TABLE `complaint_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_id` INT UNSIGNED NOT NULL,
  `changed_by` INT UNSIGNED DEFAULT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_history_complaint_id` (`complaint_id`),
  KEY `idx_history_changed_by` (`changed_by`),
  CONSTRAINT `fk_history_complaint`
    FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user`
    FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SAMPLE SEED DATA (For Testing & Demonstration)
-- Default Admin Login: admin@smartcomplaint.com / Admin@12345
-- Default User Login:  john@example.com         / User@12345
-- Default User Login:  sarah@example.com        / User@12345
-- ============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `account_status`, `created_at`) VALUES
(1, 'System Administrator', 'admin@smartcomplaint.com', '+1-555-0100', 'y$D1Jrj4R3.u9mjcKUj3hOdekQZ9tzZAe07nbuAfms4LriQXP4eVck6', 'admin', 'active', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 'John Miller', 'john@example.com', '+1-555-0142', '+1-555-0142y$REw4Ad7IMIke1YBUEUMLJexXlkJA6I1kpahEntSGNl0tjf1rCPnqS', 'user', 'active', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 'Sarah Jenkins', 'sarah@example.com', '+1-555-0198', '+1-555-0198y$REw4Ad7IMIke1YBUEUMLJexXlkJA6I1kpahEntSGNl0tjf1rCPnqS', 'user', 'active', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(4, 'David Ross', 'david@example.com', '+1-555-0166', '+1-555-0166y$REw4Ad7IMIke1YBUEUMLJexXlkJA6I1kpahEntSGNl0tjf1rCPnqS', 'user', 'inactive', DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT INTO `complaints` (`id`, `user_id`, `title`, `description`, `category`, `location`, `image_path`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Recurring Streetlight Outage on Main Avenue', 'Three consecutive LED streetlights near Block B intersection have stopped working for the past four nights, creating safety hazards for pedestrians and evening commuters.', 'Electricity & Power', 'Block B, Main Avenue Intersection', NULL, 'High', 'In Progress', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 'Water Pipeline Leakage Near North Gate', 'A main supply pipe near the North Gate parking area has a steady leak causing water accumulation and low pressure in the adjacent building.', 'Water & Sanitation', 'North Gate Parking Lot, Sector 4', NULL, 'Urgent', 'Resolved', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 3, 'Campus Wi-Fi Connectivity Drops in Library', 'The wireless access point on the 2nd floor reading hall disconnects every 15 minutes during peak hours.', 'IT & Network Services', 'Central Library - 2nd Floor Reading Hall', NULL, 'Medium', 'Under Review', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 'Damaged Sidewalk Paving Slabs', 'Several concrete slabs along the East Walkway are cracked and uneven, posing a tripping hazard.', 'Infrastructure & Facilities', 'East Walkway near Building C', NULL, 'Medium', 'Submitted', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 2, 'Incorrect Monthly Maintenance Charge on Invoice', 'My October billing statement reflects a duplicate sanitation surcharge that was already paid last month.', 'Billing & Finance', 'Accounts Office / Online Portal', NULL, 'Low', 'Rejected', DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT INTO `complaint_responses` (`id`, `complaint_id`, `admin_id`, `response`, `created_at`) VALUES
(1, 1, 1, 'Our electrical maintenance team has inspected the feeder pillar and identified a faulty relay switch. Replacement parts have been ordered and installation is scheduled within 48 hours.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 1, 'Emergency plumbing crew replaced the damaged gasket and sealed the main pipeline joint. Water pressure has been restored to normal levels.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 3, 1, 'Network diagnostics have been initiated by the IT department to check router load balancing on the 2nd floor.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 5, 1, 'After reviewing your payment ledger, the charge on the October invoice corresponds to the Q3 adjustment approved in August, not a duplicate billing.', DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT INTO `complaint_history` (`id`, `complaint_id`, `changed_by`, `old_status`, `new_status`, `remarks`, `created_at`) VALUES
(1, 1, 2, NULL, 'Submitted', 'Complaint initially submitted by user.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 1, 1, 'Submitted', 'Under Review', 'Assigned to Electrical Engineering unit for inspection.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 1, 1, 'Under Review', 'In Progress', 'Priority escalated to High. Relay switch replacement in progress.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 2, 2, NULL, 'Submitted', 'Complaint initially submitted by user.', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(5, 2, 1, 'Submitted', 'In Progress', 'Marked as Urgent and dispatched plumbing team immediately.', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(6, 2, 1, 'In Progress', 'Resolved', 'Pipeline leak repaired and site cleaned.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 3, 3, NULL, 'Submitted', 'Complaint initially submitted by user.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(8, 3, 1, 'Submitted', 'Under Review', 'Forwarded to IT Infrastructure Support.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 4, 3, NULL, 'Submitted', 'Complaint initially submitted by user.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(10, 5, 2, NULL, 'Submitted', 'Complaint initially submitted by user.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(11, 5, 1, 'Submitted', 'Rejected', 'Verified with billing records; charge is valid and not a duplicate.', DATE_SUB(NOW(), INTERVAL 6 DAY));
