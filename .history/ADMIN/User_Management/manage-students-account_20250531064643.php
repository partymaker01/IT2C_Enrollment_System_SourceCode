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
  <title>Manage Student Accounts</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body class="bg-light">
  <div class="container my-5">
    <h2 class="text-center text-success mb-4">Manage Student Accounts</h2>

    <!-- ADD BUTTON -->
    <div class="text-end mb-3">
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> Add Student
      </button>
    </div>

    <!-- STUDENTS TABLE -->
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

          <!-- EDIT MODAL -->
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

  <!-- ADD MODAL -->
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
    </div>
  </div>

  <!-- DELETE CONFIRM MODAL -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete this student?
        </div>
        <div class="modal-footer">
          <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Yes, Delete</a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function confirmDelete(id) {
      document.getElementById('confirmDeleteBtn').href = '?delete_id=' + id;
      new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
    }
  </script>
</body>
</html>
