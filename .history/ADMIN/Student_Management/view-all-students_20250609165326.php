<?php
session_start();
require_once '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $student_id = $_GET['delete_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records first
        $stmt1 = $conn->prepare("DELETE FROM enrollment_subjects WHERE enrollment_id IN (SELECT id FROM enrollments WHERE student_id = ?)");
        $stmt1->bind_param("s", $student_id);
        $stmt1->execute();
        
        $stmt2 = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
        $stmt2->bind_param("s", $student_id);
        $stmt2->execute();
        
        $stmt3 = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt3->bind_param("s", $student_id);
        $stmt3->execute();
        
        $conn->commit();
        $message = "Student deleted successfully!";
        $message_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error deleting student: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Initialize search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';

// Build query with filters
$sql = "SELECT s.*, DATE_FORMAT(s.date_registered, '%M %d, %Y') as formatted_date 
        FROM students s WHERE 1=1";

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

if (!empty($status_filter)) {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($year_filter)) {
    $sql .= " AND s.year_level = ?";
    $params[] = $year_filter;
    $types .= "s";
}

$sql .= " ORDER BY s.student_id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get filter options with better error handling
$programs = [];
$years = [];

// Get programs
$programs_query = "SELECT DISTINCT program FROM students WHERE program IS NOT NULL AND program != '' ORDER BY program";
$programs_result = $conn->query($programs_query);
if ($programs_result) {
    while ($row = $programs_result->fetch_assoc()) {
        $programs[] = $row['program'];
    }
}

// Get year levels
$years_query = "SELECT DISTINCT year_level FROM students WHERE year_level IS NOT NULL AND year_level != '' ORDER BY year_level";
$years_result = $conn->query($years_query);
if ($years_result) {
    while ($row = $years_result->fetch_assoc()) {
        $years[] = $row['year_level'];
    }
}

// If no data exists, add some default options
if (empty($programs)) {
    $programs = ['BSIT', 'BSCS', 'BSIS', 'BSBA', 'BSED', 'BEED'];
}

if (empty($years)) {
    $years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Students - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../favicon.ico" type="image/x-icon">
    <style>
        body {
            background-color: #e6f2e6;
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
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table thead {
            background: linear-gradient(135deg, #2e7d32, #388e3c);
            color: white;
        }
        .table tbody tr:hover {
            background-color: #e8f5e8;
            transition: background-color 0.3s ease;
        }
        .btn-action {
            margin: 2px;
            min-width: 70px;
        }
        .status-badge {
            font-size: 0.85rem;
        }
        .stats-row {
            margin-bottom: 30px;
        }
        .stats-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-left: 4px solid #28a745;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        @media (max-width: 768px) {
            .btn-action {
                width: 100%;
                margin-bottom: 5px;
            }
            .filter-section .row > div {
                margin-bottom: 15px;
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

    <div class="container my-5">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i>
                <strong>No students found!</strong> This might be because:
                <ul class="mb-0 mt-2">
                    <li>No students have been added to the system yet</li>
                    <li>Your search filters are too restrictive</li>
                    <li>There might be a database connection issue</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="text-success fw-bold">
                    <i class="bi bi-people"></i> View All Students
                </h2>
                <p class="text-muted">Manage and view student information</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="add-student.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Add New Student
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row stats-row">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h4 class="text-success"><?= count($students) ?></h4>
                        <small class="text-muted">Total Students</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h4 class="text-primary"><?= count(array_filter($students, fn($s) => $s['status'] == 'active')) ?></h4>
                        <small class="text-muted">Active Students</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h4 class="text-warning"><?= count(array_filter($students, fn($s) => $s['status'] == 'pending')) ?></h4>
                        <small class="text-muted">Pending Approval</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h4 class="text-info"><?= count($programs) ?></h4>
                        <small class="text-muted">Programs</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search Student</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Name or Student ID">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Program</label>
                    <select class="form-select" name="program">
                        <option value="">All Programs</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= htmlspecialchars($program) ?>" <?= $program_filter == $program ? 'selected' : '' ?>>
                                <?= htmlspecialchars($program) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Year Level</label>
                    <select class="form-select" name="year">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>" <?= $year_filter == $year ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="?" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                    <button type="button" class="btn btn-info" onclick="exportToCSV()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($student['program']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($student['year_level']) ?></td>
                                    <td>
                                        <small>
                                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($student['email']) ?><br>
                                            <i class="bi bi-phone"></i> <?= htmlspecialchars($student['contact_number']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge status-badge <?= 
                                            $student['status'] == 'active' ? 'bg-success' : 
                                            ($student['status'] == 'pending' ? 'bg-warning' : 'bg-secondary') 
                                        ?>">
                                            <?= ucfirst($student['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $student['formatted_date'] ?></td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <a href="view-student-details.php?id=<?= urlencode($student['student_id']) ?>" 
                                               class="btn btn-primary btn-action" title="View Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="edit-student.php?id=<?= urlencode($student['student_id']) ?>" 
                                               class="btn btn-warning btn-action" title="Edit Student">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button class="btn btn-danger btn-action" title="Delete Student" 
                                                    onclick="confirmDelete('<?= htmlspecialchars($student['student_id']) ?>', '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-people display-4 text-muted"></i>
                                    <h5 class="text-muted mt-2">No students found</h5>
                                    <p class="text-muted">Try adjusting your search criteria or <a href="add-student.php">add a new student</a>.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(studentId, studentName) {
            if (confirm(`Are you sure you want to delete ${studentName}? This action cannot be undone.`)) {
                window.location.href = `?delete_id=${encodeURIComponent(studentId)}`;
            }
        }

        function exportToCSV() {
            let csvContent = "Student ID,Full Name,Program,Year Level,Email,Contact Number,Status,Date Registered\n";
            
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) {
                    const studentId = cells[0].textContent.trim();
                    const fullName = cells[1].textContent.trim();
                    const program = cells[2].textContent.trim();
                    const yearLevel = cells[3].textContent.trim();
                    const contact = cells[4].textContent.trim().replace(/\n/g, ' ');
                    const status = cells[5].textContent.trim();
                    const dateRegistered = cells[6].textContent.trim();
                    
                    csvContent += `"${studentId}","${fullName}","${program}","${yearLevel}","${contact}","${status}","${dateRegistered}"\n`;
                }
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'students_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
