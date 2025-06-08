-- Update existing enrollment_periods table
ALTER TABLE enrollment_periods 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'inactive' AFTER is_active,
ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER status,
ADD FOREIGN KEY IF NOT EXISTS (created_by) REFERENCES admin_settings(id);

-- Create enrollment_documents table for file uploads
CREATE TABLE IF NOT EXISTS enrollment_documents (
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

-- Create admin activity logs
CREATE TABLE IF NOT EXISTS admin_activity_logs (
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

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
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

-- Create system_settings table
CREATE TABLE IF NOT EXISTS system_settings (
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

-- Create academic_calendar table
CREATE TABLE IF NOT EXISTS academic_calendar (
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

-- Create grades table
CREATE TABLE IF NOT EXISTS grades (
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

-- Create payment_records table
CREATE TABLE IF NOT EXISTS payment_records (
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

-- Add missing columns to existing tables
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS guardian_name VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS guardian_contact VARCHAR(30) AFTER guardian_name,
ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(30) AFTER guardian_contact,
ADD COLUMN IF NOT EXISTS blood_type VARCHAR(5) AFTER emergency_contact,
ADD COLUMN IF NOT EXISTS civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single' AFTER blood_type,
ADD COLUMN IF NOT EXISTS nationality VARCHAR(50) DEFAULT 'Filipino' AFTER civil_status,
ADD COLUMN IF NOT EXISTS religion VARCHAR(50) AFTER nationality;

ALTER TABLE enrollments 
ADD COLUMN IF NOT EXISTS total_units DECIMAL(4,1) DEFAULT 0 AFTER section,
ADD COLUMN IF NOT EXISTS enrolled_by INT UNSIGNED AFTER processed_by,
ADD COLUMN IF NOT EXISTS date_enrolled TIMESTAMP NULL AFTER date_processed,
ADD FOREIGN KEY IF NOT EXISTS (enrolled_by) REFERENCES admin_settings(id);

ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS prerequisites TEXT AFTER year_level,
ADD COLUMN IF NOT EXISTS course_type ENUM('major', 'minor', 'general_education', 'elective') DEFAULT 'major' AFTER prerequisites,
ADD COLUMN IF NOT EXISTS laboratory_hours DECIMAL(3,1) DEFAULT 0 AFTER units,
ADD COLUMN IF NOT EXISTS lecture_hours DECIMAL(3,1) DEFAULT 0 AFTER laboratory_hours;

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
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

-- Insert sample academic calendar events
INSERT IGNORE INTO academic_calendar (event_title, event_description, event_type, start_date, end_date) VALUES
('Enrollment Period - 1st Semester 2024-2025', 'Online and walk-in enrollment for first semester', 'enrollment', '2024-06-01', '2024-08-31'),
('First Semester Classes', 'Regular classes for first semester', 'classes', '2024-09-02', '2025-01-31'),
('Christmas Break', 'Holiday break for Christmas and New Year', 'holiday', '2024-12-20', '2025-01-06'),
('Preliminary Examinations', 'First quarter examinations', 'exam', '2024-10-15', '2024-10-19'),
('Midterm Examinations', 'Second quarter examinations', 'exam', '2024-11-25', '2024-11-29'),
('Final Examinations', 'Final examinations for first semester', 'exam', '2025-01-20', '2025-01-24');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_students_program ON students(program, year_level);
CREATE INDEX IF NOT EXISTS idx_enrollments_semester ON enrollments(semester, school_year);
CREATE INDEX IF NOT EXISTS idx_subjects_course_type ON subjects(course_type, program);
CREATE INDEX IF NOT EXISTS idx_notifications_unread ON notifications(user_id, user_type, is_read);

-- Update admin_settings table with additional fields
ALTER TABLE admin_settings 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER email,
ADD COLUMN IF NOT EXISTS department VARCHAR(100) AFTER role,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER department,
ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP NULL AFTER last_login;

-- Create backup_logs table for system backups
CREATE TABLE IF NOT EXISTS backup_logs (
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

-- Update enrollment status options
ALTER TABLE enrollments 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'missing_documents', 'enrolled', 'dropped', 'cancelled') DEFAULT 'pending';

-- Add enrollment statistics view
CREATE OR REPLACE VIEW enrollment_statistics AS
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

-- Add student performance view
CREATE OR REPLACE VIEW student_performance AS
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

COMMIT;
