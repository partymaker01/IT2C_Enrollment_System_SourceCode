<?php
session_start();
include '../../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    
    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT first_name, last_name, middle_name, email, program, year_level FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'name' => $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name'],
                'email' => $student['email'],
                'program' => $student['program'],
                'year_level' => $student['year_level']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
