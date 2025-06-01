<?php
// Database connection config
$host = 'localhost';     // or your server IP/domain
$db   = 'enrollment_system';
$user = 'root';          // your DB username
$pass = '';              // your DB password
$charset = 'utf8mb4';

// PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

// Fetch students
try {
    $stmt = $pdo->query("SELECT student_id, CONCAT(last_name, ', ', first_name, ' ', middle_name) AS full_name, email, program, year_level, date_registered FROM students WHERE status = 'pending'");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    exit("Error fetching students: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Approve Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #f1f8e9;
      font-family: 'Segoe UI', sans-serif;
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
      max-width: 1000px;
    }
    .badge-year {
      font-size: 0.85rem;
    }
    .table-wrapper {
      overflow-x: auto;
    }
    .form-control::placeholder {
      font-style: italic;
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
<div class="container my-5" role="main" aria-label="Approve Student Registration">
  <div class="text-center">
    <h2>Approve Student Registration</h2>
    <p class="lead">Review and manage newly registered students.</p>
  </div>

  <div class="input-group mb-4">
    <span class="input-group-text bg-success text-white"><i class="bi bi-search"></i></span>
    <input type="text" id="searchInput" class="form-control" placeholder="Search student by name..." onkeyup="searchStudent()" />
  </div>

  <div class="table-wrapper">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-success text-center">
        <tr>
          <th>Student ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Program</th>
          <th>Year Level</th>
          <th>Date Registered</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="studentTableBody">
        <?php if(count($students) > 0): ?>
          <?php foreach ($students as $student): ?>
            <tr class="text-center">
              <td><?= htmlspecialchars($student['student_id']) ?></td>
              <td><?= htmlspecialchars($student['full_name']) ?></td>
              <td><?= htmlspecialchars($student['email']) ?></td>
              <td><?= htmlspecialchars($student['program']) ?></td>
              <td><span class="badge bg-success badge-year"><?= htmlspecialchars($student['year_level']) ?></span></td>
              <td><?= htmlspecialchars($student['created_at']) ?></td>
              <td>
                <button class="btn btn-success btn-sm me-1" onclick="confirmAction('approve', this)" data-bs-toggle="tooltip" title="Approve">
                  <i class="bi bi-check-circle-fill"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="confirmAction('reject', this)" data-bs-toggle="tooltip" title="Reject">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center text-muted">No student registrations found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div id="message" class="mt-4"></div>
</div>

<script>
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach(t => new bootstrap.Tooltip(t));

  function confirmAction(action, btn) {
    const row = btn.closest("tr");
    const fullName = row.cells[1].innerText;
    const confirmed = confirm(`Are you sure you want to ${action} ${fullName}'s registration?`);

    if (confirmed) {
      const msgDiv = document.getElementById("message");
      msgDiv.innerHTML = `<div class="alert alert-${action === 'approve' ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
          <strong>${fullName}</strong>'s registration has been ${action}d.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
      row.remove();
    }
  }

  function searchStudent() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#studentTableBody tr");

    rows.forEach(row => {
      const name = row.cells[1].innerText.toLowerCase();
      row.style.display = name.includes(input) ? "" : "none";
    });
  }
</script>
</body>
</html>
