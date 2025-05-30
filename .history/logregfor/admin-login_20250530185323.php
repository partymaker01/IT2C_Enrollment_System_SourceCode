<?php
session_start();
include '../db.php'; // adjust the path if needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM admin_settings WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../ADMIN/admin-dashboard.php");
    exit();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: 'Invalid admin username or password',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href='admin-login.php';
            });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- Use your existing login CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* You can remove this if already in your style.css */
        .container {
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            background: #e6e6e6;
            display: contents;
        }
        .login-box {
            display: flex;
            width: 900px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            border-radius: 25px;
            overflow: hidden;
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
            background: white;
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
</head>
<body>
<div class="container">
    <div class="login-box">
        <div class="left-panel">
            <h2>Welcome Admin!</h2>
            <p>Use your admin credentials to access the dashboard.</p>
        </div>
        <div class="right-panel">
            <h2>Admin Login</h2>
            <form method="POST">
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="bi bi-person"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="bi bi-lock"></i>
                </div>
                <!-- Optional: Uncomment if you want a Forgot Password link -->
                <!-- <a href="#" class="forgot">Forgot password?</a> -->
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>