<?php
include '../../db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die('Invalid ID.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['staffName'];
    $email = $_POST['staffEmail'];
    $position = $_POST['staffPosition'];
    $status = $_POST['staffStatus'];

    $stmt = $conn->prepare("UPDATE registrar_staff SET name=?, email=?, position=?, status=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $position, $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage-registar-staff.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM registrar_staff WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    die('Staff not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Staff</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h3>Edit Staff</h3>
    <form method="POST" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="staffName" class="form-control" required value="<?= htmlspecialchars($staff['name']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="staffEmail" class="form-control" required value="<?= htmlspecialchars($staff['email']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Position</label>
        <select name="staffPosition" class="form-select" required>
          <option value="Registrar" <?= $staff['position'] === 'Registrar' ? 'selected' : '' ?>>Registrar</option>
          <option value="Enrollment Staff" <?= $staff['position'] === 'Enrollment Staff' ? 'selected' : '' ?>>Enrollment Staff</option>
          <option value="Teacher" <?= $staff['position'] === 'Teacher' ? 'selected' : '' ?>>Teacher</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="staffStatus" class="form-select" required>
          <option value="Active" <?= $staff['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= $staff['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Update</button>
      <a href="manage-registar-staff.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>
</html>
