-- =====================================================================
-- Vaccination Management System (VMS) - Aptech eProject Database
-- Database: vms_db
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Target Platform: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.4+ (XAMPP/WAMP)
-- Region: Pakistan Localized (PKR, Pakistani Cities, Realistic Demo Data)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `vms_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `vms_db`;

-- Drop existing tables in reverse dependency order for clean re-import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `hospital_vaccines`;
DROP TABLE IF EXISTS `vaccines`;
DROP TABLE IF EXISTS `children`;
DROP TABLE IF EXISTS `hospitals`;
DROP TABLE IF EXISTS `parents`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- TABLE: users
-- Core authentication table for all 3 system roles: admin, parent, hospital.
-- Every parent and hospital profile links back to a record in this table.
-- =====================================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Full name of user or organization representative',
  `email` VARCHAR(120) NOT NULL COMMENT 'Unique login email address',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Bcrypt password hash produced by password_hash()',
  `role` ENUM('admin', 'parent', 'hospital') NOT NULL COMMENT 'System role controlling authorization',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: parents
-- Detailed profile information for registered parents.
-- Links 1-to-1 with users where role = 'parent'.
-- =====================================================================
CREATE TABLE `parents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to users.id',
  `phone` VARCHAR(25) NOT NULL COMMENT 'Primary contact number for notifications (+92)',
  `address` TEXT NOT NULL COMMENT 'Residential physical address',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parents_user_id` (`user_id`),
  CONSTRAINT `fk_parents_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: hospitals
