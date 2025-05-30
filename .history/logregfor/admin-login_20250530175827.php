<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
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
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
