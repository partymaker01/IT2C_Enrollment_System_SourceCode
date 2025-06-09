<?php
/**
 * Generate a new student ID in the format TLGC-YY-XXXX
 * where YY is the last 2 digits of current year and XXXX starts from 0001
 */
function generateStudentId($conn, $program = '') {
    $currentYear = date('y'); // Use 'y' for 2-digit year (23, 24, etc.)
    $prefix = "TLGC-{$currentYear}-";
    
    // Count existing students with the same year prefix to get the next number
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id LIKE ?");
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    // Next number should be count + 1 (so first student gets 1, second gets 2, etc.)
    $nextNumber = $count + 1;
    
    // Format the number with leading zeros (4 digits)
    $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    
    $newStudentId = $prefix . $formattedNumber;
    
    // Double-check that this ID doesn't already exist (safety check)
    $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $checkStmt->bind_param("s", $newStudentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    // If ID already exists, find the next available number
    if ($checkResult->num_rows > 0) {
        // Get the highest number for this year and add 1
        $maxStmt = $conn->prepare("
            SELECT student_id 
            FROM students 
            WHERE student_id LIKE ? 
            ORDER BY CAST(SUBSTRING(student_id, -4) AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $maxStmt->bind_param("s", $searchPattern);
        $maxStmt->execute();
        $maxResult = $maxStmt->get_result();
        
        if ($maxResult->num_rows > 0) {
            $lastId = $maxResult->fetch_assoc()['student_id'];
            $lastNumber = intval(substr($lastId, -4));
            $nextNumber = $lastNumber + 1;
            $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $newStudentId = $prefix . $formattedNumber;
        }
        $maxStmt->close();
    }
    
    $stmt->close();
    $checkStmt->close();
    
    return $newStudentId;
}

/**
 * Alternative simpler version - mas sure na sequential
 */
function generateStudentIdSimple($conn, $program = '') {
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
        // May existing student na, kunin yung last number tapos add 1
        $lastId = $result->fetch_assoc()['student_id'];
        $lastNumber = intval(substr($lastId, -4)); // Get last 4 digits
        $newNumber = $lastNumber + 1;
    } else {
        // Walang existing student for this year, start with 1
        $newNumber = 1;
    }
    
    // Format with leading zeros
    $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    $newStudentId = $prefix . $formattedNumber;
    
    $stmt->close();
    return $newStudentId;
}
?>