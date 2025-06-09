<?php
session_start();
require_once '../db.php';

$error = "";
$success = "";
$show_form = false;

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header("Location: ../student/student-dashboard.php");
    exit;
}

$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Invalid or missing token.";
} else {
    try {
        // Validate token and check expiry
        $stmt = $pdo->prepare("SELECT email, student_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = "Invalid token. The reset link may have been used already or is incorrect.";
        } else {
            if (strtotime($reset['expires_at']) < time()) {
                $error = "This password reset link has expired. Please request a new one.";
            } else {
                $show_form = true;
            }
        }
    } catch (PDOException $e) {
        error_log("Password reset token validation error: " . $e->getMessage());
        $error = "An error occurred while processing your request. Please try again later.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();
            
            // Update the password - use email if student_id is not available
            if (!empty($reset['student_id'])) {
                $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $stmt->execute([$hashed_password, $reset['student_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $reset['email']]);
            }
            
            // Delete the used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            $pdo->commit();
            
            $success = "Your password has been reset successfully! You can now <a href='login.php'>login</a> with your new password.";
            $show_form = false;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Password reset error: " . $e->getMessage());
            $error = "Error resetting password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Top Link Global College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --accent-green: #43a047;
            --hover-green: #c8e6c9;
            --dark-green: #1b5e20;
        }

        body {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reset-password-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            padding: 3rem;
        }

        .school-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .school-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--accent-green);
            margin-bottom: 1rem;
        }

        .school-name {
            color: var(--primary-green);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .school-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .form-title {
            color: var(--primary-green);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.25rem rgba(67, 160, 71, 0.25);
            background-color: #fff;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .btn-back {
            background: transparent;
            border: 2px solid var(--accent-green);
            color: var(--accent-green);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-back:hover {
            background: var(--accent-green);
            color: white;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 10;
        }

        .input-group .form-control {
            padding-left: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="school-header">
            <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
            <div class="school-name">Top Link Global College</div>
            <div class="school-subtitle">Student Portal</div>
        </div>

        <h3 class="form-title">Reset Password</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($show_form): ?>
            <form method="POST" action="">
                <div class="input-group">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required minlength="6">
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="bi bi-eye-slash" id="password-toggle-icon"></i>
                    </span>
                </div>

                <div class="input-group">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="bi bi-eye-slash" id="confirm-password-toggle-icon"></i>
                    </span>
                </div>

                <div class="mb-4">
                    <div class="password-strength">
                        <small class="text-muted">Password must be at least 6 characters long.</small>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-circle me-2"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="btn btn-back d-block">
            <i class="bi bi-arrow-left me-2"></i> Back to Login
        </a>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
