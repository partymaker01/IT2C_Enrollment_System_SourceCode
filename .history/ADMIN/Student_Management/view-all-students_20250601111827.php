<?php
// database config - change these to your own MySQL credentials
$host = 'localhost';
$db   = 'enrollment_system';
$user = 'root';
$pass = '';

// create mysqli connection
$conn = new mysqli($host, $user, $pass, $db);

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// fetch all students from database
$sql = "SELECT * FROM students ORDER BY id ASC";
$result = $conn->query($sql);

$students = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View All Students - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e6f2e6;
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
    .table thead {
      background-color: #2e7d32;
      color: white;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .search-bar {
      max-width: 350px;
    }
    .btn-sm {
      min-width: 60px;
    }
    @media (max-width: 576px) {
      .d-flex.justify-content-between.mb-3 {
        flex-direction: column;
        gap: 10px;
      }
      .search-bar {
        max-width: 100%;
      }
      .btn-success {
        width: 100%;
      }
      table.table thead tr th, 
      table.table tbody tr td {
        font-size: 14px;
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a></li>
      </ul>
    </div>
  </div>
</nav>

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
    <h2 class="text-center mb-4 text-success">
      View All Students
    </h2>

    <div class="d-flex justify-content-between mb-3 align-items-center flex-wrap gap-2">
      <div class="input-group search-bar">
          <span class="input-group-text" id="search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
              <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.442 1.06a5 5 0 1 1 0-7.071 5 5 0 0 1 0 7.07z"/>
            </svg>
          </span>
          <input type="search" id="searchInput" class="form-control" placeholder="Search students..." aria-label="Search students" aria-describedby="search-icon" onkeyup="searchTable()" />
        </div>
        <div class="d-flex gap-2">
          <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Student_Management/add-new-student.php" class="btn btn-success">
            Add New Student
          </a>
        </div>
      </div>
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle mb-0" id="studentsTable" aria-describedby="studentTableDesc">
        <caption id="studentTableDesc" class="visually-hidden">
          List of all students in the enrollment system
        </caption>
        <thead>
          <tr>
            <th scope="col">
              Student ID
            </th>
            <th scope="col">
              Full Name
            </th>
            <th scope="col">
              Program
            </th>
            <th scope="col">
              Year Level
            </th>
            <th scope="col">
              Contact Number
            </th>
            <th scope="col" class="text-center">
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($students)): ?>
            <?php foreach ($students as $students): ?>
            <tr>
              <td><?= htmlspecialchars($students['id']) ?></td>
              <td><?= htmlspecialchars($students['first_name'] . ' ' . $students['middle_name'] . ' ' . $students['last_name']) ?></td>
              <td><?= htmlspecialchars($students['program']) ?></td>
              <td><?= htmlspecialchars($students['year_level']) ?></td>
              <td><?= htmlspecialchars($students['contact_number']) ?></td>
              <td class="text-center">
                <a href="view-student-details.php?id=<?= urlencode($students['id']) ?>" class="btn btn-primary btn-sm me-1" title="View details">
                  <i class="bi bi-eye"></i> View
                </a>
                <a href="edit-student.php?id=<?= urlencode($students['id']) ?>" class="btn btn-warning btn-sm me-1" title="Edit student">
                  <i class="bi bi-pencil-square"></i>
                  Edit
                </a>
                <button class="btn btn-danger btn-sm" title="Delete student" data-bs-toggle="modal" data-bs-target="#deleteModal" data-student-id="<?= htmlspecialchars($student['id']) ?>">
                  <i class="bi bi-trash"></i>
                  Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">No students found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete student
          <strong id="studentToDelete"></strong>
          ?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <script>
    function searchTable() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const table = document.getElementById('studentsTable');
      const trs = table.tBodies[0].getElementsByTagName('tr');

      for (let row of trs) {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
      }
    }

    // Delete modal handling
    var deleteModal = document.getElementById('deleteModal');
    var studentToDeleteSpan = document.getElementById('studentToDelete');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var studentIdToDelete = null;

    deleteModal.addEventListener('show.bs.modal', function(event) {
      var button = event.relatedTarget;
      studentIdToDelete = button.getAttribute('data-student-id');
      var row = button.closest('tr');
      var studentName = row.querySelector('td:nth-child(2)').textContent;
      studentToDeleteSpan.textContent = studentName;
    });

    confirmDeleteBtn.addEventListener('click', function() {
      if(studentIdToDelete){
        // Redirect to a delete handler PHP script with student ID as a GET parameter
        window.location.href = 'delete-student.php?id=' + encodeURIComponent(studentIdToDelete);
      }
    });
  </script>
</body>
</html>
