<?php
include '../db.php'; // adjust path based on actual directory

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $username = $_POST['username'];

    $stmt = $conn->prepare("UPDATE admin_settings SET name = ?, email = ?, username = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullName, $email, $username, $id);

    if ($stmt->execute()) {
        header("Location: manage-admins-users.php?success=updated");
        exit;
    } else {
        echo "Failed to update admin.";
    }
}
?>
