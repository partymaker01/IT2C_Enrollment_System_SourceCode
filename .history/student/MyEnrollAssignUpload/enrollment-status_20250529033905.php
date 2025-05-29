<?php
session_start();
include '../../db.php';  // FIXED: correct path

$student_id = $_SESSION['student_id'] ?? 1; 

$sql = "SELECT * FROM enrollments WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$enrollment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Enrollment Status</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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
      color: #000;
    }
    .badge-approved {
      background-color: #43a047;
      color: #fff;
    }
    .badge-rejected {
      background-color: #e53935;
      color: #fff;
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
        <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="text-success mb-4">Enrollment Status</h2>

  <?php if ($enrollment): ?>
  <div class="card shadow-sm p-4">
    <div class="row mb-3">
      <div class="col-md-6"><strong>Program:</strong> <?= htmlspecialchars($enrollment['program']) ?></div>
      <div class="col-md-3"><strong>Year:</strong> <?= htmlspecialchars($enrollment['year']) ?></div>
      <div class="col-md-3"><strong>Semester:</strong> <?= htmlspecialchars($enrollment['semester']) ?></div>
    </div>
    <div class="row mb-3">
      <div class="col-md-6"><strong>Section:</strong> <?= htmlspecialchars($enrollment['section']) ?></div>
      <div class="col-md-6"><strong>Date Submitted:</strong> <?= date("F j, Y", strtotime($enrollment['date_submitted'])) ?></div>
    </div>
    <div class="mb-3">
      <strong>Status:</strong>
      <?php
        $status = strtolower($enrollment['status']);
        $badgeClass = match ($status) {
          'approved' => 'badge-approved',
          'pending' => 'badge-pending',
          'rejected' => 'badge-rejected',
          default => 'badge-secondary',
        };
      ?>
      <span class="badge <?= $badgeClass ?>">
        <?= htmlspecialchars($enrollment['status']) ?>
      </span>
    </div>

    <?php if ($status === 'approved'): ?>
      <a href="/IT2C_Enrollment_System_SourceCode/student/history&Profile/Enrollment History.php" class="btn btn-success">
        View / Print Enrollment Slip
      </a>
    <?php elseif ($status === 'rejected'): ?>
      <div class="alert alert-danger mt-3" role="alert">
        <strong>Reason:</strong> <?= htmlspecialchars($enrollment['rejection_reason']) ?>
      </div>
    <?php else: ?>
      <div class="alert alert-warning mt-3" role="alert">
        Your enrollment is still pending. Please wait for approval.
      </div>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="alert alert-warning mt-4" role="alert">
    No enrollment found. Please fill up the
    <a href="fill-up-enrollment.php">Enrollment Form</a>.
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
