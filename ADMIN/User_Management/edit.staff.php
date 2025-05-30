<?php
include '../../db.php';

if (!isset($_GET['id'])) {
  header("Location: manage-register-staff.php");
  exit();
}

$id = intval($_GET['id']);

// Fetch existing staff info
$stmt = $conn->prepare("SELECT * FROM registrar_staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
  echo "Staff not found.";
  exit();
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['staffName'];
  $email = $_POST['staffEmail'];
  $position = $_POST['staffPosition'];
  $status = $_POST['staffStatus'];

  $stmt = $conn->prepare("UPDATE registrar_staff SET name=?, email=?, position=?, status=? WHERE id=?");
  $stmt->bind_param("ssssi", $name, $email, $position, $status, $id);
  $stmt->execute();
  $stmt->close();

  header("Location: manage-register-staff.php");
  exit();
}
?>
