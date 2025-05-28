<?php
$info_file = 'school_info.json';
$default_logo = 'school-logo.png';

$school_info = [
  'name' => 'Top Link Global College, Inc.',
  'address' => 'MRF Compound, Purok Lambingan, Brgy. Daan Sarile Cabanatuan City, Nueva Ecija, Philippines 3100',
  'contact' => 'toplinkglobalcollege.edu@gmail.com',
  'email' => 'toplinkglobalcollege.edu@gmail.com',
  'logo' => $default_logo
];

if (file_exists($info_file)) {
  $school_info = json_decode(file_get_contents($info_file), true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Update School Info</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8fafc;
    }
    .logo-preview {
      max-height: 120px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 6px;
      background: white;
      object-fit: contain;
    }
    .form-label {
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="container my-5">

    <!-- Back to Dashboard Button -->
    <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
      Back to Dashboard
    </a>

    <h2 class="text-center text-success mb-4">Update School Information</h2>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        School info updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-center mb-4">
      <img src="<?= htmlspecialchars($school_info['logo']) ?>" alt="School Logo" class="logo-preview" />
    </div>

    <form action="save-school-info.php" method="POST" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
      <div class="col-12 col-md-6">
        <label for="schoolName" class="form-label">School Name</label>
        <input type="text" class="form-control" id="schoolName" name="schoolName" placeholder="Enter school name" value="<?= htmlspecialchars($school_info['name']) ?>" required />
        <div class="invalid-feedback">
          Please enter the school name.
        </div>
      </div>

      <div class="col-12 col-md-6">
        <label for="schoolLogo" class="form-label">Upload New Logo</label>
        <input type="file" class="form-control" id="schoolLogo" name="schoolLogo" accept="image/*" />
        <div class="form-text">
          Supported formats: JPG, PNG, GIF.
        </div>
      </div>

      <div class="col-12">
        <label for="address" class="form-label">School Address</label>
        <input type="text" class="form-control" id="address" name="address" placeholder="Enter school address" value="<?= htmlspecialchars($school_info['address']) ?>" required />
        <div class="invalid-feedback">
          Please enter the school address.
        </div>
      </div>

      <div class="col-12 col-md-6">
        <label for="contact" class="form-label">
          Contact Number
        </label>
        <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter contact number" value="<?= htmlspecialchars($school_info['contact']) ?>" required />
        <div class="invalid-feedback">
          Please enter a contact number.
        </div>
      </div>

      <div class="col-12 col-md-6">
        <label for="email" class="form-label">
          School Email
        </label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter school email" value="<?= htmlspecialchars($school_info['email']) ?>" required />
        <div class="invalid-feedback">
          Please enter a valid email address.
        </div>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-success px-4">
          Save Changes
        </button>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (() => {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>
</body>
</html>
