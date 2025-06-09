<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: ../student/student-dashboard.php");
    exit;
}

require_once '../db.php';

$message = '';
$messageType = '';

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
        case 'username':
            // Allow only alphanumeric, underscore, and hyphen
            return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($input));
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
        preg_match('/[A-Z]/', $password) && 
        preg_match('/[a-z]/', $password) && 
        preg_match('/[0-9]/', $password);
}

function validateName($name) {
    return preg_match('/^[a-zA-Z\s\'-]{2,50}$/', $name);
}

// Rate limiting (simple implementation)
function checkRateLimit($identifier) {
    $key = 'login_attempts_' . $identifier;
    $attempts = $_SESSION[$key] ?? 0;
    $last_attempt = $_SESSION[$key . '_time'] ?? 0;
    
    // Reset counter if more than 15 minutes have passed
    if (time() - $last_attempt > 900) {
        $_SESSION[$key] = 0;
        $attempts = 0;
    }
    
    return $attempts < 5; // Max 5 attempts
}

function recordAttempt($identifier) {
    $key = 'login_attempts_' . $identifier;
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    $_SESSION[$key . '_time'] = time();
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Security token mismatch. Please try again.";
        $messageType = "danger";
    } else {
        // Sanitize inputs
        $username = sanitizeInput($_POST['username'] ?? '', 'username');
        $firstName = sanitizeInput($_POST['first_name'] ?? '', 'string');
        $middleName = sanitizeInput($_POST['middle_name'] ?? '', 'string');
        $lastName = sanitizeInput($_POST['last_name'] ?? '', 'string');
        $email = sanitizeInput($_POST['email'] ?? '', 'email');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Comprehensive Validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!validateUsername($username)) {
            $errors[] = "Username must be 3-20 characters and contain only letters, numbers, underscore, or hyphen.";
        }

        if (empty($firstName)) {
            $errors[] = "First name is required.";
        } elseif (!validateName($firstName)) {
            $errors[] = "First name contains invalid characters.";
        }

        if (empty($lastName)) {
            $errors[] = "Last name is required.";
        } elseif (!validateName($lastName)) {
            $errors[] = "Last name contains invalid characters.";
        }

        if (!empty($middleName) && !validateName($middleName)) {
            $errors[] = "Middle name contains invalid characters.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (!validatePassword($password)) {
            $errors[] = "Password must be at least 8 characters with uppercase, lowercase, and number.";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }

        // Check if username or email already exists
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT student_id FROM students WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $errors[] = "Username or email already exists.";
                }
            } catch (PDOException $e) {
                error_log("Registration check error: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }

        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO students (username, first_name, middle_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $firstName, $middleName, $lastName, $email, $hashedPassword]);
                
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
                
                // Generate new CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }

        if (!empty($errors)) {
            $message = implode('<br>', array_map('htmlspecialchars', $errors));
            $messageType = "danger";
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Security token mismatch. Please try again.";
        $messageType = "danger";
    } else {
        // Sanitize inputs
        $username = sanitizeInput($_POST['username'] ?? '', 'string');
        $password = $_POST['password'] ?? '';
        
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limiting check
        if (!checkRateLimit($client_ip)) {
            $message = "Too many login attempts. Please try again in 15 minutes.";
            $messageType = "warning";
        } elseif (empty($username) || empty($password)) {
            $message = "Please enter both username and password.";
            $messageType = "warning";
            recordAttempt($client_ip);
        } else {
            try {
                $stmt = $pdo->prepare("SELECT student_id, password, first_name, status FROM students WHERE (username = ? OR email = ?) AND status = 'active'");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Successful login
                    session_regenerate_id(true); // Prevent session fixation
                    
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['student_name'] = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Clear rate limiting
                    unset($_SESSION['login_attempts_' . $client_ip]);
                    unset($_SESSION['login_attempts_' . $client_ip . '_time']);
                    
                    // Generate new CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    header("Location: ../student/student-dashboard.php");
                    exit;
                } else {
                    $message = "Invalid username/email or password.";
                    $messageType = "danger";
                    recordAttempt($client_ip);
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $message = "Login failed. Please try again.";
                $messageType = "danger";
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Portal - Top Link Global College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            min-height: 600px;
            display: flex;
        }

        .login-form-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-form-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, var(--light-green) 0%, #f1f8e9 100%);
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
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .btn-toggle {
            background: transparent;
            border: 2px solid var(--accent-green);
            color: var(--accent-green);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-toggle:hover {
            background: var(--accent-green);
            color: white;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .input-group {
            position: relative;
            margin-bottom: 1rem;
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

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: var(--accent-green);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 1rem;
                min-height: auto;
            }
            
            .login-form-section,
            .register-form-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Section -->
        <div class="login-form-section">
            <div class="school-header">
                <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <div class="school-name">Top Link Global College</div>
                <div class="school-subtitle">Student Portal</div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="form-section active" id="loginForm">
                <h3 class="form-title">Welcome Back</h3>
                <form method="POST" action="" autocomplete="on">
                    <input type="hidden" name="form_action" value="login">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="input-group">
                        <i class="bi bi-person"></i>
                        <input type="text" class="form-control" name="username" placeholder="Username or Email" 
                               required autocomplete="username" maxlength="50">
                    </div>

                    <div class="input-group">
                        <i class="bi bi-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Password" 
                               required autocomplete="current-password" maxlength="100">
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>

                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot your password?</a>
                </div>

                <div class="text-center">
                    <button type="button" class="btn-toggle" onclick="toggleForm()">
                        Don't have an account? Register
                    </button>
                </div>
            </div>

            <!-- Register Form -->
            <div class="form-section" id="registerForm">
                <h3 class="form-title">Create Account</h3>
                <form method="POST" action="" autocomplete="on" id="registerFormElement">
                    <input type="hidden" name="form_action" value="register">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="username" placeholder="Username" 
                                       required autocomplete="username" maxlength="20" pattern="[a-zA-Z0-9_-]{3,20}">
                            </div>
                            <small class="text-muted">3-20 characters, letters, numbers, underscore, hyphen only</small>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="form-control" name="email" placeholder="Email" 
                                       required autocomplete="email" maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="first_name" placeholder="First Name" 
                                       required autocomplete="given-name" maxlength="50" pattern="[a-zA-Z\s\'-]{2,50}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="middle_name" placeholder="Middle Name" 
                                       autocomplete="additional-name" maxlength="50" pattern="[a-zA-Z\s\'-]{0,50}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name" 
                                       required autocomplete="family-name" maxlength="50" pattern="[a-zA-Z\s\'-]{2,50}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" name="password" placeholder="Password" 
                                       required autocomplete="new-password" maxlength="100" id="password">
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" 
                                       required autocomplete="new-password" maxlength="100" id="confirmPassword">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <div class="text-center">
                    <button type="button" class="btn-toggle" onclick="toggleForm()">
                        Already have an account? Sign In
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="register-form-section">
            <div class="text-center">
                <h4 class="text-success mb-4">Join Our Community</h4>
                <div class="mb-4">
                    <i class="bi bi-mortarboard" style="font-size: 4rem; color: var(--accent-green);"></i>
                </div>
                <h5 class="mb-3">Quality Education for Your Future</h5>
                <p class="text-muted mb-4">
                    Access your student portal to manage enrollments, view grades, 
                    upload documents, and stay connected with your academic journey.
                </p>
                <div class="row text-center">
                    <div class="col-4">
                        <i class="bi bi-journal-check" style="font-size: 2rem; color: var(--accent-green);"></i>
                        <p class="small mt-2">Easy Enrollment</p>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-cloud-upload" style="font-size: 2rem; color: var(--accent-green);"></i>
                        <p class="small mt-2">Document Upload</p>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-graph-up" style="font-size: 2rem; color: var(--accent-green);"></i>
                        <p class="small mt-2">Track Progress</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (loginForm.classList.contains('active')) {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
            } else {
                registerForm.classList.remove('active');
                loginForm.classList.add('active');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push("At least 8 characters");

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push("One uppercase letter");

            if (/[a-z]/.test(password)) strength++;
            else feedback.push("One lowercase letter");

            if (/[0-9]/.test(password)) strength++;
            else feedback.push("One number");

            if (/[^A-Za-z0-9]/.test(password)) strength++;

            return { strength, feedback };
        }

        // Real-time password validation
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const { strength, feedback } = checkPasswordStrength(password);

            let className = 'strength-weak';
            let text = 'Weak';

            if (strength >= 3) {
                className = 'strength-medium';
                text = 'Medium';
            }
            if (strength >= 4) {
                className = 'strength-strong';
                text = 'Strong';
            }

            strengthDiv.className = `password-strength ${className}`;
            strengthDiv.textContent = password ? `Password strength: ${text}` : '';

            if (feedback.length > 0 && password) {
                strengthDiv.textContent += ` (Need: ${feedback.join(', ')})`;
            }
        });

        // Form validation
        document.getElementById('registerFormElement')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const { strength } = checkPasswordStrength(password);

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (strength < 3) {
                e.preventDefault();
                alert('Password is too weak. Please use a stronger password.');
                return false;
            }
        });

        // Input sanitization on client side (additional layer)
        document.querySelectorAll('input[type="text"], input[type="email"]').forEach(input => {
            input.addEventListener('blur', function() {
                // Remove potentially dangerous characters
                this.value = this.value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            });
        });
    </script>
</body>
</html>
