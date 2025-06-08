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
        'Computer Science' => 'HRMT', 
        'in Business Administration' => 'BA',
        'Hospitality Management' => 'HRMT',
        'Tourism Management' => 'TM',
        '' => 'ACT',
        // Add more programs as needed
        'ECT' => 'ECT',
        'HST' => 'HST',
        'TVET' => 'TVET'
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
    $stmt->bind_param("s", $basePattern . '%');
    $stmt->execute();
    $result = $stmt->get_result();
    $lastId = $result->fetch_column();
    
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

// Function to update student ID
function updateStudentId($conn, $oldStudentId, $newStudentId) {
    try {
        $conn->begin_transaction();
        
        // Update students table
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        $stmt->execute();
        
        // Update enrollments table
        $stmt = $conn->prepare("UPDATE enrollments SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        $stmt->execute();
        
        // Update student_subjects table if it exists
        $stmt = $conn->prepare("UPDATE student_subjects SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>
