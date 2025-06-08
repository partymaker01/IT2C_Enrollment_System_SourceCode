<?php
// Helper file for student ID generation that works with INT student_id

// Function to generate formatted student ID based on program
function generateFormattedId($conn, $program, $studentId) {
    // Get current year (last 2 digits)
    $year = date('y');
    
    // Map programs to their abbreviations
    $programCodes = [
        'Information Technology' => 'IT',
        'Hotel and Restaurant Management Technology' => 'HRMT', 
        'Electronics and Computer Technology' => 'ECT',
        'Hospitality Services technology' => 'HST',
        'Techncal Vocational Education Techonlogy' => 'TVET',
        'Enterpreneurship Technology' => 'ET',
    ];
    
    // Get program code
    $programCode = $programCodes[$program] ?? 'GEN';
    
    // Create the base pattern
    $basePattern = "TLGC-{$programCode}-{$year}-";
    
    // Get count of students in this program to determine the number
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.student_id) as count
        FROM students s 
        JOIN enrollments e ON s.student_id = e.student_id 
        WHERE e.program = ? 
        AND s.student_id <= ?
    ");
    $stmt->bind_param("si", $program, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $number = $row['count'] ?: 1;
    
    // Format with leading zeros
    $formattedNumber = str_pad($number, 4, '0', STR_PAD_LEFT);
    
    return $basePattern . $formattedNumber;
}

// Function to check if student needs a formatted ID
function needsFormattedId($conn, $studentId) {
    $stmt = $conn->prepare("SELECT formatted_id FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return empty($row['formatted_id']);
}

// Function to update student's formatted ID
function updateFormattedId($conn, $studentId, $formattedId) {
    try {
        $stmt = $conn->prepare("UPDATE students SET formatted_id = ? WHERE student_id = ?");
        $stmt->bind_param("si", $formattedId, $studentId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update formatted_id: " . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("updateFormattedId Error: " . $e->getMessage());
        return false;
    }
}

// Function to validate formatted ID format
function validateFormattedId($formattedId) {
    return preg_match('/^TLGC-[A-Z]+-\d{2}-\d{4}$/', $formattedId);
}

// Function to get student's display ID (formatted if available, otherwise numeric)
function getStudentDisplayId($conn, $studentId) {
    $stmt = $conn->prepare("SELECT formatted_id FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['formatted_id'] ?: $studentId;
}

// For backward compatibility with your existing code
function generateStudentId($conn, $program) {
    return generateFormattedId($conn, $program, 0);
}

function updateStudentId($conn, $oldStudentId, $newStudentId) {
    // This is just a stub for backward compatibility
    // Your system now uses formatted_id instead of changing the actual student_id
    return true;
}

function needsStudentIdUpdate($studentId) {
    return !preg_match('/^TLGC-/', $studentId);
}

function validateStudentId($studentId) {
    return preg_match('/^TLGC-[A-Z]+-\d{2}-\d{4}$/', $studentId);
}

// Debug function to help troubleshoot issues
function debugStudentIdUpdate($conn, $studentId) {
    $info = [];
    
    // Check if student exists
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $info['student_exists'] = $result->num_rows > 0;
    
    // Check if formatted_id column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM students LIKE 'formatted_id'");
    $info['formatted_id_column_exists'] = $checkColumn->num_rows > 0;
    
    // Get tables with student_id column
    $result = $conn->query("
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE COLUMN_NAME = 'student_id' 
        AND TABLE_SCHEMA = DATABASE()
    ");
    
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row['TABLE_NAME'];
    }
    $info['tables_with_student_id'] = $tables;
    
    return $info;
}

// Alternative method for updating student IDs if the standard method fails
function migrateStudentId($conn, $oldStudentId, $newStudentId) {
    try {
        // Disable foreign key checks temporarily
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->begin_transaction();
        
        // Get all tables with student_id column
        $result = $conn->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE COLUMN_NAME = 'student_id' 
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            $tables[] = $row['TABLE_NAME'];
        }
        
        // Update each table
        foreach ($tables as $table) {
            $stmt = $conn->prepare("UPDATE `{$table}` SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newStudentId, $oldStudentId);
            $stmt->execute();
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollback();
        error_log("migrateStudentId Error: " . $e->getMessage());
        return false;
    }
}
?>
