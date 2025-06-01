<?php
session_start();
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../logregfor/admin-login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// CHECK IF LOGGED IN
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../ADMIN/admin-login.php");
    exit();
}

include '../db.php';

// Sanitize session username output
$admin_username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');

// Dashboard variables
$total_students = 0;
$enrolled_semester = 0;
$programs_count = 0;
$course_data = [];

// Get total students and chart data
if ($result = $conn->query("SELECT course, COUNT(*) AS total FROM students GROUP BY course")) {
    while ($row = $result->fetch_assoc()) {
        $course_data[] = $row;
        $total_students += $row['total'];
    }
    $result->free();
}

// Enrolled this semester
if ($result = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'Enrolled'")) {
    $row = $result->fetch_assoc();
    $enrolled_semester = $row['total'] ?? 0;
    $result->free();
}

// Programs/Courses count
if ($result = $conn->query("SELECT COUNT(DISTINCT course) AS total FROM students")) {
    $row = $result->fetch_assoc();
    $programs_count = $row['total'] ?? 0;
    $result->free();
}
$total_staff = 0;
if ($result = $conn->query("SELECT COUNT(*) AS total FROM staff")) {
    $row = $result->fetch_assoc();
    $total_staff = $row['total'] ?? 0;
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f4f9f4;
    }
    .navbar {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }
    .school-logo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid #fff;
    }
    .section-title {
      border-bottom: 2px solid #c8e6c9;
      padding-bottom: 5px;
      margin-bottom: 15px;
      color: #2e7d32;
    }
    .list-group-item {
      border: none;
      border-left: 4px solid transparent;
      transition: all 0.3s;
    }
    .list-group-item:hover {
      background-color: #e8f5e9;
      border-left: 4px solid #43a047;
    }

        footer {
      margin-top: auto;
      padding: 1rem 0;
      background-color: #2e7d32;
      color: #fff;
      text-align: center;
      font-size: 0.9rem;
    }

    @media (max-width: 576px) {
  .card {
    margin-bottom: 1rem;
  }
}

  </style>
</head>
<body>

<?php
$display_admin = $_SESSION['admin_username'] ?? 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
      Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/helpdeskAdminProfileSettings/HelpDesk.php">Help Desk</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            Admin (<?= htmlspecialchars($display_admin) ?>)
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/IT2C_Enrollment_System_SourceCode/ADMIN/helpdeskAdminProfileSettings/settings.php">Settings</a></li>
            <li><a class="dropdown-item" href="/IT2C_Enrollment_System_SourceCode/ADMIN/helpdeskAdminProfileSettings/profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/IT2C_Enrollment_System_SourceCode/logregfor/admin-logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
  <div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
      <div class="card text-white bg-success text-center">
        <div class="card-body">
          <h5>Total Students</h5>
          <p class="display-6"><?= $total_students ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-white bg-primary text-center">
        <div class="card-body">
          <h5>Total Teachers/Staff</h5>
          <p class="display-6"><?= $total_staff ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-dark bg-warning text-center">
        <div class="card-body">
          <h5>Enrolled This Semester</h5>
          <p class="display-6"><?= $enrolled_semester ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-white bg-info text-center">
        <div class="card-body">
          <h5>Programs/Courses</h5>
          <p class="display-6"><?= $programs_count ?></p>
        </div>
      </div>
    </div>
  </div>
  <div class="row mb-5">
    <div class="col">
      <canvas id="enrollmentChart" height="100"></canvas>
    </div>
  </div>
  <div class="row mb-5">
    <div class="col">
      <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Student List</h5>
          <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Student_Management/view-all-students.php" class="btn btn-sm btn-light">View All</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Student ID</th>
                  <th>Full Name</th>
                  <th>Year</th>
                  <th>Course</th>
                  <th>Status</th>
                </tr>
              </thead>
             <tbody>
            <?php if (!empty($recent_students)): ?>
                <?php foreach ($recent_students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                    <td><?= htmlspecialchars($student['year_level']) ?></td>
                    <td><?= htmlspecialchars($student['course']) ?></td>
                    <td>
                    <?php
                        $status = strtolower($student['status']);
                        $badgeClass = 'secondary';
                        if ($status === 'enrolled') $badgeClass = 'success';
                        elseif ($status === 'pending') $badgeClass = 'warning text-dark';
                        elseif ($status === 'dropped') $badgeClass = 'danger';
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>">
                        <?= htmlspecialchars($student['status']) ?>
                    </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                <td colspan="5" class="text-center">No recent students found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="section-title"><h4>User Management</h4></div>
    <div class="list-group mb-4">
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/User_Management/manage-admins-users.php" class="list-group-item list-group-item-action">
        Manage Admin Users
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/User_Management/manage-registar-staff.php" class="list-group-item list-group-item-action">
        Manage Registrar/Staff
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/User_Management/manage-students-account.php" class="list-group-item list-group-item-action">
        Manage Student Accounts
      </a>
    </div>

    <div class="section-title"><h4>Student Management</h4></div>
    <div class="list-group">
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Student_Management/view-all-students.php" class="list-group-item list-group-item-action">
        View All Students
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Student_Management/approve-student-registration.php" class="list-group-item list-group-item-action">
        Approve Student Registration
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Student_Management/view-enrollment-history.php" class="list-group-item list-group-item-action">
        View Enrollment History
      </a>
    </div>
  </div>

  <div class="col-md-6">
    <div class="section-title"><h4>Enrollment Management</h4></div>
    <div class="list-group mb-4">
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Enrollment_Management/view-pending-enrollment.php" class="list-group-item list-group-item-action">
        View Pending Requests
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Enrollment_Management/assign-subjects-section.php" class="list-group-item list-group-item-action">
        Assign Subjects/Sections
      </a>
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Enrollment_Management/set-enrollment-period.php" class="list-group-item list-group-item-action">
        Set Enrollment Period
      </a>
    </div>

    <div class="section-title"><h4>Reports & Settings</h4></div>
    <div class="list-group">
      <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/Report&Settings/download-students-list.php" class="list-group-item list-group-item-action">
        Download Student List
      </a>
    </div>
  </div>
</div>
</div>

 <footer>
    &copy; <?php echo date('Y'); ?> Enrollment System. All rights reserved.
  </footer>

<script>
const ctx = document.getElementById('enrollmentChart').getContext('2d');

const courseLabels = <?= json_encode(array_column($course_data, 'course')) ?>;
const courseCounts = <?= json_encode(array_column($course_data, 'total')) ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: courseLabels,
        datasets: [{
            label: 'Students per Program',
            data: courseCounts,
            backgroundColor: 'rgba(75, 192, 192, 0.7)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        },
        plugins: {
            legend: { display: true },
            tooltip: { enabled: true }
        }
    }
});
</script>

</body>
</html>
