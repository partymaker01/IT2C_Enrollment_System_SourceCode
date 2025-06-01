<?php
session_start();
include '../../db.php';

$user_id = $_SESSION['student_id'] ?? null;

if (!$user_id) {
    die(" Student not logged in.");
}
$check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    die(" Student ID not found in students table.");
}
$check->close();

function getBadgeClass($status) {
    return match($status) {
        'Uploaded' => 'badge-uploaded',
        'Verified' => 'badge-verified',
        'Rejected' => 'badge-rejected',
        'Pending'  => 'badge-pending',
        default    => 'bg-secondary'
    };
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = $_POST['document_type'] ?? '';

    if (empty($document_type)) {
        $message = "Please select a document type.";
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file.";
    } else {
        $file = $_FILES['document_file'];
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $max_size = 5 * 1024 * 1024;

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $message = "Invalid file type. Only PDF, JPG, PNG allowed.";
        } elseif ($file['size'] > $max_size) {
            $message = "File too large. Max size is 5MB.";
        } else {
            $upload_dir = __DIR__ . '/uploads/' . $user_id;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_filename = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $stmt = $conn->prepare("INSERT INTO uploaded_documents (student_id, document_name, file_path, status, remarks) VALUES (?, ?, ?, 'Uploaded', 'Waiting for verification')");
                $stmt->bind_param("iss", $user_id, $document_type, $new_filename);
                $stmt->execute();
                $stmt->close();

                $relative_path = 'uploads/' . $user_id . '/' . $new_filename;
                $fileType = mime_content_type($target_path);
                $preview = "<h5 class='mt-3'>File Preview:</h5>";

                if (str_contains($fileType, "image")) {
                    $preview .= "<img src='$relative_path' style='max-width: 400px;' class='img-thumbnail'>";
                } elseif ($fileType === "application/pdf") {
                    $preview .= "<embed src='$relative_path' type='application/pdf' width='100%' height='500px'>";
                } else {
                    $preview .= "<p>File uploaded: <a href='$relative_path' target='_blank'>Download</a></p>";
                }
                $message = "Document uploaded successfully.";
            } else {
                $message = "Failed to move uploaded file.";
            }
        }
    }
}

$uploadedDocuments = [];
$stmt = $conn->prepare("SELECT document_name, file_path, upload_date, status, remarks FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$uploadedDocuments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Upload Requirements - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
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
            <td><?= htmlspecialchars($doc['document_name']) ?></td>
            <td>
              <a href="uploads/<?= $user_id ?>/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">
                <?= htmlspecialchars($doc['file_path']) ?>
              </a>
            </td>
            <td><?= date('M d, Y', strtotime($doc['upload_date'])) ?></td>
            <td><span class="badge <?= getBadgeClass($doc['status']) ?>"><?= htmlspecialchars($doc['status']) ?></span></td>
            <td><?= htmlspecialchars($doc['remarks']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" disabled>Re-upload</button>
              <a href="uploads/<?= $user_id ?>/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
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

<?php if ($message): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Upload Complete',
    html: <?= json_encode($message) ?>,
    confirmButtonColor: '#3085d6'
  });
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>