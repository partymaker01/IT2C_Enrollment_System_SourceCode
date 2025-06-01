<?php
session_start();

// DB connection - palitan ito ng sarili mong DB credentials
$host = "localhost";
$user = "root";
$pass = "";  // your DB password
$dbname = "enrollment_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = false;

// Handle POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    if (!$startDate || !$endDate) {
        $error = 'Please fill in both dates.';
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $error = 'Start date cannot be after end date.';
    }

    if (!$error) {
        // Insert new enrollment period
        $stmt = $conn->prepare("INSERT INTO enrollment_periods (start_date, end_date) VALUES (?, ?)");
        $stmt->bind_param("ss", $startDate, $endDate);

        if ($stmt->execute()) {
            $success = true;
            // Save to session as well for immediate UI update
            $_SESSION['enrollment_period'] = [
                'start' => $startDate,
                'end' => $endDate
            ];
        } else {
            $error = 'Failed to save enrollment period.';
        }
        $stmt->close();
    }
}

// Fetch the latest enrollment period from DB (for showing current)
$savedPeriod = null;
$result = $conn->query("SELECT start_date, end_date FROM enrollment_periods ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $savedPeriod = $result->fetch_assoc();
} else {
    // fallback to session if DB empty
    $savedPeriod = $_SESSION['enrollment_period'] ?? null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Set Enrollment Period</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
    .school-logo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    h2 {
      color: #2e7d32;
      font-weight: 600;
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

  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card w-100 p-4" style="max-width: 500px;">
      <h2 class="text-center mb-4">Set Enrollment Period</h2>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">Enrollment period set successfully!</div>
      <?php endif; ?>

      <form id="enrollmentPeriodForm" method="post" novalidate>
        <div class="mb-3">
          <label for="startDate" class="form-label">Start Date</label>
          <input type="date" class="form-control" id="startDate" name="startDate" value="<?= htmlspecialchars($savedPeriod['start_date'] ?? '') ?>" required />
        </div>

        <div class="mb-3">
          <label for="endDate" class="form-label">End Date</label>
          <input type="date" class="form-control" id="endDate" name="endDate" value="<?= htmlspecialchars($savedPeriod['end_date'] ?? '') ?>" required />
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="reset" class="btn btn-outline-secondary">Clear</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.getElementById('enrollmentPeriodForm').addEventListener('submit', function(e) {
      const startDate = new Date(document.getElementById('startDate').value);
      const endDate = new Date(document.getElementById('endDate').value);

      if (startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be after end date.');
      }
    });
  </script>
</body>
</html>
