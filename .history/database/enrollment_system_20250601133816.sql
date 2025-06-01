-- Students Table
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address VARCHAR(255),
    contact_number VARCHAR(30),
    program VARCHAR(100);
    course VARCHAR(100),
    school_year VARCHAR(20),
    year_level VARCHAR(20),
    section VARCHAR(10),
    full_name VARCHAR(255),
    photo VARCHAR(255) DEFAULT 'uploads/ran.jpg',
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registrar Staff Table
CREATE TABLE registrar_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin Settings Table
CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_notify TINYINT(1) DEFAULT 0,
    image VARCHAR(255) DEFAULT 'img/default_admin.png',
    sms_notify TINYINT(1) DEFAULT 0,
    maintenance_mode TINYINT(1) DEFAULT 0,
    auto_backup TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Admin Insert
INSERT INTO admin_settings (email, password, username)
VALUES (
    'admin@example.com',
    '$2y$10$ZTg9kZyD945c94f7m3vEquCDZ5wZGuwLBv4mitSX2dS...', -- use your generated hash
    'admin'
);

-- Enrollments Table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'not_enrolled') DEFAULT 'pending',
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_level VARCHAR(50),
    rejection_reason VARCHAR(255),
    section VARCHAR(20),
    remarks VARCHAR(255),
    notification TEXT,
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id)
);

-- Subjects Table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_title VARCHAR(100) NOT NULL,
    description VARCHAR(100),
    units DECIMAL(3,1),
    instructor VARCHAR(100),
    day VARCHAR(50),
    time VARCHAR(50),
    room VARCHAR(50)
);

-- Enrollment Subjects Table (junction for enrollments and subjects)
CREATE TABLE enrollment_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    ADD COLUMN subject_description VARCHAR(255),
    subject_code VARCHAR(20) NOT NULL,
    ADD instructor VARCHAR(255),
    grade DECIMAL(4,2),
    remarks VARCHAR(50),
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    INDEX idx_enrollment_id (enrollment_id)
);

-- Student Subjects Table (for direct assignment)
CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'Confirmed',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id)
);

-- Enrollment Periods Table
CREATE TABLE enrollment_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tickets Table
CREATE TABLE tickets (
    ticket_id VARCHAR(20) PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Low',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
    date_submitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message TEXT NOT NULL
);

-- Conversations Table
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    sender VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id)
);

-- Password Resets Table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Uploaded Documents Table
CREATE TABLE uploaded_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Uploaded', 'Verified', 'Rejected', 'Pending') DEFAULT 'Uploaded',
    remarks VARCHAR(255) DEFAULT 'Waiting for verification',
    can_reupload TINYINT(1) DEFAULT 0,
    view_link VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id)
);

-- CREATE TABLE users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     full_name VARCHAR(100),
--     email VARCHAR(100) UNIQUE,
--     password VARCHAR(255),
--     is_verified TINYINT(1) DEFAULT 0,
--     verification_code VARCHAR(255)
-- );

-- // Get form data
-- $email = $_POST['email'];
-- $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
-- $verification_code = md5(rand());

-- // Check if email already exists
-- $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
-- if (mysqli_num_rows($check) > 0) {
--     echo "<script>
--         Swal.fire({
--             icon: 'error',
--             title: 'Email already registered!',
--             text: 'Please use a different Gmail address.'
--         });
--     </script>";
-- } else {
--     // Insert user as unverified
--     $insert = mysqli_query($conn, "INSERT INTO users (email, password, verification_code) VALUES ('$email', '$password', '$verification_code')");

--     if ($insert) {
--         // send email using PHPMailer here...
--         echo "<script>
--             Swal.fire({
--                 icon: 'success',
--                 title: 'Registered Successfully!',
--                 text: 'Please check your Gmail to verify your account.'
--             });
--         </script>";
--     }
-- }

