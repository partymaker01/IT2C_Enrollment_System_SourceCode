-- CONSOLIDATED DATABASE SQL FILE
-- Database: enrollment_system

CREATE DATABASE IF NOT EXISTS enrollment_system;
USE enrollment_system;


-- START OF FILE: admin-dashboard.sql

-- Students Table
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    year_level VARCHAR(10) NOT NULL,
    course VARCHAR(100) NOT NULL,
    status ENUM('Enrolled', 'Pending', 'Dropped') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE students 
ADD COLUMN username VARCHAR(100) NOT NULL AFTER id,
ADD COLUMN middle_name VARCHAR(100) AFTER first_name;


-- Staff Table (teachers and other staff)
CREATE TABLE IF NOT EXISTS staff (
    staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins Table (optional, if needed for admin login)
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- END OF FILE: admin-dashboard.sql


-- START OF FILE: admin-helpdesk.sql
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
-- END OF FILE: admin-helpdesk.sql


-- START OF FILE: documents-upload.sql
CREATE TABLE uploaded_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL, -- FK for student, change type as needed
    document_name VARCHAR(100) NOT NULL,
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Uploaded', 'Verified', 'Rejected') NOT NULL DEFAULT 'Uploaded',
    remarks TEXT,
    file_path VARCHAR(255) NOT NULL
);
-- END OF FILE: documents-upload.sql




-- START OF FILE: enromment-status-student.sql
CREATE TABLE `enrollments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `program` VARCHAR(100) NOT NULL,
  `year` VARCHAR(50) NOT NULL,
  `semester` VARCHAR(50) NOT NULL,
  `section` VARCHAR(50) NOT NULL,
  `date_submitted` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` TEXT DEFAULT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
);

SELECT * FROM enrollments WHERE student_id = 1;
ALTER TABLE enrollments ADD COLUMN year_level VARCHAR(50) NOT NULL AFTER program;


CREATE TABLE enrollments_student (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `program` VARCHAR(100) NOT NULL,
  `year` VARCHAR(50) NOT NULL,
  `semester` VARCHAR(50) NOT NULL,
  `section` VARCHAR(50) NOT NULL,
  `date_submitted` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `rejection_reason` TEXT DEFAULT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
);

-- END OF FILE: enromment-status-student.sql


-- START OF FILE: helpdesk.sql
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

INSERT INTO helpdesk_tickets (ticket_id, student_name, category, priority, priority_class, status, status_class, date_submitted, message) VALUES
('HD-2025-001', 'John Lester Lina', 'Enrollment Issue', 'High', 'bg-danger', 'Open', 'badge-open', '2025-05-20 10:15:00', 'I am having trouble with my enrollment status...'),
('HD-2025-002', 'Joshua Santos', 'Document Upload', 'Medium', 'bg-warning text-dark', 'In Progress', 'badge-inprogress', '2025-05-18 14:30:00', 'I uploaded my ID but status still pending.'),
('HD-2025-003', 'Jerick DC. Reyes', 'Payment', 'Low', 'bg-secondary', 'Resolved', 'badge-resolved', '2025-05-15 09:00:00', 'Payment confirmation not reflected yet.');

INSERT INTO helpdesk_conversations (ticket_id, sender, message, message_date) VALUES
('HD-2025-001', 'Student (JM Dela Cruz)', 'Hi, my enrollment status is not updating.', '2025-05-20 10:20:00'),
('HD-2025-001', 'Admin', "We're checking your issue, please wait for updates.", '2025-05-20 10:30:00'),
('HD-2025-002', 'Student (Maria Santos)', 'I uploaded my ID but status still pending.', '2025-05-18 14:35:00'),
('HD-2025-002', 'Admin', 'We are verifying your documents.', '2025-05-18 15:00:00'),
('HD-2025-003', 'Student (Jose Rizal)', 'Payment confirmation not reflected yet.', '2025-05-15 09:05:00'),
('HD-2025-003', 'Admin', 'Payment verified, status updated.', '2025-05-15 09:20:00');

-- END OF FILE: helpdesk.sql


-- START OF FILE: mysubjust.sql

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  description VARCHAR(255) NOT NULL,
  schedule VARCHAR(100) NOT NULL,
  instructor VARCHAR(100) NOT NULL,
  room VARCHAR(50) NOT NULL,
  status ENUM('Confirmed', 'Pending') NOT NULL DEFAULT 'Pending',
  days VARCHAR(100) NOT NULL,   -- store days as comma separated string like 'Monday,Wednesday,Friday'
  time VARCHAR(50) NOT NULL
);
-- END OF FILE: mysubjust.sql


-- START OF FILE: student-announce.sql
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    summary TEXT NOT NULL,
    content TEXT NOT NULL
);

INSERT INTO announcements (title, date, summary, content) VALUES
('Enrollment Deadline Extended', '2025-05-24', 'The deadline for enrollment...', '<p>The deadline for enrollment...</p>'),
('Orientation Schedule', '2025-06-01', 'Orientation will be held in June...', '<p>Orientation will be held on June 5 at the main hall.</p>');



-- START OF FILE: view-enrollment-historty.sql


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
-- END OF FILE: view-enrollment-historty.sql


-- START OF FILE: view-pending-enrollemnt.sql
CREATE TABLE pending_enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  year VARCHAR(50) NOT NULL,
  program VARCHAR(100) NOT NULL,
  date_submitted DATE NOT NULL
);
-- END OF FILE: view-pending-enrollemnt.sql

REATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
);

-- Insert default admin (admin@example.com / admin123)
INSERT INTO admins (name, email, password)
VALUES ('Admin', 'admin@example.com', '$2y$10$EYiI8QX0T3UccxGvFkk1N.gnr/EUl70h9FJ0I5UwKCeT9sPAbfHVe');
