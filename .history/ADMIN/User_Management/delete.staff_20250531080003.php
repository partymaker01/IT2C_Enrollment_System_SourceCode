<?php
include '../../db.php'; // Ensure the correct database connection

if (isset($_POST['id'])) {
  $id = intval($_POST['id']); // Get the ID passed via POST
  $sql = "DELETE FROM registrar_staff WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id); // Bind the ID as an integer
  if ($stmt->execute()) {
    header("Location: manage-register-staff.php"); // Redirect after successful deletion
    exit();
  } else {
    echo "Error deleting record: " . $conn->error; // If there's an error with the delete query
  }
  $stmt->close();
} else {
  echo "ID not provided."; // If no ID is passed
}
?>
