<?php
/**
 * Student ID Generation and Management Functions
 */

function needsFormattedId($conn, $studentId) {
    $stmt = $conn->prepare("SELECT formatted_id FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return empty($row['formatted_id']);
}

function generateFormattedId($conn, $program, $studentId) {
    // Get current year
    $currentYear = date('Y');
    
    // Program code mapping
    $programCodes = [
        'BSIT' => 'IT',
        'BSCS' => 'CS',
        'BSIS' => 'IS',
        'BSBA' => 'BA',
        'BSED' => 'ED',
        'BEED' => 'EE',
        // Add more program codes as needed
    ];
    
    // Get program code or use first 2 letters if not found
    $programCode = $programCodes[$program] ?? strtoupper(substr($program, 0, 2));
    
    // Format: YYYY-PROGRAM-XXXX (e.g., 2024-IT-0001)
    $formattedId = sprintf("%s-%s-%04d", $currentYear, $programCode, $studentId);
    
    return $formattedId;
}

function validateFormattedId($formattedId) {
    // Check if the formatted ID matches the expected pattern
    return preg_match('/^\d{4}-[A-Z]{2,3}-\d{4}$/', $formattedId);
}

function updateFormattedId($conn, $studentId, $formattedId) {
    $stmt = $conn->prepare("UPDATE students SET formatted_id = ? WHERE student_id = ?");
    $stmt->bind_param("si", $formattedId, $studentId);
    
    if ($stmt->execute()) {
        error_log("Updated formatted ID for student {$studentId}: {$formattedId}");
        return true;
    } else {
        error_log("Failed to update formatted ID for student {$studentId}: " . $stmt->error);
        return false;
    }
}
?>