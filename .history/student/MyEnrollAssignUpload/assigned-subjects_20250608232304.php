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
    header("Location: ../../logregfor/login.php");
    exit;
}

// Get the latest approved enrollment for the student
$stmt = $pdo->prepare("
    SELECT * FROM enrollments 
    WHERE student_id = ? AND status = 'approved' 
    ORDER BY date_submitted DESC 
    LIMIT 1
");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

// Get semester and school year from GET, enrollment data, or use defaults
$semester = $_GET['semester'] ?? ($enrollment['semester'] ?? '1st Semester');
$school_year = $_GET['school_year'] ?? ($enrollment['school_year'] ?? '2024-2025');

// Use enrollment data for student info if available
$student_program = $enrollment['program'] ?? 'N/A';
$student_year_level = $enrollment['year_level'] ?? 'N/A';
$student_section = $enrollment['section'] ?? 'N/A';

// Fetch assigned subjects
$stmt = $pdo->prepare("
    SELECT s.subject_code, s.subject_title, s.instructor, s.day, s.time, s.room, s.units, ss.grade, ss.status
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    WHERE ss.student_id = ? AND ss.semester = ? AND ss.school_year = ?
    ORDER BY s.subject_code
");
$stmt->execute([$student_id, $semester, $school_year]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no subjects found, get sample subjects for demonstration
if (empty($subjects)) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE status = 'Active' ORDER BY subject_code ASC LIMIT 6");
    $stmt->execute();
    $sampleSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sampleSubjects as $subject) {
        $subjects[] = [
            'subject_code' => $subject['subject_code'],
            'subject_title' => $subject['subject_title'],
            'instructor' => $subject['instructor'],
            'day' => $subject['day'],
            'time' => $subject['time'],
            'room' => $subject['room'],
            'units' => $subject['units'],
            'grade' => null,
            'status' => 'Enrolled'
        ];
    }
}

$totalUnits = array_sum(array_column($subjects, 'units'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assigned Subjects - Student Portal</title>
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

        .student-header {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
        .icon-instructor { color: #ff9800; }

        .enrollment-status {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        @media print {
            .no-print { display: none !important; }
            .subjects-card { box-shadow: none !important; }
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
        <!-- Enrollment Status Alert -->
        <?php if (!$enrollment): ?>
            <div class="enrollment-status">
                <h5><i class="bi bi-exclamation-triangle me-2"></i>No Approved Enrollment Found</h5>
                <p class="mb-2">You don't have an approved enrollment yet. Please complete your enrollment first.</p>
                <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                    <i class="bi bi-plus-circle me-1"></i>Complete Enrollment
                </a>
            </div>
        <?php endif; ?>

        <!-- Student Header -->
        <div class="student-header text-center">
            <h3><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h3>
            <div class="row mt-3">
                <div class="col-md-4">
                    <strong>Program:</strong> <?= htmlspecialchars($student_program) ?>
                </div>
                <div class="col-md-4">
                    <strong>Year Level:</strong> <?= htmlspecialchars($student_year_level) ?>
                </div>
                <div class="col-md-4">
                    <strong>Section:</strong> <?= htmlspecialchars($student_section) ?>
                </div>
            </div>
            <?php if ($enrollment): ?>
                <div class="mt-2">
                    <small><i class="bi bi-check-circle me-1"></i>Enrollment Status: <strong><?= ucfirst($enrollment['status']) ?></strong></small>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">
            <i class="bi bi-book"></i>
            Assigned Subjects
        </h2>

        <!-- Filter Form -->
        <div class="filter-card no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="semester" class="form-label">
                        <i class="bi bi-calendar-week me-1"></i>Semester
                    </label>
                    <select class="form-select" id="semester" name="semester">
                        <option value="1st Semester" <?= $semester == '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                        <option value="2nd Semester" <?= $semester == '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                        <option value="Summer" <?= $semester == 'Summer' ? 'selected' : '' ?>>Summer</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="school_year" class="form-label">
                        <i class="bi bi-calendar-range me-1"></i>School Year
                    </label>
                    <input type="text" class="form-control" id="school_year" name="school_year" 
                           value="<?= htmlspecialchars($school_year) ?>" placeholder="e.g., 2024-2025">
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn-custom w-100">
                        <i class="bi bi-search me-1"></i>Load Subjects
                    </button>
                </div>
            </form>
        </div>

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
                <p class="display-6 mb-0"><?= $totalUnits ?></p>
            </div>
            <div class="summary-card">
                <i class="bi bi-person-workspace summary-icon icon-instructor"></i>
                <h5>Instructors</h5>
                <p class="display-6 mb-0"><?= count(array_unique(array_column($subjects, 'instructor'))) ?></p>
            </div>
        </div>

        <!-- Subjects Table -->
        <div class="subjects-card">
            <h4 class="section-title">Subject Details - <?= htmlspecialchars($semester) ?> (<?= htmlspecialchars($school_year) ?>)</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Title</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Units</th>
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
                                    <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                    <td>
                                        <small>
                                            <i class="bi bi-calendar-day me-1"></i><?= htmlspecialchars($subject['day']) ?><br>
                                            <i class="bi bi-clock me-1"></i><?= htmlspecialchars($subject['time']) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($subject['room']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($subject['units']) ?></span></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($subject['status'] ?? 'Enrolled') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($subject['grade']): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($subject['grade']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Ongoing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-book-x" style="font-size: 3rem; color: #6c757d;"></i>
                                    <p class="text-muted mt-2">No subjects found for <?= htmlspecialchars($semester) ?> (<?= htmlspecialchars($school_year) ?>)</p>
                                    <?php if (!$enrollment): ?>
                                        <a href="../Enrollment/fill-up-enrollment-form.php" class="btn-custom">
                                            <i class="bi bi-plus-circle me-1"></i>Complete Enrollment First
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Contact the registrar to assign subjects to your enrollment.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($subjects)): ?>
                <!-- Action Buttons -->
                <div class="text-center mt-4 no-print">
                    <button class="btn-custom" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print Schedule
                    </button>
                    <a href="mysubject.php" class="btn-custom">
                        <i class="bi bi-calendar-week me-1"></i>View Weekly Schedule
                    </a>
                    <a href="current-enrollment-status.php" class="btn-custom">
                        <i class="bi bi-eye me-1"></i>View Enrollment Status
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
