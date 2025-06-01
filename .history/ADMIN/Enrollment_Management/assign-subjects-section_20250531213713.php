<?php
// Database connection
$host = 'localhost';
$db   = 'enrollment_system';
$user = 'root';        // your DB user
$pass = '';            // your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $studentName = trim($_POST['studentName'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $yearLevel = trim($_POST['yearLevel'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $subjects = $_POST['subjects'] ?? [];

    if ($studentId && $studentName && $program && $yearLevel && $section && count($subjects) > 0) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert or update student (if student_id exists, update info)
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
            $stmt->execute([$studentId]);
            $existingStudent = $stmt->fetch();

            if ($existingStudent) {
                $studentDbId = $existingStudent['id'];
                // Update student info
                $stmt = $pdo->prepare("UPDATE students SET student_name=?, program=?, year_level=?, section=? WHERE id=?");
                $stmt->execute([$studentName, $program, $yearLevel, $section, $studentDbId]);

                // Clear existing assigned subjects first
                $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE student_id=?");
                $stmt->execute([$studentDbId]);
            } else {
                // Insert new student
                $stmt = $pdo->prepare("INSERT INTO students (student_id, student_name, program, year_level, section) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$studentId, $studentName, $program, $yearLevel, $section]);
                $studentDbId = $pdo->lastInsertId();
            }

            // Assign subjects
            $subjectStmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ?");
            $insertAssign = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");

            foreach ($subjects as $subjectName) {
                $subjectStmt->execute([$subjectName]);
                $subject = $subjectStmt->fetch();
                if ($subject) {
                    $insertAssign->execute([$studentDbId, $subject['id']]);
                }
            }

            $pdo->commit();

            $message = "<div class='alert alert-success mt-4'>
                Assigned <strong>" . htmlspecialchars($studentName) . "</strong> (ID: " . htmlspecialchars($studentId) . ") to <strong>" . htmlspecialchars($program) . "</strong>, <strong>" . htmlspecialchars($yearLevel) . "</strong>, Section <strong>" . htmlspecialchars($section) . "</strong>.<br>
                Subjects: <em>" . implode(', ', array_map('htmlspecialchars', $subjects)) . "</em>
                </div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger mt-4'>Error assigning subjects: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger mt-4'>Please fill in all required fields and select at least one subject.</div>";
    }
}

// Fetch all subjects for the dropdown
$subjectsList = $pdo->query("SELECT subject_title FROM subjects ORDER BY subject_title ASC")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assign Sections & Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f1f8e9; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2e7d32; }
        .navbar-brand, .nav-link { color: #fff !important; font-weight: 600; letter-spacing: 0.05em; }
        .nav-link:hover { color: #c8e6c9 !important; }
        .school-logo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; margin-right: 10px; border: 2px solid #fff; }
        .card { border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); padding: 2rem; }
        h2 { color: #2e7d32; font-weight: 700; }
        .divider { height: 1px; background: #c5e1a5; margin: 1.5rem 0; }
        .form-floating label { font-size: 0.9rem; }
        select[multiple] { height: 150px; }
        @media (max-width: 575.98px) { .card { padding: 1rem; } }
    </style>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
            Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4 text-center">Assign Sections & Subjects</h2>

    <div class="card mx-auto" style="max-width: 900px;">
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="studentId" name="studentId" placeholder="Student ID" value="<?= htmlspecialchars($_POST['studentId'] ?? '') ?>" required>
                        <label for="studentId">Student ID</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="studentName" name="studentName" placeholder="Student Name" value="<?= htmlspecialchars($_POST['studentName'] ?? '') ?>" required>
                        <label for="studentName">Student Name</label>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="program" name="program" required>
                            <option value="" disabled <?= empty($_POST['program']) ? 'selected' : '' ?>>Select</option>
                            <?php 
                            $programs = ['IT', 'HRMT', 'ECT', 'HST', 'ET', 'TVET'];
                            foreach ($programs as $prog): ?>
                                <option value="<?= $prog ?>" <?= ($_POST['program'] ?? '') === $prog ? 'selected' : '' ?>><?= $prog ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="program">Program</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="yearLevel" name="yearLevel" required>
                            <option value="" disabled <?= empty($_POST['yearLevel']) ? 'selected' : '' ?>>Select</option>
                            <?php 
                            $years = ['1st Year', '2nd Year', '3rd Year'];
                            foreach ($years as $yr): ?>
                                <option value="<?= $yr ?>" <?= ($_POST['yearLevel'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="yearLevel">Year Level</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="section" name="section" required>
                            <option value="" disabled <?= empty($_POST['section']) ? 'selected' : '' ?>>Select</option>
                            <?php foreach (range('A', 'H') as $sec): ?>
                                <option value="<?= $sec ?>" <?= ($_POST['section'] ?? '') === $sec ? 'selected' : '' ?>><?= $sec ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="section">Section</label>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="mb-3">
                <label for="subjects" class="form-label fw-bold">Assign Subjects (Manual)</label>
                <select multiple class="form-select" id="subjects" name="subjects[]" required>
                    <?php
                    foreach ($subjectsList as $subject) {
                        $selected = (isset($_POST['subjects']) && in_array($subject, $_POST['subjects'])) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($subject) . "\" $selected>" . htmlspecialchars($subject) . "</option>";
                    }
                    ?>
                </select>
                <small class="text-muted">Hold Ctrl (or Cmd) to select multiple subjects.</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-outline-secondary">Clear</button>
                <button type="submit" class="btn btn-success">Assign</button>
            </div>
        </form>

        <?= $message ?>
    </div>
</div>

</body>
</html>
