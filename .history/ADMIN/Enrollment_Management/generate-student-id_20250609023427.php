<?php
// Helper file para sa student ID generation with improved foreign key handling

// Function to generate student ID based on program
function generateStudentId($conn, $program) {
    // Get current year (last 2 digits)
    $year = date('y');
    
    // Map programs to their abbreviations
    $programCodes = [
        'Information Technology' => 'IT',
        'Hotel and Restaurant Management Technology' => 'HRMT', 
        'Electronics and Computer Technology' => 'ECT',
        'Hospitality Services technology' => 'HST',
        'Techncal Vocational Education Techonlogy' => 'TVET',
        'Enterpreneurship Technology' => 'ET',
    ];
    
    // Get program code
    $programCode = $programCodes[$program] ?? 'GEN';
    
    // Create the base pattern
    $basePattern = "TLGC-{$programCode}-{$year}-";
    
    // Get the highest existing number for this pattern
    $stmt = $conn->prepare("
        SELECT student_id 
        FROM students 
        WHERE student_id LIKE ? 
        ORDER BY CAST(SUBSTRING(student_id, -4) AS UNSIGNED) DESC
        LIMIT 1
    ");
    
    $likePattern = $basePattern . '%';
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $lastId = $row ? $row['student_id'] : null;
    
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

// Function to find all tables that reference student_id
function findTablesWithStudentId($conn) {
    $tables = [];
    
    // Get all tables in the database
    $result = $conn->query("SHOW TABLES");
    $allTables = [];
    while ($row = $result->fetch_array()) {
        $allTables[] = $row[0];
    }
    
    // Check each table for student_id column
    foreach ($allTables as $table) {
        $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE 'student_id'");
        if ($result->num_rows > 0) {
            $tables[] = $table;
        }
    }
    
    return $tables;
}

// IMPROVED: Function to update student ID with comprehensive foreign key handling
function updateStudentId($conn, $oldStudentId, $newStudentId) {
    try {
        // Disable foreign key checks temporarily
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // Start transaction
        $conn->begin_transaction();
        
        // Check if new student ID already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE student_id = ?");
        $checkStmt->bind_param("s", $newStudentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();
        
        if ($checkResult['count'] > 0) {
            throw new Exception("New Student ID already exists: " . $newStudentId);
        }
        
        // Find all tables that have student_id column
        $tablesWithStudentId = findTablesWithStudentId($conn);
        
        $updatedTables = [];
        $errors = [];
        
        // Update each table
        foreach ($tablesWithStudentId as $table) {
            try {
                // Check if the old student_id exists in this table
                $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM `{$table}` WHERE student_id = ?");
                $checkStmt->bind_param("s", $oldStudentId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result()->fetch_assoc();
                
                if ($checkResult['count'] > 0) {
                    // Update the table
                    $updateStmt = $conn->prepare("UPDATE `{$table}` SET student_id = ? WHERE student_id = ?");
                    $updateStmt->bind_param("ss", $newStudentId, $oldStudentId);
                    
                    if (!$updateStmt->execute()) {
                        throw new Exception("Failed to update table {$table}: " . $updateStmt->error);
                    }
                    
                    $updatedTables[] = $table . " (" . $updateStmt->affected_rows . " rows)";
                }
            } catch (Exception $e) {
                $errors[] = "Table {$table}: " . $e->getMessage();
            }
        }
        
        // If there were any errors, throw an exception
        if (!empty($errors)) {
            throw new Exception("Errors updating tables: " . implode("; ", $errors));
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Commit transaction
        $conn->commit();
        
        // Log successful update
        error_log("Successfully updated Student ID from {$oldStudentId} to {$newStudentId}. Updated tables: " . implode(", ", $updatedTables));
        
        return true;
        
    } catch (Exception $e) {
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Rollback transaction
        $conn->rollback();
        
        // Log detailed error
        error_log("updateStudentId Error: " . $e->getMessage() . " | Old ID: {$oldStudentId} | New ID: {$newStudentId}");
        
        return false;
    }
}

// Alternative approach: Instead of updating, create new record and transfer data
function migrateStudentId($conn, $oldStudentId, $newStudentId) {
    try {
        $conn->begin_transaction();
        
        // Get the original student record
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $oldStudentId);
        $stmt->execute();
        $studentData = $stmt->get_result()->fetch_assoc();
        
        if (!$studentData) {
            throw new Exception("Student not found with ID: " . $oldStudentId);
        }
        
        // Create new student record with new ID
        $columns = array_keys($studentData);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $columnsList = '`' . implode('`, `', $columns) . '`';
        
        // Update the student_id in the data array
        $studentData['student_id'] = $newStudentId;
        
        $insertStmt = $conn->prepare("INSERT INTO students ({$columnsList}) VALUES ({$placeholders})");
        $insertStmt->bind_param(str_repeat('s', count($studentData)), ...array_values($studentData));
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to create new student record: " . $insertStmt->error);
        }
        
        // Update enrollments to point to new student_id
        $stmt = $conn->prepare("UPDATE enrollments SET student_id = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $newStudentId, $oldStudentId);
        $stmt->execute();
        
        // Update other tables if they exist
        $tablesWithStudentId = findTablesWithStudentId($conn);
        foreach ($tablesWithStudentId as $table) {
            if ($table !== 'students') { // Skip students table as we already handled it
                $stmt = $conn->prepare("UPDATE `{$table}` SET student_id = ? WHERE student_id = ?");
                $stmt->bind_param("ss", $newStudentId, $oldStudentId);
                $stmt->execute();
            }
        }
        
        // Delete the old student record
        $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $oldStudentId);
        $stmt->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("migrateStudentId Error: " . $e->getMessage());
        return false;
    }
}

// Function to check if student ID update is needed
function needsStudentIdUpdate($studentId) {
    return !preg_match('/^TLGC-/', $studentId);
}

// Function to validate student ID format
function validateStudentId($studentId) {
    return preg_match('/^TLGC-[A-Z]+-\d{2}-\d{4}$/', $studentId);
}

// Debug function to check what's preventing the update
function debugStudentIdUpdate($conn, $oldStudentId) {
    $debug = [];
    
    // Check if student exists
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $oldStudentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $debug['student_exists'] = $student ? true : false;
    
    // Find all tables with student_id
    $tablesWithStudentId = findTablesWithStudentId($conn);
    $debug['tables_with_student_id'] = $tablesWithStudentId;
    
    // Check foreign key constraints
    $result = $conn->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_NAME = 'students' 
            AND REFERENCED_COLUMN_NAME = 'student_id'
    ");
    
    $foreignKeys = [];
    while ($row = $result->fetch_assoc()) {
        $foreignKeys[] = $row;
    }
    $debug['foreign_keys'] = $foreignKeys;
    
    return $debug;
}
?>
