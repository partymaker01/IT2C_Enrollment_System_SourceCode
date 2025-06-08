<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit();
}

require_once '../../db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Fetch current user data
        $stmt = $pdo->prepare("SELECT password FROM students WHERE student_id = ?");
        $stmt->execute([$_SESSION['student_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "User not found.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $updateStmt->execute([$new_hashed_password, $_SESSION['student_id']]);
            
            $success = "Password successfully changed!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --accent-green: #43a047;
            --hover-green: #c8e6c9;
            --dark-green: #1b5e20;
        }

        body {
            background: linear-gradient(135deg, var(--light-green) 0%, #f1f8e9 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.3);
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-link:hover {
            color: var(--hover-green) !important;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid #fff;
        }

        .form-container {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            padding: 2.5rem;
            max-width: 500px;
            margin: 2rem auto;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .form-title {
            color: var(--primary-green);
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green), var(--primary-green));
            border-radius: 2px;
        }

        .form-label {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
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

        .btn-update {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        .input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }

        .security-tips {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .security-tips h6 {
            color: #856404;
            font-weight: 700;
        }

        .security-tips ul {
            color: #856404;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../student-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <span>Student Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../student-dashboard.php">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="form-container w-100">
            <h3 class="form-title">
                <i class="bi bi-shield-lock-fill me-2"></i>
                Change Password
            </h3>

            <!-- Security Tips -->
            <div class="security-tips">
                <h6><i class="bi bi-info-circle me-2"></i>Password Security Tips</h6>
                <ul class="small">
                    <li>Use at least 8 characters</li>
                    <li>Include uppercase and lowercase letters</li>
                    <li>Add numbers and special characters</li>
                    <li>Avoid common words or personal information</li>
                </ul>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="passwordForm">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">
                        <i class="bi bi-lock me-2"></i>Current Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                            <i class="bi bi-eye" id="currentPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="newPassword" class="form-label">
                        <i class="bi bi-key me-2"></i>New Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                            <i class="bi bi-eye" id="newPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">
                        <i class="bi bi-check-circle me-2"></i>Confirm New Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                            <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="mt-2" id="passwordMatch"></div>
                </div>

                <button type="submit" class="btn-update">
                    <i class="bi bi-shield-check me-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + 'Icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('At least 8 characters');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Lowercase letter');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Uppercase letter');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Number');

            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('Special character');

            return { strength, feedback };
        }

        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }

            const { strength, feedback } = checkPasswordStrength(password);
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 3) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
            } else if (strength < 5) {
                strengthText = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Password Strength: ${strengthText}</span>`;
            
            if (feedback.length > 0) {
                strengthDiv.innerHTML += `<br><small>Missing: ${feedback.join(', ')}</small>`;
            }
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</small>';
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
