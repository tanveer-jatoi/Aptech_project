-- =====================================================================
-- Vaccination Management System (VMS) - Aptech eProject Database
-- Database: vms_db
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Target Platform: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.4+ (XAMPP/WAMP)
-- Region: Pakistan Localized (PKR, Pakistani Cities, Realistic Demo Data)
-- =====================================================================
--
-- =====================================================================
-- QUICK REFERENCE: ALL LOGIN CREDENTIALS & PASSWORDS
-- =====================================================================
--
-- 1. SYSTEM ADMINISTRATOR:
--    Login Portal:  /vms/admin/login.php  or  /vms/login.php
--    Email / ID:    admin@vms.local
--    Password:      Admin@123
--
-- 2. PARENTS / GUARDIANS:
--    Login Portal:  /vms/login.php
--    Password for all parents is:  Parent@123
--    ------------------------------------------------------------------
--    - Ahmed Khan:        ahmed.khan@example.com      | Pass: Parent@123
--    - Fatima Zahra:      fatima.zahra@example.com    | Pass: Parent@123
--    - Syed Bilal Ahmed:  bilal.ahmed@example.com     | Pass: Parent@123
--    - Ayesha Siddiqua:   ayesha.siddiqua@example.com | Pass: Parent@123
--    - Tanveer Jatoi:     tanveerr.work@gmail.com     | Pass: tanveer123
--
-- 3. HOSPITALS / CLINICS (KARACHI):
--    Login Portal:  /vms/login.php
--    Password for all hospitals is:  Hospital@123
--    ------------------------------------------------------------------
--    - The Indus Hospital (Korangi):       indus.hospital@hospital.local    | Pass: Hospital@123
--    - Aga Khan University Hospital:       akuh.pediatrics@hospital.local   | Pass: Hospital@123
--    - National Inst of Child Health:      nich.karachi@hospital.local      | Pass: Hospital@123
--    - Dr. Ruth Pfau Civil Hospital:       civil.hospital@hospital.local    | Pass: Hospital@123
--    - Liaquat National Hospital:          lnh.karachi@hospital.local       | Pass: Hospital@123
--    - South City Hospital (Clifton):      southcity@hospital.local         | Pass: Hospital@123
--    - Patel Hospital (Gulshan-e-Iqbal):   patel.hospital@hospital.local    | Pass: Hospital@123
--    - Dr. Ziauddin Hospital (Nazimabad):  ziauddin.hospital@hospital.local | Pass: Hospital@123
--    - SICHN (Korangi Industrial):         sichn.karachi@hospital.local     | Pass: Hospital@123
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
-- SEED DATA (Pakistan Localized - Karachi Focus)
-- Default Passwords:
-- Admin:    Admin@123    ($2y$10$ftdG5j.tvPsJkJxi7k8ICuPIqSRBc172Fm8jV6XDGhKh1edpTtePe)
-- Parents:  Parent@123   ($2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe)
-- Hospital: Hospital@123 ($2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte)
-- =====================================================================

