<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

// Fetch latest enrollment
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 1");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();

// Fetch student info for display
$stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enrollment Status - Student Portal</title>
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
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
        }

        .badge-approved {
            background: linear-gradient(135deg, #4caf50 0%, #43a047 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
        }

        .badge-rejected {
            background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
        }

        .info-row {
            background: rgba(46, 125, 50, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent-green);
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
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--accent-green);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent-green);
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

    <div class="container my-4">
        <h2 class="section-title">
            <i class="bi bi-journal-check"></i>
            Enrollment Status
        </h2>

        <?php if ($enrollment): ?>
            <div class="status-card">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4 class="text-primary mb-3">
                            <i class="bi bi-person-badge"></i>
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </h4>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h5>Current Status:</h5>
                        <?php
                        $status = strtolower($enrollment['status']);
                        $badgeClass = match ($status) {
                            'approved' => 'badge-approved',
                            'pending' => 'badge-pending',
                            'rejected' => 'badge-rejected',
                            default => 'badge-secondary',
                        };
                        ?>
                        <span class="<?= $badgeClass ?>">
                            <i class="bi bi-<?= $status === 'approved' ? 'check-circle' : ($status === 'pending' ? 'clock' : 'x-circle') ?>"></i>
                            <?= ucfirst($enrollment['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <strong><i class="bi bi-mortarboard me-2"></i>Program:</strong><br>
                            <?= htmlspecialchars($enrollment['program']) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <strong><i class="bi bi-calendar3 me-2"></i>Academic Year:</strong><br>
                            <?= htmlspecialchars($enrollment['school_year']) ?>
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
                    <?= date("F j, Y \a\\t g:i A", strtotime($enrollment['date_submitted'])) ?>
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

                <!-- Status-specific content -->
                <?php if ($status === 'approved'): ?>
                    <div class="alert alert-success mt-4">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>Congratulations!</h5>
                        <p class="mb-3">Your enrollment has been approved. You can now proceed with the following:</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="mysubject.php" class="btn-custom">
                                <i class="bi bi-book me-2"></i>View Subjects
                            </a>
                            <a href="../Enrollment/print-enrollment-slip.php" class="btn-custom">
                                <i class="bi bi-printer me-2"></i>Print Enrollment Slip
                            </a>
                            <a href="../Documents/UploadRequirements.php" class="btn-custom">
                                <i class="bi bi-upload me-2"></i>Upload Documents
                            </a>
                        </div>
                    </div>
                <?php elseif ($status === 'rejected'): ?>
                    <div class="alert alert-danger mt-4">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Enrollment Rejected</h5>
                        <?php if ($enrollment['rejection_reason']): ?>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($enrollment['rejection_reason']) ?></p>
                        <?php endif; ?>
                        <p class="mb-3">Please contact the registrar's office or submit a new enrollment application.</p>
                        <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                            <i class="bi bi-arrow-clockwise me-2"></i>Submit New Application
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        <h5><i class="bi bi-hourglass-split me-2"></i>Enrollment Pending</h5>
                        <p class="mb-3">Your enrollment application is currently being reviewed. Please wait for approval from the registrar's office.</p>
                        <div class="timeline">
                            <div class="timeline-item">
                                <strong>Application Submitted</strong><br>
                                <small class="text-muted"><?= date("F j, Y", strtotime($enrollment['date_submitted'])) ?></small>
                            </div>
                            <div class="timeline-item">
                                <strong>Under Review</strong><br>
                                <small class="text-muted">Current Status</small>
                            </div>
                            <div class="timeline-item">
                                <strong>Decision</strong><br>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-card text-center">
                <div class="mb-4">
                    <i class="bi bi-journal-x" style="font-size: 4rem; color: #6c757d;"></i>
                </div>
                <h4 class="text-muted mb-3">No Enrollment Found</h4>
                <p class="text-muted mb-4">You haven't submitted an enrollment application yet.</p>
                <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                    <i class="bi bi-journal-plus me-2"></i>Start Enrollment Application
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
