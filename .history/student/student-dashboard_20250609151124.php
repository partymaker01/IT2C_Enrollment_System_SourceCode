<?php
// Add this at the top of your student dashboard after session_start()
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../logregfor/login.php");
    exit;
}

// Include database connection
require_once '../db.php';

$student_id = $_SESSION['student_id'];

// Check if student exists with current session ID
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// If student not found, try to find by old ID pattern and update session
if (!$student) {
    // Log the user out if student record is completely missing
    error_log("Student not found for ID: " . $student_id);
    session_destroy();
    header("Location: ../logregfor/login.php");
    exit;
}


// Fetch enrollment status
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 1");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();

// Set default values if no enrollment found
if (!$enrollment) {
    $enrollment = [
        'program' => 'Not Enrolled',
        'year_level' => 'N/A',
        'semester' => 'N/A',
        'section' => 'N/A',
        'status' => 'not_enrolled',
        'date_submitted' => null
    ];
}

// Count uploaded documents
$stmt = $pdo->prepare("SELECT COUNT(*) as doc_count FROM uploaded_documents WHERE student_id = ?");
$stmt->execute([$student_id]);
$docCount = $stmt->fetch()['doc_count'];

// Count assigned subjects
$stmt = $pdo->prepare("SELECT COUNT(*) as subject_count FROM student_subjects ss JOIN subjects s ON ss.subject_id = s.id WHERE ss.student_id = ?");
$stmt->execute([$student_id]);
$subjectCount = $stmt->fetch()['subject_count'];

$studentName = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
$photo = $student['photo'] ?? 'student/uploads/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - <?= htmlspecialchars($studentName) ?></title>
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
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--hover-green) !important;
            transform: translateY(-1px);
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid #fff;
            transition: transform 0.3s ease;
        }

        .school-logo:hover {
            transform: scale(1.1);
        }

        .profile-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.15);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--accent-green);
            box-shadow: 0 4px 15px rgba(67, 160, 71, 0.3);
            transition: transform 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.05);
        }

        .stats-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .icon-enrollment { color: #4caf50; }
        .icon-subjects { color: #2196f3; }
        .icon-documents { color: #ff9800; }
        .icon-profile { color: #9c27b0; }

        .quick-action-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-approved { background-color: #4caf50; color: white; }
        .status-pending { background-color: #ff9800; color: white; }
        .status-rejected { background-color: #f44336; color: white; }
        .status-not-enrolled { background-color: #9e9e9e; color: white; }

        .section-title {
            color: var(--primary-green);
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green), var(--primary-green));
            border-radius: 2px;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .list-group-item {
            border: none;
            border-radius: 10px !important;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        }

        .list-group-item:hover {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--hover-green) 100%);
            transform: translateX(5px);
            color: var(--dark-green);
        }

        .welcome-text {
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            .profile-card {
                text-align: center;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <span>Student Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($student['first_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="history&Profile/Edit Profile.php"><i class="bi bi-person-gear"></i> Edit Profile</a></li>
                            <li><a class="dropdown-item" href="history&Profile/Change Password.php"><i class="bi bi-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logregfor/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Welcome Section -->
        <div class="profile-card">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?= htmlspecialchars('../' . $photo) ?>" alt="Profile Picture" class="profile-pic" onerror="this.src='../student/uploads/default-avatar.png'">
                </div>
                <div class="col-md-9">
                    <h2 class="welcome-text mb-2">Welcome back, <?= htmlspecialchars($student['first_name']) ?>!</h2>
                    <h4 class="text-dark mb-3"><?= htmlspecialchars($studentName) ?></h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Program:</strong> <?= htmlspecialchars($enrollment['program']) ?></p>
                            <p class="mb-1"><strong>Status:</strong> 
                                <span class="status-badge status-<?= str_replace('_', '-', $enrollment['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $enrollment['status'])) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <i class="bi bi-journal-check stats-icon icon-enrollment"></i>
                    <h5>Enrollment</h5>
                    <p class="text-muted mb-0"><?= ucfirst(str_replace('_', ' ', $enrollment['status'])) ?></p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <i class="bi bi-book stats-icon icon-subjects"></i>
                    <h5>Subjects</h5>
                    <p class="text-muted mb-0"><?= $subjectCount ?> Assigned</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <i class="bi bi-file-earmark-text stats-icon icon-documents"></i>
                    <h5>Documents</h5>
                    <p class="text-muted mb-0"><?= $docCount ?> Uploaded</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <i class="bi bi-person-badge stats-icon icon-profile"></i>
                    <h5>Profile</h5>
                    <p class="text-muted mb-0">Complete</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h3 class="section-title">Quick Actions</h3>
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="quick-action-card">
                    <h5 class="text-primary"><i class="bi bi-journal-plus"></i> Enrollment</h5>
                    <p class="text-muted">Manage your enrollment status and view details</p>
                    <a href="MyEnrollAssignUpload/enrollment-status.php" class="btn-custom">View Status</a>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="quick-action-card">
                    <h5 class="text-success"><i class="bi bi-books"></i> My Subjects</h5>
                    <p class="text-muted">View your assigned subjects and schedule</p>
                    <a href="MyEnrollAssignUpload/mysubject.php" class="btn-custom">View Subjects</a>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="quick-action-card">
                    <h5 class="text-warning"><i class="bi bi-cloud-upload"></i> Documents</h5>
                    <p class="text-muted">Upload and manage your documents</p>
                    <a href="Documents/UploadRequirements.php" class="btn-custom">Upload Files</a>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="row">
            <div class="col-md-6">
                <h4 class="section-title">Enrollment Services</h4>
                <div class="list-group">
                    <a href="Enrollment/fill-up-enrollment-form.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-pencil-square me-2"></i>Fill-up Enrollment Form
                    </a>
                    <a href="MyEnrollAssignUpload/enrollment-status.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-eye me-2"></i>View Enrollment Status
                    </a>
                    <a href="MyEnrollAssignUpload/assigned-subjects.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-book me-2"></i>View Assigned Subjects
                    </a>
                    <a href="Enrollment/print-enrollment-slip.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-printer me-2"></i>Print Enrollment Slip
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <h4 class="section-title">Student Services</h4>
                <div class="list-group">
                    <a href="Documents/UploadRequirements.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-upload me-2"></i>Upload Requirements
                    </a>
                    <a href="Documents/View Uploaded Files.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-files me-2"></i>View Uploaded Files
                    </a>
                    <a href="history&Profile/Enrollment History.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-clock-history me-2"></i>Enrollment History
                    </a>
                    <a href="history&Profile/Edit Profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-gear me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Top Link Global College Inc. All rights reserved.</p>
            <p class="mb-0">Enrollment Management System</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on scroll
            const cards = document.querySelectorAll('.stats-card, .quick-action-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