-- Profile and physical location details for vaccination centers.
-- Links 1-to-1 with users where role = 'hospital'.
-- =====================================================================
CREATE TABLE `hospitals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to users.id',
  `name` VARCHAR(150) NOT NULL COMMENT 'Official name of the hospital / health center',
  `address` TEXT NOT NULL COMMENT 'Street address of the hospital',
  `city` VARCHAR(80) NOT NULL COMMENT 'City where hospital is located',
  `location` VARCHAR(150) NOT NULL COMMENT 'District, zone, or prominent landmark for parent search',
  `phone` VARCHAR(25) NOT NULL COMMENT 'Hospital reception / pediatric hotline',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Account status',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hospitals_user_id` (`user_id`),
  KEY `idx_hospitals_city` (`city`),
  KEY `idx_hospitals_status` (`status`),
  CONSTRAINT `fk_hospitals_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: children
-- Stores registered infants and children belonging to parents.
-- DOB is used to calculate immunization schedules dynamically.
-- =====================================================================
CREATE TABLE `children` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to parents.id',
  `name` VARCHAR(100) NOT NULL COMMENT 'Child full name',
  `dob` DATE NOT NULL COMMENT 'Date of birth used for schedule calculations',
  `gender` ENUM('male', 'female', 'other') NOT NULL COMMENT 'Biological gender',
  `blood_group` VARCHAR(10) NOT NULL COMMENT 'Blood group e.g. A+, B+, O+, AB-',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_children_parent_id` (`parent_id`),
  KEY `idx_children_dob` (`dob`),
  CONSTRAINT `fk_children_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: vaccines
-- Master catalog of vaccines with recommended age in days and dosage info.
-- Status indicates general availability in the healthcare network.
-- =====================================================================
CREATE TABLE `vaccines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Vaccine name e.g. BCG, OPV, MMR',
  `description` TEXT NOT NULL COMMENT 'Medical indications, preventable illnesses, instructions',
  `recommended_age_days` INT UNSIGNED NOT NULL COMMENT 'Days from birth when dose is due (0 = Birth)',
  `doses` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Total recommended doses in the series',
  `status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vaccines_status` (`status`),
  KEY `idx_vaccines_age_days` (`recommended_age_days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: hospital_vaccines
-- Inventory link between hospitals and vaccines.
-- Allows individual hospitals to declare stock availability.
-- =====================================================================
CREATE TABLE `hospital_vaccines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hospital_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to hospitals.id',
  `vaccine_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to vaccines.id',
  `status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hospital_vaccine` (`hospital_id`, `vaccine_id`),
  KEY `idx_hv_status` (`status`),
  KEY `idx_hv_hosp_status` (`hospital_id`, `status`),
  CONSTRAINT `fk_hv_hospital` FOREIGN KEY (`hospital_id`)
    REFERENCES `hospitals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hv_vaccine` FOREIGN KEY (`vaccine_id`)
    REFERENCES `vaccines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: bookings
-- Central appointment lifecycle table.
-- Flow: Parent requests -> Admin approves/rejects -> Hospital administers and logs.
-- =====================================================================
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_id` INT UNSIGNED NOT NULL COMMENT 'Child receiving vaccination',
  `parent_id` INT UNSIGNED NOT NULL COMMENT 'Parent who booked appointment',
  `hospital_id` INT UNSIGNED NOT NULL COMMENT 'Selected vaccination center',
  `vaccine_id` INT UNSIGNED NOT NULL COMMENT 'Vaccine to be administered',
  `preferred_date` DATE NOT NULL COMMENT 'Date booked for the appointment',
  `time_slot` VARCHAR(30) NOT NULL COMMENT 'e.g. 09:00 AM - 10:00 AM',
  `admin_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT 'Admin approval status',
  `vaccination_status` ENUM('pending', 'vaccinated', 'not_vaccinated') NOT NULL DEFAULT 'pending' COMMENT 'Hospital verification status',
  `vaccinated_on` DATETIME DEFAULT NULL COMMENT 'Timestamp when hospital confirmed vaccination',
  `remarks` TEXT DEFAULT NULL COMMENT 'Clinical observations or rejection/cancellation reasons',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_child` (`child_id`),
  KEY `idx_bookings_parent` (`parent_id`),
  KEY `idx_bookings_hospital` (`hospital_id`),
  KEY `idx_bookings_vaccine` (`vaccine_id`),
  KEY `idx_bookings_admin_status` (`admin_status`),
  KEY `idx_bookings_vac_status` (`vaccination_status`),
  KEY `idx_bookings_preferred_date` (`preferred_date`),
  KEY `idx_bookings_parent_child` (`parent_id`, `child_id`),
  KEY `idx_bookings_hosp_schedule` (`hospital_id`, `preferred_date`, `vaccination_status`),
  CONSTRAINT `fk_bookings_child` FOREIGN KEY (`child_id`)
    REFERENCES `children` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_hospital` FOREIGN KEY (`hospital_id`)
    REFERENCES `hospitals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_vaccine` FOREIGN KEY (`vaccine_id`)
    REFERENCES `vaccines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: reviews
-- Real parent feedback and ratings.
-- Filtered by is_approved = 1 to guarantee safe public presentation.
-- Strictly no fabricated testimonials or false claims.
-- =====================================================================
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to parents.id',
  `rating` TINYINT UNSIGNED NOT NULL COMMENT 'Rating score from 1 (lowest) to 5 (highest)',
  `comment` TEXT NOT NULL COMMENT 'Written testimonial from parent',
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Approved for public presentation, 0 = Pending/Hidden',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_parent` (`parent_id`),
  KEY `idx_reviews_approved` (`is_approved`),
  CONSTRAINT `fk_reviews_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA (Pakistan Localized)
-- Default Passwords:
-- Admin:    Admin@123    ($2y$10$ftdG5j.tvPsJkJxi7k8ICuPIqSRBc172Fm8jV6XDGhKh1edpTtePe)
-- Parents:  Parent@123   ($2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe)
-- Hospital: Hospital@123 ($2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte)
-- =====================================================================

-- 1. Insert Users (1 Admin, 2 Parents, 3 Hospitals)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@vms.local', '$2y$10$ftdG5j.tvPsJkJxi7k8ICuPIqSRBc172Fm8jV6XDGhKh1edpTtePe', 'admin', NOW()),
(2, 'Ahmed Khan', 'ahmed.khan@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW()),
(3, 'Fatima Khan', 'fatima.khan@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW()),
(4, 'Indus Children Hospital', 'indus.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(5, 'The Children\'s Hospital Lahore', 'children.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(6, 'Federal Pediatric Care Center', 'federal.pediatrics@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW());

-- 2. Insert Parents Profile Records
INSERT INTO `parents` (`id`, `user_id`, `phone`, `address`, `created_at`) VALUES
(1, 2, '+92-300-1234567', 'House 14, Street 5, Block 3, Clifton, Karachi', NOW()),
(2, 3, '+92-321-9876543', 'House 82, Sector F-8/2, Islamabad', NOW());

-- 3. Insert Hospitals Profile Records
INSERT INTO `hospitals` (`id`, `user_id`, `name`, `address`, `city`, `location`, `phone`, `status`, `created_at`) VALUES
(1, 4, 'Indus Children Hospital', 'Plot 39, Korangi Crossing, Karachi', 'Karachi', 'Korangi Crossing, Sector 39', '+92-21-35112709', 'active', NOW()),
(2, 5, 'The Children\'s Hospital Lahore', 'Main Ferozepur Road, Lahore', 'Lahore', 'Ferozepur Road Health Zone', '+92-42-99230901', 'active', NOW()),
(3, 6, 'Federal Pediatric Care Center', 'Luqman Hakeem Road, Sector G-6/2, Islamabad', 'Islamabad', 'Sector G-6/2 Pediatric Center', '+92-51-9224400', 'active', NOW());

-- 4. Insert Children (Dates dynamically relative to current date for realistic testing)
INSERT INTO `children` (`id`, `parent_id`, `name`, `dob`, `gender`, `blood_group`, `created_at`) VALUES
(1, 1, 'Hamza Khan', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'male', 'O+', NOW()),
(2, 1, 'Ayesha Khan', DATE_SUB(CURDATE(), INTERVAL 300 DAY), 'female', 'A+', NOW()),
(3, 2, 'Muhammad Ali', DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'male', 'B+', NOW());

-- 5. Insert Vaccines (8 standard infant vaccines recommended by national immunization schedule in Pakistan)
INSERT INTO `vaccines` (`id`, `name`, `description`, `recommended_age_days`, `doses`, `status`, `created_at`) VALUES
(1, 'BCG (Bacillus Calmette-Guérin)', 'Protects newborns from severe forms of tuberculosis, including tuberculous meningitis.', 0, 1, 'available', NOW()),
(2, 'Hepatitis B (HepB)', 'Prevents Hepatitis B infection, protecting the liver from lifelong chronic disease and cirrhosis.', 0, 3, 'available', NOW()),
(3, 'OPV (Oral Polio Vaccine)', 'Live attenuated vaccine administered orally to protect against wild poliovirus and paralysis.', 42, 3, 'available', NOW()),
(4, 'Pentavalent (DTP-HepB-Hib)', 'Five-in-one vaccine providing immunity against Diphtheria, Tetanus, Pertussis, Hepatitis B, and Hib.', 42, 3, 'available', NOW()),
(5, 'Rotavirus', 'Oral vaccine defending infants against severe rotavirus gastroenteritis and dangerous dehydration.', 42, 2, 'available', NOW()),
(6, 'PCV (Pneumococcal Conjugate)', 'Immunizes infants against Streptococcus pneumoniae causing pneumonia, ear infections, and bacteremia.', 42, 3, 'available', NOW()),
(7, 'IPV (Inactivated Polio Vaccine)', 'Injectable inactivated vaccine administered at 14 weeks to boost polio protection.', 98, 2, 'available', NOW()),
(8, 'MMR (Measles, Mumps, Rubella)', 'Crucial vaccine shielding children from three dangerous viral infections and secondary complications.', 270, 2, 'available', NOW());

-- 6. Insert Hospital Vaccine Stock Allocations
INSERT INTO `hospital_vaccines` (`hospital_id`, `vaccine_id`, `status`) VALUES
-- Hospital 1 (Indus Children Hospital) stocks all
(1, 1, 'available'), (1, 2, 'available'), (1, 3, 'available'), (1, 4, 'available'),
(1, 5, 'available'), (1, 6, 'available'), (1, 7, 'available'), (1, 8, 'available'),
-- Hospital 2 (The Children's Hospital Lahore)
(2, 1, 'available'), (2, 2, 'available'), (2, 3, 'available'), (2, 4, 'available'),
(2, 5, 'unavailable'), (2, 6, 'available'), (2, 7, 'available'), (2, 8, 'available'),
-- Hospital 3 (Federal Pediatric Care Center)
(3, 1, 'available'), (3, 2, 'available'), (3, 3, 'available'), (3, 4, 'available'),
(3, 5, 'available'), (3, 6, 'available'), (3, 7, 'available'), (3, 8, 'unavailable');

-- 7. Insert Sample Bookings across lifecycle states
INSERT INTO `bookings` (`id`, `child_id`, `parent_id`, `hospital_id`, `vaccine_id`, `preferred_date`, `time_slot`, `admin_status`, `vaccination_status`, `vaccinated_on`, `remarks`, `created_at`) VALUES
(1, 1, 1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), '09:00 AM - 10:00 AM', 'approved', 'vaccinated', DATE_SUB(NOW(), INTERVAL 40 DAY), 'Administered smoothly at left upper arm. No immediate reaction.', DATE_SUB(NOW(), INTERVAL 42 DAY)),
(2, 1, 1, 1, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:30 AM - 11:30 AM', 'approved', 'vaccinated', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Dose 1 given orally. Tolerated well by infant.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 2, 1, 2, 8, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00 AM - 12:00 PM', 'approved', 'pending', NULL, 'Slot approved by admin. Please bring child health booklet.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 2, 3, 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '02:00 PM - 03:00 PM', 'pending', 'pending', NULL, 'Awaiting administrative verification.', NOW());
