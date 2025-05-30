<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: admin-dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db.php';

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: admin-dashboard.php");
            exit;
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Admin not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
  body {
    background-color: #e6e6e6;
    font-family: 'Segoe UI', sans-serif;
}

.container {
    display: flex;
    height: 100vh;
    justify-content: center;
    align-items: center;
}

.login-box {
    width: 900px;
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
    border-radius: 25px;
    overflow: hidden;
    background: white;
}

.left-panel {
    background: #2e7d32;
    color: white;
    width: 50%;
    padding: 50px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border-top-left-radius: 25px;
    border-bottom-left-radius: 25px;
}

.left-panel h2 {
    font-size: 32px;
    margin-bottom: 15px;
}

.right-panel {
    width: 50%;
    padding: 50px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right-panel h2 {
    font-size: 28px;
    margin-bottom: 20px;
    text-align: center;
}

.input-box {
    position: relative;
    margin-bottom: 20px;
}

.input-box input {
    width: 100%;
    padding: 12px 40px 12px 15px;
    border: 1px solid #ccc;
    border-radius: 10px;
}

.input-box i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #777;
    cursor: pointer;
}

.btn-login {
    background: #5c6bc0;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
}

.btn-login:hover {
    background: #3f51b5;
}

.forgot {
    font-size: 13px;
    text-align: right;
    display: block;
    margin-bottom: 10px;
    color: #888;
    text-decoration: none;
}
</style>
<body>
<div class="container">
    <div class="login-box">
        <div class="left-panel">
            <h2>Welcome Admin!</h2>
            <p>Use your admin credentials to access the dashboard.</p>
        </div>
        <div class="right-panel">
            <h2>Admin Login</h2>
            <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" novalidate>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" class="form-control" required>
                    <i class="bi bi-person"></i>
                    <div class="invalid-feedback">Please enter your username.</div>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" class="form-control" required id="admin-password">
                    <i class="bi bi-lock" id="togglePassword"></i>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
                <!-- Uncomment if you already have forgot-password.php -->
                <!-- <a href="forgot-password.php" class="forgot">Forgot password?</a> -->
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Bootstrap validation
(() => {
  'use strict'
  const forms = document.querySelectorAll('form');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})();

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function () {
  const passwordInput = document.getElementById('admin-password');
  const icon = this;
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  icon.classList.toggle('bi-lock');
  icon.classList.toggle('bi-unlock');
});
</script>
</body>
</html>
