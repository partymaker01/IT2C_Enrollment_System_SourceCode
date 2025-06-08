-- Create enrollment_periods table first
CREATE TABLE IF NOT EXISTS enrollment_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester VARCHAR(50) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_settings(id)
);

-- Create subjects table if it doesn't exist
CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_title VARCHAR(100) NOT NULL,
    description TEXT,
    units DECIMAL(3,1) NOT NULL DEFAULT 3.0,
    program VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    semester VARCHAR(20),
    school_year VARCHAR(20),
    instructor VARCHAR(100),
    day VARCHAR(50),
    time VARCHAR(50),
    room VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create enrollments table if it doesn't exist
CREATE TABLE IF NOT EXISTS enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    program VARCHAR(50) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    section VARCHAR(10),
    status ENUM('pending', 'approved', 'rejected', 'missing_documents', 'enrolled', 'dropped', 'cancelled') DEFAULT 'pending',
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT UNSIGNED NULL,
    date_processed TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (processed_by) REFERENCES admin_settings(id)
);

COMMIT;
