<?php
include 'db.php';
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $conn->query("EDIT FROM staff WHERE id = $id");
}
header("Location: manage-staff.php"); // Go back to the main page
exit();
?>