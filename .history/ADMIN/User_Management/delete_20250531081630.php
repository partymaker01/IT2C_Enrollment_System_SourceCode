<?php
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM admin_settings WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: manage-admins-users.php"); // redirect back
            exit;
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    } else {
        echo "Invalid ID.";
    }
} else {
    echo "Invalid request.";
}
