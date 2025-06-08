-- Create database if not exists
CREATE DATABASE IF NOT EXISTS enrollment_system;
USE enrollment_system;

-- Students Table
CREATE TABLE IF NOT EXISTS students (
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

-- Enrollments Table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_level VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    section VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    remarks TEXT,
    notification TEXT,
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_processed TIMESTAMP NULL,
    processed_by INT UNSIGNED NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_title VARCHAR(100) NOT NULL,
    description TEXT,
    units DECIMAL(3,1) NOT NULL DEFAULT 3.0,
    instructor VARCHAR(100),
    day VARCHAR(50),
    time VARCHAR(50),
    room VARCHAR(50),
    semester VARCHAR(20),
    school_year VARCHAR(20),
    program VARCHAR(100),
    year_level VARCHAR(50),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student Subjects Table
CREATE TABLE IF NOT EXISTS student_subjects (
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

-- Enrollment Subjects Table (for tracking subjects in specific enrollments)
CREATE TABLE IF NOT EXISTS enrollment_subjects (
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

-- Uploaded Documents Table
CREATE TABLE IF NOT EXISTS uploaded_documents (
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
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Admin Settings Table
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registrar', 'staff') DEFAULT 'admin',
    email_notify BOOLEAN DEFAULT FALSE,
    sms_notify BOOLEAN DEFAULT FALSE,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    auto_backup BOOLEAN DEFAULT FALSE,
    image VARCHAR(255) DEFAULT 'img/default_admin.png',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
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

-- Enrollment Periods Table
CREATE TABLE IF NOT EXISTS enrollment_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_period (semester, school_year)
);

-- Insert default admin user
INSERT IGNORE INTO admin_settings (name, username, email, password, role) 
VALUES ('System Administrator', 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample subjects
INSERT IGNORE INTO subjects (subject_code, subject_title, description, units, instructor, day, time, room, semester, school_year, program, year_level) VALUES
('IT101', 'Introduction to Computing', 'Basic concepts of computing and information technology', 3.0, 'Prof. John Smith', 'Monday,Wednesday', '8:00-9:30 AM', 'Room 101', '1st Semester', '2024-2025', 'Bachelor of Science in Information Technology', '1st Year'),
('IT102', 'Programming Fundamentals', 'Introduction to programming concepts and logic', 3.0, 'Prof. Jane Doe', 'Tuesday,Thursday', '10:00-11:30 AM', 'Room 102', '1st Semester', '2024-2025', 'Bachelor of Science in Information Technology', '1st Year'),
('IT103', 'Computer Hardware', 'Understanding computer hardware components', 3.0, 'Prof. Mike Johnson', 'Friday', '1:00-4:00 PM', 'Lab 201', '1st Semester', '2024-2025', 'Bachelor of Science in Information Technology', '1st Year'),
('GE101', 'Mathematics in the Modern World', 'Mathematical concepts and applications', 3.0, 'Prof. Sarah Wilson', 'Monday,Wednesday', '2:00-3:30 PM', 'Room 201', '1st Semester', '2024-2025', 'Bachelor of Science in Information Technology', '1st Year'),
('GE102', 'Understanding the Self', 'Personal development and self-awareness', 3.0, 'Prof. Robert Brown', 'Tuesday,Thursday', '3:30-5:00 PM', 'Room 202', '1st Semester', '2024-2025', 'Bachelor of Science in Information Technology', '1st Year');

-- Insert sample enrollment period
INSERT IGNORE INTO enrollment_periods (semester, school_year, start_date, end_date, is_active) 
VALUES ('1st Semester', '2024-2025', '2024-06-01', '2024-08-31', TRUE);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_students_email ON students(email);
CREATE INDEX IF NOT EXISTS idx_students_username ON students(username);
CREATE INDEX IF NOT EXISTS idx_enrollments_date ON enrollments(date_submitted);
CREATE INDEX IF NOT EXISTS idx_subjects_program ON subjects(program, year_level);
CREATE INDEX IF NOT EXISTS idx_documents_upload_date ON uploaded_documents(upload_date);
