<?php
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
            $update->bind_param("si", $newPassword, $user['id']);
            if ($update->execute()) {
                echo "✅ Password has been reset successfully. <a href='login.php'>Login now</a>";
                exit;
            } else {
                echo "❌ Failed to reset password. Try again.";
                exit;
            }
        }
    } else {
        echo "❌ Invalid or expired token.";
        exit;
    }
} else {
    echo "❌ No token provided.";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="/IT2C_Enrollment_System_SourceCode/CSS/style.css">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
  <div class="container">
    <div class="form-box">
      <form method="POST">
        <h1>Reset Password</h1>
        <div class="input-box">
          <input type="password" name="new_password" placeholder="Enter new password" required>
        </div>
        <button type="submit" class="btn">Reset Password</button>
      </form>
    </div>
  </div>
</body>
</html>
