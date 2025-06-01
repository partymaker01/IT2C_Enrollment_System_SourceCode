<!-- student acoount

username: student
password: student123 -->

<?php
session_start();
include '../db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: ../student/student-dashboard.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$message = "";
$alert_type = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $message = "Please fill all the fields.";
            $alert_type = "warning";
        } else {
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
                    $_SESSION['role'] = 'student';
                    $_SESSION['student_id'] = $user['id'];
                    $_SESSION['student_username'] = $user['username'];
                    $_SESSION['student_number'] = $user['student_number'];
                    header("Location: ../student/student-dashboard.php");
                    exit;
                } else {
                    $message = "Incorrect password.";
                    $alert_type = "error";
                }
            } else {
                $message = "User not found.";
                $alert_type = "error";
            }
        }
    }

    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $raw_password = trim($_POST['password'] ?? '');

        if ($username && $first_name && $middle_name && $last_name && $email && $raw_password) {
            $password = password_hash($raw_password, PASSWORD_DEFAULT);
            $verification_code = bin2hex(random_bytes(16));

            $check = $conn->prepare("SELECT id FROM students WHERE email = ? OR username = ?");
            $check->bind_param("ss", $email, $username);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "Username or Email already exists.";
                $alert_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO students (username, first_name, middle_name, last_name, email, password, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $username, $first_name, $middle_name, $last_name, $email, $password, $verification_code);

                if ($stmt->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = $email;
                        $mail->Password = $password;
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom($email, $first_name . ' ' . $last_name);
                        $mail->addAddress($email, $first_name);
                        $mail->isHTML(true);
                        $mail->Subject = 'Email Verification';
                        $mail->Body = "Hi $first_name,<br><br>Click the link to verify your account:<br><a href='http://localhost/IT2C_Enrollment_System_SourceCode/verify.php?code=$verification_code'>Verify Email</a>";

                        $mail->send();
                        header("Location: login.php?registered=1");
                        exit;
                    } catch (Exception $e) {
                        $message = "Mailer Error: {$mail->ErrorInfo}";
                        $alert_type = "error";
                    }
                } else {
                    $message = "Registration failed.";
                    $alert_type = "error";
                }
                $stmt->close();
            }
            $check->close();
        } else {
            $message = "Please fill out all required fields.";
            $alert_type = "warning";
        }
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
        <div class="input-box"><input type="text" name="username" placeholder="Username" required /></div>
        <div class="input-box"><input type="text" name="first_name" placeholder="First Name" required /></div>
        <div class="input-box"><input type="text" name="middle_name" placeholder="Middle Name" required /></div>
        <div class="input-box"><input type="text" name="last_name" placeholder="Last Name" required /></div>
        <div class="input-box"><input type="email" name="email" placeholder="Email" required /></div>
        <div class="input-box"><input type="password" name="password" placeholder="Password" required /></div>
        <div class="input-box"><input type="password" name="password" placeholder="Password Verification" required /></div>
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
