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

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validation
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($firstName)) $errors[] = "First name is required.";
    if (empty($lastName)) $errors[] = "Last name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        }
    }

    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (username, first_name, middle_name, last_name, email, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $firstName, $middleName, $lastName, $email, $hashedPassword]);
            
            $message = "Registration successful! You can now log in.";
            $messageType = "success";
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = "danger";
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Please enter both username and password.";
        $messageType = "warning";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT student_id, password, first_name FROM students WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['student_name'] = $user['first_name'];
                $_SESSION['login_time'] = time();
                
                header("Location: ../student/student-dashboard.php");
                exit;
            } else {
                $message = "Invalid username/email or password.";
                $messageType = "danger";
            }
        } catch (PDOException $e) {
            $message = "Login failed. Please try again.";
            $messageType = "danger";
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
    <style>
        :root {
            --primary-green:rgb(204, 230, 205);
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
                <form method="POST" action="">
                    <input type="hidden" name="form_action" value="login">
                    
                    <div class="input-group">
                        <i class="bi bi-person"></i>
                        <input type="text" class="form-control" name="username" placeholder="Username or Email" required>
                    </div>

                    <div class="input-group">
                        <i class="bi bi-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
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
                <form method="POST" action="">
                    <input type="hidden" name="form_action" value="register">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="form-control" name="email" placeholder="Email" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="first_name" placeholder="First Name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="middle_name" placeholder="Middle Name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <i class="bi bi-person-badge"></i>
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
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

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwords = this.querySelectorAll('input[type="password"]');
                if (passwords.length === 2) {
                    if (passwords[0].value !== passwords[1].value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    if (passwords[0].value.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>
