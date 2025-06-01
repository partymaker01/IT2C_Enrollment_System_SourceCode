<?php
session_start();
include '../db.php';

$message = '';
$alert_type = 'info';

if (isset($_SESSION['student_id'])) {
    header("Location: htdocs/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php");
    exit;
}

// Registration logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_verify = $_POST['password_verify'] ?? $password;

    if ($username && $first_name && $middle_name && $last_name && $email && $password && $password_verify) {
        if ($password !== $password_verify) {
            $message = "Passwords do not match.";
            $alert_type = "error";
        } else {
            $stmt = $conn->prepare("SELECT id FROM students WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Username or email already exists.";
                $alert_type = "error";
            } else {
                $student_id = uniqid('SN'); // ✅ correct field name
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO students (student_id, username, first_name, middle_name, last_name, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $student_id, $username, $first_name, $middle_name, $last_name, $email, $hashed_password);

                if ($stmt->execute()) {
                    $message = "Registration successful! You may now log in.";
                    $alert_type = "success";
                } else {
                    $message = "Registration failed. Please try again.";
                    $alert_type = "error";
                }
                $stmt->close();
            }
        }
    } else {
        $message = "Please fill in all fields.";
        $alert_type = "warning";
    }
}

// Login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT student_id, password FROM students WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($student_id, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['student_id'] = $student_id;
                header("Location: ../student/student-dashboard.php");
                exit;
            } else {
                $message = "Incorrect password.";
                $alert_type = "error";
            }
        } else {
            $message = "Username not found.";
            $alert_type = "error";
        }

        $stmt->close();
    } else {
        $message = "Please enter both username and password.";
        $alert_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login/Register/ForgotPassword - For Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'/>
  <link rel="stylesheet" href="/IT2C_Enrollment_System_SourceCode/CSS/style.css">
  <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .container { max-width: 1100px !important; height: 650px; margin: auto; }
    .form-box.forgot-password form button { display: block; width: 100%; margin-bottom: 10px; }
    .form-box form { overflow-y: auto; max-height: 85vh; padding-bottom: 20px; }
    @media screen and (max-width: 650px) {
      .form-box { height: auto; max-height: 85vh; padding: 20px; overflow-y: auto; }
      .form-box form { max-height: none; }
      .form-box.register form { max-height: 70vh; overflow-y: auto; }
      .form-box.forgot-password { height: auto; max-height: 70vh; overflow-y: auto; }
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
      <form method="POST" action=";login.php">
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
        <div class="forgot-link">
          <a href="#" id="forgotLink">Forgot password?</a>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
    </div>

    <!-- Register Form -->
    <div class="form-box register">
      <form method="POST" action="login.php">
        <h1>Registration</h1>
        <input type="hidden" name="form_action" value="register" />
        <div class="input-box"><input type="text" name="username" placeholder="Username" required /></div>
        <div class="input-box"><input type="text" name="first_name" placeholder="First Name" required /></div>
        <div class="input-box"><input type="text" name="middle_name" placeholder="Middle Name" required /></div>
        <div class="input-box"><input type="text" name="last_name" placeholder="Last Name" required /></div>
        <div class="input-box"><input type="email" name="email" placeholder="Email" required /></div>
        <div class="input-box"><input type="password" name="password" placeholder="Password" required /></div>
        <div class="input-box"><input type="password" name="password_verify" placeholder="Password Verification" required /></div>
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