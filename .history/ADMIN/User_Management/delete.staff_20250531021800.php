<?php
include '../../db.php';
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $sql = "DELETE FROM registrar_staff WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}
header("Location: manage-register-staff.php"); // Go back to the main page
exit();
?>
