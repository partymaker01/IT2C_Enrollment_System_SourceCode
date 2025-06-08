<?php
session_start();
include '../../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

$error = '';
$success = false;

// Handle POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $schoolYear = $_POST['schoolYear'] ?? '';

    if (!$startDate || !$endDate || !$semester || !$schoolYear) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $error = 'Start date cannot be after end date.';
    } elseif (strtotime($startDate) < strtotime('today')) {
        $error = 'Start date cannot be in the past.';
    }

    if (!$error) {
        try {
            $conn->begin_transaction();

            // Deactivate previous enrollment periods
            $stmt = $conn->prepare("UPDATE enrollment_periods SET status = 'inactive' WHERE status = 'active'");
            $stmt->execute();

            // Insert new enrollment period
            $stmt = $conn->prepare("INSERT INTO enrollment_periods (start_date, end_date, semester, school_year, status, created_by) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->bind_param("ssssi", $startDate, $endDate, $semester, $schoolYear, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                $conn->commit();
                $success = true;
                $_SESSION['enrollment_period'] = [
                    'start' => $startDate,
                    'end' => $endDate,
                    'semester' => $semester,
                    'school_year' => $schoolYear
                ];
            } else {
                throw new Exception('Failed to save enrollment period.');
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current active enrollment period
$currentPeriod = null;
$stmt = $conn->prepare("SELECT * FROM enrollment_periods WHERE status = 'active' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $currentPeriod = $result->fetch_assoc();
}

// Fetch enrollment statistics
$stats = [];
$stmt = $conn->prepare("SELECT COUNT(*) as total_enrollments FROM enrollments WHERE status = 'pending'");
$stmt->execute();
$stats['pending'] = $stmt->get_result()->fetch_assoc()['total_enrollments'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_enrollments FROM enrollments WHERE status = 'approved'");
$stmt->execute();
$stats['approved'] = $stmt->get_result()->fetch_assoc()['total_enrollments'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Set Enrollment Period - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="../../favicon.ico" type="image/x-icon">
  <style>
    body {
      background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
    }
    .navbar {
      background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .navbar-brand, .nav-link {
      color: #fff !important;
      font-weight: 600;
      letter-spacing: 0.05em;
    }
    .nav-link:hover {
      color: #c8e6c9 !important;
      transform: translateY(-1px);
      transition: all 0.3s ease;
    }
    .school-logo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
    }
    .main-card {
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .stats-card {
      border-radius: 15px;
      background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
      border: none;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease;
    }
    .stats-card:hover {
      transform: translateY(-5px);
    }
    .current-period-card {
      background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
      color: white;
      border-radius: 15px;
      border: none;
    }
    h2 {
      color: #2e7d32;
      font-weight: 700;
      text-align: center;
      margin-bottom: 2rem;
    }
    .form-floating label {
      color: #666;
    }
    .form-control:focus {
      border-color: #2e7d32;
      box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
    }
    .btn-success {
      background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
      border: none;
      padding: 12px 30px;
      font-weight: 600;
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(46, 125, 50, 0.4);
    }
    .alert {
      border-radius: 10px;
      border: none;
    }
    .status-badge {
      font-size: 0.9rem;
      padding: 8px 16px;
      border-radius: 20px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
        Admin Panel
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="../admin-dashboard.php">
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <!-- Statistics Cards -->
    <div class="row mb-5">
      <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body text-center">
            <i class="bi bi-clock-history text-warning" style="font-size: 2.5rem;"></i>
            <h4 class="mt-3 mb-1"><?= $stats['pending'] ?></h4>
            <p class="text-muted mb-0">Pending Enrollments</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body text-center">
            <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
            <h4 class="mt-3 mb-1"><?= $stats['approved'] ?></h4>
            <p class="text-muted mb-0">Approved Enrollments</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card current-period-card h-100">
          <div class="card-body text-center">
            <i class="bi bi-calendar-check" style="font-size: 2.5rem;"></i>
            <h4 class="mt-3 mb-1">
              <?php if ($currentPeriod): ?>
                Active Period
              <?php else: ?>
                No Active Period
              <?php endif; ?>
            </h4>
            <p class="mb-0 opacity-75">
              <?php if ($currentPeriod): ?>
                <?= date('M d', strtotime($currentPeriod['start_date'])) ?> - <?= date('M d, Y', strtotime($currentPeriod['end_date'])) ?>
              <?php else: ?>
                Set enrollment period below
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Period Display -->
    <?php if ($currentPeriod): ?>
    <div class="row mb-4">
      <div class="col-12">
        <div class="card current-period-card">
          <div class="card-body">
            <h5 class="card-title mb-3">
              <i class="bi bi-calendar-event me-2"></i>Current Enrollment Period
            </h5>
            <div class="row">
              <div class="col-md-3">
                <strong>Period:</strong><br>
                <?= date('F d, Y', strtotime($currentPeriod['start_date'])) ?><br>
                to <?= date('F d, Y', strtotime($currentPeriod['end_date'])) ?>
              </div>
              <div class="col-md-3">
                <strong>Semester:</strong><br>
                <?= htmlspecialchars($currentPeriod['semester']) ?>
              </div>
              <div class="col-md-3">
                <strong>School Year:</strong><br>
                <?= htmlspecialchars($currentPeriod['school_year']) ?>
              </div>
              <div class="col-md-3">
                <strong>Status:</strong><br>
                <span class="status-badge bg-light text-success">
                  <i class="bi bi-check-circle me-1"></i>Active
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Main Form -->
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card main-card">
          <div class="card-body p-5">
            <h2>Set New Enrollment Period</h2>

            <?php if ($error): ?>
              <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <?php if ($success): ?>
              <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                Enrollment period has been set successfully!
              </div>
            <?php endif; ?>

            <form id="enrollmentPeriodForm" method="post" novalidate>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="date" class="form-control" id="startDate" name="startDate" 
                           value="<?= htmlspecialchars($currentPeriod['start_date'] ?? '') ?>" required />
                    <label for="startDate">Start Date</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="date" class="form-control" id="endDate" name="endDate" 
                           value="<?= htmlspecialchars($currentPeriod['end_date'] ?? '') ?>" required />
                    <label for="endDate">End Date</label>
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <div class="form-floating">
                    <select class="form-select" id="semester" name="semester" required>
                      <option value="">Select Semester</option>
                      <option value="1st Semester" <?= ($currentPeriod['semester'] ?? '') === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                      <option value="2nd Semester" <?= ($currentPeriod['semester'] ?? '') === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                      <option value="Summer" <?= ($currentPeriod['semester'] ?? '') === 'Summer' ? 'selected' : '' ?>>Summer</option>
                    </select>
                    <label for="semester">Semester</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating">
                    <input type="text" class="form-control" id="schoolYear" name="schoolYear" 
                           placeholder="e.g., 2024-2025" value="<?= htmlspecialchars($currentPeriod['school_year'] ?? '') ?>" required />
                    <label for="schoolYear">School Year</label>
                  </div>
                </div>
              </div>

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-outline-secondary me-md-2">
                  <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
                <button type="submit" class="btn btn-success">
                  <i class="bi bi-calendar-plus me-1"></i>Set Enrollment Period
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('enrollmentPeriodForm').addEventListener('submit', function(e) {
      const startDate = new Date(document.getElementById('startDate').value);
      const endDate = new Date(document.getElementById('endDate').value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be after end date.');
        return;
      }

      if (startDate < today) {
        e.preventDefault();
        alert('Start date cannot be in the past.');
        return;
      }

      // Confirm action
      if (!confirm('This will deactivate the current enrollment period and set a new one. Continue?')) {
        e.preventDefault();
      }
    });

    // Auto-generate school year based on start date
    document.getElementById('startDate').addEventListener('change', function() {
      const startDate = new Date(this.value);
      const year = startDate.getFullYear();
      const month = startDate.getMonth();
      
      let schoolYear;
      if (month >= 6) { // July onwards
        schoolYear = year + '-' + (year + 1);
      } else { // January to June
        schoolYear = (year - 1) + '-' + year;
      }
      
      document.getElementById('schoolYear').value = schoolYear;
    });
  </script>
</body>
</html>
