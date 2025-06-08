<?php
// Helper file para sa student ID generation
// I-save mo ito sa same folder ng process-enrollment.php

// Function to generate student ID based on program
function generateStudentId($conn, $program) {
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
    $programCode = $programCodes[$program] ?? 'GEN'; // Default to 'GEN' if not found
    
    // Create the base pattern
    $basePattern = "TLGC-{$programCode}-{$year}-";
    
    // Get the highest existing number for this pattern
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM students 
        WHERE student_id LIKE ? 
        ORDER BY student_id DESC 
        LIMIT 1
    ");
    
    $likePattern = $basePattern . '%';
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // FIXED: Use fetch_assoc() instead of fetch_column()
    $row = $result->fetch_assoc();
    $lastId = $row ? $row['student_id'] : null;
    
    if ($lastId) {
        // Extract the number part and increment
        $lastNumber = intval(substr($lastId, -4));
        $newNumber = $lastNumber + 1;
    } else {
        // First student for this program/year
        $newNumber = 1;
    }
    
    // Format with leading zeros
    $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    
    return $basePattern . $formattedNumber;
}

// FIXED: Improved function to update student ID with better error handling
function updateStudentId($conn, $oldStudentId, $newStudentId) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if new student ID already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
        $checkStmt->bind_param("s", $newStudentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();
        
        if ($checkResult['count'] > 0) {
            throw new Exception("New Student ID already exists: " . $newStudentId);
        }
        
        // Update students table
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $stmt->error);
        }
        
        $studentsUpdated = $stmt->affected_rows;
        if ($studentsUpdated === 0) {
            throw new Exception("No student found with ID: " . $oldStudentId);
        }
        
        // Update enrollments table
        $stmt = $conn->prepare("UPDATE enrollments SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update enrollments table: " . $stmt->error);
        }
        
        // Update student_subjects table if it exists (check if table exists first)
        $tableCheck = $conn->query("SHOW TABLES LIKE 'student_subjects'");
        if ($tableCheck->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE student_subjects SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newStudentId, $oldStudentId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update student_subjects table: " . $stmt->error);
            }
        }
        
        // Update any other tables that might reference student_id
        // Add more tables here if needed
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log the error for debugging
        error_log("updateStudentId Error: " . $e->getMessage());
        
        return false;
    }
}

// FIXED: Add a function to check if student ID update is needed
function needsStudentIdUpdate($studentId) {
    return !preg_match('/^TLGC-/', $studentId);
}

// FIXED: Add a function to validate student ID format
function validateStudentId($studentId) {
    return preg_match('/^TLGC-[A-Z]+-\d{2}-\d{4}$/', $studentId);
}
?>