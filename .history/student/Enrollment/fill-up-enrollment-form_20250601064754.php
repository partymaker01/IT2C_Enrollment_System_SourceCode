<?php

session_start();

$student_id = $_SESSION['student_id'] ?? null;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "enrollment_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$program = $yearLevel = $semester = $section = "";
$submitted = false;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!$student_id) {
        $errors[] = "Student not logged in. Please log in again.";
    } else {
        $checkStudent = $conn->prepare("SELECT id FROM students WHERE id = ?");
        $checkStudent->bind_param("s", $student_id);
        $checkStudent->execute();
        $result = $checkStudent->get_result();

        if ($result->num_rows == 0) {
            $errors[] = "Student ID not found in the database.";
        }
        $checkStudent->close();
    }
          $program = $_POST['program'] ?? "";
          $yearLevel = $_POST['yearLevel'] ?? "";
          $semester = $_POST['semester'] ?? "";
          $section = $_POST['section'] ?? "";

          if (empty($program)) $errors[] = "Please select a Program.";
          if (empty($yearLevel)) $errors[] = "Please select a Year Level.";
          if (empty($semester)) $errors[] = "Please select a Semester.";
          if (empty($section)) $errors[] = "Please select a Section.";

        if ($stmt) {
            $stmt->bind_param("sssss", $student_id, $program, $yearLevel, $semester, $section);

            if ($stmt->execute()) {
                $submitted = true;
            } else {
                $errors[] = "Failed to submit enrollment. Please try again.";
            }

            $stmt->close();
        } else {
            $errors[] = "Failed to prepare SQL statement.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Fill-up Enrollment Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #e6f2e6; 
    }
    .navbar {
      background-color: #2e7d32;
    }
    .navbar-brand, .nav-link {
      color: #fff !important;
      font-weight: 600;
      letter-spacing: 0.05em;
    }
    .nav-link:hover {
      color: #c8e6c9 !important;
    }
    h2 {
      color: #2e7d32;
      font-weight: 700;
    }
    label {
      color: #0f0f0f; 
      font-weight: 600;
    }
    .form-select, .form-control {
      background-color: #f1f8e9;
      border-color: #81c784;
      color: #0f0f0f;
      transition: border-color 0.3s ease;
    }
    .form-select:focus, .form-control:focus {
      border-color: #388e3c;
      box-shadow: 0 0 0 0.25rem rgba(56, 142, 60, 0.25);
      outline: none;
    }
    .btn-primary {
      background-color: #2e7d32;
      border-color: #2e7d32;
      font-weight: 600;
    }
    .btn-primary:hover {
      background-color: #27632a;
      border-color: #27632a;
    }
    .form-container {
      max-width: 480px;
      background: white;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php">
        Student Dashboard
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/student//student-dashboard.php" class="btn btn-outline-secondary mb-3">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
        </ul>
      </div>
    </div>
  </nav>
  <div class="container my-5 d-flex justify-content-center">
    <div class="form-container">
      <h2 class="text-center mb-4">
        Fill-up Enrollment Form
      </h2>

      <?php if ($submitted): ?>
        <div class="alert alert-success text-center">
          <h5>
            Enrollment Submitted Successfully!
          </h5>
          <p><strong>
            Program:
          </strong> <?= htmlspecialchars($program) ?></p>
          <p><strong>
            Year Level:
          </strong> <?= htmlspecialchars($yearLevel) ?></p>
          <p><strong>
            Semester:
          </strong> <?= htmlspecialchars($semester) ?></p>
          <p><strong>
            Section:
          </strong> <?= htmlspecialchars($section) ?></p>
        </div>
        <div class="text-center">
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary mt-3">
            Submit Another Enrollment
          </a>
        </div>
      <?php else: ?>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
          <div class="mb-3">
            <label for="program" class="form-label">
              Program
            </label>
            <select class="form-select" name="program" id="program">
              <option value="">Select Program</option>
              <option value="IT" <?= $program == "IT" ? "selected" : "" ?>>IT</option>
              <option value="ECT" <?= $program == "ECT" ? "selected" : "" ?>>ECT</option>
              <option value="HRMT" <?= $program == "HRMT" ? "selected" : "" ?>>HRMT</option>
              <option value="HST" <?= $program == "HST" ? "selected" : "" ?>>HST</option>
              <option value="ET" <?= $program == "ET" ? "selected" : "" ?>>ET</option>
              <option value="TVET" <?= $program == "TVET" ? "selected" : "" ?>>TVET</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="yearLevel" class="form-label">
              Year Level
            </label>
            <select class="form-select" name="yearLevel" id="yearLevel">
              <option value="">Select Year Level</option>
              <option value="1st Year" <?= $yearLevel == "1st Year" ? "selected" : "" ?>>1st Year</option>
              <option value="2nd Year" <?= $yearLevel == "2nd Year" ? "selected" : "" ?>>2nd Year</option>
              <option value="3rd Year" <?= $yearLevel == "3rd Year" ? "selected" : "" ?>>3rd Year</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="semester" class="form-label">
              Semester
            </label>
            <select class="form-select" name="semester" id="semester">
              <option value="">Select Semester</option>
              <option value="1st Semester" <?= $semester == "1st Semester" ? "selected" : "" ?>>1st Semester</option>
              <option value="2nd Semester" <?= $semester == "2nd Semester" ? "selected" : "" ?>>2nd Semester</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="section" class="form-label">Section</label>
            <select class="form-select" name="section" id="section">
              <option value="">Select Section</option>
              <option value="Section A" <?= $section == "Section A" ? "selected" : "" ?>>A</option>
              <option value="Section B" <?= $section == "Section B" ? "selected" : "" ?>>B</option>
              <option value="Section C" <?= $section == "Section C" ? "selected" : "" ?>>C</option>
              <option value="Section D" <?= $section == "Section D" ? "selected" : "" ?>>D</option>
              <option value="Section E" <?= $section == "Section E" ? "selected" : "" ?>>E</option>
              <option value="Section F" <?= $section == "Section F" ? "selected" : "" ?>>F</option>
              <option value="Section G" <?= $section == "Section G" ? "selected" : "" ?>>G</option>
              <option value="Section H" <?= $section == "Section H" ? "selected" : "" ?>>H</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            Submit Enrollment
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
