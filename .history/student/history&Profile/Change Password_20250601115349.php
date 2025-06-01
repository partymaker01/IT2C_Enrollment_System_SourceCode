<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: /IT2C_Enrollment_System_SourceCode/student/login.php");
    exit();
}

$host = "localhost";
$dbname = "enrollment_system";
$username = "root";
$password = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "User not found.";
    } else {
        // Verify current password with password_verify()
        if (!password_verify($current_password, $user['password'])) {
            $error = "Incorrect current password.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            // Hash new password and update DB
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE students SET password = :password WHERE id = :id");
            $updateStmt->execute([
                'password' => $new_hashed_password,
                'id' => $user['id']
            ]);
            $success = "Password successfully changed.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e8f5e9;
      font-family: 'Segoe UI', sans-serif;
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
    .form-container {
      background: #fff;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      max-width: 420px;
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
          <li class="nav-item">
            <a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="form-container w-100">

      <h3 class="text-center text-success mb-4">
        <i class="bi bi-shield-lock-fill me-2"></i>
        Change Password
      </h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" action="">

        <div class="mb-3">
          <label for="currentPassword" class="form-label">Current Password</label>
          <input type="password" class="form-control" id="currentPassword" name="current_password" required />
        </div>

        <div class="mb-3">
          <label for="newPassword" class="form-label">New Password</label>
          <input type="password" class="form-control" id="newPassword" name="new_password" required />
        </div>

        <div class="mb-3">
          <label for="confirmPassword" class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required />
        </div>

        <button type="submit" class="btn btn-success w-100">Update Password</button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
