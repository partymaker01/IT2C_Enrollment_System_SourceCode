<?php
session_start();
require_once '../db.php';

$error = "";
$success = "";

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header("Location: ../student/student-dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT student_id, first_name FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (student_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['student_id'], $token, $expires]);
            
            // In a real application, send email with reset link
            // For demo purposes, we'll just show the link
            $reset_link = "reset-password.php?token=" . $token;
            
            $success = "Password reset link has been sent to your email address. The link will expire in 1 hour.<br><br>
                       <strong>Demo link:</strong> <a href='$reset_link'>$reset_link</a>";
        } else {
            // Don't reveal if email exists or not for security
            $success = "If your email exists in our system, you will receive a password reset link shortly.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Top Link Global College</title>
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

        .forgot-password-container {
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
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="school-header">
            <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
            <div class="school-name">Top Link Global College</div>
            <div class="school-subtitle">Student Portal</div>
        </div>

        <h3 class="form-title">Forgot Password</h3>

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
        <?php else: ?>
            <form method="POST" action="">
                <div class="input-group">
                    <i class="bi bi-envelope"></i>
                    <input type="email" class="form-control" name="email" placeholder="Enter your email address" required>
                </div>

                <p class="text-muted mb-4">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-send me-2"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="btn btn-back d-block">
            <i class="bi bi-arrow-left me-2"></i> Back to Login
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
