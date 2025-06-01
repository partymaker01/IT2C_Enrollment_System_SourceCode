<?php
session_start();
$student_id = $_SESSION['student_id'] ?? 1;  // example student id

// DB Connection - CHANGE with your own credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enrollment_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle file upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_type']) && isset($_FILES['uploaded_file'])) {
    $docType = $_POST['document_type'];
    $file = $_FILES['uploaded_file'];

    // Check for errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Clean filename & create unique name to avoid conflicts
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = $student_id . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9_-]/", "_", $docType) . '.' . $ext;
        $targetFile = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Insert to DB
            $stmt = $conn->prepare("INSERT INTO uploaded_documents (student_id, document_name, upload_date, status, remarks, file_path) VALUES (?, ?, NOW(), 'Uploaded', '-', ?)");
            $stmt->bind_param("iss", $student_id, $docType, $newFileName);
            $stmt->execute();
            $stmt->close();

            echo "<div class='alert alert-success'>Document uploaded successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>File upload error.</div>";
    }
}

// Fetch documents from DB for this student
$sql = "SELECT * FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$uploadedDocs = [];
while ($row = $result->fetch_assoc()) {
    $uploadedDocs[] = $row;
}

$stmt->close();
$conn->close();

// Helper function for status class
function getStatusClass($status) {
    switch ($status) {
        case 'Uploaded': return 'status-uploaded';
        case 'Verified': return 'status-verified';
        case 'Rejected': return 'status-rejected';
        default: return '';
    }
}

// Check if reupload allowed (only if status is Rejected)
function canReupload($status) {
    return $status === 'Rejected';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Document Upload & Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body { background-color: #e8f5e9; }
    .navbar { background-color: #2e7d32; }
    .navbar-brand, .nav-link { color: #fff !important; font-weight: 600; letter-spacing: 0.05em; }
    .nav-link:hover { color: #c8e6c9 !important; }
    .school-logo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #fff;
}

    .status-uploaded { color: #fbc02d; font-weight: 600; }
    .status-verified { color: #43a047; font-weight: 600; }
    .status-rejected { color: #e53935; font-weight: 600; }
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
    <h2 class="text-success mb-4">Upload Required Documents</h2>

    <form id="uploadForm" class="mb-5" enctype="multipart/form-data" method="post" action="">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label for="documentType" class="form-label">Document Type</label>
          <select class="form-select" id="documentType" name="document_type" required>
            <option value="" selected disabled>Select type...</option>
            <option value="ID">ID</option>
            <option value="Birth Certificate">Birth Certificate</option>
            <option value="Form 137">Form 137</option>
            <option value="Others">Others</option>
          </select>
        </div>
        <div class="col-md-5">
          <label for="fileUpload" class="form-label">Choose File</label>
          <input class="form-control" type="file" id="fileUpload" name="uploaded_file" accept=".pdf,.jpg,.jpeg,.png" required />
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-success w-100">Upload Document</button>
        </div>
      </div>
    </form>

    <h3 class="text-success mb-3">Uploaded Documents</h3>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-success">
          <tr>
            <th>Document Name</th>
            <th>Upload Date</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($uploadedDocs as $doc): ?>
            <tr>
              <td><?= htmlspecialchars($doc['document_name']) ?></td>
              <td><?= date('F j, Y, g:i a', strtotime($doc['upload_date'])) ?></td>
              <td class="<?= getStatusClass($doc['status']) ?>"><?= htmlspecialchars($doc['status']) ?></td>
              <td><?= htmlspecialchars($doc['remarks']) ?></td>
              <td>
                <a href="uploads/documents/<?= urlencode($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
                <?php if (canReupload($doc['status'])): ?>
                  <button class="btn btn-sm btn-danger" onclick="alert('Please upload a new file using the upload form.')">Re-upload</button>
                <?php else: ?>
                  <button class="btn btn-sm btn-secondary" disabled>Re-upload</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($uploadedDocs)): ?>
            <tr><td colspan="5" class="text-center">No documents uploaded yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
  <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
      const docType = document.getElementById('documentType').value;
      const file = document.getElementById('fileUpload').files[0];
      if (!docType || !file) {
        e.preventDefault();
        alert("Please choose a document type and select a file to upload.");
      }
    });
  </script>
</body>
</html>
