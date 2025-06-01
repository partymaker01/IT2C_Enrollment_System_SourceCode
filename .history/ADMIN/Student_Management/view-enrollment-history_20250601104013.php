<?php
// Database connection - update with your credentials
$host = "localhost";
$user = "root";
$pass = "";
$db = "enrollment_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all enrollments with joined student info
$sql = "SELECT e.id as enrollment_id, s.first_name, s.middle_name, s.last_name, s.program, e.semester, e.school_year
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        ORDER BY e.school_year DESC, e.semester DESC, s.last_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Enrollment History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #f8fafc;
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
    .card-header.bg-success {
      background-color: #198754 !important;
    }
    table tbody tr:hover {
      background-color: #e9f5ee;
    }
    .no-subjects {
      font-style: italic;
      color: #6c757d;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo" />
        Admin Panel
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php">
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h2 class="mb-4 text-center text-success">Enrollment History</h2>

    <form class="row g-3 mb-4" role="search" aria-label="Search enrollment history">
      <div class="col-sm-12 col-md-6">
        <input type="search" class="form-control" id="searchInput" placeholder="Search by Student Name or Semester"
               aria-label="Search" onkeyup="filterCards()" />
      </div>
      <div class="col-sm-6 col-md-3 d-grid">
        <button type="button" class="btn btn-primary" onclick="filterCards()">Search</button>
      </div>
    </form>
    <div id="enrollmentCards">
      <?php
      if ($result && $result->num_rows > 0) {
          while ($enrollment = $result->fetch_assoc()) {
              $enrollment_id = $enrollment['enrollment_id'];

             $sqlSubjects = "SELECT subject_code, subject_description, units, instructor, grade, remarks 
                FROM enrollment_subjects WHERE enrollment_id = $enrollment_id";
              $subResult = $conn->query($sqlSubjects);

              $studentLower = strtolower($enrollment['last_name'] . ', ' . $enrollment['first_name'] . ' ' . $enrollment['middle_name']);
              $semesterLower = strtolower($enrollment['semester']);
              $programLower = strtolower($enrollment['program']);
      ?>
      <div class="card mb-4 enrollment-card" data-student="<?= $studentLower ?>" data-semester="<?= $semesterLower ?>">
        <div class="card-header bg-success text-white fw-bold">
          <?= htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name'] . ' ' . $enrollment['middle_name']) ?>
          - <?= htmlspecialchars(ucwords($enrollment['semester'] . ' ' . $enrollment['school_year'])) ?> - <?= htmlspecialchars($enrollment['program']) ?>
        </div>
        <div class="card-body p-0">
          <?php if ($subResult && $subResult->num_rows > 0) { ?>
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Subject Code</th>
                    <th>Description</th>
                    <th>Units</th>
                    <th>Instructor</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($subject = $subResult->fetch_assoc()) { ?>
                    <tr>
                      <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                      <td><?= htmlspecialchars($subject['subject_description']) ?></td>
                      <td><?= htmlspecialchars($subject['units']) ?></td>
                      <td><?= htmlspecialchars($subject['instructor']) ?></td>
                      <td><?= htmlspecialchars(number_format($subject['grade'], 2)) ?></td>
                      <td><?= htmlspecialchars($subject['remarks']) ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          <?php } else { ?>
            <p class="no-subjects m-3">No enrolled subjects this semester.</p>
          <?php } ?>
        </div>
      </div>
      <?php
          }
      } else {
          echo "<p>No enrollment history found.</p>";
      }
      $conn->close();
      ?>
    </div>
  </div>

  <script>
    function filterCards() {
      const searchValue = document.getElementById('searchInput').value.toLowerCase();
      const cards = document.querySelectorAll('.enrollment-card');

      cards.forEach(card => {
        const student = card.getAttribute('data-student');
        const semester = card.getAttribute('data-semester');

        const matchesSearch = student.includes(searchValue) || semester.includes(searchValue);

        if (matchesSearch) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
