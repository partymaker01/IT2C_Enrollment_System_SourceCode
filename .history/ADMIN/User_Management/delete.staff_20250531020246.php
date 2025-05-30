<?php
include '../../db.php';

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  // Use prepared statement
  $stmt = $conn->prepare("DELETE FROM registrar_staff WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: manage-register-staff.php");
exit();
?>
