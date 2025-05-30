<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../ADMIN/admin-dashboard.php");
    exit();
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
            <form action="admin-process-login.php" method="POST">
    <input type="text" name="username" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
        </div>
    </div>
</div>
</body>
</html>