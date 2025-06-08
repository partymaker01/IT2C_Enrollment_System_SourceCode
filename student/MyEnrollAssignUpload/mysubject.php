<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

// Fetch assigned subjects for the student
$stmt = $pdo->prepare("
    SELECT s.*, ss.status as enrollment_status, ss.grade 
    FROM subjects s 
    JOIN student_subjects ss ON s.id = ss.subject_id 
    WHERE ss.student_id = ? 
    ORDER BY s.subject_code ASC
");
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll();

// If no subjects found, get sample subjects for demonstration
if (empty($subjects)) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE status = 'Active' ORDER BY subject_code ASC LIMIT 5");
    $stmt->execute();
    $subjects = $stmt->fetchAll();
    
    // Add default enrollment status for display
    foreach ($subjects as &$subject) {
        $subject['enrollment_status'] = 'Enrolled';
        $subject['grade'] = null;
    }
}

$weekdays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

// Function to parse days from database
function parseDays($dayString) {
    if (empty($dayString)) return [];
    return array_map('trim', explode(',', $dayString));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Subjects - Student Portal</title>
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

        .subjects-card {
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

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
            transform: scale(1.01);
        }

        .status-enrolled {
            color: var(--primary-green);
            font-weight: 600;
        }

        .status-pending {
            color: #fbc02d;
            font-weight: 600;
        }

        .status-dropped {
            color: #e53935;
            font-weight: 600;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .calendar-day {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .calendar-day:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .calendar-day h6 {
            border-bottom: 2px solid var(--accent-green);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            color: var(--primary-green);
        }

        .class-item {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            margin-bottom: 0.5rem;
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .class-item:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
        }

        .class-item.pending {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #000;
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
            margin: 0.25rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .summary-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .icon-subjects { color: #4caf50; }
        .icon-units { color: #2196f3; }
        .icon-schedule { color: #ff9800; }
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
            <i class="bi bi-book"></i>
            My Subjects & Class Schedule
        </h2>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <i class="bi bi-book-fill summary-icon icon-subjects"></i>
                <h5>Total Subjects</h5>
                <p class="display-6 mb-0"><?= count($subjects) ?></p>
            </div>
            <div class="summary-card">
                <i class="bi bi-calculator summary-icon icon-units"></i>
                <h5>Total Units</h5>
                <p class="display-6 mb-0"><?= array_sum(array_column($subjects, 'units')) ?></p>
            </div>
            <div class="summary-card">
                <i class="bi bi-clock summary-icon icon-schedule"></i>
                <h5>Class Days</h5>
                <p class="display-6 mb-0"><?= count(array_unique(array_merge(...array_map('parseDays', array_column($subjects, 'day'))))) ?></p>
            </div>
        </div>

        <!-- Subjects Table -->
        <div class="subjects-card">
            <h4 class="section-title">Enrolled Subjects</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Title</th>
                            <th>Units</th>
                            <th>Schedule</th>
                            <th>Instructor</th>
                            <th>Room</th>
                            <th>Status</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subjects)): ?>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($subject['subject_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($subject['subject_title']) ?></td>
                                    <td><?= htmlspecialchars($subject['units']) ?></td>
                                    <td>
                                        <small>
                                            <?= htmlspecialchars($subject['day']) ?><br>
                                            <?= htmlspecialchars($subject['time']) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                    <td><?= htmlspecialchars($subject['room']) ?></td>
                                    <td>
                                        <span class="status-<?= strtolower($subject['enrollment_status'] ?? 'enrolled') ?>">
                                            <i class="bi bi-<?= strtolower($subject['enrollment_status'] ?? 'enrolled') === 'enrolled' ? 'check-circle' : 'clock' ?>"></i>
                                            <?= htmlspecialchars($subject['enrollment_status'] ?? 'Enrolled') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($subject['grade']): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($subject['grade']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-book-x" style="font-size: 3rem; color: #6c757d;"></i>
                                    <p class="text-muted mt-2">No subjects assigned yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Weekly Schedule -->
        <?php if (!empty($subjects)): ?>
            <div class="subjects-card">
                <h4 class="section-title">Weekly Class Schedule</h4>
                <div class="calendar">
                    <?php foreach ($weekdays as $day): ?>
                        <div class="calendar-day">
                            <h6><i class="bi bi-calendar-day"></i> <?= $day ?></h6>
                            <?php
                            $hasClass = false;
                            foreach ($subjects as $subject) {
                                $subjectDays = parseDays($subject['day']);
                                if (in_array($day, $subjectDays)) {
                                    $hasClass = true;
                                    $statusClass = strtolower($subject['enrollment_status'] ?? 'enrolled') === 'pending' ? 'pending' : '';
                                    echo "<div class='class-item $statusClass'>"
                                         . "<strong>" . htmlspecialchars($subject['subject_code']) . "</strong><br>"
                                         . htmlspecialchars($subject['subject_title']) . "<br>"
                                         . "<small><i class='bi bi-clock'></i> " . htmlspecialchars($subject['time']) . "</small><br>"
                                         . "<small><i class='bi bi-geo-alt'></i> " . htmlspecialchars($subject['room']) . "</small>"
                                         . "</div>";
                                }
                            }
                            if (!$hasClass) {
                                echo "<div class='text-center py-3'>"
                                     . "<i class='bi bi-calendar-x' style='font-size: 2rem; color: #6c757d;'></i><br>"
                                     . "<small class='text-muted'>No classes</small>"
                                     . "</div>";
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="#" class="btn-custom" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Schedule
                </a>
                <a href="#" class="btn-custom" onclick="downloadPDF()">
                    <i class="bi bi-download"></i> Download PDF
                </a>
                <a href="../Enrollment/view-enrollment-status.php" class="btn-custom">
                    <i class="bi bi-eye"></i> View Enrollment Status
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            // Simple implementation - in a real system, you'd generate a proper PDF
            alert('PDF download feature will be implemented with a proper PDF library.');
        }

        // Print styles
        const printStyles = `
            @media print {
                .navbar, .btn-custom { display: none !important; }
                .subjects-card { box-shadow: none !important; }
                .calendar-day { break-inside: avoid; }
            }
        `;
        
        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = printStyles;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>
