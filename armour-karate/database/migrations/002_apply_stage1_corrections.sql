-- =====================================================================
-- Armour Martial Arts – Gujju Karate Association
-- Approved Corrections to Stage 1 Seed Data
-- File: database/migrations/002_apply_stage1_corrections.sql
-- Database: armour_karate_db
-- =====================================================================

SET NAMES utf8mb4;

-- 1. Fee Structures: Set unverified admission & training fees to 0.00 and clear other charges
UPDATE fee_structures
SET admission_fee = 0.00,
    training_fee = 0.00,
    other_charges = NULL,
    notes = 'Please contact the academy for the latest fee details and schedule.'
WHERE id = 1;

-- 2. Belts: Set unverified exam fees to 0.00 (internal unknown convention, not free)
UPDATE belts
SET exam_fee = 0.00;

-- 3. Instructor Profiles: Replace unverified name with Chief Instructor, suppress unverified ranks/claims
UPDATE instructor_profiles
SET name = 'Chief Instructor',
    profile_photo_url = '/assets/images/academy-logo.png',
    profile_photo_public_id = NULL,
    karate_rank = NULL,
    qualification = NULL,
    experience = NULL,
    achievements = NULL,
    biography = NULL,
    academy_role = 'Chief Technical Director'
WHERE id = 1;

-- 4. Class Locations: Suppress unverified demo dojo branches from public display
UPDATE class_locations
SET is_active = 0;

-- 5. Black Belt Profiles: Hide demonstration Hall of Fame profiles without deleting records
UPDATE black_belt_profiles
SET is_visible = 0;

-- 6. Academy Master Settings: Remove unverified contacts, founding year and claims
UPDATE academy_settings
SET academy_name = 'Armour Martial Arts Gujju Karate Association',
    tagline = 'Discipline · Strength · Honor',
    description = 'Dedicated to imparting authentic martial discipline, physical fitness, mental toughness, and character development across all age categories.',
    founded_year = NULL,
    phone = NULL,
    email = NULL,
    website = NULL,
    address = 'Gujarat, India',
    social_links = NULL
WHERE id = 1;

-- 7. Head Office Details: Remove unverified street address, phone, email and timing
UPDATE head_office
SET address = 'Gujarat, India',
    phone = NULL,
    email = NULL,
    office_timing = NULL,
    map_url = NULL
WHERE id = 1;
