<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uploadDir = 'uploads/';
  if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

  function uploadFile($inputName, $studentId) {
    global $uploadDir;
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === 0) {
      $file = $_FILES[$inputName];
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = $studentId . '_' . $inputName . '.' . $ext;
      $destination = $uploadDir . $filename;
      return move_uploaded_file($file['tmp_name'], $destination);
    }
    return false;
  }

  $studentId = $_POST['studentId'] ?? '';
  if (empty($studentId)) {
    header('Location: upload-student-documents.php?msg=' . urlencode("Please select a student"));
    exit;
  }

  $requiredFields = ['one_by_one', 'passport', 'form137', 'report_card', 'birth_cert', 'diploma', 'good_moral'];
  $allUploaded = true;

  foreach ($requiredFields as $field) {
    if (!uploadFile($field, $studentId)) $allUploaded = false;
  }

  if (!empty($_FILES['additional_docs']['name'][0])) {
    foreach ($_FILES['additional_docs']['name'] as $key => $name) {
      $tmpName = $_FILES['additional_docs']['tmp_name'][$key];
      $ext = pathinfo($name, PATHINFO_EXTENSION);
      $newName = $studentId . '_additional_' . $key . '.' . $ext;
      move_uploaded_file($tmpName, $uploadDir . $newName);
    }
  }

  $msg = $allUploaded ? "All required documents uploaded successfully." : "Some required documents failed to upload.";
  header("Location: upload-student-documents.php?msg=" . urlencode($msg));
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Upload Student Documents</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #e6f2e6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    h2 {
      color: #2e7d32;
      font-weight: 600;
    }
    .preview-img {
      max-width: 120px;
      max-height: 120px;
      margin-right: 12px;
      margin-bottom: 12px;
      border: 1px solid #ccc;
      padding: 4px;
      border-radius: 6px;
      object-fit: contain;
      background-color: #fff;
    }
    #filePreview > div {
      max-width: 140px;
      padding: 8px;
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      margin-right: 12px;
      margin-bottom: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    @media (max-width: 576px) {
      .preview-img, #filePreview > div {
        max-width: 100px;
        max-height: 100px;
        margin-right: 8px;
        margin-bottom: 8px;
      }
    }
  </style>
</head>
<body>

  <div class="container my-5">
    <h2 class="mb-4 text-center">
      Upload Student Document
    </h2>
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="mb-4 text-start">
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <form id="upload-student-documents" action="upload-student-documents.php" method="POST" enctype="multipart/form-data" novalidate>
      <div class="mb-4">
        <label for="studentSelect" class="form-label fw-semibold">
          Select Student
        </label>
        <select name="studentId" id="studentSelect" class="form-select" required>
          <option value="" disabled selected>-- Select Student --</option>
          <option value="20250001">
            20250001 - Juan Dela Cruz
          </option>
          <option value="20250002">
            20250002 - Maria Clara
          </option>
          <option value="20250003">
            20250003 - Pedro Santos
          </option>
        </select>
        <div class="invalid-feedback">
          Please select a student.
        </div>
      </div>

      <?php
      $documents = [
        'one_by_one' => '3 pcs 1x1 picture',
        'passport' => '4 pcs Passport size picture',
        'form137' => 'Form 137 / SF10',
        'report_card' => 'Report Card / SF9',
        'birth_cert' => 'PSA Birth Certificate',
        'diploma' => 'Diploma Photocopy',
        'good_moral' => 'Certificate of Good Moral'
      ];
      foreach ($documents as $name => $label):
      ?>
        <div class="mb-4">
          <label for="<?= $name ?>" class="form-label fw-semibold"><?= $label ?> <small class="text-muted">(jpg, png, pdf)
          </small></label>
          <input type="file" class="form-control file-check" id="<?= $name ?>" name="<?= $name ?>" accept=".jpg,.jpeg,.png,.pdf" required />
          <div class="invalid-feedback">
            Please upload <?= strtolower($label) ?>.</div>
        </div>
      <?php endforeach; ?>

      <div class="mb-4">
        <label for="additionalDocs" class="form-label fw-semibold">
          Additional Documents<small class="text-muted">
          (optional)
        </small></label>
        <input type="file" class="form-control" id="additionalDocs" name="additional_docs[]" accept=".jpg,.jpeg,.png,.pdf" multiple />
      </div>

      <div id="message" class="mb-3"></div>
      <div id="filePreview" class="d-flex flex-wrap"></div>

      <button type="submit" class="btn btn-success w-100 py-2 fs-5 fw-semibold">
        Upload Documents
      </button>
    </form>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  (() => {
    const form = document.getElementById('upload-student-documents');
    const filePreview = document.getElementById('filePreview');
    const messageDiv = document.getElementById('message');
    const studentSelect = document.getElementById('studentSelect');
    const fileInputs = document.querySelectorAll('.file-check');
    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    const maxFileSize = 5 * 1024 * 1024; // 5MB

    function previewFile(file) {
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'preview-img';
          filePreview.appendChild(img);
        };
        reader.readAsDataURL(file);
      } else {
        const div = document.createElement('div');
        div.textContent = file.name;
        filePreview.appendChild(div);
      }
    }

    function clearPreview() {
      filePreview.innerHTML = '';
    }

    form.addEventListener('change', () => {
      clearPreview();
      fileInputs.forEach(input => {
        if (input.files[0]) previewFile(input.files[0]);
      });

      const additional = document.getElementById('additionalDocs').files;
      for (const file of additional) {
        previewFile(file);
      }
    });

    form.addEventListener('submit', (e) => {
      messageDiv.innerHTML = '';
      let valid = true;

      // Native form validation
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        valid = false;
      }

      // Additional custom validations
      if (studentSelect.value === '') {
        valid = false;
        studentSelect.classList.add('is-invalid');
      } else {
        studentSelect.classList.remove('is-invalid');
      }

      // Check required files
      for (const input of fileInputs) {
        const file = input.files[0];
        if (!file) {
          input.classList.add('is-invalid');
          valid = false;
        } else if (!allowedTypes.includes(file.type)) {
          messageDiv.innerHTML = `<div class="alert alert-danger">Invalid file type for required documents. Allowed types: jpg, png, pdf.</div>`;
          valid = false;
        } else if (file.size > maxFileSize) {
          messageDiv.innerHTML = `<div class="alert alert-danger">File size must be less than 5MB.</div>`;
          valid = false;
        } else {
          input.classList.remove('is-invalid');
        }
      }

      // Check additional docs
      const additional = document.getElementById('additionalDocs').files;
      for (const file of additional) {
        if (!allowedTypes.includes(file.type)) {
          messageDiv.innerHTML = `<div class="alert alert-danger">Invalid file type in additional documents.</div>`;
          valid = false;
          break;
        } else if (file.size > maxFileSize) {
          messageDiv.innerHTML = `<div class="alert alert-danger">Additional documents must be less than 5MB each.</div>`;
          valid = false;
          break;
        }
      }

      if (!valid) {
        e.preventDefault();
      }
    });
  })();
</script>

</body>
</html>
