-- =====================================================================
-- Armour Martial Arts – Gujju Karate Association
-- Default Configuration Seed Data (Stage 1 - Approved Sanitized)
-- File: database/seeds/001_seed_defaults.sql
-- Database: armour_karate_db
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- 1. Academy Master Settings (Singleton id = 1)
-- ---------------------------------------------------------------------
INSERT INTO academy_settings (
    id, academy_name, tagline, description, founded_year,
    phone, email, website, address, social_links
) VALUES (
    1,
    'Armour Martial Arts Gujju Karate Association',
    'Discipline · Strength · Honor',
    'Dedicated to imparting authentic martial discipline, physical fitness, mental toughness, and character development across all age categories.',
    NULL,
    NULL,
    NULL,
    NULL,
    'Gujarat, India',
    NULL
) ON DUPLICATE KEY UPDATE
    academy_name = VALUES(academy_name),
    tagline = VALUES(tagline),
    description = VALUES(description),
    founded_year = VALUES(founded_year),
    phone = VALUES(phone),
    email = VALUES(email),
    website = VALUES(website),
    address = VALUES(address),
    social_links = VALUES(social_links);

-- ---------------------------------------------------------------------
-- 2. Head Office Details (Singleton id = 1)
-- ---------------------------------------------------------------------
INSERT INTO head_office (
    id, address, phone, email, office_timing, map_url
) VALUES (
    1,
    'Gujarat, India',
    NULL,
    NULL,
    NULL,
    NULL
) ON DUPLICATE KEY UPDATE
    address = VALUES(address),
    phone = VALUES(phone),
    email = VALUES(email),
    office_timing = VALUES(office_timing),
    map_url = VALUES(map_url);

-- ---------------------------------------------------------------------
-- 3. Default Fee Structure (Singleton id = 1)
-- ---------------------------------------------------------------------
INSERT INTO fee_structures (
    id, admission_fee, training_fee, other_charges, notes
) VALUES (
    1,
    0.00,
    0.00,
    NULL,
    'Please contact the academy for the latest fee details and schedule.'
) ON DUPLICATE KEY UPDATE
    admission_fee = VALUES(admission_fee),
    training_fee = VALUES(training_fee),
    other_charges = VALUES(other_charges),
    notes = VALUES(notes);

-- ---------------------------------------------------------------------
-- 4. Chief Sensei / Instructor Profile (Singleton id = 1)
-- ---------------------------------------------------------------------
INSERT INTO instructor_profiles (
    id, name, profile_photo_url, karate_rank, qualification,
    experience, achievements, biography, academy_role
) VALUES (
    1,
    'Chief Instructor',
    '/assets/images/academy-logo.png',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'Chief Technical Director'
) ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    profile_photo_url = VALUES(profile_photo_url),
    karate_rank = VALUES(karate_rank),
    qualification = VALUES(qualification),
    experience = VALUES(experience),
    achievements = VALUES(achievements),
    biography = VALUES(biography),
    academy_role = VALUES(academy_role);

