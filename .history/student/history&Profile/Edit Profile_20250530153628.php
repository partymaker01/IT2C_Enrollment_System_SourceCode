<?php
session_start();

// Replace with your actual DB credentials
$host = 'localhost';
$dbname = 'enrollment_system';
$username = 'your_username';
$password = 'your_password';

// Connect to database using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Assume student is logged in and we have student ID in session
if (!isset($_SESSION['student_id'])) {
    die("You must be logged in to access this page.");
}

$student_id = $_SESSION['student_id'];
$errors = [];
$success = "";

// Fetch current student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student record not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $profilePicturePath = $student['profile_picture']; // Default to existing pic

    // Validation
    if ($fullName === '') $errors[] = "Full Name is required.";
    if ($dob === '') $errors[] = "Date of Birth is required.";
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = "Please select a valid Gender.";
    if ($contactNumber === '') $errors[] = "Contact Number is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid Email is required.";
    if ($address === '') $errors[] = "Address is required.";

    // Check if email already exists for other students
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$email, $student_id]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already taken by another user.";
    }

    // Profile picture upload handling
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed for the profile picture.';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $newFileName = 'profile_' . $student_id . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profilePicturePath = $targetFile;
            } else {
                $errors[] = 'Failed to upload profile picture.';
            }
        }
    }

    // If no errors, update the record
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, dob = ?, gender = ?, contact_number = ?, email = ?, address = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([
            $fullName,
            $dob,
            $gender,
            $contactNumber,
            $email,
            $address,
            $profilePicturePath,
            $student_id
        ]);
        $success = "Profile updated successfully!";

        // Refresh student data from DB after update
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
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
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
        Student Dashboard
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a></li>
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
          <img src="<?= htmlspecialchars($student['profile_picture'] ?: 'uploads/default.png') ?>" alt="Profile Picture"
            class="profile-img mb-3" id="profilePreview" title="Click to change profile picture" />
        </label>
        <input type="file" id="profilePicture" name="profile_picture" accept="image/*" class="d-none"
          onchange="previewProfilePic(event)" />
      </div>

      <div class="mb-3">
        <label for="fullName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="fullName" name="full_name" required
          value="<?= htmlspecialchars($student['full_name']) ?>" />
      </div>

      <div class="mb-3">
        <label for="dob" class="form-label">Date of Birth</label>
        <input type="date" class="form-control" id="dob" name="dob" required
          value="<?= htmlspecialchars($student['dob']) ?>" />
      </div>

      <div class="mb-3">
        <label for="gender" class="form-label">Gender</label>
        <select class="form-select" id="gender" name="gender" required>
          <option value="" <?= $student['gender'] == '' ? 'selected' : '' ?>>Select Gender</option>
          <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="contact" class="form-label">Contact Number</label>
        <input type="tel" class="form-control" id="contact" name="contact_number" required
          value="<?= htmlspecialchars($student['contact_number']) ?>" />
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" required
          value="<?= htmlspecialchars($student['email']) ?>" />
      </div>

      <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3"
          required><?= htmlspecialchars($student['address']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-success w-100">Save Changes</button>
    </form>
  </div>

  <script>
    function previewProfilePic(event) {
      const reader = new FileReader();
      reader.onload = function () {
        document.getElementById('profilePreview').src = reader.result;
      };
      reader.readAsDataURL(event.target.files[0]);
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
