  <?php

  session_start();

  if (!isset($_SESSION['student_id'])) {
      header('Location: login.php');
      exit();
  }

  $student_id = $_SESSION['student_id'];

  // Define semester and school year early (before using them!)
  $semester = $_GET['semester'] ?? '1st Semester';
  $school_year = $_GET['school_year'] ?? '2024-2025';

  // Database connection info
  $host = "localhost";
  $dbname = "enrollment_system";
  $username = "root";
  $password = "";

  try {
      $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
      die("Database connection failed: " . $e->getMessage());
  }

  // Fetch student
  $stmtStudent = $pdo->prepare("SELECT * FROM students WHERE student_id = :student_id LIMIT 1");
  $stmtStudent->execute(['student_id' => $student_id]);
  $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

  if (!$student) {
      die("Student not found.");
  }

  // Set default values (prevents undefined variable warnings)
  $program = '';
  $year_level = '';
  $section = '';

  // Fetch enrollment based on selected semester and year
  $stmtEnrollment = $pdo->prepare("
      SELECT * FROM enrollments 
      WHERE student_id = :student_id 
      AND semester = :semester 
      AND school_year = :school_year 
      LIMIT 1
  ");
  $stmtEnrollment->execute([
      'student_id' => $student_id,
      'semester' => $semester,
      'school_year' => $school_year
  ]);
  $enrollment = $stmtEnrollment->fetch(PDO::FETCH_ASSOC);

  // Fetch subjects only if enrollment found
  $subjects = [];

  if ($enrollment) {
      $program = $enrollment['program'];
      $year_level = $enrollment['year_level'];
      $section = $enrollment['section'];

      $sqlSubjects = "SELECT s.subject_code, s.subject_title, s.instructor, s.day, s.time, s.room, s.units
                      FROM subjects s
                      JOIN student_subjects ss ON s.id = ss.subject_id
                      WHERE ss.student_id = :student_id
                      ORDER BY s.subject_code";
      $stmtSubjects = $pdo->prepare($sqlSubjects);
      $stmtSubjects->execute(['student_id' => $student_id]);
      $subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);
  }
  ?>

  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Assigned Subjects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
      body {
        background-color: #f0fdf4;
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
      h2 {
        color: #2e7d32;
        font-weight: bold;
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
        background-color: #43a047;
        color: white;
      }
      .btn-outline-success {
        border-radius: 50px;
      }
      @media print {
        .no-print {
          display: none;
        }
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
        <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
        <span class="text-white ms-2">Student Dashboard</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


    <div class="container mt-5">
      <h3 class="mb-3">Your Enrollment Information</h3>
      <div class="card mb-4">
          <div class="card-body">
              <p><strong>Program:</strong> <?= htmlspecialchars($program) ?></p>
              <p><strong>Year Level:</strong> <?= htmlspecialchars($year_level) ?></p>
              <p><strong>Section:</strong> <?= htmlspecialchars($section) ?></p>
              <p><strong>Semester:</strong> <?= htmlspecialchars($semester . ', SY ' . $school_year) ?></p>
          </div>
      </div>


      <form method="GET" class="row g-3 mb-4 no-print">
        <div class="col-md-4">
          <label for="semester" class="form-label">Semester</label>
          <select class="form-select" id="semester" name="semester">
            <option <?= $semester == '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
            <option <?= $semester == '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="school_year" class="form-label">School Year</label>
          <input
            type="text"
            class="form-control"
            id="school_year"
            name="school_year"
            value="<?= htmlspecialchars($school_year) ?>"
          />
        </div>
        <div class="col-md-4 align-self-end">
          <button type="submit" class="btn btn-success w-100">Load Subjects</button>
        </div>
      </form>

  <div class="table-responsive">
    <?php if (!empty($subjects)): ?>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Subject Code</th>
                <th>Title</th>
                <th>Instructor</th>
                <th>Schedule</th>
                <th>Room</th>
                <th>Units</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['subject_code']) ?></td>
                    <td><?= htmlspecialchars($row['subject_title']) ?></td>
                    <td><?= htmlspecialchars($row['instructor']) ?></td>
                    <td><?= htmlspecialchars($row['day']) ?> <?= htmlspecialchars($row['time']) ?></td>
                    <td><?= htmlspecialchars($row['room']) ?></td>
                    <td><?= htmlspecialchars($row['units']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-info">No assigned subjects found.</div>
    <?php endif; ?>
  </div>  

      <div class="d-flex justify-content-end gap-2 mt-3 no-print">
        <button class="btn btn-outline-success" onclick="window.print()">
          <i class="bi bi-printer"></i> Print Schedule
        </button>
      </div>
    </div>
  </body>
</html>