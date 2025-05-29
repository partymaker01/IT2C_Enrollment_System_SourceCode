<?php
session_start();

// Database connection info
$host = "localhost";
$dbname = "enrollment_system";
$username = "root";
$password = "";

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Ensure student is logged in
if (!isset($_SESSION['student_number'])) {
    die("Unauthorized access.");
}

$student_number = $_SESSION['student_number'];

// Fetch student by student_number
$stmtStudent = $pdo->prepare("SELECT * FROM students WHERE student_number = :student_number LIMIT 1");
$stmtStudent->execute(['student_number' => $student_number]);
$student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

// Get semester and school year from GET or use defaults
$semester = $_GET['semester'] ?? '1st Semester';
$school_year = $_GET['school_year'] ?? '2024-2025';

// Fetch assigned subjects
$sqlSubjects = "SELECT s.subject_code, s.subject_title, s.instructor, s.day, s.time, s.room, s.units
                FROM subjects s
                JOIN student_subjects ss ON s.id = ss.subject_id
                WHERE ss.student_id = :student_id
                AND ss.semester = :semester
                AND ss.school_year = :school_year
                ORDER BY s.subject_code";

$stmtSubjects = $pdo->prepare($sqlSubjects);
$stmtSubjects->execute([
    'student_id' => $student['id'],
    'semester' => $semester,
    'school_year' => $school_year
]);
$subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);
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
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php">
        Student Dashboard
      </a>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a
              class="nav-link"
              href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php"
              class="btn btn-outline-secondary mb-3"
            >
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4">Assigned Subjects<?= htmlspecialchars($student['student_name']) ?></h2>
    <p><strong>Program:</strong> <?= htmlspecialchars($student['program'] ?? 'N/A') ?></p>
    <p><strong>Year Level:</strong> <?= htmlspecialchars($student['year_level'] ?? 'N/A') ?></p>
    <p><strong>Section:</strong> <?= htmlspecialchars($student['section'] ?? 'N/A') ?></p>

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
      <table class="table table-bordered table-hover align-middle">
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Subject Title</th>
            <th>Instructor</th>
            <th>Day</th>
            <th>Time</th>
            <th>Room</th>
            <th>Units</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($subjects) > 0): ?>
            <?php foreach ($subjects as $subject): ?>
              <tr>
                <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                <td><?= htmlspecialchars($subject['subject_title']) ?></td>
                <td><?= htmlspecialchars($subject['instructor']) ?></td>
                <td><?= htmlspecialchars($subject['day']) ?></td>
                <td><?= htmlspecialchars($subject['time']) ?></td>
                <td><?= htmlspecialchars($subject['room']) ?></td>
                <td><?= htmlspecialchars($subject['units']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="text-center">No subjects found for this semester and school year.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3 no-print">
      <button class="btn btn-outline-success" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Schedule
      </button>
    </div>
  </div>
</body>
</html>
