<?php
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $username = $_POST['username'];

    $stmt = $conn->prepare("UPDATE admin_settings SET name = ?, email = ?, username = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullName, $email, $username, $id);

    if ($stmt->execute()) {
        header("Location: manage-admin-users.php");
        exit;
    } else {
        echo "<script>alert('Error updating admin: " . $conn->error . "'); window.history.back();</script>";
    }
}
?>