-- =====================================================
-- COMPLETE ENROLLMENT SYSTEM DATABASE SETUP
-- This script creates everything from scratch
-- =====================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS enrollment_system;
USE enrollment_system;

-- Drop existing tables if they exist (for clean setup)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS payment_records;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS enrollment_documents;
DROP TABLE IF EXISTS enrollment_subjects;
DROP TABLE IF EXISTS student_subjects;
DROP TABLE IF EXISTS uploaded_documents;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS academic_calendar;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS admin_activity_logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS enrollment_periods;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS admin_settings;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Admin Settings Table
CREATE TABLE admin_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registrar', 'staff') DEFAULT 'admin',
    phone VARCHAR(20),
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    email_notify BOOLEAN DEFAULT FALSE,
    sms_notify BOOLEAN DEFAULT FALSE,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    auto_backup BOOLEAN DEFAULT FALSE,
    image VARCHAR(255) DEFAULT 'img/default_admin.png',
    last_login TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contact_number VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    guardian_name VARCHAR(100),
    guardian_contact VARCHAR(30),
    emergency_contact VARCHAR(30),
    blood_type VARCHAR(5),
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single',
    nationality VARCHAR(50) DEFAULT 'Filipino',
    religion VARCHAR(50),
    program VARCHAR(100),
    course VARCHAR(100),
    school_year VARCHAR(20),
    year_level VARCHAR(20),
    section VARCHAR(10),
    photo VARCHAR(255) DEFAULT 'uploads/default-avatar.png',
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Subjects Table
CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_title VARCHAR(100) NOT NULL,
    description TEXT,
    units DECIMAL(3,1) NOT NULL DEFAULT 3.0,
    lecture_hours DECIMAL(3,1) DEFAULT 0,
    laboratory_hours DECIMAL(3,1) DEFAULT 0,
    instructor VARCHAR(100),
    day VARCHAR(50),
    time VARCHAR(50),
    room VARCHAR(50),
    semester VARCHAR(20),
    school_year VARCHAR(20),
    program VARCHAR(100),
    year_level VARCHAR(50),
    prerequisites TEXT,
    course_type ENUM('major', 'minor', 'general_education', 'elective') DEFAULT 'major',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Enrollment Periods Table
CREATE TABLE enrollment_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_settings(id),
    UNIQUE KEY unique_period (semester, school_year)
);

-- Enrollments Table
CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_level VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    section VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    total_units DECIMAL(4,1) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'missing_documents', 'enrolled', 'dropped', 'cancelled') DEFAULT 'pending',
    rejection_reason TEXT,
    remarks TEXT,
    notification TEXT,
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_processed TIMESTAMP NULL,
    date_enrolled TIMESTAMP NULL,
    processed_by INT UNSIGNED NULL,
    enrolled_by INT UNSIGNED NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES admin_settings(id),
    FOREIGN KEY (enrolled_by) REFERENCES admin_settings(id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- =====================================================
-- ACADEMIC MANAGEMENT TABLES
-- =====================================================

-- Student Subjects Table
CREATE TABLE student_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'Enrolled',
    grade DECIMAL(4,2) NULL,
    date_enrolled TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id, semester, school_year),
    INDEX idx_student_id (student_id),
    INDEX idx_subject_id (subject_id)
);

-- Enrollment Subjects Table
CREATE TABLE enrollment_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_title VARCHAR(100),
    units DECIMAL(3,1),
    instructor VARCHAR(100),
    grade DECIMAL(4,2) NULL,
    remarks VARCHAR(100),
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    INDEX idx_enrollment_id (enrollment_id)
);

-- Grades Table
CREATE TABLE grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    prelim_grade DECIMAL(4,2) NULL,
    midterm_grade DECIMAL(4,2) NULL,
    final_grade DECIMAL(4,2) NULL,
    final_rating DECIMAL(4,2) NULL,
    remarks VARCHAR(50),
    instructor_id INT UNSIGNED,
    date_encoded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES admin_settings(id),
    UNIQUE KEY unique_grade (student_id, subject_id, enrollment_id),
    INDEX idx_student_id (student_id),
    INDEX idx_subject_id (subject_id)
);

