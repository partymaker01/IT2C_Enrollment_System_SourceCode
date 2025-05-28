<?php
session_start();

$servername = "localhost";
$db_username = "your_db_user";
$db_password = "your_db_pass";
$dbname = "enrollment_system";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";
$show_form = false;

$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Invalid or missing token.";
} else {
    // Validate token and check expiry
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        $error = "Invalid token.";
    } else {
        $stmt->bind_result($user_id, $expires_at);
        $stmt->fetch();
        $stmt->close();

        if (strtotime($expires_at) < time()) {
            $error = "Token has expired.";
        } else {
            $show_form = true;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$new_password || !$confirm_password) {
        $error = "Please fill all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            // Delete used token
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $success = "Password has been reset successfully! You can now <a href='login.php'>login</a>.";
            $show_form = false;
        } else {
            $error = "Error resetting password, please try again.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password</title>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'/>
<link rel="stylesheet" href="/IT2C_Enrollment_System_SourceCode/CSS/style.css">
<link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
  <div class="container">
    <div class="form-box forgot-password">
      <form action="send-reset.php" method="POST">
          <h1>Forgot Password</h1>
        <div class="input-box">
          <input type="email" name="email" placeholder="Enter your email" required />
          <i class='bx bxs-envelope'></i>
        </div>
        <p>We will send a reset link to your email.</p>
        <button type="submit" class="btn">Send Link</button>
        <button type="button" class="btn login-btn" onclick="window.location.href='login.php'">Back to Login</button>
      </form>
    </div>
  </div>
</body>