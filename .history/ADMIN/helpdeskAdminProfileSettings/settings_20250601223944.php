<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: ../logregfor/login.php");
  exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "enrollment_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Fetch admin settings (assuming id=1 for the admin)
$sql = "SELECT * FROM admin_settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows === 1) {
  $settings = $result->fetch_assoc();
} else {
  die("Admin settings not found.");
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $conn->real_escape_string(trim($_POST['adminName']));
  $email = filter_var($_POST['adminEmail'], FILTER_SANITIZE_EMAIL);
  $email_notify = isset($_POST['emailNotify']) ? 1 : 0;
  $sms_notify = isset($_POST['smsNotify']) ? 1 : 0;
  $maintenance_mode = isset($_POST['maintenanceMode']) ? 1 : 0;
  $auto_backup = isset($_POST['autoBackup']) ? 1 : 0;

  // Password change handling
  if (!empty($_POST['currentPassword']) && !empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
    if (password_verify($_POST['currentPassword'], $settings['password'])) {
      if ($_POST['newPassword'] === $_POST['confirmPassword']) {
        $newPasswordHash = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
      } else {
        $error = "New password and confirmation do not match.";
      }
    } else {
      $error = "Current password is incorrect.";
    }
  }

  if (empty($error)) {
    // Build update query
    $update_sql = "UPDATE admin_settings SET 
      name = '$name',
      email = '$email',
      email_notify = $email_notify,
      sms_notify = $sms_notify,
      maintenance_mode = $maintenance_mode,
      auto_backup = $auto_backup";

    if (isset($newPasswordHash)) {
      $update_sql .= ", password = '" . $conn->real_escape_string($newPasswordHash) . "'";
      $success = "Password and settings updated successfully!";
    } else {
      $success = "Settings updated successfully!";
    }

    $update_sql .= " WHERE id = 1";

    if ($conn->query($update_sql) === TRUE) {
      // Refresh $settings with updated data
      $settings = [
        "name" => $name,
        "email" => $email,
        "email_notify" => $email_notify,
        "sms_notify" => $sms_notify,
        "maintenance_mode" => $maintenance_mode,
        "auto_backup" => $auto_backup,
        "password" => $settings['password'] // Keep current or new password
      ];
    } else {
      $error = "Error updating settings: " . $conn->error;
    }
  }
}

$conn->close();
?>

<!-- Your HTML form below unchanged except for PHP variables -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #e8f5e9, #f1f8e9);
      min-height: 100vh;
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar {
      background-color: #2e7d32;
    }
    .navbar-brand, .nav-link {
      color: #fff !important;
      font-weight: 600;
    }
    .navbar-brand img {
      height: 50px;
      width: 50px;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid white;
    }
    .card {
      background: #fff;
      padding: 2rem;
      border-radius: 1rem;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    h2 {
      color: #2e7d32;
    }
    section h5 {
      border-left: 4px solid #2e7d32;
      padding-left: 10px;
      margin-bottom: 1rem;
      color: #388e3c;
    }
    .form-check-label {
      font-weight: 500;
    }
    .btn-primary {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-primary:hover {
      background-color: #1b5e20;
    }
    .alert {
      max-width: 800px;
      margin: 20px auto;
    }
  </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
      Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
      </ul>
      </div>
    </div>
  </nav>

  <main class="py-4">
    <div class="card">
      <h2 class="text-center mb-4">Admin Settings</h2>

      <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center">
          <i class="bi bi-check-circle-fill me-2"></i>
          <div><?= htmlspecialchars($success) ?></div>
        </div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger d-flex align-items-center">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <section class="mb-4">
          <h5><i class="bi bi-person-circle me-2"></i>Profile Information</h5>
          <div class="mb-3">
            <label for="adminName" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="adminName" name="adminName" value="<?= htmlspecialchars($settings['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="adminEmail" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="adminEmail" name="adminEmail" value="<?= htmlspecialchars($settings['email']) ?>" required>
          </div>
        </section>

        <section class="mb-4">
          <h5><i class="bi bi-key-fill me-2"></i>Change Password</h5>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="currentPassword" class="form-label">Current Password</label>
              <input type="password" class="form-control" id="currentPassword" name="currentPassword">
            </div>
            <div class="col-md-4">
              <label for="newPassword" class="form-label">New Password</label>
              <input type="password" class="form-control" id="newPassword" name="newPassword">
            </div>
            <div class="col-md-4">
              <label for="confirmPassword" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
            </div>
          </div>
        </section>

        <section class="mb-4">
          <h5><i class="bi bi-bell-fill me-2"></i>Notification Preferences</h5>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="emailNotify" name="emailNotify" <?= $settings['email_notify'] ? 'checked' : '' ?> />
            <label class="form-check-label" for="emailNotify">Email Notifications</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="smsNotify" name="smsNotify" <?= $settings['sms_notify'] ? 'checked' : '' ?> />
            <label class="form-check-label" for="smsNotify">SMS Notifications</label>
          </div>
        </section>

        <section class="mb-4">
          <h5><i class="bi bi-gear-fill me-2"></i>System Settings</h5>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenanceMode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?> />
            <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="autoBackup" name="autoBackup" <?= $settings['auto_backup'] ? 'checked' : '' ?> />
            <label class="form-check-label" for="autoBackup">Automatic Backups</label>
          </div>
        </section>

        <div class="text-end">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Changes
          </button>
        </div>
      </form>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
