<?php
$pdo = new PDO("mysql:host=localhost;dbname=enrollment_system", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle student add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $program = $_POST['program'] ?? '';
    $yearLevel = $_POST['yearLevel'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($name && $email && $program && $yearLevel && $status) {
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            // Update student
            $stmt = $pdo->prepare("UPDATE students SET name=:name, email=:email, program=:program, year_level=:year_level, status=:status WHERE id=:id");
            $stmt->bindParam(':id', $_POST['edit_id']);
        } else {
            // Insert new student
            $stmt = $pdo->prepare("INSERT INTO students (name, email, program, year_level, status) VALUES (:name, :email, :program, :year_level, :status)");
        }
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':program', $program);
        $stmt->bindParam(':year_level', $yearLevel);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        header("Location: manage-students-account.php");
        exit();
    }
}

// Fetch students
$stmt = $pdo->prepare("SELECT * FROM students");
$stmt->execute();
$studentList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit student (prefill form)
$studentToEdit = null;
if (isset($_GET['edit_id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $editStmt->bindParam(':id', $_GET['edit_id']);
    $editStmt->execute();
    $studentToEdit = $editStmt->fetch(PDO::FETCH_ASSOC);
}

// Delete student
if (isset($_GET['delete_id'])) {
    $deleteStmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
    $deleteStmt->bindParam(':id', $_GET['delete_id']);
    $deleteStmt->execute();
    header("Location: manage-students-account.php");
    exit();
}
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
      margin-bottom: 40px;
    }
    h2 {
      font-weight: 700;
      margin-bottom: 30px;
    }
    .btn-success:hover {
      filter: brightness(0.9);
      transition: filter 0.2s ease;
    }
    .table-responsive {
      background: white;
      padding: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 0 12px rgba(0,0,0,0.05);
    }
    .btn-group > button {
      min-width: 70px;
    }
    .modal-header {
      background-color: #43a047;
      color: white;
      border-top-left-radius: 1rem;
      border-top-right-radius: 1rem;
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
    </div>
  </nav> 

  <div class="container">
    <h2 class="text-success text-center">
      Manage Student Accounts
    </h2>

    <div class="d-flex justify-content-end mb-4">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-plus-circle me-1"></i>
        Add Student
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0">
        <thead class="table-success text-center">
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Program</th>
            <th>Year Level</th>
            <th>Status</th>
            <th style="min-width: 140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($studentList as $student): ?>
            <tr>
              <td><?= isset($student['name']) ? htmlspecialchars($student['name']) : '—' ?></td>
              <td><?= isset($student['email']) ? htmlspecialchars($student['email']) : '—' ?></td>
              <td><?= isset($student['program']) ? htmlspecialchars($student['program']) : '—' ?></td>
              <td><?= isset($student['year_level']) ? htmlspecialchars($student['year_level']) : '—' ?></td>
              <td class="text-center">
                <span class="badge <?= $student['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                  <?= htmlspecialchars($student['status']) ?>
                </span>
              </td>
              <td class="text-center">
                <div class="btn-group" role="group">
                  <a href="manage-students.php?edit_id=<?= $student['id'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                  <a href="manage-students.php?delete_id=<?= $student['id'] ?>" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addStudentModalLabel">
              <?= isset($studentToEdit) ? 'Edit Student' : 'Add Student' ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" class="needs-validation" novalidate>
            <?php if (isset($studentToEdit)): ?>
              <input type="hidden" name="edit_id" value="<?= $studentToEdit['id'] ?>">
            <?php endif; ?>
            <div class="modal-body">
              <div class="mb-3">
                <label for="fullName" class="form-label">Full Name</label>
                <input type="text" class="form-control" name="fullName" value="<?= isset($studentToEdit['name']) ? htmlspecialchars($studentToEdit['name']) : '' ?>" required />
                <div class="invalid-feedback">Please enter full name.</div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= isset($studentToEdit['email']) ? htmlspecialchars($studentToEdit['email']) : '' ?>" required />
                <div class="invalid-feedback">Please enter a valid email.</div>
              </div>
              <div class="mb-3">
                <label for="program" class="form-label">Program</label>
                <select class="form-select" name="program" required>
                  <option value="" disabled selected>Select Program</option>
                  <option value="IT" <?= isset($studentToEdit['program']) && $studentToEdit['program'] == 'IT' ? 'selected' : '' ?>>IT</option>
                  <option value="HRMT" <?= isset($studentToEdit['program']) && $studentToEdit['program'] == 'HRMT' ? 'selected' : '' ?>>HRMT</option>
                  <option value="ECT" <?= isset($studentToEdit['program']) && $studentToEdit['program'] == 'ECT' ? 'selected' : '' ?>>ECT</option>
                  <option value="HST" <?= isset($studentToEdit['program']) && $studentToEdit['program'] == 'HST' ? 'selected' : '' ?>>HST</option>
                </select>
                <div class="invalid-feedback">Please select a program.</div>
              </div>
              <div class="mb-3">
                <label for="yearLevel" class="form-label">Year Level</label>
                <select class="form-select" name="yearLevel" required>
                  <option value="" disabled selected>Select Year Level</option>
                  <option value="1st Year" <?= isset($studentToEdit['year_level']) && $studentToEdit['year_level'] == '1st Year' ? 'selected' : '' ?>>1st Year</option>
                  <option value="2nd Year" <?= isset($studentToEdit['year_level']) && $studentToEdit['year_level'] == '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                  <option value="3rd Year" <?= isset($studentToEdit['year_level']) && $studentToEdit['year_level'] == '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                </select>
                <div class="invalid-feedback">Please select year level.</div>
              </div>
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" required>
                  <option value="" disabled selected>Select Status</option>
                  <option value="Active" <?= isset($studentToEdit['status']) && $studentToEdit['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                  <option value="Inactive" <?= isset($studentToEdit['status']) && $studentToEdit['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <div class="invalid-feedback">Please select status.</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
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
  </script>
</body>
</html>