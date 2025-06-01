<?php
include '../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$message = "";
$alert_type = "info";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_name = trim($_POST["student_name"] ?? '');
    $first_name = trim($_POST["first_name"] ?? '');
    $last_name = trim($_POST["last_name"] ?? '');
    $year_level = trim($_POST["year_level"] ?? '');
    $course = trim($_POST["course"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $raw_password = trim($_POST["password"] ?? '');

    if ($student_number && $first_name && $last_name && $year_level && $course && $email && $raw_password) {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        $verification_code = bin2hex(random_bytes(16));

        $check = $conn->prepare("SELECT id FROM students WHERE student_name = ? OR email = ?");
        $check->bind_param("ss", $student_name, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "❌ Student Number or Email already exists.";
            $alert_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO students (student_number, first_name, last_name, year_level, course, email, password, verification_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $student_number, $first_name, $last_name, $year_level, $course, $email, $password, $verification_code);

            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'youremail@gmail.com';   // ← Palitan
                    $mail->Password = 'your_app_password';     // ← Palitan
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('youremail@gmail.com', 'Enrollment System');
                    $mail->addAddress($email, $first_name);
                    $mail->isHTML(true);
                    $mail->Subject = 'Email Verification';
                    $mail->Body = "Hi $first_name,<br><br>Click the link below to verify your account:<br><a href='http://localhost/IT2C_Enrollment_System_SourceCode/verify.php?code=$verification_code'>Verify Email</a>";

                    $mail->send();
                    $message = "✅ Registration successful! Please check your email.";
                    $alert_type = "success";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000);
                    </script>";
                } catch (Exception $e) {
                    $message = "Mailer Error: {$mail->ErrorInfo}";
                    $alert_type = "error";
                }
            } else {
                $message = "❌ Registration failed. Please try again.";
                $alert_type = "error";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $message = "⚠️ Please fill out all required fields.";
        $alert_type = "warning";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: linear-gradient(135deg, #d4f5e9, #a4d4ae);
      min-height: 100vh;
    }
    .card {
      border: none;
      border-radius: 16px;
    }
    .btn-success {
      background-color: #28a745;
      border: none;
    }
    .form-control {
      border-radius: 12px;
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

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow p-4">
          <h3 class="text-center text-success mb-4">Student Registration</h3>
          <form method="POST" action="register.php">
            <input type="text" name="student_number" class="form-control mb-2" placeholder="Student Number" required />
            <input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" required />
            <input type="text" name="last_name" class="form-control mb-2" placeholder="Last Name" required />
            <input type="text" name="year_level" class="form-control mb-2" placeholder="Year Level" required />
            <input type="text" name="course" class="form-control mb-2" placeholder="Course" required />
            <input type="email" name="email" class="form-control mb-2" placeholder="Email" required />
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required />
            <button type="submit" class="btn btn-success w-100">Register</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
