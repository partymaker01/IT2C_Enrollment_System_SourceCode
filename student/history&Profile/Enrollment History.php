<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit();
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

try {
    // Get all enrollments of the student with subjects
    $stmt = $pdo->prepare("
        SELECT 
            e.id AS enrollment_id, 
            e.semester, 
            e.program, 
            e.year_level, 
            e.section, 
            e.school_year,
            e.date_submitted AS enrollment_date, 
            e.status,
            e.remarks,
            s.subject_code, 
            s.subject_title, 
            s.units,
            s.instructor,
            s.day,
            s.time,
            s.room
        FROM enrollments e
        LEFT JOIN enrollment_subjects es ON e.id = es.enrollment_id
        LEFT JOIN subjects s ON es.subject_code = s.subject_code
        WHERE e.student_id = ?
        ORDER BY e.date_submitted DESC, s.subject_title ASC
    ");

    $stmt->execute([$student_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data to group subjects under each enrollment
    $enrollments = [];
    foreach ($rows as $row) {
        $eid = $row['enrollment_id'];

        if (!isset($enrollments[$eid])) {
            $enrollments[$eid] = [
                'id' => $eid,
                'semester' => $row['semester'],
                'program' => $row['program'],
                'year' => $row['year_level'],
                'section' => $row['section'],
                'school_year' => $row['school_year'],
                'date' => $row['enrollment_date'],
                'status' => $row['status'],
                'remarks' => $row['remarks'],
                'subjects' => []
            ];
        }

        if ($row['subject_code']) {
            $enrollments[$eid]['subjects'][] = [
                'code' => $row['subject_code'],
                'title' => $row['subject_title'],
                'units' => $row['units'],
                'instructor' => $row['instructor'],
                'schedule' => $row['day'] . ' ' . $row['time'],
                'room' => $row['room']
            ];
        }
    }

    // Fetch student info
    $stmt = $pdo->prepare("SELECT first_name, last_name, student_id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function getStatusBadge($status) {
    return match(strtolower($status)) {
        'approved' => 'bg-success',
        'pending' => 'bg-warning text-dark',
        'rejected' => 'bg-danger',
        default => 'bg-secondary'
    };
}

function getStatusIcon($status) {
    return match(strtolower($status)) {
        'approved' => 'bi-check-circle',
        'pending' => 'bi-clock',
        'rejected' => 'bi-x-circle',
        default => 'bi-question-circle'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enrollment History - Student Portal</title>
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

        .history-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            padding: 2rem;
            margin-bottom: 2rem;
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

        .accordion-button {
            background: linear-gradient(135deg, var(--light-green) 0%, #f1f8e9 100%);
            border: none;
            border-radius: 15px !important;
            font-weight: 600;
            color: var(--dark-green);
            padding: 1.25rem 1.5rem;
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 160, 71, 0.25);
        }

        .accordion-body {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 0 0 15px 15px;
            padding: 2rem;
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
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: rgba(67, 160, 71, 0.1);
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid var(--accent-green);
        }

        .info-item strong {
            color: var(--dark-green);
        }

        .btn-download {
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

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .icon-total { color: #2196f3; }
        .icon-approved { color: #4caf50; }
        .icon-pending { color: #ff9800; }
        .icon-rejected { color: #f44336; }
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
            <i class="bi bi-clock-history"></i>
            Enrollment History
        </h2>

        <?php if ($student): ?>
            <div class="history-card">
                <h4 class="text-primary mb-3">
                    <i class="bi bi-person-badge"></i>
                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                    <small class="text-muted">(ID: <?= htmlspecialchars($student['student_id']) ?>)</small>
                </h4>

                <!-- Summary Statistics -->
                <div class="summary-stats">
                    <div class="stat-card">
                        <i class="bi bi-journal-text stat-icon icon-total"></i>
                        <h5><?= count($enrollments) ?></h5>
                        <small class="text-muted">Total Enrollments</small>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-check-circle stat-icon icon-approved"></i>
                        <h5><?= count(array_filter($enrollments, fn($e) => strtolower($e['status']) === 'approved')) ?></h5>
                        <small class="text-muted">Approved</small>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-clock stat-icon icon-pending"></i>
                        <h5><?= count(array_filter($enrollments, fn($e) => strtolower($e['status']) === 'pending')) ?></h5>
                        <small class="text-muted">Pending</small>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-x-circle stat-icon icon-rejected"></i>
                        <h5><?= count(array_filter($enrollments, fn($e) => strtolower($e['status']) === 'rejected')) ?></h5>
                        <small class="text-muted">Rejected</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="history-card">
            <?php if (empty($enrollments)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="text-muted mt-3">No Enrollment History</h4>
                    <p class="text-muted">You haven't submitted any enrollment applications yet.</p>
                    <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-download">
                        <i class="bi bi-journal-plus me-2"></i>Start Your First Enrollment
                    </a>
                </div>
            <?php else: ?>
                <div class="accordion" id="historyAccordion">
                    <?php foreach ($enrollments as $index => $enrollment): ?>
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $index ?>" aria-expanded="false" 
                                        aria-controls="collapse<?= $index ?>">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div>
                                            <strong><?= htmlspecialchars($enrollment['semester']) ?> - <?= htmlspecialchars($enrollment['school_year']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($enrollment['program']) ?></small>
                                        </div>
                                        <span class="badge <?= getStatusBadge($enrollment['status']) ?>">
                                            <i class="bi <?= getStatusIcon($enrollment['status']) ?> me-1"></i>
                                            <?= htmlspecialchars($enrollment['status']) ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" 
                                 aria-labelledby="heading<?= $index ?>" data-bs-parent="#historyAccordion">
                                <div class="accordion-body">
                                    <!-- Enrollment Details -->
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <strong><i class="bi bi-mortarboard me-2"></i>Program:</strong><br>
                                            <?= htmlspecialchars($enrollment['program']) ?>
                                        </div>
                                        <div class="info-item">
                                            <strong><i class="bi bi-bookmark me-2"></i>Year Level:</strong><br>
                                            <?= htmlspecialchars($enrollment['year']) ?>
                                        </div>
                                        <div class="info-item">
                                            <strong><i class="bi bi-people me-2"></i>Section:</strong><br>
                                            <?= htmlspecialchars($enrollment['section']) ?>
                                        </div>
                                        <div class="info-item">
                                            <strong><i class="bi bi-calendar me-2"></i>Date Submitted:</strong><br>
                                            <?= date("F j, Y g:i A", strtotime($enrollment['date'])) ?>
                                        </div>
                                    </div>

                                    <?php if ($enrollment['remarks']): ?>
                                        <div class="alert alert-info">
                                            <strong><i class="bi bi-info-circle me-2"></i>Remarks:</strong><br>
                                            <?= nl2br(htmlspecialchars($enrollment['remarks'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Subjects Table -->
                                    <?php if (!empty($enrollment['subjects'])): ?>
                                        <h5 class="mt-4 mb-3">
                                            <i class="bi bi-book me-2"></i>Enrolled Subjects 
                                            <span class="badge bg-primary"><?= count($enrollment['subjects']) ?> subjects</span>
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
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
                                                    <?php 
                                                    $totalUnits = 0;
                                                    foreach ($enrollment['subjects'] as $subject): 
                                                        $totalUnits += $subject['units'];
                                                    ?>
                                                        <tr>
                                                            <td><strong><?= htmlspecialchars($subject['code']) ?></strong></td>
                                                            <td><?= htmlspecialchars($subject['title']) ?></td>
                                                            <td><?= htmlspecialchars($subject['units']) ?></td>
                                                            <td><small><?= htmlspecialchars($subject['schedule']) ?></small></td>
                                                            <td><?= htmlspecialchars($subject['room']) ?></td>
                                                            <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-success">
                                                        <th colspan="2">Total Units</th>
                                                        <th><?= $totalUnits ?></th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            No subjects assigned for this enrollment.
                                        </div>
                                    <?php endif; ?>

                                    <!-- Action Buttons -->
                                    <div class="mt-4">
                                        <?php if (strtolower($enrollment['status']) === 'approved'): ?>
                                            <a href="../Enrollment/print-enrollment-slip.php?id=<?= $enrollment['id'] ?>" 
                                               class="btn-download" target="_blank">
                                                <i class="bi bi-printer me-2"></i>Print Enrollment Slip
                                            </a>
                                            <a href="#" class="btn-download" onclick="downloadPDF(<?= $enrollment['id'] ?>)">
                                                <i class="bi bi-download me-2"></i>Download PDF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadPDF(enrollmentId) {
            // In a real implementation, this would generate and download a PDF
            alert('PDF download feature will be implemented with a proper PDF library.');
        }

        // Auto-expand the first (most recent) enrollment
        document.addEventListener('DOMContentLoaded', function() {
            const firstAccordion = document.querySelector('#collapse0');
            const firstButton = document.querySelector('[data-bs-target="#collapse0"]');
            
            if (firstAccordion && firstButton) {
                firstAccordion.classList.add('show');
                firstButton.classList.remove('collapsed');
                firstButton.setAttribute('aria-expanded', 'true');
            }
        });
    </script>
</body>
</html>
