<?php
/**
 * Generate a new student ID in the format TLGC-YY-XXXX
 */
function generateStudentId($conn, $program = '') {
    $currentYear = date('y'); // 2-digit year
    $prefix = "TLGC-{$currentYear}-";
    
    // Get the highest existing number for this year from formatted_id column
    $stmt = $conn->prepare("
        SELECT formatted_id 
        FROM students 
        WHERE formatted_id LIKE ? 
        ORDER BY CAST(SUBSTRING(formatted_id, -4) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['formatted_id'];
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
 * Update student formatted_id - FIXED for your actual schema
 */
function updateStudentId($conn, $oldId, $newId) {
    $conn->begin_transaction();
    try {
        error_log("=== STARTING STUDENT ID UPDATE ===");
        error_log("Old ID: {$oldId} (this is the integer student_id)");
        error_log("New formatted ID: {$newId}");
        
        // Find the student by integer student_id
        $checkStmt = $conn->prepare("SELECT student_id, formatted_id FROM students WHERE student_id = ?");
        $checkStmt->bind_param("i", $oldId); // Use integer binding
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student with ID {$oldId} not found");
        }
        
        $student = $result->fetch_assoc();
        $checkStmt->close();
        error_log("✓ Found student with ID: {$oldId}, current formatted_id: " . ($student['formatted_id'] ?: 'NULL'));
        
        // Check if new formatted ID already exists
        $checkNewStmt = $conn->prepare("SELECT student_id FROM students WHERE formatted_id = ?");
        $checkNewStmt->bind_param("s", $newId);
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        
        if ($newResult->num_rows > 0) {
            throw new Exception("Formatted ID {$newId} already exists");
        }
        $checkNewStmt->close();
        error_log("✓ New formatted ID is available");
        
        // Update the formatted_id in students table
        $stmt = $conn->prepare("UPDATE students SET formatted_id = ? WHERE student_id = ?");
        $stmt->bind_param("si", $newId, $oldId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated in students table");
        }
        
        error_log("✓ Updated students table: " . $stmt->affected_rows . " rows affected");
        $stmt->close();
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT formatted_id FROM students WHERE student_id = ?");
        $verifyStmt->bind_param("i", $oldId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verifiedStudent = $verifyResult->fetch_assoc();
        
        if (!$verifiedStudent || $verifiedStudent['formatted_id'] !== $newId) {
            throw new Exception("Verification failed - formatted_id not updated correctly");
        }
        $verifyStmt->close();
        error_log("✓ Verification successful - formatted_id is now: " . $verifiedStudent['formatted_id']);
        
        $conn->commit();
        error_log("=== STUDENT ID UPDATE COMPLETED SUCCESSFULLY ===");
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("=== STUDENT ID UPDATE FAILED ===");
        error_log("Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Preview what the next student ID will be
 */
function previewNextStudentId($conn, $program = '') {
    $currentYear = date('y');
    $prefix = "TLGC-{$currentYear}-";
    
    // Get the highest existing number for this year from formatted_id
    $stmt = $conn->prepare("
        SELECT formatted_id 
        FROM students 
        WHERE formatted_id LIKE ? 
        ORDER BY CAST(SUBSTRING(formatted_id, -4) AS UNSIGNED) DESC 
        LIMIT 1
    ");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['formatted_id'];
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