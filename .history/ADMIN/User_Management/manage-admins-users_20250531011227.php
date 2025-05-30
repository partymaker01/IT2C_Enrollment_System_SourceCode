<?php
include '../../db.php';

// Insert admin if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = $_POST['fullName'];
  $email = $_POST['email'];
  $username = $_POST['username'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

  $stmt = $conn->prepare("INSERT INTO admin_settings (name, email, username, password) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $fullName, $email, $username, $password);

  if ($stmt->execute()) {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  } else {
    echo "<script>alert('Error adding admin: " . $conn->error . "');</script>";
  }
}

// Fetch admins for display
$result = mysqli_query($conn, "SELECT * FROM admin_settings");
$admins = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Admin Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #f1f8e9;
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
      margin-top: 40px;
      max-width: 900px;
    }
    .modal-header, .btn-primary {
      background-color: #2e7d32;
      color: white;
    }
    .btn-primary:hover, .btn-warning:hover, .btn-danger:hover {
      filter: brightness(0.9);
      transition: filter 0.2s ease-in-out;
    }
    .table-responsive {
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      border-radius: 0.25rem;
      background: white;
      padding: 0.5rem;
    }
    @media (max-width: 576px) {
      .btn-sm {
        width: 100%;
        margin-bottom: 0.3rem;
      }
      .btn-group-sm > .btn {
        margin-bottom: 0.3rem;
      }
    }
  </style>
</head>
<body>
  <!-- 🟢 Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-white" id="editAdminModalLabel">Edit Admin</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editForm" method="POST" action="edit.php">
          <input type="hidden" id="editId" name="id">
          <div class="mb-3">
            <label for="editFullName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="editFullName" name="fullName" required>
          </div>
          <div class="mb-3">
            <label for="editEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="editEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label for="editUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="editUsername" name="username" required>
          </div>
          <button type="submit" class="btn btn-warning w-100">Update Admin</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- 🔴 Delete Confirmation Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteAdminModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this admin?
      </div>
      <div class="modal-footer">
        <form id="deleteForm" method="POST" action="delete.php">
          <input type="hidden" id="deleteId" name="id">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
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
      Manage Admin Users
    </h2>
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
        <i class="bi bi-plus-circle me-1"></i>
        Add Admin
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
              Username
            </th>
            <th style="min-width:140px;">
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $admin): ?>
          <tr>
            <td class="text-center"><?= htmlspecialchars($admin['id']) ?></td>
            <td><?= htmlspecialchars($admin['name']) ?></td>
            <td><?= htmlspecialchars($admin['email']) ?></td>
            <td><?= htmlspecialchars($admin['username']) ?></td>
            <td class="text-center">
            <button class="btn btn-warning" title="Edit"
              onclick="showEditModal(<?= $admin['id'] ?>, '<?= addslashes($admin['name']) ?>', '<?= $admin['email'] ?>', '<?= $admin['username'] ?>')">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-danger" title="Delete"
              onclick="showDeleteModal(<?= $admin['id'] ?>)">
              <i class="bi bi-trash"></i> Delete
            </button></div>
          </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>


  <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAdminModalLabel">
            Add New Admin
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" novalidate>
            <div class="mb-3">
              <label for="fullName" class="form-label">
                Full Name
              </label>
              <input
                type="text"
                class="form-control"
                id="fullName"
                name="fullName"
                placeholder="Enter full name"
                required
              />
              <div class="invalid-feedback">
                Please enter the full name.
              </div>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">
                Email
              </label>
              <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="Enter email address"
                required
              />
              <div class="invalid-feedback">
                Please enter a valid email address.
              </div>
            </div>
            <div class="mb-3">
              <label for="username" class="form-label">
                Username
              </label>
              <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                placeholder="Enter username"
                required
              />
              <div class="invalid-feedback">
                Please enter a username.
              </div>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">
                Password
              </label>
              <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="Enter password"
                required
              />
              <div class="invalid-feedback">
                Please enter a password.
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              Save Admin
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
     function showEditModal(id, name, email, username) {
    document.getElementById('editId').value = id;
    document.getElementById('editFullName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editUsername').value = username;
    var editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
    editModal.show();
  }

  function showDeleteModal(id) {
    document.getElementById('deleteId').value = id;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
    deleteModal.show();
  }

  // Validation
  (() => {
    'use strict';
    const forms = document.querySelectorAll('form');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
    function editData(id) {
    window.location.href = 'edit.php?id=' + id;
  }

  function deleteData(id) {
    if (confirm('Are you sure you want to delete this record?')) {
      window.location.href = 'delete.php?id=' + id;
    }
  }
    (() => {
      'use strict';
      const forms = document.querySelectorAll('form');

      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>

  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
  />
</body>
</html>
