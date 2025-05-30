<?php
include '../db.php';

$email = 'admin@example.com';
$newPassword = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $newPassword, $email);

if ($stmt->execute()) {
    echo "✅ Password updated for admin@example.com";
} else {
    echo "❌ Failed to update: " . $stmt->error;
}
?>
