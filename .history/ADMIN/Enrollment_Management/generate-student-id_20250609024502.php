<?php
// Helper file for student ID generation that works with INT student_id

// Function to generate formatted student ID based on program
function generateFormattedId($conn, $program, $studentId) {
    // Use the SQL function we created
    $stmt = $conn->prepare("SELECT GenerateFormattedId(?, ?) as formatted_id");
    $stmt->bind_param("si", $program, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['formatted_id'];
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
?>