-- =====================================================
-- DOCUMENT MANAGEMENT TABLES
-- =====================================================

-- Uploaded Documents Table
CREATE TABLE uploaded_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    doc_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Uploaded', 'Verified', 'Rejected', 'Pending') DEFAULT 'Uploaded',
    remarks TEXT DEFAULT 'Waiting for verification',
    verified_by INT UNSIGNED NULL,
    verified_date TIMESTAMP NULL,
    can_reupload BOOLEAN DEFAULT FALSE,
    view_link VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_settings(id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Enrollment Documents Table
CREATE TABLE enrollment_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED,
    mime_type VARCHAR(100),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT UNSIGNED NULL,
    verified_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_settings(id),
    INDEX idx_enrollment_id (enrollment_id),
    INDEX idx_status (status)
);

-- =====================================================
-- SYSTEM MANAGEMENT TABLES
-- =====================================================

-- System Settings Table
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT UNSIGNED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_settings(id)
);

-- Academic Calendar Table
CREATE TABLE academic_calendar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(255) NOT NULL,
    event_description TEXT,
    event_type ENUM('enrollment', 'classes', 'exam', 'holiday', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_settings(id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (event_type)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_type ENUM('student', 'admin') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_user (user_id, user_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Admin Activity Logs Table
CREATE TABLE admin_activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    target_type VARCHAR(50),
    target_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_settings(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Password Resets Table
CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_type ENUM('student', 'admin') NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user (user_id, user_type)
);

-- Payment Records Table
CREATE TABLE payment_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    payment_type ENUM('tuition', 'miscellaneous', 'laboratory', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'check', 'bank_transfer', 'online') NOT NULL,
    reference_number VARCHAR(100),
    payment_date DATE NOT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    processed_by INT UNSIGNED,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES admin_settings(id),
    INDEX idx_student_id (student_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
);

-- Backup Logs Table
CREATE TABLE backup_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('manual', 'automatic') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED,
    backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    status ENUM('success', 'failed', 'in_progress') DEFAULT 'in_progress',
    error_message TEXT,
    FOREIGN KEY (created_by) REFERENCES admin_settings(id),
    INDEX idx_backup_date (backup_date),
    INDEX idx_status (status)
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_username ON students(username);
CREATE INDEX idx_students_program ON students(program, year_level);
CREATE INDEX idx_enrollments_date ON enrollments(date_submitted);
CREATE INDEX idx_enrollments_semester ON enrollments(semester, school_year);
CREATE INDEX idx_subjects_program ON subjects(program, year_level);
CREATE INDEX idx_subjects_course_type ON subjects(course_type, program);
CREATE INDEX idx_documents_upload_date ON uploaded_documents(upload_date);
CREATE INDEX idx_notifications_unread ON notifications(user_id, user_type, is_read);

-- =====================================================
-- DATABASE VIEWS
-- =====================================================

-- Enrollment Statistics View
CREATE VIEW enrollment_statistics AS
SELECT 
    e.semester,
    e.school_year,
    e.program,
    e.year_level,
    COUNT(*) as total_enrollments,
    SUM(CASE WHEN e.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN e.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN e.status = 'enrolled' THEN 1 ELSE 0 END) as enrolled_count,
    SUM(CASE WHEN e.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN e.status = 'missing_documents' THEN 1 ELSE 0 END) as missing_docs_count
FROM enrollments e
GROUP BY e.semester, e.school_year, e.program, e.year_level;

-- Student Performance View
CREATE VIEW student_performance AS
SELECT 
    s.student_id,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.program,
    s.year_level,
    COUNT(g.id) as total_subjects,
    AVG(g.final_rating) as gpa,
    SUM(CASE WHEN g.final_rating >= 75 THEN 1 ELSE 0 END) as passed_subjects,
    SUM(CASE WHEN g.final_rating < 75 THEN 1 ELSE 0 END) as failed_subjects
FROM students s
LEFT JOIN grades g ON s.student_id = g.student_id
WHERE g.final_rating IS NOT NULL
GROUP BY s.student_id;

-- =====================================================
-- DEFAULT DATA INSERTION
-- =====================================================

-- Insert default admin user
INSERT INTO admin_settings (name, username, email, password, role, is_active) 
VALUES ('System Administrator', 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('school_name', 'TLGC Enrollment System', 'text', 'Official school name', TRUE),
('school_address', 'Sample Address, City, Province', 'text', 'School address', TRUE),
('school_contact', '+63 123 456 7890', 'text', 'School contact number', TRUE),
('school_email', 'info@tlgc.edu.ph', 'text', 'School email address', TRUE),
('enrollment_fee', '500.00', 'number', 'Enrollment fee amount', FALSE),
('late_enrollment_fee', '200.00', 'number', 'Late enrollment penalty', FALSE),
('max_units_regular', '24', 'number', 'Maximum units for regular students', FALSE),
('max_units_irregular', '15', 'number', 'Maximum units for irregular students', FALSE),
('allow_online_enrollment', 'true', 'boolean', 'Enable online enrollment', FALSE),
('maintenance_mode', 'false', 'boolean', 'System maintenance mode', FALSE);

-- Insert sample subjects
INSERT INTO subjects (subject_code, subject_title, description, units, lecture_hours, laboratory_hours, instructor, day, time, room, semester, school_year, program, year_level, course_type) VALUES
('IT101', 'Introduction to Computing', 'Basic concepts of computing and information technology', 3.0, 3.0, 0.0, 'Instructor 1', 'Monday,Wednesday', '8:00-9:30 AM', 'Room 101', '1st Semester', '2024-2025', 'Information Technology', '1st Year', 'major'),
('IT102', 'Programming Fundamentals', 'Introduction to programming concepts and logic', 3.0, 2.0, 1.0, 'Instructor 2', 'Tuesday,Thursday', '10:00-11:30 AM', 'Room 102', '1st Semester', '2024-2025', 'Information Technology', '1st Year', 'major'),
('IT103', 'Computer Hardware', 'Understanding computer hardware components', 3.0, 2.0, 1.0, 'Instructor 3', 'Friday', '1:00-4:00 PM', 'Lab 201', '1st Semester', '2024-2025', 'Information Technology', '1st Year', 'major'),
('GE101', 'Mathematics in the Modern World', 'Mathematical concepts and applications', 3.0, 3.0, 0.0, 'Instructor4', 'Monday,Wednesday', '2:00-3:30 PM', 'Room 201', '1st Semester', '2024-2025', 'Information Technology', '1st Year', 'general_education'),
('GE102', 'Understanding the Self', 'Personal development and self-awareness', 3.0, 3.0, 0.0, 'Instructor 5', 'Tuesday,Thursday', '3:30-5:00 PM', 'Room 202', '1st Semester', '2024-2025', 'Information Technology', '1st Year', 'general_education');

-- Insert sample enrollment period
INSERT INTO enrollment_periods (semester, school_year, start_date, end_date, is_active, status) 
VALUES ('1st Semester', '2024-2025', '2024-06-01', '2024-08-31', TRUE, 'active');

-- Insert sample academic calendar events
INSERT INTO academic_calendar (event_title, event_description, event_type, start_date, end_date) VALUES
('Enrollment Period - 1st Semester 2024-2025', 'Online and walk-in enrollment for first semester', 'enrollment', '2024-06-01', '2024-08-31'),
('First Semester Classes', 'Regular classes for first semester', 'classes', '2024-09-02', '2025-01-31'),
('Christmas Break', 'Holiday break for Christmas and New Year', 'holiday', '2024-12-20', '2025-01-06'),
('Preliminary Examinations', 'First quarter examinations', 'exam', '2024-10-15', '2024-10-19'),
('Midterm Examinations', 'Second quarter examinations', 'exam', '2024-11-25', '2024-11-29'),
('Final Examinations', 'Final examinations for first semester', 'exam', '2025-01-20', '2025-01-24');

-- =====================================================
-- FINAL COMMIT
-- =====================================================

COMMIT;

-- Display success message
SELECT 'Database setup completed successfully!' as message;
SELECT 'Default admin login: admin / password' as admin_info;
SELECT 'You can now test the enrollment system!' as status;
