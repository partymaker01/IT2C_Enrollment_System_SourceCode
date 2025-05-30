-- CLEANED & CONSOLIDATED DATABASE SQL FILE
-- Database: enrollment_system

CREATE DATABASE IF NOT EXISTS enrollment_system;
USE enrollment_system;

-- Students Table
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(50) NOT NULL,
    year_level VARCHAR(10) NOT NULL,
    course VARCHAR(100) NOT NULL,
    status ENUM('Enrolled', 'Pending', 'Dropped') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tickets System
CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id VARCHAR(20) UNIQUE NOT NULL,
  student_name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL,
  priority ENUM('Low','Medium','High') NOT NULL DEFAULT 'Low',
  status ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  date_submitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  message TEXT NOT NULL
);

CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id VARCHAR(20) NOT NULL,
  sender VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);

-- Uploaded Documents
CREATE TABLE uploaded_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_name VARCHAR(100) NOT NULL,
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Uploaded', 'Verified', 'Rejected') NOT NULL DEFAULT 'Uploaded',
    remarks TEXT,
    file_path VARCHAR(255) NOT NULL
);

-- Enrollments
CREATE TABLE enrollments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  program VARCHAR(100) NOT NULL,
  year_level VARCHAR(50) NOT NULL,
  year VARCHAR(50) NOT NULL,
  semester VARCHAR(50) NOT NULL,
  section VARCHAR(50) NOT NULL,
  date_submitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  rejection_reason TEXT DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Helpdesk UI (UI Specific Table)
CREATE TABLE helpdesk_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id VARCHAR(20) NOT NULL UNIQUE,
  student_name VARCHAR(100) NOT NULL,
  category VARCHAR(50) NOT NULL,
  priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Low',
  priority_class VARCHAR(50) NOT NULL,
  status ENUM('Open', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
  status_class VARCHAR(50) NOT NULL,
  date_submitted DATETIME NOT NULL,
  message TEXT NOT NULL
);

CREATE TABLE helpdesk_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id VARCHAR(20) NOT NULL,
  sender VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  message_date DATETIME NOT NULL,
  FOREIGN KEY (ticket_id) REFERENCES helpdesk_tickets(ticket_id) ON DELETE CASCADE
);

-- Subjects Table
CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  description VARCHAR(255) NOT NULL,
  schedule VARCHAR(100) NOT NULL,
  instructor VARCHAR(100) NOT NULL,
  room VARCHAR(50) NOT NULL,
  status ENUM('Confirmed', 'Pending') NOT NULL DEFAULT 'Pending',
  days VARCHAR(100) NOT NULL,
  time VARCHAR(50) NOT NULL
);

-- Admin Settings Table
CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_notify BOOLEAN DEFAULT TRUE,
    sms_notify BOOLEAN DEFAULT FALSE,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    auto_backup BOOLEAN DEFAULT TRUE,
    password VARCHAR(255) NOT NULL
);

-- Announcements Table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    summary TEXT NOT NULL,
    content TEXT NOT NULL
);

-- Enrollment History View
CREATE TABLE enrollment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  semester VARCHAR(50),
  school_year VARCHAR(20),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE enrollment_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enrollment_id INT,
  subject_code VARCHAR(20),
  description VARCHAR(100),
  units INT,
  instructor VARCHAR(100),
  grade DECIMAL(3,2),
  remarks VARCHAR(20),
  FOREIGN KEY (enrollment_id) REFERENCES enrollment(id)
);

-- Pending Enrollments View
CREATE TABLE pending_enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  year VARCHAR(50) NOT NULL,
  program VARCHAR(100) NOT NULL,
  date_submitted DATE NOT NULL
);

-- Sample Insert
INSERT INTO admin_settings (name, email, email_notify, sms_notify, maintenance_mode, auto_backup, password)
VALUES ('', 'admin@example.com', TRUE, FALSE, FALSE, TRUE, '$2y$10$TKh8H1.Pri7en2ZWjiOgVeuUIZ9W9LZ9ZxQAw/0JX2jXQozj8fG4W');
