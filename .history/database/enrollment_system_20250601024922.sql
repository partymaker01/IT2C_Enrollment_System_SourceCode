-- admin database

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE,
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    course VARCHAR(100),
    year_level VARCHAR(20),
    section VARCHAR(10) NOT NULL
    photo VARCHAR(255) DEFAULT 'uploads/ran.jpg',
    status VARCHAR(20) DEFAULT 'Pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registrar_staffstaff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    position VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    email_notify TINYINT(1) DEFAULT 0,
    image VARCHAR(255) DEFAULT 'img/default_admin.png',
    sms_notify TINYINT(1) DEFAULT 0,
    maintenance_mode TINYINT(1) DEFAULT 0,
    auto_backup TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_settings (email, password, username)
VALUES (
    'admin@example.com',
    '$2y$10$ZTg9kZyD945c94f7m3vEquCDZ5wZGuwLBv4mitSX2dS...', -- use your generated hash
    'admin'
);

CREATE TABLE enrollment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'Not Enrolled',
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    program VARCHAR(100) NOT NULL,
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);

CREATE TABLE enrollment_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    description VARCHAR(100) NOT NULL,
    units DECIMAL(3,1) NOT NULL,
    instructor VARCHAR(100),
    grade DECIMAL(4,2),
    remarks VARCHAR(50),
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    subject_title VARCHAR(100) NOT NULL
    FOREIGN KEY (enrollment_id) REFERENCES enrollment(id)
);

CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE enrollment_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tickets (
    ticket_id VARCHAR(20) PRIMARY KEY,        
    student_name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Low',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Open',
    date_submitted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message TEXT NOT NULL
);

CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(20) NOT NULL,
    sender VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);

-- CREATE TABLE students (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     student_id VARCHAR(50) UNIQUE NOT NULL,
--     first_name VARCHAR(50) NOT NULL,
--     middle_name VARCHAR(50),
--     last_name VARCHAR(50) NOT NULL,
--     email VARCHAR(100) UNIQUE NOT NULL,
--     program VARCHAR(50) NOT NULL,
--     year_level VARCHAR(20) NOT NULL,
--     status VARCHAR(20) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE admin_settings (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     email VARCHAR(100) NOT NULL UNIQUE,
--     username VARCHAR(50) NOT NULL UNIQUE,
--     password VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE students (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     student_id VARCHAR(50) UNIQUE NOT NULL,
--     full_name VARCHAR(100) NOT NULL,
--     program VARCHAR(100) NOT NULL
--     -- Add other fields as needed
-- );

-- for student database

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);