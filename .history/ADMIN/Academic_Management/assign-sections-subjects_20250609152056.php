<?php
session_start();
include '../../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $yearLevel = trim($_POST['yearLevel'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $schoolYear = trim($_POST['schoolYear'] ?? '');
    $subjects = $_POST['subjects'] ?? [];

    if ($studentId && $program && $yearLevel && $section && $semester && $schoolYear && count($subjects) > 0) {
        try {
            $conn->begin_transaction();

            // Check if student exists
            $stmt = $conn->prepare("SELECT id, first_name, last_name FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if (!$student) {
                throw new Exception('Student not found. Please verify the Student ID.');
            }

            $studentDbId = $student['id'];
            $studentName = $student['first_name'] . ' ' . $student['last_name'];

            // Check if enrollment already exists for this semester
            $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND semester = ? AND school_year = ?");
            $stmt->bind_param("sss", $studentId, $semester, $schoolYear);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception('Student is already enrolled for this semester and school year.');
            }

            // Create enrollment record
            $stmt = $conn->prepare("INSERT INTO enrollments (student_id, program, year_level, section, semester, school_year, status, enrolled_by, date_enrolled) VALUES (?, ?, ?, ?, ?, ?, 'enrolled', ?, NOW())");
            $stmt->bind_param("ssssssi", $studentId, $program, $yearLevel, $section, $semester, $schoolYear, $_SESSION['admin_id']);
            $stmt->execute();
            $enrollmentId = $conn->insert_id;

            // Assign subjects
            $totalUnits = 0;
            $assignedSubjects = [];
            
            foreach ($subjects as $subjectId) {
                // Get subject details
                $stmt = $conn->prepare("SELECT subject_code, subject_title, units FROM subjects WHERE id = ?");
                $stmt->bind_param("i", $subjectId);
                $stmt->execute();
                $result = $stmt->get_result();
                $subject = $result->fetch_assoc();
                
                if ($subject) {
                    // Insert enrollment subject
                    $stmt = $conn->prepare("INSERT INTO enrollment_subjects (enrollment_id, subject_id, subject_code, subject_title, units) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissi", $enrollmentId, $subjectId, $subject['subject_code'], $subject['subject_title'], $subject['units']);
                    $stmt->execute();
                    
                    $totalUnits += $subject['units'];
                    $assignedSubjects[] = $subject['subject_code'] . ' - ' . $subject['subject_title'];
                }
            }

            // Update enrollment with total units
            $stmt = $conn->prepare("UPDATE enrollments SET total_units = ? WHERE id = ?");
            $stmt->bind_param("ii", $totalUnits, $enrollmentId);
            $stmt->execute();

            $conn->commit();

            $message = "Successfully assigned <strong>" . htmlspecialchars($studentName) . "</strong> (ID: " . htmlspecialchars($studentId) . ") to:<br>
                      Program: <strong>" . htmlspecialchars($program) . "</strong><br>
                      Year Level: <strong>" . htmlspecialchars($yearLevel) . "</strong><br>
                      Section: <strong>" . htmlspecialchars($section) . "</strong><br>
                      Semester: <strong>" . htmlspecialchars($semester) . " " . htmlspecialchars($schoolYear) . "</strong><br>
                      Total Units: <strong>" . $totalUnits . "</strong><br>
                      Subjects: <em>" . implode(', ', $assignedSubjects) . "</em>";
            $messageType = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'Please fill in all required fields and select at least one subject.';
        $messageType = 'danger';
    }
}

// Fetch subjects grouped by program and year level
$subjectsByProgram = [];
$stmt = $conn->prepare("SELECT id, subject_code, subject_title, units, program, year_level FROM subjects ORDER BY program, year_level, subject_code");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjectsByProgram[$row['program']][$row['year_level']][] = $row;
}

