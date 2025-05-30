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

<!-- Simple edit form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Staff</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
  <div class="container">
    <h2 class="mb-4">Edit Staff Account</h2>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="staffName" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="staffEmail" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Position</label>
        <select name="staffPosition" class="form-select" required>
          <option <?= $staff['position'] == 'Registrar' ? 'selected' : '' ?>>Registrar</option>
          <option <?= $staff['position'] == 'Enrollment Staff' ? 'selected' : '' ?>>Enrollment Staff</option>
          <option <?= $staff['position'] == 'Teacher' ? 'selected' : '' ?>>Teacher</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="staffStatus" class="form-select" required>
          <option <?= $staff['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
          <option <?= $staff['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Update Staff</button>
      <a href="manage-register-staff.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>
</html>
