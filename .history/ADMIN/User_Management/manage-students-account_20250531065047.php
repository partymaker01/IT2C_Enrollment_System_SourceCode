<?php
$pdo = new PDO("mysql:host=localhost;dbname=enrollment_system", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['edit_id'] ?? null;
    $student_id = $_POST['student_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $program = $_POST['program'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($student_id && $first_name && $last_name && $email && $program && $year_level && $status) {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE students SET student_id=:student_id, first_name=:first_name, middle_name=:middle_name, last_name=:last_name, email=:email, program=:program, year_level=:year_level, status=:status WHERE id=:id");
            $stmt->bindParam(':id', $id);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, middle_name, last_name, email, program, year_level, status) VALUES (:student_id, :first_name, :middle_name, :last_name, :email, :program, :year_level, :status)");
        }

        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':middle_name', $middle_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':program', $program);
        $stmt->bindParam(':year_level', $year_level);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        header("Location: manage-students-account.php");
        exit();
    }
}

// Delete student
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
    $stmt->bindParam(':id', $_GET['delete_id']);
    $stmt->execute();
    header("Location: manage-students-account.php");
    exit();
}

// Get all students
$stmt = $pdo->prepare("SELECT * FROM students");
$stmt->execute();
$studentList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Student Accounts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
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
    .table-responsive {
      background: white;
      padding: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 0 12px rgba(0,0,0,0.05);
    }
    .modal-header.edit-mode {
      background-color:rgb(8, 129, 18);
      color: white;
    }
    .modal-header.confirm-delete {
      background-color: #dc3545;
      color: white;
    }
    .btn-delete-confirm {
      background-color: #dc3545;
      color: white;
    }
  </style>
</head>
<body class="bg-light">
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

  <div class="container my-5">
    <h2 class="text-center text-success mb-4">Manage Student Accounts</h2>

    <div class="d-flex justify-content-end mb-4">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-plus-circle me-1"></i> Add Student
      </button>
    </div>

    <table class="table table-bordered table-striped text-center align-middle">
      <thead class="table-success">
        <tr>
          <th>No</th>
          <th>Student ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Program</th>
          <th>Year</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($studentList as $s): ?>
          <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['student_id']) ?></td>
            <td><?= htmlspecialchars("{$s['first_name']} {$s['middle_name']} {$s['last_name']}") ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= htmlspecialchars($s['program']) ?></td>
            <td><?= htmlspecialchars($s['year_level']) ?></td>
            <td><span class="badge <?= strtolower($s['status']) === 'active' ? 'bg-success' : 'bg-secondary' ?>">
              <?= $s['status'] ?></span></td>
            <td>
              <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $s['id'] ?>)"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <form method="POST">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Add Student</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <?php $studentToEdit = []; include 'student-form-fields.php'; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">Save</button>
          </div>
        </form>
      </div>


<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" class="needs-validation" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Program</label>
            <select name="program" class="form-select" required>
              <option disabled selected>Select Program</option>
              <option value="IT">IT</option>
              <option value="HRMT">HRMT</option>
              <option value="ECT">ECT</option>
              <option value="HST">HST</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Year Level</label>
            <select name="year_level" class="form-select" required>
              <option disabled selected>Select Year</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
              <option disabled selected>Select Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header confirm-delete">
          <h5 class="modal-title" id="confirmDeleteLabel">Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this student?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a id="confirmDeleteBtn" href="#" class="btn btn-delete-confirm">Yes, Delete</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function confirmDelete(id) {
      const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
      document.getElementById('confirmDeleteBtn').href = "?delete_id=" + id;
      deleteModal.show();
    }

    (() => {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      });
    })();

      document.addEventListener('DOMContentLoaded', () => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('edit_id')) {
      const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
      modal.show();
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
  const urlParams = new URLSearchParams(window.location.search);
  const editId = urlParams.get('edit_id');

  if (editId) {
    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editModal.show();
  }
});
  </script>
</body>