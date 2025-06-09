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
 * Update student ID across all related tables - IMPROVED VERSION
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
        $checkStmt->close();
        
        // Check which tables actually exist and have student_id column
        $tablesToUpdate = [];
        $possibleTables = [
            'enrollments',
            'student_subjects', 
            'uploaded_documents',
            'tickets',
            'password_resets'
        ];
        
        foreach ($possibleTables as $table) {
            // Check if table exists and has student_id column
            $checkTableStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND column_name = 'student_id'
            ");
            $checkTableStmt->bind_param("s", $table);
            $checkTableStmt->execute();
            $tableResult = $checkTableStmt->get_result();
            $tableExists = $tableResult->fetch_assoc()['count'] > 0;
            $checkTableStmt->close();
            
            if ($tableExists) {
                $tablesToUpdate[] = $table;
                error_log("Table {$table} exists and will be updated");
            } else {
                error_log("Table {$table} does not exist or doesn't have student_id column - skipping");
            }
        }
        
        // Update related tables first (child tables)
        foreach ($tablesToUpdate as $table) {
            try {
                $stmt = $conn->prepare("UPDATE {$table} SET student_id = ? WHERE student_id = ?");
                $stmt->bind_param("ss", $newId, $oldId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update table {$table}: " . $conn->error);
                }
                $affectedRows = $stmt->affected_rows;
                error_log("Updated {$affectedRows} rows in table {$table}");
                $stmt->close();
            } catch (Exception $e) {
                error_log("Error updating table {$table}: " . $e->getMessage());
                // Continue with other tables instead of failing completely
            }
        }
        
        // Finally update students table (parent table)
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("No rows updated in students table - student may not exist");
        }
        
        error_log("Successfully updated students table: " . $stmt->affected_rows . " rows affected");
        $stmt->close();
        
        // Verify the update was successful
        $verifyStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $verifyStmt->bind_param("s", $newId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 0) {
            throw new Exception("Student ID update verification failed - new ID not found");
        }
        $verifyStmt->close();
        
        $conn->commit();
        error_log("Successfully updated student ID from {$oldId} to {$newId}");
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("updateStudentId error: " . $e->getMessage());
        error_log("Rolling back transaction for student ID update");
        return false;
    }
}

/**
 * Preview what the next student ID will be (for display purposes only)
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