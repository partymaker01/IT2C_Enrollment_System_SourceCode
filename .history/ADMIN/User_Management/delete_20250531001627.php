<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'];

  $stmt = $conn->prepare("DELETE FROM admin_settings WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: manage-admins.php?status=deleted");
}