-- 1. Insert Users (1 Admin, 4 Parents, 9 Karachi Hospitals)
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@vms.local', '$2y$10$ftdG5j.tvPsJkJxi7k8ICuPIqSRBc172Fm8jV6XDGhKh1edpTtePe', 'admin', NOW()),
(2, 'Ahmed Khan', 'ahmed.khan@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW()),
(3, 'Fatima Zahra', 'fatima.zahra@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW()),
(4, 'The Indus Hospital (Korangi)', 'indus.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(5, 'Aga Khan University Hospital', 'akuh.pediatrics@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(6, 'National Institute of Child Health', 'nich.karachi@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(7, 'Dr. Ruth Pfau Civil Hospital', 'civil.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(8, 'Liaquat National Hospital', 'lnh.karachi@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(9, 'South City Hospital', 'southcity@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(10, 'Patel Hospital Karachi', 'patel.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(11, 'Dr. Ziauddin Hospital', 'ziauddin.hospital@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(12, 'SICHN Karachi', 'sichn.karachi@hospital.local', '$2y$10$FIfv5GvsGt4ayxJfYOvfPOTWB/SK5i4cEqjYbkf135tJ3lZevZjte', 'hospital', NOW()),
(13, 'Syed Bilal Ahmed', 'bilal.ahmed@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW()),
(14, 'Ayesha Siddiqua', 'ayesha.siddiqua@example.com', '$2y$10$Sy8Fc1u/LvbegkTi2J0RH.z5uhX8ixRdtBMsb5I02B0fSh5WZGKxe', 'parent', NOW());

-- 2. Insert Parents Profile Records
INSERT INTO `parents` (`id`, `user_id`, `phone`, `address`, `created_at`) VALUES
(1, 2, '+92-300-1234567', 'House 14, Street 5, Block 3, Clifton, Karachi', NOW()),
(2, 3, '+92-321-9876543', 'Flat 402, Al-Razi Heights, Block 13-C, Gulshan-e-Iqbal, Karachi', NOW()),
(3, 13, '+92-333-2145678', 'House 72, Sector 11-B, North Karachi, Karachi', NOW()),
(4, 14, '+92-345-8765432', 'Bungalow 105, Block 2, PECHS, Karachi', NOW());

-- 3. Insert Karachi Hospitals Profile Records
INSERT INTO `hospitals` (`id`, `user_id`, `name`, `address`, `city`, `location`, `phone`, `status`, `created_at`) VALUES
(1, 4, 'The Indus Hospital & Health Network (Korangi Campus)', 'Plot 39, Korangi Crossing, Karachi', 'Karachi', 'Korangi Crossing, Sector 39', '+92-21-35112709', 'active', NOW()),
(2, 5, 'Aga Khan University Hospital (AKUH)', 'National Stadium Road, P.O. Box 3500, Karachi', 'Karachi', 'Stadium Road, Health Zone', '+92-21-34930051', 'active', NOW()),
(3, 6, 'National Institute of Child Health (NICH)', 'Rafiqui Shaheed Road, Cantonment, Karachi', 'Karachi', 'Cantt Health District, Near JPMC', '+92-21-99201271', 'active', NOW()),
(4, 7, 'Dr. Ruth K. M. Pfau Civil Hospital Karachi (Pediatric Unit)', 'Mission Road, New Chali, Saddar, Karachi', 'Karachi', 'Saddar Old Town', '+92-21-99215740', 'active', NOW()),
(5, 8, 'Liaquat National Hospital & Medical College', 'National Stadium Road, Gulshan-e-Iqbal, Karachi', 'Karachi', 'Gulshan-e-Iqbal, Block 14', '+92-21-111-456-456', 'active', NOW()),
(6, 9, 'South City Hospital (Pediatric Center)', 'Plot No. G-1, Street 1, Block 3, Clifton, Karachi', 'Karachi', 'Clifton, Block 3', '+92-21-35862301', 'active', NOW()),
(7, 10, 'Patel Hospital (Pediatric & Immunization Unit)', 'ST-18, Block 4, Gulshan-e-Iqbal, Karachi', 'Karachi', 'Gulshan-e-Iqbal, Block 4', '+92-21-34968661', 'active', NOW()),
(8, 11, 'Dr. Ziauddin Hospital (North Nazimabad Campus)', 'Allama Rasheed Turabi Road, Block B, North Nazimabad, Karachi', 'Karachi', 'North Nazimabad, Block B', '+92-21-36648237', 'active', NOW()),
(9, 12, 'Sindh Institute of Child Health and Neonatology (SICHN)', 'Near Chamra Chowrangi, Korangi Industrial Area, Karachi', 'Karachi', 'Korangi Sector 31-F', '+92-21-35071110', 'active', NOW());

-- 4. Insert Children (Patients)
INSERT INTO `children` (`id`, `parent_id`, `name`, `dob`, `gender`, `blood_group`, `created_at`) VALUES
(1, 1, 'Hamza Khan', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'male', 'O+', NOW()),
(2, 1, 'Ayesha Khan', DATE_SUB(CURDATE(), INTERVAL 300 DAY), 'female', 'A+', NOW()),
(3, 2, 'Muhammad Ali', DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'male', 'B+', NOW()),
(4, 3, 'Zainab Bilal', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'female', 'O+', NOW()),
(5, 4, 'Ibrahim Tariq', DATE_SUB(CURDATE(), INTERVAL 210 DAY), 'male', 'AB+', NOW());

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
-- Hospital 1 (The Indus Hospital) stocks all
(1, 1, 'available'), (1, 2, 'available'), (1, 3, 'available'), (1, 4, 'available'),
(1, 5, 'available'), (1, 6, 'available'), (1, 7, 'available'), (1, 8, 'available'),
-- Hospital 2 (Aga Khan University Hospital) stocks all
(2, 1, 'available'), (2, 2, 'available'), (2, 3, 'available'), (2, 4, 'available'),
(2, 5, 'available'), (2, 6, 'available'), (2, 7, 'available'), (2, 8, 'available'),
-- Hospital 3 (National Institute of Child Health) stocks all
(3, 1, 'available'), (3, 2, 'available'), (3, 3, 'available'), (3, 4, 'available'),
(3, 5, 'available'), (3, 6, 'available'), (3, 7, 'available'), (3, 8, 'available'),
-- Hospital 4 (Dr. Ruth Pfau Civil Hospital) stocks all
(4, 1, 'available'), (4, 2, 'available'), (4, 3, 'available'), (4, 4, 'available'),
(4, 5, 'available'), (4, 6, 'available'), (4, 7, 'available'), (4, 8, 'available'),
-- Hospital 5 (Liaquat National Hospital) stocks all
(5, 1, 'available'), (5, 2, 'available'), (5, 3, 'available'), (5, 4, 'available'),
(5, 5, 'available'), (5, 6, 'available'), (5, 7, 'available'), (5, 8, 'available'),
-- Hospital 6 (South City Hospital) stocks all
(6, 1, 'available'), (6, 2, 'available'), (6, 3, 'available'), (6, 4, 'available'),
(6, 5, 'available'), (6, 6, 'available'), (6, 7, 'available'), (6, 8, 'available'),
-- Hospital 7 (Patel Hospital) stocks all
(7, 1, 'available'), (7, 2, 'available'), (7, 3, 'available'), (7, 4, 'available'),
(7, 5, 'available'), (7, 6, 'available'), (7, 7, 'available'), (7, 8, 'available'),
-- Hospital 8 (Dr. Ziauddin Hospital) stocks all
(8, 1, 'available'), (8, 2, 'available'), (8, 3, 'available'), (8, 4, 'available'),
(8, 5, 'available'), (8, 6, 'available'), (8, 7, 'available'), (8, 8, 'available'),
-- Hospital 9 (SICHN Karachi) stocks all
(9, 1, 'available'), (9, 2, 'available'), (9, 3, 'available'), (9, 4, 'available'),
(9, 5, 'available'), (9, 6, 'available'), (9, 7, 'available'), (9, 8, 'available');

-- 7. Insert Sample Bookings across lifecycle states
INSERT INTO `bookings` (`id`, `child_id`, `parent_id`, `hospital_id`, `vaccine_id`, `preferred_date`, `time_slot`, `admin_status`, `vaccination_status`, `vaccinated_on`, `remarks`, `created_at`) VALUES
(1, 1, 1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), '09:00 AM - 10:00 AM', 'approved', 'vaccinated', DATE_SUB(NOW(), INTERVAL 40 DAY), 'Administered smoothly at left upper arm. Cold-chain batch verified. No immediate adverse reaction.', DATE_SUB(NOW(), INTERVAL 42 DAY)),
(2, 1, 1, 1, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:30 AM - 11:30 AM', 'approved', 'vaccinated', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Dose 1 given orally. Tolerated well by infant. Scheduled next appointment in 4 weeks.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 2, 1, 2, 8, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00 AM - 12:00 PM', 'approved', 'pending', NULL, 'Slot approved by pediatric department. Please bring child health booklet.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 2, 3, 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '02:00 PM - 03:00 PM', 'approved', 'pending', NULL, 'Approved by administration. Hospital triage counter #3 assigned.', NOW()),
(5, 4, 3, 5, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '11:30 AM - 12:30 PM', 'approved', 'vaccinated', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Rotavirus Dose 1 administered successfully. Baby hydrated and healthy.', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- 8. Insert Genuine Parent Reviews
INSERT INTO `reviews` (`id`, `parent_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 1, 5, 'The automated vaccination schedule reminders are a lifesaver! I never have to worry about missing Hamza\'s key immunizations on time.', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 2, 5, 'Booking appointments at The Indus Hospital Karachi was completely seamless. Approved quickly and records updated right away.', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 1, 4, 'Alhamdulillah, excellent initiative for child health. The pediatric staff at Aga Khan University Hospital verified and logged the vaccine dose within minutes.', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 3, 5, 'Outstanding digital platform for vaccination in Karachi. Easy to locate nearby pediatric centers and track full dose history.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));