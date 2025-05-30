<?php
$pdo = new PDO("mysql:host=localhost;dbname=enrollment_system", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['edit_id'] ?? null;
    $student_number = $_POST['student_number'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $program = $_POST['program'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($student_number && $first_name && $last_name && $email && $program && $year_level && $status) {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE students SET student_number=:student_number, first_name=:first_name, middle_name=:middle_name, last_name=:last_name, email=:email, program=:program, year_level=:year_level, status=:status WHERE id=:id");
            $stmt->bindParam(':id', $id);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO students (student_number, first_name, middle_name, last_name, email, program, year_level, status) VALUES (:student_number, :first_name, :middle_name, :last_name, :email, :program, :year_level, :status)");
        }

        $stmt->bindParam(':student_number', $student_number);
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

// Fetch students
$stmt = $pdo->prepare("SELECT * FROM students");
$stmt->execute();
$studentList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get edit student
$studentToEdit = null;
if (isset($_GET['edit_id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $editStmt->bindParam(':id', $_GET['edit_id']);
    $editStmt->execute();
    $studentToEdit = $editStmt->fetch(PDO::FETCH_ASSOC);
}

// Delete
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
  <h2 class="text-success text-center">Manage Student Accounts</h2>

  <div class="d-flex justify-content-end mb-4">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
      <i class="bi bi-plus-circle me-1"></i> Add Student
    </button>
  </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0">
        <thead class="table-success text-center">
          <tr>
            <th>NO.</th>
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
            <td class="text-center"><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['student_id']) ?></td>
            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['middle_name'] . ' ' . $s['last_name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= htmlspecialchars($s['program']) ?></td>
            <td><?= htmlspecialchars($s['year_level']) ?></td>
            <td class="text-center">
              <span class="badge <?= $s['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                <?= htmlspecialchars($s['status']) ?>
              </span>
            </td>
            <td class="text-center">
              <a href="?edit_id=<?= $s['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
              <a href="?delete_id=<?= $s['id'] ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<!-- Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="addStudentModalLabel"><?= $studentToEdit ? 'Edit Student' : 'Add Student' ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" class="needs-validation" novalidate>
            <?php if ($studentToEdit): ?>
              <input type="hidden" name="edit_id" value="<?= $studentToEdit['id'] ?>">
            <?php endif; ?>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_ID" class="form-control" required value="<?= $studentToEdit['student_id'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" required value="<?= $studentToEdit['first_name'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" value="<?= $studentToEdit['middle_name'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" required value="<?= $studentToEdit['last_name'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= $studentToEdit['email'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Program</label>
                <select name="program" class="form-select" required>
                  <option disabled selected>Select Program</option>
                  <?php
                  $programs = ['IT', 'HRMT', 'ECT', 'HST'];
                  foreach ($programs as $p):
                  ?>
                    <option value="<?= $p ?>" <?= ($studentToEdit['program'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Year Level</label>
                <select name="year_level" class="form-select" required>
                  <option disabled selected>Select Year</option>
                  <?php
                  $years = ['1st Year', '2nd Year', '3rd Year'];
                  foreach ($years as $y):
                  ?>
                    <option value="<?= $y ?>" <?= ($studentToEdit['year_level'] ?? '') === $y ? 'selected' : '' ?>><?= $y ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option disabled selected>Select Status</option>
                  <option value="Active" <?= ($studentToEdit['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
                  <option value="Inactive" <?= ($studentToEdit['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
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