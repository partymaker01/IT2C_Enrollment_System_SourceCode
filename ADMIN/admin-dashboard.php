<?php
session_start();
include '../db.php';

// Check if database connection exists
if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../logregfor/admin-login.php");
    exit();
}

// Get admin info with error handling
$admin = [
    'name' => $_SESSION['admin_username'] ?? 'Admin',
    'username' => $_SESSION['admin_username'] ?? 'admin',
    'role' => $_SESSION['admin_role'] ?? 'admin',
    'image' => '../img/default_admin.png'
];

// If admin_id is set in session, get more details from database
if (isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT name, username, role, image FROM admin_settings WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Get dashboard statistics - Fixed queries
$stats = [];

// Enrollment statistics
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM enrollments GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
$stats['enrollments'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['enrollments'][$row['status']] = $row['count'];
}

// Student statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_students'] = $result->fetch_assoc()['total'] ?? 0;

// Subject statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE status = 'Active'");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_subjects'] = $result->fetch_assoc()['total'] ?? 0;

// Recent enrollments - Fixed query based on actual table structure
$recentEnrollments = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            e.program,
            e.year_level,
            e.status,
            e.date_submitted
        FROM enrollments e 
        LEFT JOIN students s ON e.student_id = s.student_id
        ORDER BY e.date_submitted DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Fallback query if JOIN fails
    try {
        $stmt = $conn->prepare("
            SELECT 
                id,
                student_id,
                CONCAT('Student ID: ', student_id) as student_name,
                program,
                year_level,
                status,
                date_submitted
            FROM enrollments 
            ORDER BY date_submitted DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e2) {
        $recentEnrollments = [];
    }
}

// Current enrollment period
$stmt = $conn->prepare("SELECT * FROM enrollment_periods WHERE status = 'active' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$currentPeriod = $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Monthly enrollment trends - Fixed query
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(date_submitted, '%Y-%m') as month,
        COUNT(*) as count
    FROM enrollments 
    WHERE date_submitted >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_submitted, '%Y-%m')
    ORDER BY month
");
$stmt->execute();
$monthlyTrends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Program distribution - Fixed query
$stmt = $conn->prepare("
    SELECT program, COUNT(*) as count 
    FROM enrollments 
    WHERE status IN ('approved', 'enrolled')
    GROUP BY program 
    ORDER BY count DESC
");
$stmt->execute();
$programDistribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - TLGC Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid #fff;
        }
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            background: white;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .quick-action-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .period-alert {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            border: none;
            border-radius: 10px;
            color: white;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                TLGC Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?= htmlspecialchars($admin['image'] ?? '../img/default_admin.png') ?>" 
                                 alt="Admin" class="rounded-circle me-2" width="32" height="32">
                            <?= htmlspecialchars($admin['name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="helpdeskAdminProfileSettings/profile.php">
                                <i class="bi bi-person me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="helpdeskAdminProfileSettings/settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logregfor/admin-logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">Welcome back, <?= htmlspecialchars($admin['name']) ?>!</h2>
                        <p class="text-muted mb-0">Here's what's happening with your enrollment system today.</p>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted"><?= date('l, F j, Y') ?></div>
                        <div class="small text-muted"><?= date('g:i A') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Period Alert -->
        <?php if ($currentPeriod): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert period-alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-check me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1">Active Enrollment Period</h6>
                            <p class="mb-0">
                                <?= htmlspecialchars($currentPeriod['semester']) ?> - <?= htmlspecialchars($currentPeriod['school_year']) ?>
                                (<?= date('M d', strtotime($currentPeriod['start_date'])) ?> - <?= date('M d, Y', strtotime($currentPeriod['end_date'])) ?>)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon bg-warning me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?= $stats['enrollments']['pending'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Pending Enrollments</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon bg-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?= $stats['enrollments']['approved'] ?? 0 ?></h3>
                            <p class="text-muted mb-0">Approved Enrollments</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon bg-primary me-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?= $stats['total_students'] ?></h3>
                            <p class="text-muted mb-0">Total Students</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon bg-info me-3">
                            <i class="bi bi-book"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?= $stats['total_subjects'] ?></h3>
                            <p class="text-muted mb-0">Active Subjects</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Quick Actions</h4>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Enrollment_Management/pending-enrollments.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-list-check text-warning" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">Pending Enrollments</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Academic_Management/manage-subjects.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">Manage Subjects</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Academic_Management/assign-sections-subjects.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-person-plus text-success" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">Assign Students</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Student_Management/view-all-students.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-people text-info" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">View Students</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Settings/set-enrollment-period.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-calendar-event text-danger" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">Set Period</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="Reports/download-student-lists.php" class="text-decoration-none">
                    <div class="card quick-action-card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-download text-secondary" style="font-size: 2rem;"></i>
                            <h6 class="mt-2 mb-0">Reports</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Charts and Recent Activity -->
        <div class="row">
            <!-- Enrollment Trends Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Enrollment Trends (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Program Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="programChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Enrollments -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Enrollments</h5>
                        <a href="Enrollment_Management/pending-enrollments.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentEnrollments)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No recent enrollments found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Program</th>
                                            <th>Year Level</th>
                                            <th>Status</th>
                                            <th>Date Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentEnrollments as $enrollment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($enrollment['student_name']) ?></td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($enrollment['program']) ?></span></td>
                                            <td><?= htmlspecialchars($enrollment['year_level']) ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'missing_documents' => 'info'
                                                ];
                                                $class = $statusClass[$enrollment['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $enrollment['status'])) ?></span>
                                            </td>
                                            <td><?= date('M d, Y g:i A', strtotime($enrollment['date_submitted'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enrollment Trends Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        const enrollmentData = <?= json_encode($monthlyTrends) ?>;
        
        new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: enrollmentData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Enrollments',
                    data: enrollmentData.map(item => item.count),
                    borderColor: '#2e7d32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Program Distribution Chart
        const programCtx = document.getElementById('programChart').getContext('2d');
        const programData = <?= json_encode($programDistribution) ?>;
        
        new Chart(programCtx, {
            type: 'doughnut',
            data: {
                labels: programData.map(item => item.program),
                datasets: [{
                    data: programData.map(item => item.count),
                    backgroundColor: [
                        '#2e7d32',
                        '#388e3c',
                        '#4caf50',
                        '#66bb6a',
                        '#81c784',
                        '#a5d6a7'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