-- ---------------------------------------------------------------------
-- 5. Standard Belts (8 Ranks: White to Black)
-- Note: exam_fee = 0.00 indicates unverified internal unknown fee convention
-- ---------------------------------------------------------------------
INSERT INTO belts (rank, name, color, exam_fee, description) VALUES
(1, 'White Belt (10th Kyu)', '#FFFFFF', 0.00, 'Beginning of the journey. Signifies purity, enthusiasm, and a blank canvas ready to learn basic stances and punches.'),
(2, 'Yellow Belt (9th & 8th Kyu)', '#EAB308', 0.00, 'The first ray of sunlight. Focus on balance, Zenkutsu Dachi, basic blocks (Gedan Barai, Age Uke), and front kicks.'),
(3, 'Orange Belt (7th Kyu)', '#F97316', 0.00, 'Developing strength and flexibility. Mastery of Taikyoku Kata, side thrust kicks, and partner striking drills.'),
(4, 'Green Belt (6th Kyu)', '#16A34A', 0.00, 'Growth and refinement. Gekisai Dai Ichi kata, roundhouse kicks, breathing awareness, and light controlled sparring.'),
(5, 'Blue Belt (5th & 4th Kyu)', '#2563EB', 0.00, 'Reaching higher like the sky. Gekisai Dai Ni kata, joint locks, defensive counter-attacks, and combination drills.'),
(6, 'Purple Belt (3rd Kyu)', '#9333EA', 0.00, 'Transition towards senior student. Saifa kata, tensho breathing drills, sweeps, and advanced tournament sparring tactics.'),
(7, 'Brown Belt (2nd & 1st Kyu)', '#78350F', 0.00, 'Maturity and depth of martial wisdom. Sanchin Kata testing, bunkai combat applications, power breaking, and mentorship.'),
(8, 'Black Belt (1st Dan - Shodan)', '#0A0A0A', 0.00, 'The beginning of true mastery. Complete mastery of Goju-Ryu fundamentals, Seiunchin kata, 10-person kumite, and lifelong dedication.')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    color = VALUES(color),
    exam_fee = VALUES(exam_fee),
    description = VALUES(description);

-- ---------------------------------------------------------------------
-- 6. Dojo Locations (3 Demonstration Branches - Hidden by default)
-- ---------------------------------------------------------------------
INSERT INTO class_locations (
    id, location_name, address, instructor, training_days,
    training_time, contact, map_url, is_active
) VALUES
(
    1,
    'Central Honbu Dojo (Headquarters)',
    'Sports Complex, Gujarat',
    'Chief Instructor',
    'Monday, Wednesday, Friday, Saturday',
    'Morning & Evening Batches',
    NULL,
    NULL,
    0
),
(
    2,
    'West Coast Dojo Branch',
    'Community Recreation Center, Gujarat',
    'Senior Instructor',
    'Tuesday, Thursday, Saturday',
    'Evening Batches',
    NULL,
    NULL,
    0
),
(
    3,
    'Heritage Dojo Center',
    'Fitness Wing, Gujarat',
    'Senior Instructor',
    'Monday, Wednesday, Friday',
    'Evening Batches',
    NULL,
    NULL,
    0
) ON DUPLICATE KEY UPDATE
    location_name = VALUES(location_name),
    address = VALUES(address),
    instructor = VALUES(instructor),
    training_days = VALUES(training_days),
    training_time = VALUES(training_time),
    contact = VALUES(contact),
    map_url = VALUES(map_url),
    is_active = VALUES(is_active);

-- ---------------------------------------------------------------------
-- 7. Black Belt Hall of Fame (Demo Profiles - Hidden by default)
-- ---------------------------------------------------------------------
INSERT INTO black_belt_profiles (
    id, name, photo, photo_public_id, black_belt_rank,
    year_achieved, achievements, tournament_achievements,
    description, display_order, is_visible, student_profile_id
) VALUES
(
    1,
    'Senior Student',
    NULL,
    NULL,
    '3rd Dan Black Belt (Sandan)',
    2018,
    'State Kumite Champion',
    'National Martial Arts Tournament',
    'Specializes in dynamic counter-attacking and Mawashi Geri techniques.',
    1,
    0,
    NULL
),
(
    2,
    'Senior Student',
    NULL,
    NULL,
    '2nd Dan Black Belt (Nidan)',
    2020,
    'State Kata Champion',
    'National Martial Arts Games',
    'Renowned for razor-sharp precision in Goju-Ryu Classical Katas.',
    2,
    0,
    NULL
) ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    black_belt_rank = VALUES(black_belt_rank),
    year_achieved = VALUES(year_achieved),
    achievements = VALUES(achievements),
    tournament_achievements = VALUES(tournament_achievements),
    description = VALUES(description),
    display_order = VALUES(display_order),
    is_visible = VALUES(is_visible);

-- ---------------------------------------------------------------------
-- 8. Student ID Counter Initial Sequence
-- ---------------------------------------------------------------------
INSERT INTO student_id_counter (year, last_seq)
VALUES (2026, 0)
ON DUPLICATE KEY UPDATE year = year;
