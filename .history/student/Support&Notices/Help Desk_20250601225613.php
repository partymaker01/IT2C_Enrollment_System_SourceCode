<?php
session_start();

// Database connection settings
$host = 'localhost';
$db   = 'enrollment_system';
$user = 'root';  // change to your DB user
$pass = '';      // change to your DB password
$charset = 'utf8mb4';

// Set up DSN and options for PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errors = [];
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if ($subject === '') $errors[] = "Subject is required.";
    if ($category === '') $errors[] = "Category is required.";
    if ($description === '') $errors[] = "Description is required.";

    // Handle file upload if any
    $attachmentName = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // To avoid file name conflicts, prepend timestamp
        $filename = time() . '_' . basename($_FILES['attachment']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            $attachmentName = $filename;
        } else {
            $errors[] = "Failed to upload attachment.";
        }
    }

    if (empty($errors)) {
        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO tickets (subject, category, description, attachment) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialchars($subject),
            htmlspecialchars($category),
            htmlspecialchars($description),
            $attachmentName
        ]);

        $success = "Ticket submitted successfully!";
        // Clear inputs after success
        $subject = $category = $description = '';
    }
}

// Fetch all tickets for the current session (or you may want to fetch all for admin view)
$tickets = [];
try {
    $stmt = $pdo->query("SELECT * FROM tickets ORDER BY date_submitted DESC");
    $tickets = $stmt->fetchAll();
} catch (\PDOException $e) {
    $errors[] = "Failed to fetch tickets: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Help Desk</title>
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

    .status-open {
      color: #2e7d32; /* green */
      font-weight: 600;
    }
    .status-inprogress {
      color: #f9a825; /* amber */
      font-weight: 600;
    }
    .status-resolved {
      color: #1565c0; /* blue */
      font-weight: 600;
    }
    .status-closed {
      color: #b71c1c; /* red */
      font-weight: 600;
    }
    .card-header.bg-success {
      background-color: #2e7d32 !important;
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
    <h2 class="text-success mb-4">Help Desk</h2>

    <div class="card mb-5 shadow-sm">
      <div class="card-header bg-success text-white fw-bold">
        Submit a New Concern / Ticket
      </div>
      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form id="helpDeskForm" method="POST" enctype="multipart/form-data" novalidate>
          <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars($subject ?? '') ?>" required />
          </div>
          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
              <option value="" disabled <?= empty($category) ? 'selected' : '' ?>>Select category</option>
              <option value="Enrollment" <?= ($category ?? '') === 'Enrollment' ? 'selected' : '' ?>>Enrollment</option>
              <option value="Documents" <?= ($category ?? '') === 'Documents' ? 'selected' : '' ?>>Documents</option>
              <option value="Account" <?= ($category ?? '') === 'Account' ? 'selected' : '' ?>>Account</option>
              <option value="Others" <?= ($category ?? '') === 'Others' ? 'selected' : '' ?>>Others</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Description / Details</label>
            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($description ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label for="attachment" class="form-label">Attachment (optional)</label>
            <input class="form-control" type="file" id="attachment" name="attachment" />
          </div>
          <button type="submit" class="btn btn-success w-100">Submit Ticket</button>
        </form>
      </div>
    </div>

    <h4>Your Submitted Tickets</h4>
    <?php if (empty($tickets)): ?>
      <p>No tickets submitted yet.</p>
    <?php else: ?>
      <div class="list-group">
        <?php foreach ($tickets as $ticket): ?>
          <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <div>
              <strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?><br />
              <small class="text-muted">Submitted on <?= date("F d, Y", strtotime($ticket['date_submitted'])) ?> | Category: <?= htmlspecialchars($ticket['category']) ?></small>
              <?php if ($ticket['attachment']): ?>
                <br><small class="text-muted">Attachment: <?= htmlspecialchars($ticket['attachment']) ?></small>
              <?php endif; ?>
            </div>
            <span class="badge <?= "status-" . strtolower(str_replace(' ', '', $ticket['status'])) ?>"><?= htmlspecialchars($ticket['status']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Back to Dashboard Button -->
    <div class="mt-4">
      <a href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
