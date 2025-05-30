<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'];
  $name = $_POST['fullName'];
  $email = $_POST['email'];
  $username = $_POST['username'];

  $stmt = $conn->prepare("UPDATE admin_settings SET name=?, email=?, username=? WHERE id=?");
  $stmt->bind_param("sssi", $name, $email, $username, $id);
  $stmt->execute();
  header("Location: manage-admins.php?status=edited");
}