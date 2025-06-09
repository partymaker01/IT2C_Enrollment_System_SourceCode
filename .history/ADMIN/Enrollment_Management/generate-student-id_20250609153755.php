<?php

require_once '../../generate-student-id.php';

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
        
        // Update in correct order to avoid foreign key issues
        
        // 1. Update enrollments first (child table)
        $stmt = $conn->prepare("UPDATE enrollments SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update enrollments table: " . $conn->error);
        }
        
        // 2. Update other related tables
        $tables = [
            'student_subjects',
            'uploaded_documents', 
            'tickets',
            'password_resets'
        ];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("UPDATE {$table} SET student_id = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $newId, $oldId);
            if (!$stmt->execute()) {
                // Log but don't fail if optional tables don't exist or have no records
                error_log("Warning: Failed to update {$table}: " . $conn->error);
            }
        }
        
        // 3. Finally update students table (parent table)
        $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newId, $oldId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update students table: " . $conn->error);
        }
        
        // Verify the update was successful
        $verifyStmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $verifyStmt->bind_param("s", $newId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 0) {
            throw new Exception("Student ID update verification failed");
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("updateStudentId error: " . $e->getMessage());
        return false;
    }
}
?>