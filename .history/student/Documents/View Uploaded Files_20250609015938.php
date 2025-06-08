<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

// Fetch uploaded documents
$stmt = $pdo->prepare("SELECT * FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->execute([$student_id]);
$documents = $stmt->fetchAll();

// Helper functions
function getStatusClass($status) {
    return match(strtolower($status)) {
        'uploaded' => 'bg-primary',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending' => 'bg-warning text-dark',
        default => 'bg-secondary'
    };
}

function getStatusIcon($status) {
    return match(strtolower($status)) {
        'uploaded' => 'bi-upload',
        'verified' => 'bi-check-circle',
        'rejected' => 'bi-x-circle',
        'pending' => 'bi-clock',
        default => 'bi-file'
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
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../student-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <span>Student Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../student-dashboard.php">
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
            <th>File Name</th>
            <th>Upload Date</th>
            <th>Status</th>
            <th>Remarks</th>
            <th style="width: 140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($uploadedFiles): ?>
            <?php foreach ($uploadedFiles as $file): ?>
              <tr>
                <td><?= htmlspecialchars($file['doc_type']) ?></td>
                <td><?= htmlspecialchars($file['file_name']) ?></td>
                <td><?= date('M d, Y', strtotime($file['upload_date'])) ?></td>
                <td>
                  <span class="badge <?= getStatusClass($file['status']) ?>">
                    <?= htmlspecialchars($file['status']) ?>
                  </>
                </td>
                <td><?= htmlspecialchars($file['remarks']) ?></td>
                <td>
                  <a href="<?= htmlspecialchars($file['view_link']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1" aria-label="View <?= htmlspecialchars($file['doc_type']) ?>">View</a>
                  <?php if ($file['can_reupload']): ?>
                    <a href="#" class="btn btn-sm btn-outline-primary btn-reupload" aria-label="Re-upload <?= htmlspecialchars($file['doc_type']) ?>">Re-upload</a>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-primary btn-reupload" disabled aria-label="Re-upload <?= htmlspecialchars($file['doc_type']) ?>">Re-upload</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">No uploaded documents found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
