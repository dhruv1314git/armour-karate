-- =====================================================================
-- Armour Martial Arts – Gujju Karate Association
-- Database Schema Migration (Stage 1)
-- File: database/migrations/001_create_schema.sql
-- Database: armour_karate_db
-- Target: MySQL 8.0+ / MariaDB 10.4+
-- Collation: utf8mb4_unicode_ci | Engine: InnoDB
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =====================================================================
-- 1. AUTHENTICATION & USERS
-- =====================================================================

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    image VARCHAR(512) NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('STUDENT', 'ADMIN') NOT NULL DEFAULT 'STUDENT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email),
    INDEX idx_users_role (role),
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_profiles (1-to-1 with users where role = 'ADMIN')
CREATE TABLE IF NOT EXISTS admin_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    profile_photo_url VARCHAR(512) NULL,
    profile_photo_public_id VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    karate_rank VARCHAR(100) NULL,
    qualifications TEXT NULL,
    experience TEXT NULL,
    achievements TEXT NULL,
    biography TEXT NULL,
    academy_info TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_admin_profiles_user UNIQUE (user_id),
    CONSTRAINT fk_admin_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: password_reset_tokens (replaces external auth verification tokens)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_prt_token_hash UNIQUE (token_hash),
    CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_prt_user (user_id),
    INDEX idx_prt_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. ACADEMY LOCATIONS & BELT SYSTEM
-- =====================================================================

-- Table: class_locations (Dojos & Branches)
CREATE TABLE IF NOT EXISTS class_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    instructor VARCHAR(255) NULL,
    training_days VARCHAR(255) NOT NULL,
    training_time VARCHAR(100) NOT NULL,
    contact VARCHAR(50) NULL,
    map_url VARCHAR(512) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_locations_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: belts (Ranks 1 to 8: White through Black)
