<?php
include '../../db.php'; // Ensure you're including the correct database connection

if (isset($_POST['id'])) {
  // Sanitize the ID to prevent SQL injection
  $id = intval($_POST['id']);
  
  // Perform the deletion
  $stmt = $conn->prepare("DELETE FROM admin_settings WHERE id = ?");
  $stmt->bind_param("i", $id);
  
  if ($stmt->execute()) {
    // Redirect back to the manage admin users page after deleting
    header("Location: ../../manage-admins-users.php");
    exit();
  } else {
    echo "Error deleting record: " . $conn->error; // Output any error if the delete fails
  }
  $stmt->close();
} else {
  echo "No ID provided"; // Handle case where no ID is passed
}
?>
