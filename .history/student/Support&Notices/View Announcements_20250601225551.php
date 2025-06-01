<?php
// DB connection config - edit these as needed
$host = 'localhost';
$db   = 'enrollment_system';
$user = 'your_db_user';
$pass = 'your_db_password';
$charset = 'utf8mb4';

// Set up PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch announcements from DB ordered by date descending
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY date DESC");
$announcements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Announcements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e8f5e9;
    }
    .school-logo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #fff;
}

    .announcement-card {
      cursor: pointer;
      transition: box-shadow 0.3s ease;
    }
    .announcement-card:hover {
      box-shadow: 0 4px 15px rgba(67, 160, 71, 0.4);
    }
    .text-truncate-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2; /* limits to 2 lines */
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-clamp: 2;
      box-orient: vertical;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
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

<div class="container my-5">
  <h2 class="text-success mb-4">Announcements</h2>

  <div class="list-group">
    <?php foreach ($announcements as $announcement): ?>
      <a href="#" class="list-group-item list-group-item-action announcement-card"
         data-bs-toggle="modal"
         data-bs-target="#announcementModal<?= $announcement['id'] ?>">
        <div class="d-flex w-100 justify-content-between">
          <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
          <small><?= date("F d, Y", strtotime($announcement['date'])) ?></small>
        </div>
        <p class="mb-1 text-truncate-2"><?= htmlspecialchars($announcement['summary']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Back to Dashboard Button -->
  <div class="mt-4">
    <a href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
  </div>
</div>

<!-- Modals for Announcements -->
<?php foreach ($announcements as $announcement): ?>
  <div class="modal fade" id="announcementModal<?= $announcement['id'] ?>" tabindex="-1" aria-labelledby="announcementModalLabel<?= $announcement['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="announcementModalLabel<?= $announcement['id'] ?>">
            <?= htmlspecialchars($announcement['title']) ?>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?= $announcement['content'] ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
