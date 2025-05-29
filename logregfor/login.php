<?php
session_start();
include '../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$message = "";
$alert_type = "info";

// ✅ Define $action to prevent undefined variable warning
$action = $_POST['form_action'] ?? '';

if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $account_type = $_POST['account_type'] ?? '';

    if (!$username || !$password || !$account_type) {
        $message = "Please fill all the fields.";
        $alert_type = "warning";
    } else {
        if ($account_type === "admin") {
            $stmt = $conn->prepare("SELECT * FROM admin_settings WHERE email = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['role'] = "admin";
                    header("Location: ../admin/dashboard.php");
                    exit;
                } else {
                    $message = "Incorrect password.";
                    $alert_type = "error";
                }
            } else {
                $message = "Admin account not found.";
                $alert_type = "error";
            }
        } elseif ($account_type === "student") {
            $stmt = $conn->prepare("SELECT * FROM students WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if ($user['is_verified'] != 1) {
                    $message = "Please verify your email first.";
                    $alert_type = "warning";
                } elseif (password_verify($password, $user['password'])) {
                    $_SESSION['student_id'] = $user['id'];
                    $_SESSION['student_username'] = $user['username'];
                    $_SESSION['student_number'] = $user['student_number'];
                    $_SESSION['student_logged_in'] = true;
                    $_SESSION['role'] = "student";
                    header("Location: ../student/student-dashboard.php");
                    exit;
                } else {
                    $message = "Incorrect password.";
                    $alert_type = "error";
                }
            } else {
                $message = "Account not found.";
                $alert_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login/Register/Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'/>
  <link rel="stylesheet" href="/IT2C_Enrollment_System_SourceCode/CSS/style.css">
  <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .container {
      max-width: 1100px !important;
      height: 650px;
      margin: auto;
    }
    .form-box.forgot-password form button {
      display: block;
      width: 100%;
      margin-bottom: 10px;
    }
    .form-box form {
      overflow-y: auto;
      max-height: 85vh;
      padding-bottom: 20px;
    }
@media screen and (max-width: 650px) {
  .form-box {
    height: auto;
    max-height: 85vh;
    padding: 20px;
    overflow-y: auto;
  }

  .form-box form {
    max-height: none;
  }

  .form-box.register form {
    max-height: 70vh;
    overflow-y: auto;
  }

  .form-box.forgot-password {
    height: auto;
    max-height: 70vh;
    overflow-y: auto;
  }
}
</style>
</head>
<body>
  <?php if ($message): ?>
    <script>
      Swal.fire({
        icon: '<?= $alert_type ?>',
        html: <?= json_encode($message) ?>,
        timer: 3000,
        showConfirmButton: false
      });
    </script>
  <?php endif; ?>

  <div class="container">
    <!-- Login Form -->
    <div class="form-box login">
      <form method="POST" action="">
        <h1>Login</h1>
        <input type="hidden" name="form_action" value="login" />
        <div class="input-box">
          <input type="text" name="username" placeholder="Username" required />
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required />
          <i class='bx bxs-lock-alt'></i>
        </div>
        <div class="input-box">
          <select name="account_type" required>
            <option value="">Select Account Type</option>
            <option value="admin">Admin</option>
            <option value="student">Student</option>
          </select>
          <i class='bx bxs-user'></i>
        </div>
        <div class="forgot-link">
          <a href="#" id="forgotLink">Forgot password?</a>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
    </div>

    <!-- Register Form -->
    <div class="form-box register">
      <form method="POST" action="">
        <h1>Registration</h1>
        <input type="hidden" name="form_action" value="register" />
          <div class="input-box">
          <input type="text" name="username" placeholder="Username" required />
        </div>
        <!-- <div class="input-box">
          <input type="text" name="student_ID" placeholder="Student ID" required />
        </div> -->
        <div class="input-box">
          <input type="text" name="first_name" placeholder="First Name" required />
          <div class="input-box">
          <input type="text" name="middle_name" placeholder="Middle Name" required />
        </div>
        </div>
        <div class="input-box">
          <input type="text" name="last_name" placeholder="Last Name" required />
        </div>
        <!-- <div class="input-box">
          <input type="text" name="year_level" placeholder="Year Level" required />
        </div>
        <div class="input-box">
          <input type="text" name="course" placeholder="Course" required />
        </div> -->
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" required />
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required />
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password Verification" required />
        </div>
        <button type="submit" class="btn">Register</button>
      </form>
    </div>

    <!-- Forgot Password Form -->
    <div class="form-box forgot-password">
      <form action="send-reset.php" method="POST">
        <h1>Forgot Password</h1>
        <div class="input-box">
          <input type="email" name="email" placeholder="Enter your email" required />
        </div>
        <p>We will send a reset link to your email.</p>
        <button type="submit" class="btn">Send Link</button>
        <button type="button" class="btn login-btn">Back to Login</button>
      </form>
    </div>

    <!-- Panel Toggle -->
    <div class="toggle-box">
      <div class="toggle-panel toggle-left">
        <h1>Hello, Welcome!</h1>
        <p>Don't have an account?</p>
        <button class="btn register-btn">Register</button>
      </div>
      <div class="toggle-panel toggle-right">
        <h1>Welcome Back!</h1>
        <p>Already have an account?</p>
        <button class="btn login-btn">Login</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/IT2C_Enrollment_System_SourceCode/JS/script.js"></script>
</body>
</html>