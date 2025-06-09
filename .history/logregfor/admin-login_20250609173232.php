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

// Input Sanitization Function
function sanitizeInput($input, $type = 'string') {
    if (empty($input)) return '';
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'string':
            return filter_var(trim($input), FILTER_SANITIZE_SPECIAL_CHARS);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Rate limiting for admin login
function checkAdminRateLimit($identifier) {
    $key = 'admin_login_attempts_' . $identifier;
    $attempts = $_SESSION[$key] ?? 0;
    $last_attempt = $_SESSION[$key . '_time'] ?? 0;
    
    // Reset counter if more than 30 minutes have passed (stricter for admin)
    if (time() - $last_attempt > 1800) {
        $_SESSION[$key] = 0;
        $attempts = 0;
    }
    
    return $attempts < 3; // Max 3 attempts for admin
}

function recordAdminAttempt($identifier) {
    $key = 'admin_login_attempts_' . $identifier;
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    $_SESSION[$key . '_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token mismatch. Please try again.";
    } else {
        // Sanitize inputs
        $email = sanitizeInput($_POST['email'] ?? '', 'email');
        $password = $_POST['password'] ?? '';
        
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limiting check (stricter for admin)
        if (!checkAdminRateLimit($client_ip)) {
            $error = "Too many failed login attempts. Please try again in 30 minutes.";
            // Log security incident
            error_log("Admin login rate limit exceeded from IP: " . $client_ip . " at " . date('Y-m-d H:i:s'));
        } elseif (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
            recordAdminAttempt($client_ip);
        } else {
            try {
                // Check in admin_settings table
                $stmt = $pdo->prepare("SELECT id, username, email, password, name, role, status FROM admin_settings WHERE (email = ? OR username = ?) AND status = 'active'");
                $stmt->execute([$email, $email]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // Successful login
                    session_regenerate_id(true); // Prevent session fixation
                    
                    // Set session variables with sanitized data
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['admin_username'] = htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['admin_name'] = htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['admin_role'] = htmlspecialchars($admin['role'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_time'] = time();
                    
                    // Clear rate limiting
                    unset($_SESSION['admin_login_attempts_' . $client_ip]);
                    unset($_SESSION['admin_login_attempts_' . $client_ip . '_time']);
                    
                    // Log successful admin login
                    error_log("Admin login successful: " . $admin['username'] . " from IP: " . $client_ip . " at " . date('Y-m-d H:i:s'));
                    
                    // Generate new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    header("Location: ../ADMIN/admin-dashboard.php");
                    exit;
                } else {
                    $error = "Invalid email/username or password.";
                    recordAdminAttempt($client_ip);
                    
                    // Log failed admin login attempt
                    error_log("Admin login failed for: " . $email . " from IP: " . $client_ip . " at " . date('Y-m-d H:i:s'));
                }
            } catch (PDOException $e) {
                $error = "Login failed. Please try again.";
                error_log("Admin login database error: " . $e->getMessage());
                recordAdminAttempt($client_ip);
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
    <title>Admin Login - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--accent-green);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-align: center;
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
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h3 class="form-title">Admin Login</h3>
        <form method="POST" action="" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="input-group">
                <i class="bi bi-envelope"></i>
                <input type="text" class="form-control" name="email" placeholder="Email or Username" 
                       required autocomplete="username" maxlength="100">
            </div>

            <div class="input-group">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" name="password" placeholder="Password" 
                       required autocomplete="current-password" maxlength="100">
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> 
                For security, admin sessions expire after 30 minutes of inactivity.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Additional client-side security measures
        
        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Disable F12, Ctrl+Shift+I, Ctrl+U
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
            }
        });

        // Clear form on page unload
        window.addEventListener('beforeunload', function() {
            document.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
        });

        // Input sanitization on client side
        document.querySelectorAll('input[type="text"], input[type="email"]').forEach(input => {
            input.addEventListener('input', function() {
                // Remove potentially dangerous characters
                this.value = this.value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                this.value = this.value.replace(/[<>]/g, '');
            });
        });

        // Auto-logout warning
        let warningShown = false;
        setTimeout(function() {
            if (!warningShown) {
                alert('Your session will expire in 5 minutes due to inactivity.');
                warningShown = true;
            }
        }, 25 * 60 * 1000); // 25 minutes
    </script>
</body>
</html>
