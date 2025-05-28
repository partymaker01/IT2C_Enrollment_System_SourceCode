<?php
session_start();

// Example: Assuming logged-in student ID stored in session
$student_id = $_SESSION['student_id'] ?? 1; // Replace 1 with session value in real use

// DB connection (edit your credentials here)
$host = "localhost";
$dbname = "enrollment_system";
$user = "root";
$pass = ""; // your db password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all enrollments of the student with subjects
    $stmt = $pdo->prepare("
        SELECT e.enrollment_id, e.semester, e.program, e.year_level, e.section, e.enrollment_date, e.status,
               s.subject_code, s.title, s.schedule, s.units
        FROM enrollments e
        LEFT JOIN enrollment_subjects es ON e.enrollment_id = es.enrollment_id
        LEFT JOIN subjects s ON es.subject_id = s.subject_id
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC, s.title ASC
    ");

    $stmt->execute([$student_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data to group subjects under each enrollment
    $enrollments = [];
    foreach ($rows as $row) {
        $eid = $row['enrollment_id'];
        if (!isset($enrollments[$eid])) {
            $enrollments[$eid] = [
                'semester' => $row['semester'],
                'program' => $row['program'],
                'year' => $row['year_level'],
                'section' => $row['section'],
                'date' => date("F j, Y", strtotime($row['enrollment_date'])),
                'status' => $row['status'],
                'subjects' => []
            ];
        }
        if ($row['subject_code']) {
            $enrollments[$eid]['subjects'][] = [
                'code' => $row['subject_code'],
                'title' => $row['title'],
                'schedule' => $row['schedule'],
                'units' => $row['units']
            ];
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Enrollment History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body { background-color: #e8f5e9; }
    .accordion-button:focus { box-shadow: none; }
    .badge-approved { background-color: #43a047; }
    .badge-pending { background-color: #fbc02d; color: black; }
    .badge-rejected { background-color: #e53935; }
    .table th, .table td { vertical-align: middle; }
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
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
        Student Dashboard
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php"><i class="bi bi-arrow-left"></i> Back to Dashboard</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container my-5">
    <h2 class="text-success mb-4"><i class="bi bi-clock-history me-2"></i> Enrollment History </h2>

    <div class="accordion" id="historyAccordion">
      <?php if(empty($enrollments)): ?>
        <p>No enrollment records found.</p>
      <?php else: ?>
        <?php foreach ($enrollments as $index => $entry): ?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="heading<?= $index ?>">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                Semester: <?= htmlspecialchars($entry['semester']) ?>
              </button>
            </h2>
            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#historyAccordion">
              <div class="accordion-body">
                <p><strong>Program:</strong> <?= htmlspecialchars($entry['program']) ?></p>
                <p><strong>Year Level:</strong> <?= htmlspecialchars($entry['year']) ?></p>
                <p><strong>Section:</strong> <?= htmlspecialchars($entry['section']) ?></p>
                <p><strong>Enrollment Date:</strong> <?= htmlspecialchars($entry['date']) ?></p>
                <p><strong>Status:</strong> 
                  <span class="badge <?= $entry['status'] === 'Approved' ? 'badge-approved' : ($entry['status'] === 'Pending' ? 'badge-pending' : 'badge-rejected') ?>">
                    <?= htmlspecialchars($entry['status']) ?>
                  </span>
                </p>

                <h5 class="mt-4">Subjects Enrolled</h5>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead class="table-success">
                      <tr>
                        <th>Subject Code</th>
                        <th>Subject Title</th>
                        <th>Schedule</th>
                        <th>Units</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($entry['subjects'] as $subject): ?>
                        <tr>
                          <td><?= htmlspecialchars($subject['code']) ?></td>
                          <td><?= htmlspecialchars($subject['title']) ?></td>
                          <td><?= htmlspecialchars($subject['schedule']) ?></td>
                          <td><?= htmlspecialchars($subject['units']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <a href="#" class="btn btn-success mt-3"><i class="bi bi-download me-1"></i> Download Enrollment Slip</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
