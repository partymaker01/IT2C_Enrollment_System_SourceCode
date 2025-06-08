<?php
session_start();

// Only allow access to this script if the user is an admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Access denied. Admin authentication required.");
}

require_once '../../db.php';

$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_POST['admin_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($admin_id) || empty($new_password)) {
        $error = "Admin ID and new password are required.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE admin_settings SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $admin_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = true;
            } else {
                $error = "Admin ID not found or no changes made.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all admins for the dropdown
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, username, name, role FROM admin_settings ORDER BY name");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching admin list: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Admin Password - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #2e7d32;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }
        .btn-primary:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Reset Admin Password</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Password has been reset successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="admin_id" class="form-label">Select Admin</label>
                        <select class="form-select" id="admin_id" name="admin_id" required>
                            <option value="">-- Select Admin --</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?= htmlspecialchars($admin['id']) ?>">
                                    <?= htmlspecialchars($admin['name']) ?> (<?= htmlspecialchars($admin['username']) ?>) - <?= htmlspecialchars($admin['role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Reset Password
                        </button>
                        <a href="../admin-dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('new_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
