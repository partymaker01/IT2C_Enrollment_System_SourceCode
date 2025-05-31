<?php
include '../../db.php'; 

if (isset($_POST['id'])) {
  $id = intval($_POST['id']); 
  $sql = "DELETE FROM registrar_staff WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header("Location: manage-register-staff.php");
    exit();
  } else {
    echo "Error deleting record: " . $conn->error; 
  }
  $stmt->close();
} else {
  echo "ID not provided."; 
}
?>
