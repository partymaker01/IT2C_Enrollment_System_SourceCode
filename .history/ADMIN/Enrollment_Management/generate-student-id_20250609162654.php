<?php
/**
 * Generate a new student ID in the format TLGC-YY-XXXX
 */
function generateStudentId($conn, $program = '') {
    $currentYear = date('y'); // 2-digit year
    $prefix = "TLGC-{$currentYear}-";
    
    // Get the highest existing number for this year
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM students 
        WHERE student_id LIKE ? 
        ORDER BY CAST(SUBSTRING(student_id, -4) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['student_id'];
        $lastNumber = intval(substr($lastId, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    $newStudentId = $prefix . $formattedNumber;
    
    $stmt->close();
    return $newStudentId;
}

/**
 * Update student ID - OPTIMIZED for your database schema
 */
function updateStudentId($conn, $oldId, $newId) {
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    $conn->begin_transaction();
    try {
        error_log("Starting student ID update from {$oldId} to {$newId}");
        
        // First, check if the old ID exists
        $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkStmt->bind_param("s", $oldId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student with ID {$oldId} not found");
        }
        $checkStmt->close();
        
        // Check if new ID already exists
        $checkNewStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkNewStmt->bind_param("s", $newId);
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        
        if ($newResult->num_rows > 0) {
            throw new Exception("Student ID {$newId} already exists");
        }
        $checkNewStmt->close();
        
        // Update child tables first (in order based on your schema)
        $childTables = [
            'password_resets',
            'uploaded_documents', 
            'tickets',
            'enrollments'
        ];
        
        foreach ($childTables as $table) {
            $stmt = $conn->prepare("UPDATE {$table} SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newId, $oldId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update {$table}: " . $conn->error);
            }
            
            $affectedRows = $stmt->affected_rows;
            error_log("Updated {$affectedRows} rows in {$table}");
            $stmt->close();
        }
        
        // Handle student_subjects separately (has UNIQUE constraint)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_subjects WHERE student_id = ?");
        $stmt->bind_param("s", $oldId);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasStudentSubjects = $result->fetch_assoc()['count'] > 0;
        $stmt->close();
        
        if ($hasStudentSubjects) {
            $stmt = $conn->prepare("UPDATE student_subjects SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newId, $oldId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update student_subjects: " . $conn->error);
            }
            
            error_log("Updated " . $stmt->affected_rows . " rows in student_subjects");
            $stmt->close();
        }
        
        // Handle enrollment_periods separately (has UNIQUE constraint)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollment_periods WHERE student_id = ?");
        $stmt->bind_param("s", $oldId);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasEnrollmentPeriods = $result->fetch_assoc()['count'] > 0;
        $stmt->close();
        
        if ($hasEnrollmentPeriods) {
            $stmt = $conn->prepare("UPDATE enrollment_periods SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newId, $oldId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update enrollment_periods: " . $conn->error);
            }
            
            error_log("Updated " . $stmt->affected_rows . " rows in enrollment_periods");
            $stmt->close();
        }
        
        // Finally update the parent table (students)
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated in students table");
        }
        
        error_log("Successfully updated students table: " . $stmt->affected_rows . " rows affected");
        $stmt->close();
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $verifyStmt->bind_param("s", $newId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 0) {
            throw new Exception("Verification failed - new student ID not found");
        }
        $verifyStmt->close();
        
        $conn->commit();
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        error_log("Successfully updated student ID from {$oldId} to {$newId}");
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        error_log("updateStudentId error: " . $e->getMessage());
        return false;
    }
}

/**
 * Preview what the next student ID will be
 */
function previewNextStudentId($conn, $program = '') {
    $currentYear = date('y');
    $prefix = "TLGC-{$currentYear}-";
    
    // Get the highest existing number for this year
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM students 
        WHERE student_id LIKE ? 
        ORDER BY CAST(SUBSTRING(student_id, -4) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['student_id'];
        $lastNumber = intval(substr($lastId, -4));
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    $stmt->close();
    return $prefix . $formattedNumber;
}
?>