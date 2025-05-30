<?php
// DB connection config (adjust with your credentials)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'enrollment_system';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch subjects from database
$sql = "SELECT * FROM subjects ORDER BY code ASC";
$result = $conn->query($sql);

$subjects = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // Convert comma separated days string to array
    $row['days'] = explode(',', $row['days']);
    $subjects[] = $row;
  }
} else {
  $subjects = [];
}

$weekdays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Assigned Subjects / Class Schedule</title>
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
    .status-confirmed {
      color: #2e7d32;
      font-weight: 600;
    }
    .status-pending {
      color: #fbc02d;
      font-weight: 600;
    }
    .calendar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 2rem;
    }
    .calendar-day {
      background-color: white;
      border-radius: 6px;
      padding: 0.75rem;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }
    .calendar-day h6 {
      border-bottom: 1px solid #ccc;
      padding-bottom: 0.4rem;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: #2e7d32;
    }
    .class-item {
      background-color: #a5d6a7;
      margin-bottom: 0.5rem;
      border-radius: 5px;
      padding: 0.5rem;
      font-size: 0.9rem;
    }
    .class-item.pending {
      background-color: #fff59d;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
        Student Dashboard
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
  <div class="container py-4">
    <h2 class="text-success mb-4 fw-bold">
      Assigned Subjects / Class Schedule
    </h2>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success">
          <tr class="text-center">
            <th>Subject Code</th>
            <th>Description</th>
            <th>Schedule</th>
            <th>Instructor</th>
            <th>Room</th>
            <th>Status</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $sub): ?>
              <tr>
                <td><?= htmlspecialchars($sub['code']) ?></td>
                <td><?= htmlspecialchars($sub['description']) ?></td>
                <td><?= htmlspecialchars($sub['schedule']) ?></td>
                <td><?= htmlspecialchars($sub['instructor']) ?></td>
                <td><?= htmlspecialchars($sub['room']) ?></td>
                <td>
                  <span class="<?= strtolower($sub['status']) === 'confirmed' ? 'status-confirmed' : 'status-pending' ?>">
                    <?= htmlspecialchars($sub['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="#" class="btn btn-sm btn-outline-success">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center">No subjects assigned yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h4 class="text-success mt-5 mb-3 fw-bold">Weekly Class Schedule</h4>
    <div class="calendar">
      <?php foreach ($weekdays as $day): ?>
        <div class="calendar-day">
          <h6><?= $day ?></h6>
          <?php
          $hasClass = false;
          foreach ($subjects as $sub) {
            if (in_array($day, $sub['days'])) {
              $hasClass = true;
              $statusClass = strtolower($sub['status']) === 'pending' ? 'pending' : '';
              echo "<div class='class-item $statusClass'>"
                   . htmlspecialchars($sub['code']) . "<br>"
                   . htmlspecialchars($sub['time']) . "<br>"
                   . htmlspecialchars($sub['room']) .
                   "</div>";
            }
          }
          if (!$hasClass) {
            echo "<small class='text-muted'>No classes</small>";
          }
          ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-4 text-end">
      <button class="btn btn-success me-2">
        Download Schedule (PDF)
      </button>
      <button class="btn btn-outline-success">
        Print Schedule
      </button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>