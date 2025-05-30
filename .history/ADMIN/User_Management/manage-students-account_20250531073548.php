<?php
$pdo = new PDO("mysql:host=localhost;dbname=enrollment_system", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle Add or Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['edit_id'] ?? null;
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $program = $_POST['program'];
    $year_level = $_POST['year_level'];
    $status = $_POST['status'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE students SET student_id=?, first_name=?, middle_name=?, last_name=?, email=?, program=?, year_level=?, status=? WHERE id=?");
        $stmt->execute([$student_id, $first_name, $middle_name, $last_name, $email, $program, $year_level, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, middle_name, last_name, email, program, year_level, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $first_name, $middle_name, $last_name, $email, $program, $year_level, $status]);
    }
    header("Location: manage-students-account.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: manage-students-account.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM students");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-success">Manage Students</h2>
    <button class="btn btn-success" onclick="openAddModal()"><i class="bi bi-plus-circle"></i> Add Student</button>
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
      <?php foreach ($students as $s): ?>
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
          <button class="btn btn-warning btn-sm"
            onclick='openEditModal(<?= json_encode($s) ?>)'>
            <i class="bi bi-pencil"></i>
          </button>
          <a href="?delete_id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this student?')">
            <i class="bi bi-trash"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form method="POST" id="studentForm">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="studentModalLabel">Add Student</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="mb-3">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" id="student_id" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" id="middle_name" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Program</label>
            <select name="program" id="program" class="form-select" required>
              <option disabled selected value="">Select Program</option>
              <option value="IT">IT</option>
              <option value="HRMT">HRMT</option>
              <option value="ECT">ECT</option>
              <option value="HST">HST</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Year Level</label>
            <select name="year_level" id="year_level" class="form-select" required>
              <option disabled selected value="">Select Year</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
              <option disabled selected value="">Select Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="submitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const studentModal = new bootstrap.Modal(document.getElementById('studentModal'));

function openAddModal() {
  document.getElementById('studentModalLabel').innerText = "Add Student";
  document.getElementById('submitBtn').innerText = "Add";
  document.getElementById('studentForm').reset();
  document.getElementById('edit_id').value = '';
  studentModal.show();
}

function openEditModal(data) {
  document.getElementById('studentModalLabel').innerText = "Edit Student";
  document.getElementById('submitBtn').innerText = "Update";
  document.getElementById('edit_id').value = data.id;
  document.getElementById('student_id').value = data.student_id;
  document.getElementById('first_name').value = data.first_name;
  document.getElementById('middle_name').value = data.middle_name;
  document.getElementById('last_name').value = data.last_name;
  document.getElementById('email').value = data.email;
  document.getElementById('program').value = data.program;
  document.getElementById('year_level').value = data.year_level;
  document.getElementById('status').value = data.status;
  studentModal.show();
}
</script>

</body>
</html>
