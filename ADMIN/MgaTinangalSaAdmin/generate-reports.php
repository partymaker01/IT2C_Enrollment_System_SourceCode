<?php

$activeStudents = [
    ['id' => '20250001', 'name' => 'Jerick Dela Cruz Reyes', 'program' => 'IT', 'year' => '1st Year', 'status' => 'Enrolled'],
    ['id' => '20250002', 'name' => 'Jerick Dela Cruz', 'program' => 'ECT', 'year' => '2nd Year', 'status' => 'Enrolled'],
];

$subjectLoad = [
    ['id' => '20250001', 'name' => 'Jerick Dela Cruz Reyes', 'program' => 'HRMT', 'year' => '1st Year', 'status' => '5 Subjects'],
    ['id' => '20250003', 'name' => 'Pedro Santos', 'program' => 'HST', 'year' => '3rd Year', 'status' => '4 Subjects'],
];

$enrollmentSummary = [
    ['id' => '20250001', 'name' => 'Jerick Dela Cruz Reyes', 'program' => 'IT', 'year' => '1st Year', 'status' => 'Paid'],
    ['id' => '20250002', 'name' => 'Maria Clara', 'program' => 'HRMT', 'year' => '2nd Year', 'status' => 'Unpaid'],
];

$reportData = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['reportType'] ?? '';
    $schoolYear = $_POST['schoolYear'] ?? '';
    $semester = $_POST['semester'] ?? '';

    if ($reportType === 'Choose Report Type' || !$reportType) {
        $error = 'Please select a valid report type.';
    } else {
        switch ($reportType) {
            case 'Active Students':
                $reportData = $activeStudents;
                break;
            case 'Subject Load':
                $reportData = $subjectLoad;
                break;
            case 'Enrollment Summary':
                $reportData = $enrollmentSummary;
                break;
            default:
                $reportData = [];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Generate Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #f9f9f9;
    }
    .form-select:invalid {
      color: #6c757d;
    }
    .form-select option[disabled] {
      color: #6c757d;
    }
    table tbody tr:hover {
      background-color: #d1e7dd;
    }
    button:disabled {
      cursor: not-allowed;
      opacity: 0.6;
    }
  </style>
</head>
<body>
  <div class="container my-5">

    <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
      ← Back to Dashboard
    </a>

    <h2 class="text-center text-success mb-4">
      Generate Reports
    </h2>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3 mb-4 align-items-center">
      <div class="col-12 col-md-4">
        <select name="reportType" class="form-select" required aria-label="Select report type">
          <option value="" disabled <?= (!isset($reportType) || $reportType === '' || $reportType == 'Choose Report Type') ? 'selected' : '' ?>>Choose Report Type</option>
          <option <?= (isset($reportType) && $reportType == 'Active Students') ? 'selected' : '' ?>>Active Students</option>
          <option <?= (isset($reportType) && $reportType == 'Subject Load') ? 'selected' : '' ?>>Subject Load</option>
          <option <?= (isset($reportType) && $reportType == 'Enrollment Summary') ? 'selected' : '' ?>>Enrollment Summary</option>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <select name="schoolYear" class="form-select" required aria-label="Select school year">
          <option value="" disabled <?= (!isset($schoolYear) || $schoolYear === '' || $schoolYear == 'School Year') ? 'selected' : '' ?>>School Year</option>
          <option <?= (isset($schoolYear) && $schoolYear == '2023-2024') ? 'selected' : '' ?>>2023-2024</option>
          <option <?= (isset($schoolYear) && $schoolYear == '2024-2025') ? 'selected' : '' ?>>2024-2025</option>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <select name="semester" class="form-select" required aria-label="Select semester">
          <option value="" disabled <?= (!isset($semester) || $semester === '' || $semester == 'Semester') ? 'selected' : '' ?>>Semester</option>
          <option <?= (isset($semester) && $semester == '1st Semester') ? 'selected' : '' ?>>1st Semester</option>
          <option <?= (isset($semester) && $semester == '2nd Semester') ? 'selected' : '' ?>>2nd Semester</option>
        </select>
      </div>

      <div class="col-12 col-md-2 d-grid">
        <button type="submit" class="btn btn-success">Generate</button>
      </div>
    </form>

    <?php if (!empty($reportData)): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <strong>
            Report Preview
          </strong>
          <div class="small text-muted mt-1">
            Report: 
            <?= htmlspecialchars($reportType) ?> |
            School Year: 
            <?= htmlspecialchars($schoolYear) ?> |
            Semester: 
            <?= htmlspecialchars($semester) ?>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
              <thead class="table-success">
                <tr>
                  <th scope="col">
                    Student ID
                  </th>
                  <th scope="col">
                    Full Name
                  </th>
                  <th scope="col">
                    Program
                  </th>
                  <th scope="col">
                    Year Level
                  </th>
                  <th scope="col">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reportData as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['program']) ?></td>
                    <td><?= htmlspecialchars($row['year']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-end">
          <button class="btn btn-outline-primary" disabled>
            Download PDF (not implemented)
          </button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
