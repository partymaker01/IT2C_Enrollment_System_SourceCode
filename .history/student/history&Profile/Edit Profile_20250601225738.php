<?php
session_start();

$host = 'localhost';
$dbname = 'enrollment_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['student_id'])) {
    die("You must be logged in to access this page.");
}

$student_id = $_SESSION['student_id'];
$errors = [];
$success = "";

$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $profilePicturePath = $student['photo'] ?? 'uploads/default.png';

    if ($firstName === '') $errors[] = "First name is required.";
    if ($lastName === '') $errors[] = "Last name is required.";
    if ($dob === '') $errors[] = "Date of Birth is required.";
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = "Please select a valid Gender.";
    if ($contactNumber === '') $errors[] = "Contact Number is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid Email is required.";
    if ($address === '') $errors[] = "Address is required.";

    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
    $stmt->execute([$email, $student_id]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already taken by another user.";
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        if (strpos($_FILES['profile_picture']['type'], 'image') !== 0) {
            $errors[] = 'Only image files are allowed.';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $newFileName = 'profile_' . $student_id . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profilePicturePath = $targetFile;
            } else {
                $errors[] = 'Failed to upload profile picture.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE students SET first_name = ?, middle_name = ?, last_name = ?, dob = ?, gender = ?, contact_number = ?, email = ?, address = ?, photo = ? WHERE student_id = ?");
        $stmt->execute([
            $firstName,
            $middleName,
            $lastName,
            $dob,
            $gender,
            $contactNumber,
            $email,
            $address,
            $profilePicturePath,
            $student_id
        ]);


        header("Location: /IT2C_Enrollment_System_SourceCode/student/student-dashboard.php?updated=1");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e8f5e9;
      min-height: 100vh;
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

    .profile-img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #43a047;
      cursor: pointer;
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
    <h2 class="text-success mb-4 text-center">Edit Profile</h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data"
      class="bg-white p-4 rounded shadow-sm mx-auto" style="max-width: 600px;">
      
      <div class="mb-4 text-center">
        <label for="profilePicture">
          <img src="<?= htmlspecialchars($student['photo']) ?>" class="profile-img mb-3" id="profilePreview" title="Click to change profile picture" />
        </label>
        <input type="file" id="profilePicture" name="profile_picture" accept="image/*" class="d-none"
          onchange="previewProfilePic(event)" />
      </div>

          <div class="form-group">
          <label for="firstName">First Name</label>
          <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" class="form-control" name="middle_name" value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="lastName">Last Name</label>
          <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
        </div>

      <div class="mb-3">
        <label for="dob" class="form-label">Date of Birth</label>
        <input type="date" class="form-control" id="dob" name="dob" required
          value="<?= htmlspecialchars($student['dob'] ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label for="gender" class="form-label">Gender</label>
        <select class="form-select" id="gender" name="gender" required>
          <option value="" <?= ($student['gender'] ?? '') == '' ? 'selected' : '' ?>>Select Gender</option>
          <option value="Male" <?= ($student['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($student['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= ($student['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="contact" class="form-label">Contact Number</label>
        <input type="tel" class="form-control" id="contact" name="contact_number" required
          value="<?= htmlspecialchars($student['contact_number'] ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required
          value="<?= htmlspecialchars($student['email'] ?? '') ?>" />
      </div>

      <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3"
          required><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-success w-100">Save Changes</button>
    </form>
  </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function previewProfilePic(event) {
    const reader = new FileReader();
    reader.onload = function () {
      document.getElementById('profilePreview').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
  }

  <?php if ($success): ?>
    Swal.fire({
      toast: true,
      icon: 'success',
      title: <?= json_encode($success) ?>,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      background: '#e8f5e9',
      iconColor: '#2e7d32',
    });
  <?php endif; ?>
</script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>