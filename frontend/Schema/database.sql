-- Database TalentTrack
-- Sistem rekrutmen internal untuk satu perusahaan
-- MySQL 8+ / MariaDB

CREATE DATABASE IF NOT EXISTS talenttrack
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE talenttrack;

-- 1. Akun admin dan pelamar
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pelamar') NOT NULL DEFAULT 'pelamar',
    phone VARCHAR(30) NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- 2. Profil tambahan untuk akun pelamar (relasi one-to-one dengan users)
CREATE TABLE IF NOT EXISTS applicant_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    photo_path VARCHAR(255) NULL,
    address TEXT NULL,
    education_level VARCHAR(100) NULL,
    institution VARCHAR(150) NULL,
    major VARCHAR(150) NULL,
    bio TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Lowongan pekerjaan yang dikelola admin
CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    category VARCHAR(100) NULL,
    employment_type ENUM('full_time', 'part_time', 'internship', 'contract')
        NOT NULL DEFAULT 'full_time',
    location VARCHAR(150) NULL,
    description TEXT NOT NULL,
    requirements TEXT NULL,
    responsibilities TEXT NULL,
    salary_min DECIMAL(15,2) NULL,
    salary_max DECIMAL(15,2) NULL,
    deadline DATE NULL,
    status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_status_deadline (status, deadline),
    INDEX idx_jobs_title (title),
    CONSTRAINT fk_jobs_admin
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. Lamaran pelamar terhadap lowongan
CREATE TABLE IF NOT EXISTS applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    applicant_id BIGINT UNSIGNED NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'submitted',
        'administrative_review',
        'interview',
        'accepted',
        'rejected',
        'withdrawn'
    ) NOT NULL DEFAULT 'submitted',
    cover_letter TEXT NULL,
    admin_note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application_job_applicant (job_id, applicant_id),
    INDEX idx_applications_status (status),
    INDEX idx_applications_applicant (applicant_id),
    CONSTRAINT fk_applications_job
        FOREIGN KEY (job_id) REFERENCES jobs(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_applications_applicant
        FOREIGN KEY (applicant_id) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. Dokumen yang diunggah untuk setiap lamaran (CV, portofolio, dll.)
CREATE TABLE IF NOT EXISTS application_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('cv', 'portfolio', 'other') NOT NULL DEFAULT 'cv',
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size BIGINT UNSIGNED NULL,
    uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_documents_application (application_id),
    CONSTRAINT fk_documents_application
        FOREIGN KEY (application_id) REFERENCES applications(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

