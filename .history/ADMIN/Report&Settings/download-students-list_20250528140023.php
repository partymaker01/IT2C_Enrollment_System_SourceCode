<?php
// DB credentials - update to your real values
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'enrollment_system';

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filters with defaults
$program = "Program";
$yearLevel = "Year Level";
$section = "Section";
$schoolYear = "School Year";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program = $_POST['program'] ?? $program;
    $yearLevel = $_POST['yearLevel'] ?? $yearLevel;
    $section = $_POST['section'] ?? $section;
    $schoolYear = $_POST['schoolYear'] ?? $schoolYear;
}

// Fetch distinct options for dropdowns from DB
function fetchOptions($conn, $column) {
    $sql = "SELECT DISTINCT $column FROM students ORDER BY $column ASC";
    $result = $conn->query($sql);
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row[$column];
        }
    }
    return $options;
}

$programOptions = fetchOptions($conn, 'program');
$yearLevelOptions = fetchOptions($conn, 'year_level');
$sectionOptions = fetchOptions($conn, 'section');
$schoolYearOptions = fetchOptions($conn, 'school_year');

// Prepare SQL query with filters
$sql = "SELECT * FROM students WHERE 1=1";

$params = [];
$types = "";

// Add filters only if they are set (and not default)
if ($program !== "Program") {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= "s";
}
if ($yearLevel !== "Year Level") {
    $sql .= " AND year_level = ?";
    $params[] = $yearLevel;
    $types .= "s";
}
if ($section !== "Section") {
    $sql .= " AND section = ?";
    $params[] = $section;
    $types .= "s";
}
if ($schoolYear !== "School Year") {
    $sql .= " AND school_year = ?";
    $params[] = $schoolYear;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Download Student Lists</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    /* Same styles as your original code */
    body {
      background: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 2rem 1rem;
      min-height: 100vh;
    }
    .navbar {
      width: 103%;
      margin-left: -20px; 
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
    .school-logo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
    }
    .container {
      max-width: 900px;
      top: 40px;
      background: #fff;
      padding: 2rem 2.5rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgb(0 0 0 / 0.1);
    }
    h2 {
      color: #198754;
      font-weight: 700;
      margin-bottom: 1.8rem;
      text-align: center;
    }
    .form-select:invalid {
      color: #6c757d;
    }
    .filter-label {
      font-weight: 600;
      margin-bottom: 0.3rem;
      color: #333;
    }
    .btn-apply {
      width: 100%;
      font-weight: 600;
      padding: 0.55rem 0;
      font-size: 1.05rem;
    }
    @media (min-width: 768px) {
      .btn-apply {
        width: auto;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
      }
    }
    table thead {
      background-color: #d1e7dd !important;
      color: #0f5132 !important;
    }
    .export-buttons button {
      min-width: 140px;
    }
    .export-buttons button:disabled {
      cursor: not-allowed;
      opacity: 0.6;
    }
  </style>
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

  <div class="container position-relative">

    <h2>Download Student Lists</h2>

    <form method="post" class="row g-3 mb-4 align-items-end">
      <div class="col-6 col-md-3">
        <label for="program" class="filter-label">Program</label>
        <select id="program" class="form-select" name="program" required>
          <option value="" disabled <?= $program === "Program" ? "selected" : "" ?>>Select Program</option>
          <?php foreach ($programOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt) ?>" <?= $program === $opt ? "selected" : "" ?>>
              <?= htmlspecialchars($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label for="yearLevel" class="filter-label">Year Level</label>
        <select id="yearLevel" class="form-select" name="yearLevel" required>
          <option value="" disabled <?= $yearLevel === "Year Level" ? "selected" : "" ?>>Select Year</option>
          <?php foreach ($yearLevelOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt) ?>" <?= $yearLevel === $opt ? "selected" : "" ?>>
              <?= htmlspecialchars($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label for="section" class="filter-label">Section</label>
        <select id="section" class="form-select" name="section" required>
          <option value="" disabled <?= $section === "Section" ? "selected" : "" ?>>Select Section</option>
          <?php foreach ($sectionOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt) ?>" <?= $section === $opt ? "selected" : "" ?>>
              <?= htmlspecialchars($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-3">
        <label for="schoolYear" class="filter-label">School Year</label>
        <select id="schoolYear" class="form-select" name="schoolYear" required>
          <option value="" disabled <?= $schoolYear === "School Year" ? "selected" : "" ?>>Select Year</option>
          <?php foreach ($schoolYearOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt) ?>" <?= $schoolYear === $opt ? "selected" : "" ?>>
              <?= htmlspecialchars($opt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-2 d-grid">
        <button type="submit" class="btn btn-success btn-apply">Apply Filter</button>
      </div>
    </form>

    <div class="mb-4">
      <strong>Showing results for:</strong><br />
      Program = <span class="text-success"><?= htmlspecialchars($program) ?></span>,
      Year Level = <span class="text-success"><?= htmlspecialchars($yearLevel) ?></span>,
      Section = <span class="text-success"><?= htmlspecialchars($section) ?></span>,
      School Year = <span class="text-success"><?= htmlspecialchars($schoolYear) ?></span>
    </div>

    <div class="table-responsive shadow-sm rounded mb-4">
      <table class="table table-bordered table-striped mb-0 align-middle text-center">
        <thead>
          <tr>
            <th scope="col">Student ID</th>
            <th scope="col">Full Name</th>
            <th scope="col">Program</th>
            <th scope="col">Year</th>
            <th scope="col">Section</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($student = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($student['student_id']) ?></td>
                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                <td><?= htmlspecialchars($student['program']) ?></td>
                <td><?= htmlspecialchars($student['year_level']) ?></td>
                <td><?= htmlspecialchars($student['section']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No students found for these filters.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end gap-3 export-buttons flex-wrap">
      <button class="btn btn-outline-primary" disabled>Export to PDF</button>
      <button class="btn btn-outline-success" disabled>Export to Excel</button>
      <button class="btn btn-outline-dark" disabled>Export to CSV</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>