<?php
include '../../db.php'; // Make sure db.php has your DB connection

// Insert new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
          </a>
      </ul>
      </div>
    </div>
  </nav> 
  <div class="container">
    <h2 class="text-center mb-4 text-success fw-bold">
      Manage Registrar/Staff Accounts
    </h2>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="bi bi-plus-circle me-1"></i>
        Add Staff
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0">
        <thead class="table-success text-center">
          <tr>
            <th>
              ID
            </th>
            <th>
              Full Name
            </th>
            <th>
              Email
            </th>
            <th>
              Position
            </th>
            <th>
              Status
            </th>
            <th style="min-width:130px;">
              Actions
            </th>
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
              <span class="badge <?= $staff['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                <?= htmlspecialchars($staff['status']) ?>
              </span>
            </td>
            <td class="text-center">
          <div class="btn-group" role="group" aria-label="Staff Actions">
            <a href="edit-staff.php?id=<?= $staff['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
              <i class="bi bi-pencil"></i> Edit
            </a>
            <button class="btn btn-danger" title="Delete" onclick="showDeleteModal(<?= $admin['id'] ?>)">
  <i class="bi bi-trash"></i> Delete
</button>

          </div>
        </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStaffModalLabel">
            Add New Staff
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" novalidate>
          <div class="modal-body">
            <div class="mb-3">
              <label for="staffName" class="form-label">
                Full Name
              </label>
              <input
                type="text"
                class="form-control"
                id="staffName"
                name="staffName"
                placeholder="Enter full name"
                required
              />
              <div class="invalid-feedback">
                Please enter full name.
              </div>
            </div>
            <div class="mb-3">
              <label for="staffEmail" class="form-label">
                Email
              </label>
              <input
                type="email"
                class="form-control"
                id="staffEmail"
                name="staffEmail"
                placeholder="Enter email"
                required
              />
              <div class="invalid-feedback">
                Please enter a valid email.
              </div>
            </div>
            <div class="mb-3">
              <label for="staffPosition" class="form-label">
                Position
              </label>
              <select class="form-select" id="staffPosition" name="staffPosition" required>
                <option value="" disabled selected>
                  Select Position
                </option>
                <option value="Registrar">
                  Registrar
                </option>
                <option value="Enrollment Staff">
                  Enrollment Staff
                </option>
                <option value="Teacher">
                  Teacher
                </option>
              </select>
              </select>
              <div class="invalid-feedback">
                Please select a position.
              </div>
            </div>
            <div class="mb-3">
              <label for="staffStatus" class="form-label">
                Status
              </label>
              <select class="form-select" id="staffStatus" name="staffStatus" required>
                <option value="" disabled selected>
                  Select Status
                </option>
                <option value="Active">
                  Active
                </option>
                <option value="Inactive">
                  Inactive
                </option>
              </select>
              <div class="invalid-feedback">
                Please select status.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button type="submit" class="btn btn-success">
              Add Staff
            </button>
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
  </script>
</body>
</html>