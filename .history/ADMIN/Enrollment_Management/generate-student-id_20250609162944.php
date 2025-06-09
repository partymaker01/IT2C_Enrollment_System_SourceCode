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
 * SIMPLIFIED Update student ID - Focus on essential tables only
 */
function updateStudentId($conn, $oldId, $newId) {
    $conn->begin_transaction();
    try {
        error_log("=== STARTING STUDENT ID UPDATE ===");
        error_log("Old ID: {$oldId}");
        error_log("New ID: {$newId}");
        
        // Check if old ID exists
        $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkStmt->bind_param("s", $oldId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Student with ID {$oldId} not found");
        }
        $checkStmt->close();
        error_log("✓ Old student ID found");
        
        // Check if new ID already exists
        $checkNewStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $checkNewStmt->bind_param("s", $newId);
        $checkNewStmt->execute();
        $newResult = $checkNewStmt->get_result();
        
        if ($newResult->num_rows > 0) {
            throw new Exception("Student ID {$newId} already exists");
        }
        $checkNewStmt->close();
        error_log("✓ New student ID is available");
        
        // ONLY UPDATE ESSENTIAL TABLES - Skip problematic ones for now
        $essentialTables = [
            'enrollments',
            'password_resets'
        ];
        
        foreach ($essentialTables as $table) {
            try {
                // Check if table has records for this student
                $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM {$table} WHERE student_id = ?");
                $countStmt->bind_param("s", $oldId);
                $countStmt->execute();
                $countResult = $countStmt->get_result();
                $recordCount = $countResult->fetch_assoc()['count'];
                $countStmt->close();
                
                if ($recordCount > 0) {
                    error_log("Updating {$recordCount} records in {$table}");
                    
                    $stmt = $conn->prepare("UPDATE {$table} SET student_id = ? WHERE student_id = ?");
                    $stmt->bind_param("ss", $newId, $oldId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update {$table}: " . $conn->error);
                    }
                    
                    $affectedRows = $stmt->affected_rows;
                    error_log("✓ Updated {$affectedRows} rows in {$table}");
                    $stmt->close();
                } else {
                    error_log("No records found in {$table} for student {$oldId}");
                }
                
            } catch (Exception $e) {
                error_log("ERROR updating {$table}: " . $e->getMessage());
                throw $e; // Re-throw to trigger rollback
            }
        }
        
        // Update the main students table LAST
        error_log("Updating main students table...");
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated in students table");
        }
        
        error_log("✓ Updated students table: " . $stmt->affected_rows . " rows affected");
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
        error_log("✓ Verification successful");
        
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