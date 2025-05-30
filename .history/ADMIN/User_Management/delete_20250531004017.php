<?php
include '../db.php'; // adjust path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM admin_settings WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage-admins-users.php?success=deleted");
        exit;
    } else {
        echo "Failed to delete admin.";
    }
}
?>