// Get current enrollment period
$currentPeriod = null;
$stmt = $conn->prepare("SELECT semester, school_year FROM enrollment_periods WHERE status = 'active' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $currentPeriod = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assign Sections & Subjects - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        body { 
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }
        .navbar { 
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand, .nav-link { 
            color: #fff !important; 
            font-weight: 600; 
            letter-spacing: 0.05em; 
        }
        .nav-link:hover { 
            color: #c8e6c9 !important; 
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }
        .school-logo { 
            width: 50px; 
            height: 50px; 
            object-fit: cover; 
            border-radius: 50%; 
            margin-right: 10px; 
            border: 2px solid #fff; 
        }
        .main-card { 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .period-card {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            color: white;
            border-radius: 15px;
            border: none;
        }
        h2 { 
            color: #2e7d32; 
            font-weight: 700; 
            text-align: center;
            margin-bottom: 2rem;
        }
        .divider { 
            height: 2px; 
            background: linear-gradient(90deg, #c5e1a5 0%, #2e7d32 50%, #c5e1a5 100%); 
            margin: 2rem 0; 
            border-radius: 1px;
        }
        .form-floating label { 
            font-size: 0.9rem; 
            color: #666;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }
        .subjects-container {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
        }
        .subject-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        .subject-item:hover {
            border-color: #2e7d32;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
        }
        .subject-item input[type="checkbox"]:checked + label {
            color: #2e7d32;
            font-weight: 600;
        }
        .btn-success {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .units-counter {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            color: #1976d2;
        }
        @media (max-width: 575.98px) { 
            .main-card { 
                margin: 10px;
                padding: 1rem; 
            } 
        }
    </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
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
              <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

<div class="container py-5">
    <!-- Current Period Display -->
    <?php if ($currentPeriod): ?>
    <div class="row mb-4">
      <div class="col-12">
        <div class="card period-card">
          <div class="card-body text-center">
            <h5 class="card-title mb-2">
              <i class="bi bi-calendar-event me-2"></i>Current Enrollment Period
            </h5>
            <p class="mb-0">
              <?= htmlspecialchars($currentPeriod['semester']) ?> - <?= htmlspecialchars($currentPeriod['school_year']) ?>
            </p>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <h2><i class="bi bi-person-plus-fill me-2"></i>Assign Sections & Subjects</h2>

    <div class="card main-card mx-auto" style="max-width: 1000px;">
        <div class="card-body p-4">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> mb-4">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="assignmentForm">
                <!-- Student Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="studentId" name="studentId" 
                                   placeholder="Student ID" value="<?= htmlspecialchars($_POST['studentId'] ?? '') ?>" required>
                            <label for="studentId"><i class="bi bi-person-badge me-1"></i>Student ID</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-outline-primary w-100 h-100" id="verifyStudent">
                            <i class="bi bi-search me-1"></i>Verify Student
                        </button>
                    </div>
                </div>

                <div id="studentInfo" style="display: none;" class="alert alert-info mb-4">
                    <strong>Student Found:</strong> <span id="studentName"></span>
                </div>

                <div class="divider"></div>

                <!-- Academic Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="program" name="program" required>
                                <option value="" disabled <?= empty($_POST['program']) ? 'selected' : '' ?>>Select</option>
                                <?php 
                                $programs = ['IT', 'HRMT', 'ECT', 'HST', 'ET', 'TVET'];
                                foreach ($programs as $prog): ?>
                                    <option value="<?= $prog ?>" <?= ($_POST['program'] ?? '') === $prog ? 'selected' : '' ?>><?= $prog ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="program"><i class="bi bi-mortarboard me-1"></i>Program</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="yearLevel" name="yearLevel" required>
                                <option value="" disabled <?= empty($_POST['yearLevel']) ? 'selected' : '' ?>>Select</option>
                                <?php 
                                $years = ['1st Year', '2nd Year', '3rd Year'];
                                foreach ($years as $yr): ?>
                                    <option value="<?= $yr ?>" <?= ($_POST['yearLevel'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="yearLevel"><i class="bi bi-bar-chart-steps me-1"></i>Year Level</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <select class="form-select" id="section" name="section" required>
                                <option value="" disabled <?= empty($_POST['section']) ? 'selected' : '' ?>>Select</option>
                                <?php foreach (range('A', 'H') as $sec): ?>
                                    <option value="<?= $sec ?>" <?= ($_POST['section'] ?? '') === $sec ? 'selected' : '' ?>><?= $sec ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="section"><i class="bi bi-collection me-1"></i>Section</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="" disabled>Select</option>
                                <option value="1st Semester" <?= ($currentPeriod['semester'] ?? '') === '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                                <option value="2nd Semester" <?= ($currentPeriod['semester'] ?? '') === '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                                <option value="Summer" <?= ($currentPeriod['semester'] ?? '') === 'Summer' ? 'selected' : '' ?>>Summer</option>
                            </select>
                            <label for="semester"><i class="bi bi-calendar3 me-1"></i>Semester</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="schoolYear" name="schoolYear" 
                                   placeholder="2024-2025" value="<?= htmlspecialchars($currentPeriod['school_year'] ?? '') ?>" required>
                            <label for="schoolYear"><i class="bi bi-calendar-range me-1"></i>School Year</label>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Subject Assignment -->
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold mb-3">
                            <i class="bi bi-book me-1"></i>Available Subjects
                        </label>
                        <div class="subjects-container" id="subjectsContainer">
                            <p class="text-muted text-center">Please select a program and year level to view available subjects.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-3">
                            <i class="bi bi-calculator me-1"></i>Units Summary
                        </label>
                        <div class="units-counter">
                            <div class="h4 mb-1" id="totalUnits">0</div>
                            <div class="small">Total Units</div>
                        </div>
                        <div class="mt-3">
                            <div class="small text-muted">
                                <strong>Selected Subjects:</strong>
                                <div id="selectedSubjects" class="mt-2">None selected</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="d-flex justify-content-end gap-3">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Form
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                        <i class="bi bi-check-circle me-1"></i>Assign Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Subject data from PHP
const subjectsByProgram = <?= json_encode($subjectsByProgram) ?>;

// Verify student
document.getElementById('verifyStudent').addEventListener('click', function() {
    const studentId = document.getElementById('studentId').value;
    if (!studentId) {
        alert('Please enter a Student ID');
        return;
    }

    // Simulate student verification (you can implement actual AJAX call)
    fetch('verify-student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'student_id=' + encodeURIComponent(studentId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('studentInfo').style.display = 'block';
            document.getElementById('studentName').textContent = data.name;
        } else {
            alert('Student not found');
            document.getElementById('studentInfo').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error verifying student');
    });
});

// Update subjects when program/year level changes
function updateSubjects() {
    const program = document.getElementById('program').value;
    const yearLevel = document.getElementById('yearLevel').value;
    const container = document.getElementById('subjectsContainer');

    if (!program || !yearLevel) {
        container.innerHTML = '<p class="text-muted text-center">Please select a program and year level to view available subjects.</p>';
        return;
    }

    const subjects = subjectsByProgram[program]?.[yearLevel] || [];
    
    if (subjects.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No subjects available for this program and year level.</p>';
        return;
    }

    let html = '';
    subjects.forEach(subject => {
        html += `
            <div class="subject-item">
                <div class="form-check">
                    <input class="form-check-input subject-checkbox" type="checkbox" 
                           value="${subject.id}" id="subject_${subject.id}" 
                           data-units="${subject.units}" data-title="${subject.subject_code} - ${subject.subject_title}">
                    <label class="form-check-label w-100" for="subject_${subject.id}">
                        <div class="d-flex justify-content-between">
                            <span><strong>${subject.subject_code}</strong> - ${subject.subject_title}</span>
                            <span class="badge bg-primary">${subject.units} units</span>
                        </div>
                    </label>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    // Add event listeners to checkboxes
    document.querySelectorAll('.subject-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateUnitsCounter);
    });
}

// Update units counter
function updateUnitsCounter() {
    const checkboxes = document.querySelectorAll('.subject-checkbox:checked');
    let totalUnits = 0;
    let selectedSubjects = [];

    checkboxes.forEach(checkbox => {
        totalUnits += parseInt(checkbox.dataset.units);
        selectedSubjects.push(checkbox.dataset.title);
    });

    document.getElementById('totalUnits').textContent = totalUnits;
    
    const selectedSubjectsDiv = document.getElementById('selectedSubjects');
    if (selectedSubjects.length > 0) {
        selectedSubjectsDiv.innerHTML = selectedSubjects.map(subject => 
            `<div class="small text-success mb-1"><i class="bi bi-check me-1"></i>${subject}</div>`
        ).join('');
    } else {
        selectedSubjectsDiv.innerHTML = '<div class="text-muted">None selected</div>';
    }

    // Enable/disable submit button
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = selectedSubjects.length === 0;
}

// Event listeners
document.getElementById('program').addEventListener('change', updateSubjects);
document.getElementById('yearLevel').addEventListener('change', updateSubjects);

// Form validation
document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    const selectedSubjects = document.querySelectorAll('.subject-checkbox:checked');
    if (selectedSubjects.length === 0) {
        e.preventDefault();
        alert('Please select at least one subject.');
        return;
    }

    if (!confirm('Are you sure you want to assign this student to the selected subjects?')) {
        e.preventDefault();
    }
});

// Initialize
updateSubjects();
</script>
</body>
</html>
