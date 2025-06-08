<?php
// Function to generate student ID
function generateStudentId($conn, $program) {
    $year = date('y');
    $programCodes = [
        'Information Technology' => 'IT',
        'Hotel and Restaurant Management Technology' => 'HRMT',
        'Electronics and Computer Technology' => 'ECT',
        'Hospitality Services technology' => 'HST',
        'Techncal Vocational Education Techonlogy' => 'TVET',
        'Enterpreneurship Technology' => 'ET',
    ];
    $programCode = $programCodes[$program] ?? 'GEN';
    $basePattern = "TLGC-{$programCode}-{$year}-";

    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
    $likePattern = $basePattern . '%';
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastId = $result->fetch_column();

    $newNumber = $lastId ? intval(substr($lastId, -4)) + 1 : 1;
    $formattedNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    return $basePattern . $formattedNumber;
}

// Function to update student ID
function updateStudentId($conn, $oldId, $newId) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) throw new Exception("Failed to update students table");

        $stmt = $conn->prepare("UPDATE enrollments SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) throw new Exception("Failed to update enrollments table");

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("updateStudentId error: " . $e->getMessage());
        return false;
    }
}
?>
