<?php
session_start();
require_once '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE students SET status = 'active', approved_date = NOW(), approved_by = ? WHERE student_id = ?");
        $stmt->bind_param("is", $_SESSION['admin_id'], $student_id);
        
        if ($stmt->execute()) {
            // Send approval email (optional)
            $message = "Student approved successfully!";
            $message_type = "success";
        } else {
            $message = "Error approving student.";
            $message_type = "danger";
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE students SET status = 'rejected', rejected_date = NOW(), rejected_by = ? WHERE student_id = ?");
        $stmt->bind_param("is", $_SESSION['admin_id'], $student_id);
        
        if ($stmt->execute()) {
            $message = "Student registration rejected.";
            $message_type = "warning";
        } else {
            $message = "Error rejecting student.";
            $message_type = "danger";
        }
    }
    $stmt->close();
}

// Fetch pending students
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';

$sql = "SELECT s.*, DATE_FORMAT(s.date_registered, '%M %d, %Y') as formatted_date 
        FROM students s 
        WHERE s.status = 'pending'";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE ? OR s.middle_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($program_filter)) {
    $sql .= " AND s.program = ?";
    $params[] = $program_filter;
    $types .= "s";
}

$sql .= " ORDER BY s.date_registered DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get programs for filter
$programs_query = "SELECT DISTINCT program FROM students ORDER BY program";
$programs_result = $conn->query($programs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Student Registration - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: #f1f8e9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #2e7d32, #388e3c);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            max-width: 1200px;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .student-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .badge-year {
            font-size: 0.85rem;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        .btn-approve:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            color: white;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            border: none;
            color: white;
        }
        .btn-reject:hover {
            background: linear-gradient(135deg, #c82333, #dc2626);
            color: white;
        }
        .stats-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-left: 4px solid #28a745;
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

    <div class="container my-5">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="text-success fw-bold">
                    <i class="bi bi-person-check"></i> Approve Student Registration
                </h2>
                <p class="text-muted">Review and manage newly registered students</p>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= count($students) ?></h3>
                        <small class="text-muted">Pending Approvals</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Search Student</label>
                    <div class="input-group">
                        <span class="input-group-text bg-success text-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name or student ID">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Program</label>
                    <select class="form-select" name="program">
                        <option value="">All Programs</option>
                        <?php while ($program = $programs_result->fetch_assoc()): ?>
                            <option value="<?= $program['program'] ?>" <?= $program_filter == $program['program'] ? 'selected' : '' ?>>
                                <?= $program['program'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Students Grid -->
        <?php if (count($students) > 0): ?>
            <div class="row">
                <?php foreach ($students as $student): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card student-card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-success fw-bold">
                                    <?= htmlspecialchars($student['student_id']) ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?>
                                </h5>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($student['email']) ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?= htmlspecialchars($student['program']) ?></span>
                                    <span class="badge bg-info badge-year"><?= htmlspecialchars($student['year_level']) ?></span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> Registered: <?= $student['formatted_date'] ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-grid gap-2">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve w-100" 
                                                onclick="return confirm('Approve this student registration?')">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject w-100" 
                                                onclick="return confirm('Reject this student registration?')">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-person-check display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No pending registrations</h4>
                    <p class="text-muted">All student registrations have been processed.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
