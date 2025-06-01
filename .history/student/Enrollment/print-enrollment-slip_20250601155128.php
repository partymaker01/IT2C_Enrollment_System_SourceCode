<?php
// DB config
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enrollment_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get enrollment id from URL
$enrollment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($enrollment_id <= 0) {
    die("❌ Invalid enrollment ID.");
}

// Fetch enrollment info (only if status is Approved)
$sqlEnrollment = "SELECT * FROM enrollments WHERE id = ? AND status = 'approved'";
$stmt = $conn->prepare($sqlEnrollment);
$stmt->bind_param("i", $enrollment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Enrollment not found or not yet approved. Please wait for admin confirmation.");
}

$enrollment = $result->fetch_assoc();

// Check if studentID is empty, then generate and update
if (empty($enrollment['studentID'])) {
    $currentYear = date("Y");
    $studentID = $currentYear . '-' . str_pad($enrollment_id, 4, '0', STR_PAD_LEFT);

    $updateStmt = $conn->prepare("UPDATE enrollments SET studentID = ? WHERE id = ?");
    $updateStmt->bind_param("si", $student_id, $enrollment_id);
    $updateStmt->execute();
    $updateStmt->close();

    // Refresh local value
    $enrollment['studentID'] = $studentID;
}

// Fetch subjects linked to enrollment (JOIN to get subject details)
$sqlSubjects = "SELECT s.subject_code, s.subject_title, s.units, s.day, s.time, s.room, es.instructor
    FROM enrollment_subjects es
    JOIN subjects s ON es.subject_code = s.subject_code
    WHERE es.enrollment_id = ?";
$stmt2 = $conn->prepare($sqlSubjects);
$stmt2->bind_param("i", $enrollment_id);
$stmt2->execute();
$subjectsResult = $stmt2->get_result();

$subjects = [];
while ($row = $subjectsResult->fetch_assoc()) {
    $subjects[] = $row;
}

$stmt->close();
$stmt2->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Enrollment Slip</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e8f5e9;
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
    .school-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .school-header img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #4caf50;
    }
    .enrollment-slip {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .table thead th {
      background-color: #43a047;
      color: white;
    }
    .badge-status {
      font-size: 1rem;
      padding: 6px 12px;
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
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
        Student Dashboard
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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

  <div class="container my-5">
    <div class="school-header">
      <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" />
      <h4 class="mt-2 mb-0">Top Link Global College Inc.</h4>
      <p class="mb-0">MRF Compound, Purok Lambingan, Brgy. Daan Sarile, Cabanatuan City</p>
      <p class="mb-2">Nueva Ecija, Philippines</p>
      <h5 class="fw-bold text-success">Enrollment Slip / Certificate</h5>
    </div>

    <div class="enrollment-slip" id="enrollmentSlip">
      <div class="row mb-4">
        <div class="col-md-6">
          <p><strong>Student Name:</strong> <?= htmlspecialchars($enrollment['student_name'] ?? '') ?></p>
          <p><strong>Student ID:</strong> <?= htmlspecialchars($enrollment['studentID'] ?? '') ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Program:</strong> <?= htmlspecialchars($enrollment['program'] ?? '') ?></p>
          <p><strong>Year Level:</strong> <?= htmlspecialchars($enrollment['year_level'] ?? '') ?></p>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6">
          <p><strong>Semester:</strong> <?= htmlspecialchars($enrollment['semester'] ?? '') ?></p>
          <p><strong>Section:</strong> <?= htmlspecialchars($enrollment['section'] ?? '') ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Date Submitted:</strong> <?= isset($enrollment['date_submitted']) ? date("F d, Y", strtotime($enrollment['date_submitted'])) : '' ?></p>
          <p><strong>Status:</strong>
            <span class="badge bg-success badge-status">
              <?= htmlspecialchars($enrollment['status'] ?? '') ?>
            </span>
          </p>
        </div>
      </div>

      <h5 class="mb-3">Assigned Subjects / Class Schedule</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Subject Code</th>
              <th>Subject Title</th>
              <th>Units</th>
              <th>Schedule</th>
              <th>Instructor</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($subjects) > 0): ?>
              <?php foreach ($subjects as $subject): ?>
                <tr>
                  <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                  <td><?= htmlspecialchars($subject['subject_title']) ?></td>
                  <td><?= htmlspecialchars($subject['units']) ?></td>
                  <td>
                    <?= htmlspecialchars($subject['day']) ?> <?= htmlspecialchars($subject['time']) ?>
                    <?php if (!empty($subject['room'])): ?>
                      <br><small>Room: <?= htmlspecialchars($subject['room']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($subject['instructor']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center">No subjects assigned.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-4 text-center">
        <p><strong>Enrollment No:</strong> ENR-<?= $enrollment_id ?></p>
        <img src="images/qr/qr_enrollment_<?= $enrollment_id ?>.png" width="120" alt="QR Code" />
      </div>

      <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-success me-2">Print Slip</button>
        <button onclick="downloadPDF()" class="btn btn-primary">Download PDF</button>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    function downloadPDF() {
      const element = document.getElementById('enrollmentSlip');
      html2pdf().set({
        margin: 1,
        filename: 'enrollment_slip_ENR-<?= $enrollment_id ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
      }).from(element).save();
    }
  </script>
</body>
</html>