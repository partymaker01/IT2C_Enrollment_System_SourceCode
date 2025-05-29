<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../logregfor/login.php");
    exit;
}

// DB connection
$host = 'localhost';
$dbname = 'enrollment_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$student_id = $_SESSION['student_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    session_destroy();
    header("Location: ../logregfor/login.php");
    exit;
}

$studentName = $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name'];
$studentID = $student['student_number'] ?? 'Not Assigned';
$course = $student['course'] ?? 'N/A';
$yearLevel = $student['year_level'] ?? 'N/A';
$photo = $student['photo'] ?? 'uploads/ran.jpg';

// Fixed SQL: use 'year' instead of 'school_year'
$enrollInfo = $pdo->prepare("SELECT status, program, semester, year FROM enrollments WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 1");
$enrollInfo->execute([$student_id]);
$enrollment = $enrollInfo->fetch(PDO::FETCH_ASSOC);

$enrollmentStatus = $enrollment['status'] ?? 'Not Enrolled';
$program = $enrollment['program'] ?? $course;
$semesterText = $enrollment['semester'] ?? 'N/A';
$schoolYear = $enrollment['year'] ?? 'N/A';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile'])) {
    $targetDir = "uploads/";
    $fileName = uniqid() . "_" . basename($_FILES["profile"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (move_uploaded_file($_FILES["profile"]["tmp_name"], $targetFile)) {
        $update = $pdo->prepare("UPDATE students SET photo = ? WHERE id = ?");
        $update->execute([$targetFile, $student_id]);
        $photo = $targetFile;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f0f8ff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
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
    .profile-card {
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(46, 125, 50, 0.15);
      background: #fff;
      padding: 1.25rem;
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .profile-pic {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #2e7d32;
      flex-shrink: 0;
    }
    .profile-info h4 {
      margin-bottom: 0.2rem;
      color: #2e7d32;
      font-weight: 700;
    }
    .profile-info p {
      color: #555;
      font-size: 0.95rem;
    }
    .card {
      border-radius: 12px;
      transition: transform 0.2s ease-in-out;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    .card-title {
      color:rgb(30, 36, 30);
      font-weight: 600;
    }
    .btn {
      font-weight: 600;
    }
    h4.section-title {
      margin-top: 3rem;
      margin-bottom: 1rem;
      color: #2e7d32;
      font-weight: 700;
      border-bottom: 2px solid #2e7d32;
      padding-bottom: 0.25rem;
    }
    .list-group-item {
      cursor: pointer;
      transition: background-color 0.15s ease-in-out;
    }
    .list-group-item:hover, 
    .list-group-item:focus {
      background-color: #d0f0c0;
      color: #2e7d32;
      font-weight: 600;
      outline: none;
    }
    footer {
      margin-top: auto;
      padding: 1rem 0;
      background-color: #2e7d32;
      color: #fff;
      text-align: center;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="/IT2C_Enrollment_System_SourceCode/student/student-dashboard.php">
      Student Dashboard
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/logregfor/logout.php">
          Logout
        </a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <section class="profile-card">
    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Student Photo" class="profile-pic" />
    <div class="profile-info">
      <h4><?php echo htmlspecialchars($studentName); ?></h4>
      <p><?php echo htmlspecialchars($course) . " - " . htmlspecialchars($yearLevel); ?> | Student ID: <?php echo htmlspecialchars($studentID); ?></p>
    </div>
  </section>

      <section class="row g-4 my-4">
      <div class="col-12 col-md-4">
        <div class="card bg-light h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">
              My Enrollment Status
            </h5>
            <p>
              Status:
              <span class="text-success fw-bold">
              Enrolled
            </span></p>
            <a href="/IT2C_Enrollment_System_SourceCode/student/MyEnrollAssignUpload/enrollment-status.php" class="btn btn-success btn-sm mt-auto align-self-start">
              View Details
            </a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card bg-light h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">
              Assigned Subjects
            </h5>
            <p>
              View your current subjects and schedule.
            </p>
            <a href="/IT2C_Enrollment_System_SourceCode/student/MyEnrollAssignUpload/mysubject.php" class="btn btn-primary btn-sm mt-auto align-self-start">
              View Subjects
            </a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card bg-light h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">
              Upload Documents
            </h5>
            <p>
              Submit required school documents.
            </p>
            <a href="/IT2C_Enrollment_System_SourceCode/student/MyEnrollAssignUpload/document-upload.php" class="btn btn-warning btn-sm mt-auto align-self-start">
              Upload Now
            </a>
          </div>
        </div>
      </div>
    </section>

    <section class="row g-3">
      <div class="col-12 col-md-4">
        <div class="card bg-success text-white h-100">
          <div class="card-body">
            <h5 class="card-title">
              Enrollment Status
            </h5>
            <p class="card-text"><?php echo htmlspecialchars($enrollmentStatus); ?></p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card bg-primary text-white h-100">
          <div class="card-body">
            <h5 class="card-title">Program</h5>
            <p class="card-text"><?php echo htmlspecialchars($program); ?></p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card bg-warning text-dark h-100">
          <div class="card-body">
            <h5 class="card-title">
              Semester
            </h5>
            <p class="card-text"><?php echo htmlspecialchars($semesterText . ", SY " . $schoolYear); ?></p>
          </div>
        </div>
      </div>
    </section>

    <section>
      <h4 class="section-title">
        Enrollment
      </h4>
      <div class="list-group">
        <a href="/IT2C_Enrollment_System_SourceCode/student/Enrollment/fill-up-enrollment-form.php" class="list-group-item list-group-item-action">
          Fill-up Enrollment Form
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/Enrollment/view-enrollment-status.php" class="list-group-item list-group-item-action">
          View Enrollment Status
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/Enrollment/view_assigned_subjects.php" class="list-group-item list-group-item-action">
          View Assigned Subjects
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/Enrollment/print-enrollment-slip.php" class="list-group-item list-group-item-action">
          Print Enrollment Slip
        </a>
      </div>

      <h4 class="section-title mt-4">
        Documents
      </h4>
      <div class="list-group">
        <a href="/IT2C_Enrollment_System_SourceCode/student/Documents/UploadRequirements.php" class="list-group-item list-group-item-action">
          Upload Requirements
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/Documents/View Uploaded Files.php" class="list-group-item list-group-item-action">
          View Uploaded Files
        </a>
      </div>

      <h4 class="section-title mt-4">
        History & Profile
      </h4>
      <div class="list-group">
        <a href="/IT2C_Enrollment_System_SourceCode/student/history&Profile/Enrollment History.php" class="list-group-item list-group-item-action">
          Enrollment History
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/history&Profile/Edit Profile.php" class="list-group-item list-group-item-action">
          Edit Profile
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/history&Profile/Change Password.php" class="list-group-item list-group-item-action">
          Change Password
        </a>
      </div>

      <h4 class="section-title mt-4">
        Support & Notices
      </h4>
      <div class="list-group mb-5">
        <a href="/IT2C_Enrollment_System_SourceCode/student/Support&Notices/View Announcements.php" class="list-group-item list-group-item-action">
          View Announcements
        </a>
        <a href="/IT2C_Enrollment_System_SourceCode/student/Support&Notices/Help Desk.php" class="list-group-item list-group-item-action">
          Help Desk
        </a>
      </div>
    </section>
  </main>

  <?php include 'dashboard-sections.html'; ?>

</main>

<footer>
  &copy; <?php echo date('Y'); ?> Enrollment System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function () {
    document.getElementById('preview-img').src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>