CREATE TABLE IF NOT EXISTS belts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rank INT NOT NULL,
    color VARCHAR(7) NOT NULL,
    exam_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_belts_rank UNIQUE (rank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: student_id_counter (Atomic sequence counter for KAR-YYYY-NNNN)
CREATE TABLE IF NOT EXISTS student_id_counter (
    year INT NOT NULL PRIMARY KEY,
    last_seq INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. STUDENT MANAGEMENT
-- =====================================================================

-- Table: student_profiles (1-to-1 with users where role = 'STUDENT')
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    student_id VARCHAR(20) NULL,
    full_name VARCHAR(255) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('MALE', 'FEMALE', 'OTHER') NULL,
    mobile_number VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    parent_name VARCHAR(255) NULL,
    parent_contact VARCHAR(20) NULL,
    joining_date DATE NOT NULL,
    status ENUM('PENDING', 'ACTIVE', 'INACTIVE', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    profile_photo_url VARCHAR(512) NULL,
    profile_photo_public_id VARCHAR(255) NULL,
    preferred_location_id INT UNSIGNED NULL,
    current_belt_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_student_profiles_user UNIQUE (user_id),
    CONSTRAINT uq_student_profiles_student_id UNIQUE (student_id),
    CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_profiles_location FOREIGN KEY (preferred_location_id) REFERENCES class_locations(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_student_profiles_belt FOREIGN KEY (current_belt_id) REFERENCES belts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_student_status (status),
    INDEX idx_student_id (student_id),
    INDEX idx_student_email (email),
    INDEX idx_student_location (preferred_location_id),
    INDEX idx_student_belt (current_belt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. EXAMINATIONS & BELT PROMOTIONS
-- =====================================================================

-- Table: exams
CREATE TABLE IF NOT EXISTS exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(255) NOT NULL,
    exam_date DATE NOT NULL,
    time VARCHAR(50) NOT NULL,
    venue VARCHAR(255) NOT NULL,
    eligibility TEXT NULL,
    exam_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT NULL,
    status ENUM('UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'UPCOMING',
    target_belt_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_exams_target_belt FOREIGN KEY (target_belt_id) REFERENCES belts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_exams_date (exam_date),
    INDEX idx_exams_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: exam_registrations
CREATE TABLE IF NOT EXISTS exam_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_profile_id INT UNSIGNED NOT NULL,
    exam_id INT UNSIGNED NOT NULL,
    registration_date DATE NOT NULL,
    result ENUM('PENDING', 'PASS', 'FAIL') NOT NULL DEFAULT 'PENDING',
    marks INT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    promotion_status ENUM('NOT_PROMOTED', 'PROMOTED') NOT NULL DEFAULT 'NOT_PROMOTED',
    remarks TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_er_student_exam UNIQUE (student_profile_id, exam_id),
    CONSTRAINT fk_er_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_er_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_er_student (student_profile_id),
    INDEX idx_er_exam (exam_id),
    INDEX idx_er_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: student_belt_history (Audit log of all promotions)
CREATE TABLE IF NOT EXISTS student_belt_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_profile_id INT UNSIGNED NOT NULL,
    old_belt_id INT UNSIGNED NULL,
    new_belt_id INT UNSIGNED NOT NULL,
    exam_registration_id INT UNSIGNED NULL,
    promoted_by_admin_id INT UNSIGNED NOT NULL,
    promotion_date DATE NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sbh_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sbh_old_belt FOREIGN KEY (old_belt_id) REFERENCES belts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sbh_new_belt FOREIGN KEY (new_belt_id) REFERENCES belts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sbh_exam_reg FOREIGN KEY (exam_registration_id) REFERENCES exam_registrations(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sbh_promoted_by FOREIGN KEY (promoted_by_admin_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_sbh_student (student_profile_id),
    INDEX idx_sbh_date (promotion_date),
    INDEX idx_sbh_promoted_by (promoted_by_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. FEES & PAYMENTS
-- =====================================================================

-- Table: fee_structures (Singleton table, id = 1)
CREATE TABLE IF NOT EXISTS fee_structures (
    id INT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    admission_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    training_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_charges JSON NULL,
    notes TEXT NULL,
    updated_by_admin_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fee_structures_admin FOREIGN KEY (updated_by_admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payments
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_ref VARCHAR(50) NOT NULL,
    student_profile_id INT UNSIGNED NOT NULL,
    created_by_admin_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_period VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    fees_paid_up_to DATE NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_payments_ref UNIQUE (payment_ref),
    CONSTRAINT fk_payments_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_admin FOREIGN KEY (created_by_admin_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_payments_student (student_profile_id),
    INDEX idx_payments_date (payment_date),
    INDEX idx_payments_ref (payment_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. EVENTS, GALLERY & HALL OF FAME
-- =====================================================================

-- Table: events
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    type ENUM('TOURNAMENT', 'BELT_EXAM', 'TRAINING_CAMP', 'SEMINAR', 'DEMONSTRATION', 'SPECIAL_PRACTICE', 'ACADEMY_FUNCTION') NOT NULL,
    event_date DATE NOT NULL,
    start_time VARCHAR(50) NOT NULL,
    venue VARCHAR(255) NOT NULL,
    description TEXT NULL,
    poster_url VARCHAR(512) NULL,
    poster_public_id VARCHAR(255) NULL,
    registration_details TEXT NULL,
    status ENUM('DRAFT', 'PUBLISHED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_events_date (event_date),
    INDEX idx_events_status (status),
    INDEX idx_events_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: gallery_images
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(512) NOT NULL,
    public_id VARCHAR(255) NULL,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    category ENUM('TRAINING', 'TOURNAMENTS', 'BELT_EXAMS', 'AWARDS', 'CAMPS', 'BLACK_BELT', 'ACADEMY_EVENTS') NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_by_admin_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gallery_admin FOREIGN KEY (created_by_admin_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_gallery_cat (category),
    INDEX idx_gallery_visible (is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: black_belt_profiles (Hall of Fame)
CREATE TABLE IF NOT EXISTS black_belt_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_profile_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    photo VARCHAR(512) NULL,
    photo_public_id VARCHAR(255) NULL,
    black_belt_rank VARCHAR(100) NOT NULL,
    year_achieved INT NOT NULL,
    achievements TEXT NULL,
    tournament_achievements TEXT NULL,
    description TEXT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_bb_student UNIQUE (student_profile_id),
    CONSTRAINT fk_bb_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_bb_visible_order (is_visible, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: contact_messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('UNREAD', 'READ', 'ARCHIVED') NOT NULL DEFAULT 'UNREAD',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_status (status),
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. ACADEMY CONFIGURATION (SINGLETONS)
-- =====================================================================

-- Table: academy_settings (Singleton table, id = 1)
CREATE TABLE IF NOT EXISTS academy_settings (
    id INT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    academy_name VARCHAR(255) NOT NULL,
    tagline VARCHAR(500) NULL,
    description TEXT NULL,
    founded_year INT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    address TEXT NULL,
    social_links JSON NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: head_office (Singleton table, id = 1)
CREATE TABLE IF NOT EXISTS head_office (
    id INT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    address TEXT NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    office_timing VARCHAR(255) NULL,
    map_url VARCHAR(512) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: instructor_profiles (Singleton table, id = 1)
CREATE TABLE IF NOT EXISTS instructor_profiles (
    id INT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    profile_photo_url VARCHAR(512) NULL,
    profile_photo_public_id VARCHAR(255) NULL,
    karate_rank VARCHAR(100) NULL,
    qualification TEXT NULL,
    experience TEXT NULL,
    achievements TEXT NULL,
    biography TEXT NULL,
    academy_role VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. SECURITY & AUDITING
-- =====================================================================

-- Table: audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(50) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_audit_admin (admin_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
