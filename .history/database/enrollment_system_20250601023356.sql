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

CREATE TABLE enrollment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
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
    FOREIGN KEY (enrollment_id) REFERENCES enrollment(id)
);