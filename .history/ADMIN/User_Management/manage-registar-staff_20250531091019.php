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
<body class="p-4">
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
        <h2 class="mb-4">Staff Management</h2>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="fas fa-user-plus"></i> Add Staff
        </button>
    </div>

<!-- Staff Table -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th width="160">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['staff_name'] ?></td>
                        <td><?= $row['staff_email'] ?></td>
                        <td><?= $row['staff_position'] ?></td>
                        <td><?= $row['staff_status'] ?></td>
                        <td>
                            <button 
                                class="btn btn-sm btn-warning editStaffBtn"
                                data-id="<?= $row['staff_id'] ?>"
                                data-name="<?= htmlspecialchars($row['staff_name']) ?>"
                                data-email="<?= htmlspecialchars($row['staff_email']) ?>"
                                data-position="<?= $row['staff_position'] ?>"
                                data-status="<?= $row['staff_status'] ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#editStaffModal"
                            >
                                <i class="fas fa-pen"></i>
                            </button>
                            <button 
                                class="btn btn-sm btn-danger deleteStaffBtn"
                                data-id="<?= $row['staff_id'] ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteStaffModal"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
  </div>
  
    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input name="staff_name" class="form-control mb-2" placeholder="Name" required>
                    <input name="staff_email" type="email" class="form-control mb-2" placeholder="Email" required>
                    <input name="staff_position" class="form-control mb-2" placeholder="Position" required>
                    <select name="staff_status" class="form-control" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button name="addStaff" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
  <script>
    (() => {
      'use strict';
      const forms = document.querySelectorAll('form');

      Array.from(forms).forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      });
    })();

      function confirmDelete(id) {
    Swal.fire({
      title: 'Are you sure?',
      text: 'This staff account will be permanently deleted.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'delete-staff.php?id=' + id;
      }
    });
  }

  function openEditModal(id, name, email, position, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_position').value = position;
    document.getElementById('edit_status').value = status;
  }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>