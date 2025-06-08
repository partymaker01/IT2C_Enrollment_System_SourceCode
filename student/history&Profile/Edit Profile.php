<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit();
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];
$errors = [];
$success = "";

// Fetch current student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student record not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $profilePicturePath = $student['photo'] ?? 'uploads/default-avatar.png';

    // Validation
    if (empty($firstName)) $errors[] = "First name is required.";
    if (empty($lastName)) $errors[] = "Last name is required.";
    if (empty($dob)) $errors[] = "Date of Birth is required.";
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = "Please select a valid gender.";
    if (empty($contactNumber)) $errors[] = "Contact number is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($address)) $errors[] = "Address is required.";

    // Check if email is already taken by another student
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
    $stmt->execute([$email, $student_id]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already taken by another student.";
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and GIF image files are allowed.';
        } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
            $errors[] = 'Profile picture must be less than 5MB.';
        } else {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/IT2C_Enrollment_System_SourceCode/student/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $newFileName = 'profile_' . $student_id . '_' . time() . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profilePicturePath = 'student/uploads/' . $newFileName;
                
                // Delete old profile picture if it exists and is not default
                if ($student['photo'] && $student['photo'] !== 'uploads/default-avatar.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . '/IT2C_Enrollment_System_SourceCode/' . $student['photo'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/IT2C_Enrollment_System_SourceCode/' . $student['photo']);
                }
            } else {
                $errors[] = 'Failed to upload profile picture. Check file permissions.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET first_name = ?, middle_name = ?, last_name = ?, dob = ?, gender = ?, contact_number = ?, email = ?, address = ?, photo = ?, updated_at = NOW() WHERE student_id = ?");
            $stmt->execute([
                $firstName,
                $middleName,
                $lastName,
                $dob,
                $gender,
                $contactNumber,
                $email,
                $address,
                $profilePicturePath,
                $student_id
            ]);

            $success = "Profile updated successfully!";
            
            // Refresh student data
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile - Student Portal</title>
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

        .profile-container {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.15);
            padding: 2.5rem;
            max-width: 700px;
            margin: 2rem auto;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .section-title {
            color: var(--primary-green);
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .section-title::after {
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

        .profile-pic-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--accent-green);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.3);
        }

        .profile-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(67, 160, 71, 0.4);
        }

        .upload-hint {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .form-label {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.25rem rgba(67, 160, 71, 0.25);
            background-color: #fff;
        }

        .btn-save {
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

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        .required {
            color: #dc3545;
        }

        .character-count {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: right;
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

    <div class="container">
        <div class="profile-container">
            <h2 class="section-title">
                <i class="bi bi-person-gear me-2"></i>
                Edit Profile
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <!-- Profile Picture -->
                <div class="profile-pic-container">
                    <label for="profilePicture" style="cursor: pointer;">
                        <img src="<?= htmlspecialchars($student['photo'] ?? 'uploads/default-avatar.png') ?>" 
                             class="profile-img" id="profilePreview" 
                             alt="Profile Picture" title="Click to change profile picture">
                    </label>
                    <input type="file" id="profilePicture" name="profile_picture" 
                           accept="image/*" class="d-none" onchange="previewProfilePic(event)">
                    <div class="upload-hint">
                        <i class="bi bi-camera me-1"></i>
                        Click photo to change (Max: 5MB, JPG/PNG/GIF)
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-row mb-3">
                    <div>
                        <label for="firstName" class="form-label">
                            <i class="bi bi-person me-1"></i>First Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" id="firstName" name="first_name" 
                               value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label for="middleName" class="form-label">
                            <i class="bi bi-person me-1"></i>Middle Name
                        </label>
                        <input type="text" class="form-control" id="middleName" name="middle_name" 
                               value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="lastName" class="form-label">
                        <i class="bi bi-person me-1"></i>Last Name <span class="required">*</span>
                    </label>
                    <input type="text" class="form-control" id="lastName" name="last_name" 
                           value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                </div>

                <div class="form-row mb-3">
                    <div>
                        <label for="dob" class="form-label">
                            <i class="bi bi-calendar me-1"></i>Date of Birth <span class="required">*</span>
                        </label>
                        <input type="date" class="form-control" id="dob" name="dob" 
                               value="<?= htmlspecialchars($student['dob'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label for="gender" class="form-label">
                            <i class="bi bi-gender-ambiguous me-1"></i>Gender <span class="required">*</span>
                        </label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($student['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-row mb-3">
                    <div>
                        <label for="contact" class="form-label">
                            <i class="bi bi-telephone me-1"></i>Contact Number <span class="required">*</span>
                        </label>
                        <input type="tel" class="form-control" id="contact" name="contact_number" 
                               value="<?= htmlspecialchars($student['contact_number'] ?? '') ?>" 
                               placeholder="+63 XXX XXX XXXX" required>
                    </div>
                    <div>
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>Email Address <span class="required">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">
                        <i class="bi bi-geo-alt me-1"></i>Address <span class="required">*</span>
                    </label>
                    <textarea class="form-control" id="address" name="address" rows="3" 
                              maxlength="500" required><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                    <div class="character-count">
                        <span id="addressCount">0</span>/500 characters
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewProfilePic(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    event.target.value = '';
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed');
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Character counter for address
        const addressField = document.getElementById('address');
        const addressCount = document.getElementById('addressCount');

        function updateAddressCount() {
            const count = addressField.value.length;
            addressCount.textContent = count;
            
            if (count > 450) {
                addressCount.style.color = '#dc3545';
            } else if (count > 400) {
                addressCount.style.color = '#ffc107';
            } else {
                addressCount.style.color = '#6c757d';
            }
        }

        addressField.addEventListener('input', updateAddressCount);
        updateAddressCount(); // Initial count

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const requiredFields = ['firstName', 'lastName', 'dob', 'gender', 'contact', 'email', 'address'];
            let isValid = true;

            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Remove invalid class on input
        document.querySelectorAll('.form-control, .form-select').forEach(element => {
            element.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Phone number formatting
        document.getElementById('contact').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+63' + value.substring(1);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
