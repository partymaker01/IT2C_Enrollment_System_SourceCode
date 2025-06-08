<?php
// Database connection parameters - replace with your actual credentials
$host = 'localhost';
$dbname = 'enrollment_system';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Assuming student_id is known, e.g., from session or request
$student_id = 1; // Change this dynamically according to logged-in user

// Fetch uploaded documents for this student
$stmt = $pdo->prepare("SELECT * FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->execute([$student_id]);
$uploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to return badge class by status
function badgeClass($status) {
    return match (strtolower($status)) {
        'uploaded' => 'bg-primary',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
}

// Base URL or path for uploaded files - adjust if needed
$baseUploadPath = '/IT2C_Enrollment_System_SourceCode/uploads/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>View Uploaded Files</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="icon" href="favicon.ico" type="image/x-icon">
<style>
  body { background-color: #e8f5e9; }
  tbody tr:hover {
    background-color: #d0f0d6;
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
  .btn-reupload:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  .file-preview img {
    max-width: 120px;
    max-height: 80px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
  }
  .file-preview embed {
    width: 120px;
    height: 80px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
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
    <h2 class="text-success mb-4">
      View Uploaded Files
    </h2>
    <div class="table-responsive shadow-sm bg-white rounded">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-success">
          <tr>
            <th>Document Type</th>
            <th>File Preview</th>
            <th>Upload Date</th>
            <th>Status</th>
            <th>Remarks</th>
            <th style="width: 140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($uploadedFiles as $file): 
            $filePath = $file['file_path'] ?? '';
            $fullFilePath = $baseUploadPath . $filePath;
            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileName = basename($filePath);
            // For mime type detection, fallback to extension check if mime_content_type not available on server
            $fullServerPath = $_SERVER['DOCUMENT_ROOT'] . $fullFilePath;
            $mimeType = @mime_content_type($fullServerPath) ?: '';
            ?>
            <tr>
              <td><?= htmlspecialchars($file['document_name'] ?? 'N/A') ?></td>
              <td class="file-preview">
                <?php if (str_starts_with($mimeType, 'image/') || in_array($fileExt, ['jpg','jpeg','png','gif','bmp','webp'])): ?>
                  <img src="<?= htmlspecialchars($fullFilePath) ?>" alt="Image preview of <?= htmlspecialchars($fileName) ?>" />
                <?php elseif ($mimeType === 'application/pdf' || $fileExt === 'pdf'): ?>
                  <embed src="<?= htmlspecialchars($fullFilePath) ?>" type="application/pdf" />
                <?php else: ?>
                  <a href="<?= htmlspecialchars($fullFilePath) ?>" target="_blank"><?= htmlspecialchars($fileName) ?></a>
                <?php endif; ?>
              </td>
              <td><?= !empty($file['upload_date']) ? date('M d, Y', strtotime($file['upload_date'])) : 'N/A' ?></td>
              <td>
                <span class="badge <?= badgeClass($file['status'] ?? '') ?>">
                  <?= htmlspecialchars($file['status'] ?? 'Unknown') ?>
                </span>
              </td>
              <td><?= htmlspecialchars($file['remarks'] ?? '') ?></td>
              <td>
                <a href="<?= htmlspecialchars($fullFilePath) ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1">View</a>
                <?php if (!empty($file['can_reupload'])): ?>
                  <a href="#" class="btn btn-sm btn-outline-primary btn-reupload">Re-upload</a>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-primary btn-reupload" disabled>Re-upload</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
