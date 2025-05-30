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
        // Update
        $stmt = $pdo->prepare("UPDATE students SET student_id=?, first_name=?, middle_name=?, last_name=?, email=?, program=?, year_level=?, status=? WHERE id=?");
        $stmt->execute([$student_id, $first_name, $middle_name, $last_name, $email, $program, $year_level, $status, $id]);
    } else {
        // Insert
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

// Fetch all students
$stmt = $pdo->query("SELECT * FROM students");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Student Accounts</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background-color: #f1f8e9; }
    .table-responsive { background: white; padding: 1rem; border-radius: .5rem; box-shadow: 0 0 12px rgba(0,0,0,0.05); }
  </style>
</head>
<body>

<div class="container my-5">
  <h2 class="text-success text-center">Manage Student Accounts</h2>

  <div class="text-end mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-circle me-1"></i> Add Student
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-striped text-center">
      <thead class="table-success">
        <tr>
          <th>#</th><th>Student ID</th><th>Full Name</th><th>Email</th><th>Program</th><th>Year</th><th>Status</th><th>Actions</th>
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
          <td><span class="badge <?= strtolower($s['status']) === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $s['status'] ?></span></td>
          <td>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $s['id'] ?>)"><i class="bi bi-trash"></i></button>
          </td>
        </tr>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
              <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $s['id'] ?>">
                <div class="modal-header bg-success text-white">
                  <h5 class="modal-title">Edit Student</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <?php include 'student-form-fields.php'; ?>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-success">Update</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php $studentToEdit = null; include 'student-form-fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete this student?</div>
      <div class="modal-footer">
        <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Yes, Delete</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDelete(id) {
  const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
  document.getElementById('confirmDeleteBtn').href = '?delete_id=' + id;
  modal.show();
}
</script>
</body>
</html>