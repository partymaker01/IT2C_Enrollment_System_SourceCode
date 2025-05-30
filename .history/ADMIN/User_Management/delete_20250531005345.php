<?php
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM admin_settings WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage-admin-users.php");
        exit;
    } else {
        echo "<script>alert('Error deleting admin: " . $conn->error . "'); window.history.back();</script>";
    }
}
?>
