<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    session_destroy();
    header("Location: ../../logregfor/login.php");
    exit;
}

// Fetch current enrollment status
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 1");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values if no enrollment found
if (!$enrollment) {
    $enrollment = [
        'program' => 'Not Enrolled',
        'year_level' => 'N/A',
        'semester' => 'N/A',
        'section' => 'N/A',
        'school_year' => 'N/A',
        'date_submitted' => null,
        'status' => 'not_enrolled',
        'remarks' => '',
        'notification' => ''
    ];
}

$dateSubmitted = $enrollment['date_submitted'] ? date("F j, Y", strtotime($enrollment['date_submitted'])) : 'N/A';
$status = $enrollment['status'];

$badgeClass = [
    'pending' => 'badge-pending',
    'approved' => 'badge-approved',
    'rejected' => 'badge-rejected',
    'not_enrolled' => 'badge-secondary'
][$status] ?? 'badge-pending';

$badgeLabel = ucfirst(str_replace('_', ' ', $status));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Current Enrollment Status - Student Portal</title>
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

        .status-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            padding: 2rem;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

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

        .badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #000;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }

        .badge-approved {
            background: linear-gradient(135deg, #4caf50 0%, #43a047 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }

        .badge-rejected {
            background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }

        .info-row {
            background: rgba(46, 125, 50, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 5px solid var(--accent-green);
            transition: all 0.3s ease;
        }

        .info-row:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.1);
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .student-info {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .icon-pending { color: #ffc107; }
        .icon-approved { color: #4caf50; }
        .icon-rejected { color: #f44336; }
        .icon-not-enrolled { color: #6c757d; }
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

    <div class="container my-4">
        <!-- Student Info Header -->
        <div class="student-info text-center">
            <h3><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h3>
            <p class="mb-0">Student ID: <?= htmlspecialchars($student['student_id']) ?></p>
        </div>

        <h2 class="section-title">
            <i class="bi bi-journal-check"></i>
            Current Enrollment Status
        </h2>

        <div class="status-card">
            <!-- Status Overview -->
            <div class="text-center mb-4">
                <?php
                $iconClass = match ($status) {
                    'approved' => 'bi-check-circle-fill icon-approved',
                    'pending' => 'bi-clock-fill icon-pending',
                    'rejected' => 'bi-x-circle-fill icon-rejected',
                    default => 'bi-journal-x icon-not-enrolled',
                };
                ?>
                <i class="bi <?= $iconClass ?> status-icon"></i>
                <h4>Enrollment Status</h4>
                <span class="<?= $badgeClass ?>">
                    <?= $badgeLabel ?>
                </span>
            </div>

            <?php if ($status !== 'not_enrolled'): ?>
                <!-- Enrollment Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <strong><i class="bi bi-mortarboard me-2"></i>Program:</strong><br>
                            <?= htmlspecialchars($enrollment['program']) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <strong><i class="bi bi-calendar-range me-2"></i>School Year:</strong><br>
                            <?= htmlspecialchars($enrollment['school_year'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="info-row">
                            <strong><i class="bi bi-bookmark me-2"></i>Year Level:</strong><br>
                            <?= htmlspecialchars($enrollment['year_level']) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <strong><i class="bi bi-calendar-week me-2"></i>Semester:</strong><br>
                            <?= htmlspecialchars($enrollment['semester']) ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <strong><i class="bi bi-people me-2"></i>Section:</strong><br>
                            <?= htmlspecialchars($enrollment['section']) ?>
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <strong><i class="bi bi-clock-history me-2"></i>Date Submitted:</strong><br>
                    <?= $dateSubmitted ?>
                </div>

                <?php if ($enrollment['date_processed']): ?>
                    <div class="info-row">
                        <strong><i class="bi bi-check2-circle me-2"></i>Date Processed:</strong><br>
                        <?= date("F j, Y \a\\t g:i A", strtotime($enrollment['date_processed'])) ?>
                    </div>
                <?php endif; ?>

                <?php if ($enrollment['remarks']): ?>
                    <div class="info-row">
                        <strong><i class="bi bi-chat-text me-2"></i>Remarks:</strong><br>
                        <?= nl2br(htmlspecialchars($enrollment['remarks'])) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Status-specific Actions -->
            <div class="action-buttons">
                <?php if ($status === 'approved'): ?>
                    <a href="mysubject.php" class="btn-custom">
                        <i class="bi bi-book me-2"></i>View Subjects
                    </a>
                    <a href="../Enrollment/print-enrollment-slip.php?student_id=<?= $enrollment['id'] ?>" class="btn-custom" target="_blank">
                        <i class="bi bi-printer me-2"></i>Print Enrollment Slip
                    </a>
                    <a href="../Documents/UploadRequirements.php" class="btn-custom">
                        <i class="bi bi-upload me-2"></i>Upload Documents
                    </a>
                <?php elseif ($status === 'rejected'): ?>
                    <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                        <i class="bi bi-arrow-clockwise me-2"></i>Submit New Application
                    </a>
                <?php elseif ($status === 'pending'): ?>
                    <a href="enrollment-status.php" class="btn-custom">
                        <i class="bi bi-eye me-2"></i>View Detailed Status
                    </a>
                <?php else: ?>
                    <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                        <i class="bi bi-journal-plus me-2"></i>Start Enrollment
                    </a>
                <?php endif; ?>
                
                <a href="../history&Profile/Enrollment History.php" class="btn-custom">
                    <i class="bi bi-clock-history me-2"></i>View History
                </a>
            </div>

            <?php if (!empty($enrollment['notification'])): ?>
                <div class="alert alert-info mt-4">
                    <h6><i class="bi bi-info-circle me-2"></i>Notification:</h6>
                    <?= nl2br(htmlspecialchars($enrollment['notification'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
