<?php
session_start();

if (!isset($_SESSION['student_logged_in']) || !isset($_SESSION['student_number'])) {
    header("Location: ../logregfor/login.php");
    exit;
}

$host = 'localhost';
$dbname = 'enrollment_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$student_number = $_SESSION['student_number'];

// Fetch enrollment status for logged-in student
$stmt = $pdo->prepare("SELECT * FROM enrollment_status WHERE student_number = ? ORDER BY date_submitted DESC LIMIT 1");
$stmt->execute([$student_number]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enrollment) {
    // No enrollment found, set defaults or redirect
    $enrollment = [
        'program' => 'N/A',
        'year_level' => 'N/A',
        'semester' => 'N/A',
        'section' => 'N/A',
        'date_submitted' => null,
        'status' => 'pending',
        'remarks' => '',
        'notification' => ''
    ];
}

// Format date for display
$dateSubmitted = $enrollment['date_submitted'] ? date("F j, Y", strtotime($enrollment['date_submitted'])) : 'N/A';

$status = $enrollment['status'];

$badgeClass = [
  'pending' => 'badge-pending',
  'approved' => 'badge-approved',
  'rejected' => 'badge-rejected'
][$status] ?? 'badge-pending';

$badgeLabel = ucfirst($status);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Current Enrollment Status</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e8f5e9;
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
    .badge-pending {
      background-color: #fbc02d;
      color: #212529;
    }
    .badge-approved {
      background-color: #43a047;
    }
    .badge-rejected {
      background-color: #e53935;
    }
    .card {
      border-radius: 12px;
      border: none;
    }
    .btn-success {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-success:hover {
      background-color: #27642b;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="/student/student-dashboard.php">
        Student Dashboard
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/student/student-dashboard.php" class="btn btn-outline-secondary mb-3">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container my-5">
    <h2 class="text-success mb-4 fw-bold">
      Current Enrollment Status
    </h2>

    <div class="card shadow-sm p-4 mb-4">
      <div class="row mb-3">
        <div class="col-md-6"><strong>Program:</strong> <?= htmlspecialchars($enrollment['program']) ?></div>
        <div class="col-md-3"><strong>Year Level:</strong> <?= htmlspecialchars($enrollment['year_level']) ?></div>
        <div class="col-md-3"><strong>Semester:</strong> <?= htmlspecialchars($enrollment['semester']) ?></div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6"><strong>Section:</strong> <?= htmlspecialchars($enrollment['section']) ?></div>
        <div class="col-md-6"><strong>Date Submitted:</strong> <?= htmlspecialchars($dateSubmitted) ?></div>
      </div>

      <div class="mb-3">
        <strong>Status:</strong>
        <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2"><?= $badgeLabel ?></span>
      </div>

      <?php if ($status === 'rejected'): ?>
        <div class="alert alert-danger mt-3">
          <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($enrollment['remarks'])) ?>
        </div>
      <?php endif; ?>

      <a href="#" class="btn btn-success mt-2">
        View / Print Enrollment Slip
      </a>
    </div>

    <?php if (!empty($enrollment['notification'])): ?>
      <div class="alert alert-info">
        <strong>Notification:</strong> <?= nl2br(htmlspecialchars($enrollment['notification'])) ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
