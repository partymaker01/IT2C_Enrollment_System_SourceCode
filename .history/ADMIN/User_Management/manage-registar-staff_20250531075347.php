<?php
include '../../db.php'; // Make sure db.php has your DB connection

// Insert new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staffName'])) {
  $name = $_POST['staffName'];
  $email = $_POST['staffEmail'];
  $position = $_POST['staffPosition'];
  $status = $_POST['staffStatus'];

  $stmt = $conn->prepare("INSERT INTO registrar_staff (name, email, position, status) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $name, $email, $position, $status);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['PHP_SELF']); // Refresh to show new data
  exit();
}

// Handle Update Staff
if (isset($_POST['update_staff'])) {
  $id = $_POST['edit_id'];
  $name = $_POST['edit_name'];
  $email = $_POST['edit_email'];
  $position = $_POST['edit_position'];
  $status = $_POST['edit_status'];

  $stmt = $conn->prepare("UPDATE registrar_staff SET name=?, email=?, position=?, status=? WHERE id=?");
  $stmt->bind_param("ssssi", $name, $email, $position, $status, $id);
  $stmt->execute();
  $stmt->close();
}

// Handle Delete Staff
if (isset($_POST['delete_staff'])) {
  $id = $_POST['delete_id'];
  $stmt = $conn->prepare("DELETE FROM registrar_staff WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Fetch staff list
$staffList = [];
$result = $conn->query("SELECT * FROM registrar_staff ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
  $staffList[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Register Staff Accounts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
  />
  <style>
    body {
      background-color: #f1f8e9;
      min-height: 100vh;
    }
    .navbar {
      background-color: #2e7d32;
    }
    .navbar-brand, .nav-link {
      color: #fff !important;
      font-weight: 600;
      letter-spacing: 0.05em;
    }
    .nav-link:hover {
      color: #c8e6c9 !important;
    }
    .school-logo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
    }
    .container {
      max-width: 960px;
      margin-top: 40px;
    }
    .card {
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .modal-header {
      background-color: #43a047;
      color: white;
      border-top-left-radius: 1rem;
      border-top-right-radius: 1rem;
    }
    .btn-success:hover {
      filter: brightness(0.9);
      transition: filter 0.2s ease-in-out;
    }
    .table-responsive {
      background: white;
      padding: 0.75rem;
      border-radius: 0.5rem;
      box-shadow: 0 0 8px rgba(0,0,0,0.05);
    }

    @media (max-width: 576px) {
      .btn-group {
        flex-direction: column;
        gap: 0.3rem;
      }
      .btn-group .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
        Admin Panel
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <h2 class="text-center mb-4 text-success fw-bold">Manage Registrar/Staff Accounts</h2>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="bi bi-plus-circle me-1"></i> Add Staff
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0">
        <thead class="table-success text-center">
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Position</th>
            <th>Status</th>
            <th style="min-width:130px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staffList as $staff): ?>
          <tr>
            <td class="text-center"><?= htmlspecialchars($staff['id']) ?></td>
            <td><?= htmlspecialchars($staff['name']) ?></td>
            <td><?= htmlspecialchars($staff['email']) ?></td>
            <td><?= htmlspecialchars($staff['position']) ?></td>
            <td class="text-center">
              <span class="badge <?= $staff['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($staff['status']) ?></span>
            </td>
            <td class="text-center">
              <div class="btn-group" role="group" aria-label="Staff Actions">
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStaffModal" onclick="openEditModal(<?= $staff['id'] ?>, '<?= $staff['name'] ?>', '<?= $staff['email'] ?>', '<?= $staff['position'] ?>', '<?= $staff['status'] ?>')">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <form method="POST" action="" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?= $staff['id'] ?>">
                  <button type="submit" name="delete_staff" class="btn btn-danger btn-sm" onclick="return confirm('Delete this staff?')">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Staff Modal -->
  <?php foreach ($staffList as $staff): ?>
<div class="modal fade" id="editStaffModal<?= $staff['id'] ?>" tabindex="-1" aria-labelledby="editStaffModalLabel<?= $staff['id'] ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-warning">
          <h5 class="modal-title" id="editStaffModalLabel<?= $staff['id'] ?>">Edit Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" value="<?= $staff['id'] ?>">
          <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="edit_name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="edit_email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Position</label>
            <select name="edit_position" class="form-select" required>
              <?php
                $positions = ['Registrar', 'Enrollment Staff', 'Teacher'];
                foreach ($positions as $pos):
              ?>
                <option value="<?= $pos ?>" <?= $staff['position'] === $pos ? 'selected' : '' ?>><?= $pos ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select name="edit_status" class="form-select" required>
              <option value="Active" <?= $staff['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
              <option value="Inactive" <?= $staff['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_staff" class="btn btn-warning">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

  <script>
    function openEditModal(id, name, email, position, status) {
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_position').value = position;
      document.getElementById('edit_status').value = status;
    }
  </script>
</body>
</html>
