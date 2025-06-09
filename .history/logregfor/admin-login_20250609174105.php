<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../ADMIN/admin-dashboard.php");
    exit;
}

require_once '../db.php';

$error = '';

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Simple input sanitization
function cleanInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Rate limiting
function checkRateLimit($ip) {
    $key = 'admin_attempts_' . $ip;
    $attempts = $_SESSION[$key] ?? 0;
    $last_attempt = $_SESSION[$key . '_time'] ?? 0;
    
    if (time() - $last_attempt > 1800) { // 30 minutes
        $_SESSION[$key] = 0;
        $attempts = 0;
    }
    
    return $attempts < 3;
}

function recordAttempt($ip) {
    $key = 'admin_attempts_' . $ip;
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    $_SESSION[$key . '_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token mismatch. Please refresh and try again.";
    } else {
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!checkRateLimit($client_ip)) {
            $error = "Too many failed attempts. Please try again in 30 minutes.";
        } elseif (empty($email) || empty($password)) {
            $error = "Please enter both email/username and password.";
            recordAttempt($client_ip);
        } else {
            try {
                // Query using your exact table structure
                $stmt = $pdo->prepare("SELECT id, username, email, password, name, role, is_active, phone, department FROM admin_settings WHERE (email = ? OR username = ?) AND is_active = 1");
                $stmt->execute([$email, $email]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Successful login
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_phone'] = $admin['phone'];
                    $_SESSION['admin_department'] = $admin['department'];
                    $_SESSION['last_activity'] = time();
                    
                    // Clear rate limiting
                    unset($_SESSION['admin_attempts_' . $client_ip]);
                    unset($_SESSION['admin_attempts_' . $client_ip . '_time']);
                    
                    // Update last login and last activity
                    try {
                        $updateStmt = $pdo->prepare("UPDATE admin_settings SET last_login = NOW(), last_activity = NOW() WHERE id = ?");
                        $updateStmt->execute([$admin['id']]);
                    } catch (Exception $e) {
                        // Continue even if update fails
                        error_log("Failed to update admin last login: " . $e->getMessage());
                    }
                    
                    // Generate new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    header("Location: ../ADMIN/admin-dashboard.php");
                    exit;
                } else {
                    $error = "Invalid email/username or password.";
                    recordAttempt($client_ip);
                }
            } catch (PDOException $e) {
                error_log("Admin login error: " . $e->getMessage());
                $error = "Login failed. Please try again.";
                recordAttempt($client_ip);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Top Link Global College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        :root {
            --primary-green: rgb(60, 109, 61);
            --light-green: #e8f5e9;
            --accent-green: #43a047;
            --hover-green: #c8e6c9;
            --dark-green: #1b5e20;
        }

        body {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
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

        .btn-login {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
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

        .security-notice {
            background: #e8f5e9;
            border: 1px solid var(--accent-green);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-align: center;
            color: var(--dark-green);
        }

        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="school-header">
            <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
            <div class="school-name">Top Link Global College</div>
            <div class="school-subtitle">Admin Portal</div>
        </div>

        <div class="security-notice">
            <i class="bi bi-shield-lock"></i> Secure Admin Access
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h3 class="form-title">Admin Login</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="input-group">
                <i class="bi bi-envelope"></i>
                <input type="text" class="form-control" name="email" placeholder="Email or Username" required maxlength="100">
            </div>

            <div class="input-group">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" name="password" placeholder="Password" required maxlength="100">
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> 
                Contact IT support if you need access
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
