<?php
include '../db.php';

echo "<h2>Database Table Structure Debug</h2>";

// Check if tables exist and their structure
$tables = ['enrollments', 'students', 'subjects', 'admin_settings'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    
    try {
        $stmt = $conn->prepare("DESCRIBE $table");
        $stmt->execute();
        $columns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($columns)) {
            echo "<p style='color: red;'>Table $table does not exist!</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking table $table: " . $e->getMessage() . "</p>";
    }
}

// Check sample data
echo "<h3>Sample Enrollments Data</h3>";
try {
    $stmt = $conn->prepare("SELECT * FROM enrollments LIMIT 3");
    $stmt->execute();
    $sampleData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($sampleData)) {
        echo "<p>No data in enrollments table</p>";
    } else {
        echo "<pre>" . print_r($sampleData, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting sample data: " . $e->getMessage() . "</p>";
}
?>
