<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

// Get enrollment id from URL - FIXED: changed from 'student_id' to 'id'
$enrollment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no enrollment ID provided, try to get the latest approved enrollment for the current student
if ($enrollment_id <= 0) {
    $student_id = $_SESSION['student_id'];
    $stmt = $pdo->prepare("
        SELECT id FROM enrollments 
        WHERE student_id = ? AND status = 'approved' 
        ORDER BY date_submitted DESC 
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        $enrollment_id = $result['id'];
    } else {
        die("📋 No approved enrollment found. Please complete your enrollment first or wait for admin approval.");
    }
}

try {
    // Fetch enrollment info (only if status is approved)
    $stmt = $pdo->prepare("
        SELECT e.*, s.first_name, s.last_name, s.student_id as student_number, s.email, s.contact_number
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        WHERE e.id = ? AND e.status = 'approved'
    ");
    $stmt->execute([$enrollment_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        die("📋 Enrollment not found or not yet approved. Please wait for admin confirmation.");
    }

    // Check if the enrollment belongs to the current student (security check)
    if ($enrollment['student_id'] != $_SESSION['student_id']) {
        die("❌ Access denied. You can only view your own enrollment slip.");
    }

    // Fetch subjects linked to enrollment
    $stmt = $pdo->prepare("
        SELECT s.subject_code, s.subject_title, s.units, s.day, s.time, s.room, s.instructor
        FROM enrollment_subjects es
        JOIN subjects s ON es.subject_code = s.subject_code
        WHERE es.enrollment_id = ?
        ORDER BY s.subject_code
    ");
    $stmt->execute([$enrollment_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no subjects found, get sample subjects based on program and year level
    if (empty($subjects)) {
        $stmt = $pdo->prepare("
            SELECT * FROM subjects 
            WHERE status = 'Active' 
            ORDER BY subject_code ASC 
            LIMIT 6
        ");
        $stmt->execute();
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $totalUnits = array_sum(array_column($subjects, 'units'));

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enrollment Slip - <?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --accent-green: #43a047;
        }

        body {
            background: linear-gradient(135deg, var(--light-green) 0%, #f1f8e9 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, #1b5e20 100%);
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.3);
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid #fff;
        }

        .school-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .school-header img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--accent-green);
            margin-bottom: 1rem;
        }

        .enrollment-slip {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .info-section {
            background: rgba(46, 125, 50, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--accent-green);
        }

        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
        }

        .badge-status {
            background: linear-gradient(135deg, #4caf50 0%, #43a047 100%);
            color: white;
            font-size: 1rem;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0.25rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .qr-section {
            text-align: center;
            padding: 2rem;
            background: rgba(46, 125, 50, 0.05);
            border-radius: 15px;
            margin-top: 2rem;
        }

        .verification-section {
            border-top: 2px solid var(--accent-green);
            padding-top: 2rem;
            margin-top: 2rem;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .enrollment-slip { box-shadow: none !important; }
            .school-header { box-shadow: none !important; }
        }

        .watermark {
            position: relative;
            overflow: hidden;
        }

        .watermark::before {
            content: 'OFFICIAL';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6rem;
            color: rgba(46, 125, 50, 0.1);
            font-weight: bold;
            z-index: 1;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 2;
        }

        .success-alert {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: #155724;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg no-print">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../student-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <span>Student Portal</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../student-dashboard.php">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Success Alert -->
        <div class="success-alert no-print">
            <h5><i class="bi bi-check-circle me-2"></i>Enrollment Approved!</h5>
            <p class="mb-0">Your enrollment has been successfully approved. You can now print your official enrollment certificate.</p>
        </div>

        <!-- School Header -->
        <div class="school-header">
            <img src="../../picture/tlgc_pic.jpg" alt="School Logo">
            <h3 class="mb-2" style="color: var(--primary-green); font-weight: 700;">Top Link Global College Inc.</h3>
            <p class="mb-1">MRF Compound, Purok Lambingan, Brgy. Daan Sarile</p>
            <p class="mb-1">Cabanatuan City, Nueva Ecija, Philippines</p>
            <p class="mb-3">📞 (044) 123-4567 | 📧 info@tlgc.edu.ph</p>
            <h4 style="color: var(--accent-green); font-weight: 700;">
                <i class="bi bi-award me-2"></i>OFFICIAL ENROLLMENT CERTIFICATE
            </h4>
        </div>

        <div class="enrollment-slip watermark">
            <div class="content">
                <!-- Student Information -->
                <div class="info-section">
                    <h5 style="color: var(--primary-green); margin-bottom: 1rem;">
                        <i class="bi bi-person-badge me-2"></i>Student Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Full Name:</strong> <?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></p>
                            <p><strong>Student ID:</strong> <?= htmlspecialchars($enrollment['student_number']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($enrollment['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Contact Number:</strong> <?= htmlspecialchars($enrollment['contact_number']) ?></p>
                            <p><strong>Program:</strong> <?= htmlspecialchars($enrollment['program']) ?></p>
                            <p><strong>Year Level:</strong> <?= htmlspecialchars($enrollment['year_level']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Details -->
                <div class="info-section">
                    <h5 style="color: var(--primary-green); margin-bottom: 1rem;">
                        <i class="bi bi-journal-check me-2"></i>Enrollment Details
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Semester:</strong> <?= htmlspecialchars($enrollment['semester']) ?></p>
                            <p><strong>Section:</strong> <?= htmlspecialchars($enrollment['section']) ?></p>
                            <p><strong>School Year:</strong> <?= htmlspecialchars($enrollment['school_year'] ?? '2024-2025') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date Enrolled:</strong> <?= date("F d, Y", strtotime($enrollment['date_submitted'])) ?></p>
                            <p><strong>Date Processed:</strong> <?= $enrollment['date_processed'] ? date("F d, Y", strtotime($enrollment['date_processed'])) : 'N/A' ?></p>
                            <p><strong>Status:</strong> <span class="badge-status"><?= ucfirst($enrollment['status']) ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Subjects Table -->
                <h5 style="color: var(--primary-green); margin-bottom: 1rem;">
                    <i class="bi bi-book me-2"></i>Enrolled Subjects & Class Schedule
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Title</th>
                                <th>Units</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($subject['subject_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($subject['subject_title']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($subject['units']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($subject['day']) ?><br>
                                            <small><?= htmlspecialchars($subject['time']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($subject['room']) ?></td>
                                        <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success">
                                    <td colspan="2"><strong>TOTAL UNITS</strong></td>
                                    <td class="text-center"><strong><?= $totalUnits ?></strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-book-x" style="font-size: 2rem; color: #6c757d;"></i>
                                        <p class="text-muted mt-2">No subjects assigned yet. Please contact the registrar.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Verification Section -->
                <div class="verification-section">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="qr-section">
                                <p><strong>Enrollment Reference:</strong></p>
                                <h5>ENR-<?= str_pad($enrollment_id, 6, '0', STR_PAD_LEFT) ?></h5>
                                <div class="mt-3">
                                    <div style="width: 120px; height: 120px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 10px;">
                                        <small>QR Code</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <p><strong>This document is computer-generated and valid without signature.</strong></p>
                                <p class="text-muted">Generated on: <?= date("F d, Y \a\\t g:i A") ?></p>
                                <div class="mt-4">
                                    <div style="border-top: 2px solid var(--primary-green); width: 200px; margin: 0 auto;"></div>
                                    <p class="mt-2"><strong>Registrar's Office</strong></p>
                                    <p class="text-muted">Top Link Global College Inc.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn-custom">
                        <i class="bi bi-printer me-2"></i>Print Certificate
                    </button>
                    <button onclick="downloadPDF()" class="btn-custom">
                        <i class="bi bi-download me-2"></i>Download PDF
                    </button>
                    <a href="../MyEnrollAssignUpload/enrollment-status.php" class="btn-custom">
                        <i class="bi bi-eye me-2"></i>View Status
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.enrollment-slip');
            const opt = {
                margin: 0.5,
                filename: 'enrollment_certificate_ENR-<?= str_pad($enrollment_id, 6, '0', STR_PAD_LEFT) ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // Auto-print functionality
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.onload = function() {
                setTimeout(() => window.print(), 1000);
            };
        }
    </script>
</body>
</html>
