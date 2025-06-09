<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';
$student_id = $_SESSION['student_id'];

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program = trim($_POST['program'] ?? '');
    $yearLevel = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $schoolYear = trim($_POST['school_year'] ?? '');

    // Validation
    if (empty($program)) $errors[] = "Program is required.";
    if (empty($yearLevel)) $errors[] = "Year Level is required.";
    if (empty($semester)) $errors[] = "Semester is required.";
    if (empty($section)) $errors[] = "Section is required.";
    if (empty($schoolYear)) $errors[] = "School Year is required.";

    if ($existingEnrollment && $existingEnrollment['status'] === 'approved') {
        $errors[] = "You already have an approved enrollment for this period.";
    }

    if (empty($errors)) {
        try {
            // If there's a pending enrollment, update it; otherwise, create new
            if ($existingEnrollment && $existingEnrollment['status'] === 'pending') {
                $stmt = $pdo->prepare("UPDATE enrollments SET program = ?, year_level = ?, semester = ?, section = ?, school_year = ?, date_submitted = NOW() WHERE id = ?");
                $stmt->execute([$program, $yearLevel, $semester, $section, $schoolYear, $existingEnrollment['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, program, year_level, semester, section, school_year, status, date_submitted) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$student_id, $program, $yearLevel, $semester, $section, $schoolYear]);
            }
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get current school year
$currentYear = date('Y');
$nextYear = $currentYear + 1;
$defaultSchoolYear = $currentYear . '-' . $nextYear;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enrollment Form - Student Portal</title>
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

        .form-container {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 600px;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .form-title {
            color: var(--primary-green);
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .form-title::after {
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

        .form-label {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-select, .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.25rem rgba(67, 160, 71, 0.25);
            background-color: #fff;
        }

        .btn-submit {
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

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .warning-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .existing-enrollment {
            background: linear-gradient(135deg, #cce5ff 0%, #b3d9ff 100%);
            border: 1px solid #007bff;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
        <div class="form-container">
            <h2 class="form-title">
                <h3><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h3>
                <i class="bi bi-journal-plus"></i>
                Enrollment Application Form
            </h2>

            <?php if ($existingEnrollment): ?>
                <div class="existing-enrollment">
                    <h5><i class="bi bi-info-circle"></i> Existing Enrollment Found</h5>
                    <p><strong>Status:</strong> <?= ucfirst($existingEnrollment['status']) ?></p>
                    <p><strong>Program:</strong> <?= htmlspecialchars($existingEnrollment['program']) ?></p>
                    <p><strong>Year Level:</strong> <?= htmlspecialchars($existingEnrollment['year_level']) ?></p>
                    <p><strong>Semester:</strong> <?= htmlspecialchars($existingEnrollment['semester']) ?></p>
                    <?php if ($existingEnrollment['status'] === 'pending'): ?>
                        <p class="text-warning mb-0">You can update your enrollment details below.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> Enrollment Submitted Successfully!</h5>
                    <p class="mb-0">Your enrollment application has been submitted and is now pending review by the registrar.</p>
                    <hr>
                    <a href="../MyEnrollAssignUpload/enrollment-status.php" class="btn btn-success btn-sm">View Status</a>
                    <a href="../student-dashboard.php" class="btn btn-outline-success btn-sm">Back to Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" action="" id="enrollmentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="program" class="form-label">
                                <i class="bi bi-mortarboard"></i> Program
                            </label>
                            <select class="form-select" name="program" id="program" required>
                                <option value="">Select Program</option>
                                <option value="Information Technology" <?= ($_POST['program'] ?? '') === 'Information Technology' ? 'selected' : '' ?>>IT</option>

                                <option value="Hotel and Restaurant Management Technology" <?= ($_POST['program'] ?? '') === 'Hotel and Restaurant Management Technology' ? 'selected' : '' ?>>HRMT</option>

                                <option value="Electronics and Computer Technology" <?= ($_POST['program'] ?? '') === 'Electronics and Computer Technology' ? 'selected' : '' ?>>ECT</option>

                                <option value="Hospitality Services technology" <?= ($_POST['program'] ?? '') === 'Hospitality Services technology' ? 'selected' : '' ?>>HST</option>

                                <option value="Enterpreneurship Technology" <?= ($_POST['program'] ?? '') === 'Enterpreneurship Technology' ? 'selected' : '' ?>>ET</option>

                                <option value="Techncal Vocational Education Techonlogy" <?= ($_POST['program'] ?? '') === 'Techncal Vocational Education Techonlogy' ? 'selected' : '' ?>>TVET</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="year_level" class="form-label">
                                <i class="bi bi-calendar3"></i> Year Level
                            </label>
                            <select class="form-select" name="year_level" id="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1st Year" <?= ($_POST['year_level'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= ($_POST['year_level'] ?? '') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= ($_POST['year_level'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="semester" class="form-label">
                                <i class="bi bi-calendar-week"></i> Semester
                            </label>
                            <select class="form-select" name="semester" id="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1st Semester" <?= ($_POST['semester'] ?? '') === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                <option value="2nd Semester" <?= ($_POST['semester'] ?? '') === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                <option value="Summer" <?= ($_POST['semester'] ?? '') === 'Summer' ? 'selected' : '' ?>>Summer</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="section" class="form-label">
                                <i class="bi bi-people"></i> Section
                            </label>
                            <select class="form-select" name="section" id="section" required>
                                <option value="">Select Section</option>
                                <option value="A" <?= ($_POST['section'] ?? '') === 'A' ? 'selected' : '' ?>>Section A</option>

                                <option value="B" <?= ($_POST['section'] ?? '') === 'B' ? 'selected' : '' ?>>Section B</option>

                                <option value="C" <?= ($_POST['section'] ?? '') === 'C' ? 'selected' : '' ?>>Section C</option>

                                <option value="D" <?= ($_POST['section'] ?? '') === 'D' ? 'selected' : '' ?>>Section D</option>

                                <option value="E" <?= ($_POST['section'] ?? '') === 'E' ? 'selected' : '' ?>>Section E</option>

                                <option value="F" <?= ($_POST['section'] ?? '') === 'F' ? 'selected' : '' ?>>Section E</option>

                                <option value="G" <?= ($_POST['section'] ?? '') === 'G' ? 'selected' : '' ?>>Section E</option>

                                <option value="H" <?= ($_POST['section'] ?? '') === 'H' ? 'selected' : '' ?>>Section H</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="school_year" class="form-label">
                            <i class="bi bi-calendar-range"></i> School Year
                        </label>
                        <input type="text" class="form-control" name="school_year" id="school_year" 
                            value="<?= $_POST['school_year'] ?? $defaultSchoolYear ?>" 
                            placeholder="e.g., 2024-2025" required>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send"></i> Submit Enrollment Application
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and enhancement
        document.getElementById('enrollmentForm')?.addEventListener('submit', function(e) {
            const requiredFields = ['program', 'year_level', 'semester', 'section', 'school_year'];
            let isValid = true;

            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    element.classList.add('is-invalid');
                    isValid = false;
                } else {
                    element.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Remove invalid class on input
        document.querySelectorAll('.form-select, .form-control').forEach(element => {
            element.addEventListener('change', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>
