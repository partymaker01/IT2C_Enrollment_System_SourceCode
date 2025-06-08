<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

// DB Connection parameters
include '../../db.php';

// Initialize variables
$profileData = [
    "id" => $_SESSION['admin_id'],
    "name" => $_SESSION['admin_name'] ?? "",
    "email" => "",
    "image" => "../../img/default_admin.png"
];

// Fetch admin profile from DB
$stmt = $pdo->prepare("SELECT * FROM admin_settings WHERE id = ?");
$stmt->execute([$profileData['id']]);
$admin = $stmt->fetch();

if ($admin) {
    $profileData = [
        "id" => $admin['id'],
        "name" => $admin['name'],
        "email" => $admin['email'],
        "image" => $admin['image'] ?? "../../img/default_admin.png"
    ];
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['adminName']);
    $email = trim($_POST['adminEmail']);
    
    // Handle profile picture upload
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === 0) {
        // Create directory if it doesn't exist
        $uploadDir = '../../img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = $uploadDir . 'admin_profile_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profilePic']['tmp_name'], $filename)) {
                $profileData['image'] = $filename;
            }
        }
    }

    // Update database
    $sql = "UPDATE admin_settings SET name = ?, email = ?, image = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $profileData['image'], $profileData['id']]);

    // Update session
    $_SESSION['admin_name'] = $name;
    
    $success = true;

    // Reload updated data
    $stmt = $pdo->prepare("SELECT * FROM admin_settings WHERE id = ?");
    $stmt->execute([$profileData['id']]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $profileData = [
            "id" => $admin['id'],
            "name" => $admin['name'],
            "email" => $admin['email'],
            "image" => $admin['image'] ?? "../../img/default_admin.png"
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
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
            background-color: #f4f9f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background-color: var(--primary-green);
        }
        
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        
        .nav-link:hover {
            color: var(--hover-green) !important;
        }
        
        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid #fff;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent-green);
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-img:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../admin-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin-dashboard.php">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="d-flex justify-content-center align-items-center p-4" style="min-height: calc(100vh - 80px);">
        <div class="profile-card text-center shadow-sm">
            <h2 class="text-success mb-4"><i class="bi bi-person-circle me-2"></i>Admin Profile</h2>
            
            <label for="profilePic" tabindex="0" aria-label="Change profile picture">
                <img src="<?= htmlspecialchars($profileData['image']) ?>" alt="Profile Picture" class="profile-img" id="profileImage" title="Click to change picture" />
            </label>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>Profile updated successfully!
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="text-start mt-3" novalidate>
                <div class="mb-3">
                    <label for="adminName" class="form-label"><i class="bi bi-person me-2"></i>Full Name</label>
                    <input type="text" class="form-control" id="adminName" name="adminName" value="<?= htmlspecialchars($profileData['name']) ?>" required autocomplete="name" />
                </div>
                <div class="mb-3">
                    <label for="adminEmail" class="form-label"><i class="bi bi-envelope me-2"></i>Email Address</label>
                    <input type="email" class="form-control" id="adminEmail" name="adminEmail" value="<?= htmlspecialchars($profileData['email']) ?>" required autocomplete="email" />
                </div>
                <div class="mb-4">
                    <input class="form-control d-none" type="file" name="profilePic" id="profilePic" accept="image/*" />
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        const profilePicInput = document.getElementById('profilePic');
        const profileImage = document.getElementById('profileImage');

        profileImage.addEventListener('click', () => profilePicInput.click());
        profileImage.addEventListener('keypress', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                profilePicInput.click();
            }
        });

        profilePicInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
