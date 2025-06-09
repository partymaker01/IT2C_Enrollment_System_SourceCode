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
 * Update student ID across all related tables
 */
function updateStudentId($conn, $oldId, $newId) {
    $conn->begin_transaction();
    try {
        // First, check if the old ID exists
        $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkStmt->bind_param("s", $oldId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student with ID {$oldId} not found");
        }
        
        // Update related tables first (child tables)
        $tables = [
            'enrollments',
            'student_subjects', 
            'uploaded_documents',
            'tickets',
            'password_resets'
        ];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("UPDATE {$table} SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newId, $oldId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Finally update students table (parent table)
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        $stmt->close();
        
        // Verify the update was successful
        $verifyStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $verifyStmt->bind_param("s", $newId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 0) {
            throw new Exception("Student ID update verification failed");
        }
        $verifyStmt->close();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("updateStudentId error: " . $e->getMessage());
        return false;
    }
}

/**
 * Preview what the next student ID will be (for display purposes only)
 */
function previewNextStudentId($conn, $program = '') {
    $currentYear = date('y');
    $prefix = "TLGC-{$currentYear}-";
    
    // Count existing students to get next number
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id LIKE ?");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    $nextNumber = $count + 1;
    $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    
    $stmt->close();
    return $prefix . $formattedNumber;
}
?>