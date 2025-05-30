<?php
session_start();
include 'db.php';

// For demo, hardcoded user_id, replace with your session user id.
$user_id = $_SESSION['user_id'] ?? 1;

function getBadgeClass($status) {
  switch ($status) {
    case 'Uploaded': return 'badge-uploaded';
    case 'Verified': return 'badge-verified';
    case 'Rejected': return 'badge-rejected';
    case 'Pending':  return 'badge-pending';
    default:         return 'bg-secondary';
  }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_type = $_POST['document_type'] ?? '';
    if (empty($document_type)) {
        $message = "Please select a document type.";
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file.";
    } else {
        $file = $_FILES['document_file'];
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            $message = "Invalid file type. Only PDF, JPG, PNG allowed.";
        } elseif ($file['size'] > $max_size) {
            $message = "File too large. Max size is 5MB.";
        } else {
            // Create uploads folder if not exist
            $upload_dir = __DIR__ . '/uploads/' . $user_id;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Save to DB
                $stmt = $conn->prepare("INSERT INTO uploaded_documents (user_id, document_type, filename, status, remarks, reupload) VALUES (?, ?, ?, 'Uploaded', 'Waiting for verification', 0)");
                $stmt->bind_param("iss", $user_id, $document_type, $new_filename);
                $stmt->execute();
                $message = "Document uploaded successfully.";
            } else {
                $message = "Failed to move uploaded file.";
            }
        }
    }
}

// Fetch documents from DB
$stmt = $conn->prepare("SELECT * FROM uploaded_documents WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$uploadedDocuments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Upload Requirements - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body { background-color: #e8f5e9; }
    .navbar { background-color: #2e7d32; }
    .navbar-brand, .nav-link { color: #fff !important; font-weight: 600; letter-spacing: 0.05em; }
    .nav-link:hover { color: #c8e6c9 !important; }
    .badge-uploaded { background-color: #2196f3; }
    .badge-verified { background-color: #43a047; }
    .badge-rejected { background-color: #e53935; }
    .badge-pending { background-color: #fbc02d; color: #000; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">Student Dashboard</a>
  </div>
</nav>
<div class="container my-5">
  <h2 class="text-success mb-4">Upload Requirements</h2>

  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="uploadForm">
        <div class="mb-3">
          <label for="documentType" class="form-label">Select Document Type</label>
          <select class="form-select" id="documentType" name="document_type" required>
            <option value="" disabled selected>Select a document</option>
            <option value="3pcs 1x1 picture">3pcs 1x1 picture</option>
            <option value="4pcs Passport size picture">4pcs Passport size picture</option>
            <option value="Form 137 / SF10">Form 137 / SF10</option>
            <option value="Report Card / SF9">Report Card / SF9</option>
            <option value="PSA Birth Certificate">PSA Birth Certificate</option>
            <option value="Diploma (photocopy)">Diploma (photocopy)</option>
            <option value="Certificate of Good Moral">Certificate of Good Moral</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="documentFile" class="form-label">Choose File (PDF, JPG, PNG, max 5MB)</label>
          <input class="form-control" type="file" id="documentFile" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required />
        </div>

        <button type="submit" class="btn btn-success">Upload Document</button>
      </form>
    </div>
  </div>

  <h4>Previously Uploaded Documents</h4>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead class="table-success">
        <tr>
          <th>Document Type</th>
          <th>Filename</th>
          <th>Upload Date</th>
          <th>Status</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($uploadedDocuments)): ?>
          <?php foreach ($uploadedDocuments as $doc): ?>
          <tr>
            <td><?= htmlspecialchars($doc['document_type']) ?></td>
            <td>
              <a href="uploads/<?= $user_id ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank">
                <?= htmlspecialchars($doc['filename']) ?>
              </a>
            </td>
            <td><?= date('M d, Y', strtotime($doc['upload_date'])) ?></td>
            <td><span class="badge <?= getBadgeClass($doc['status']) ?>"><?= htmlspecialchars($doc['status']) ?></span></td>
            <td><?= htmlspecialchars($doc['remarks']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" <?= $doc['reupload'] ? '' : 'disabled' ?>>Re-upload</button>
              <a href="uploads/<?= $user_id ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center">No uploaded documents yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
