<?php
session_start();
require_once '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';

// Build the query with filters
$sql = "SELECT e.id as enrollment_id, s.student_id, s.first_name, s.middle_name, s.last_name, 
               s.program, e.semester, e.school_year, e.enrollment_date, e.status as enrollment_status
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE ? OR s.middle_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($semester_filter)) {
    $sql .= " AND e.semester = ?";
    $params[] = $semester_filter;
    $types .= "s";
}

if (!empty($year_filter)) {
    $sql .= " AND e.school_year = ?";
    $params[] = $year_filter;
    $types .= "s";
}

if (!empty($program_filter)) {
    $sql .= " AND s.program = ?";
    $params[] = $program_filter;
    $types .= "s";
}

$sql .= " ORDER BY e.school_year DESC, e.semester DESC, s.last_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get filter options
$programs_query = "SELECT DISTINCT program FROM students ORDER BY program";
$programs_result = $conn->query($programs_query);

$years_query = "SELECT DISTINCT school_year FROM enrollments ORDER BY school_year DESC";
$years_result = $conn->query($years_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment History - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        body {
            background-color: #f8fafc;
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
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        .card-header.bg-success {
            background: linear-gradient(135deg, #198754, #20c997) !important;
            border: none;
        }
        .table tbody tr:hover {
            background-color: #e8f5e8;
            transition: background-color 0.3s ease;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn-export {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            border: none;
            color: white;
        }
        .btn-export:hover {
            background: linear-gradient(135deg, #138496, #1ea085);
            color: white;
        }
        .stats-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-left: 4px solid #28a745;
        }
        .no-subjects {
            font-style: italic;
            color: #6c757d;
            text-align: center;
            padding: 20px;
        }
        .enrollment-badge {
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
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
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="text-success fw-bold">
                    <i class="bi bi-clock-history"></i> Enrollment History
                </h2>
                <p class="text-muted">View and manage student enrollment records</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-export" onclick="exportToCSV()">
                    <i class="bi bi-download"></i> Export CSV
                </button>
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
                    <label class="form-label fw-semibold">Semester</label>
                    <select class="form-select" name="semester">
                        <option value="">All Semesters</option>
                        <option value="1st Semester" <?= $semester_filter == '1st Semester' ? 'selected' : '' ?>>1st Semester</option>
                        <option value="2nd Semester" <?= $semester_filter == '2nd Semester' ? 'selected' : '' ?>>2nd Semester</option>
                        <option value="Summer" <?= $semester_filter == 'Summer' ? 'selected' : '' ?>>Summer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">School Year</label>
                    <select class="form-select" name="year">
                        <option value="">All Years</option>
                        <?php while ($year = $years_result->fetch_assoc()): ?>
                            <option value="<?= $year['school_year'] ?>" <?= $year_filter == $year['school_year'] ? 'selected' : '' ?>>
                                <?= $year['school_year'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
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
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div id="enrollmentCards">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($enrollment = $result->fetch_assoc()): ?>
                    <?php
                    $enrollment_id = $enrollment['enrollment_id'];
                    $sqlSubjects = "SELECT es.subject_code, es.subject_description, es.units, es.instructor, 
                                           es.grade, es.remarks, es.schedule
                                    FROM enrollment_subjects es 
                                    WHERE es.enrollment_id = ?";
                    $subStmt = $conn->prepare($sqlSubjects);
                    $subStmt->bind_param("i", $enrollment_id);
                    $subStmt->execute();
                    $subResult = $subStmt->get_result();
                    ?>
                    
                    <div class="card mb-4 enrollment-card">
                        <div class="card-header bg-success text-white">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <i class="bi bi-person-circle"></i>
                                        <?= htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name'] . ' ' . $enrollment['middle_name']) ?>
                                    </h5>
                                    <small>Student ID: <?= htmlspecialchars($enrollment['student_id']) ?></small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-light text-dark enrollment-badge">
                                        <?= htmlspecialchars($enrollment['program']) ?>
                                    </span>
                                    <br>
                                    <small><?= htmlspecialchars($enrollment['semester'] . ' ' . $enrollment['school_year']) ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($subResult && $subResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Subject Code</th>
                                                <th>Description</th>
                                                <th>Units</th>
                                                <th>Instructor</th>
                                                <th>Schedule</th>
                                                <th>Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_units = 0;
                                            while ($subject = $subResult->fetch_assoc()): 
                                                $total_units += $subject['units'];
                                            ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= htmlspecialchars($subject['subject_code']) ?></td>
                                                    <td><?= htmlspecialchars($subject['subject_description']) ?></td>
                                                    <td><?= htmlspecialchars($subject['units']) ?></td>
                                                    <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                                    <td><?= htmlspecialchars($subject['schedule']) ?></td>
                                                    <td>
                                                        <?php if ($subject['grade']): ?>
                                                            <span class="badge <?= $subject['grade'] >= 75 ? 'bg-success' : 'bg-danger' ?>">
                                                                <?= number_format($subject['grade'], 2) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not graded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($subject['remarks']): ?>
                                                            <span class="badge <?= $subject['remarks'] == 'Passed' ? 'bg-success' : 'bg-warning' ?>">
                                                                <?= htmlspecialchars($subject['remarks']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2">Total Units</th>
                                                <th><?= $total_units ?></th>
                                                <th colspan="4"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-subjects">
                                    <i class="bi bi-info-circle"></i>
                                    No enrolled subjects found for this semester.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-light">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i>
                                Enrolled on: <?= date('F j, Y', strtotime($enrollment['enrollment_date'])) ?>
                                <span class="ms-3">
                                    <i class="bi bi-flag"></i>
                                    Status: 
                                    <span class="badge <?= $enrollment['enrollment_status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($enrollment['enrollment_status']) ?>
                                    </span>
                                </span>
                            </small>
                        </div>
                    </div>
                    <?php $subStmt->close(); ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No enrollment records found</h4>
                        <p class="text-muted">Try adjusting your search criteria or filters.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            // Create CSV content
            let csvContent = "Student ID,Full Name,Program,Semester,School Year,Subject Code,Subject Description,Units,Instructor,Grade,Remarks\n";
            
            // Get all enrollment cards
            const cards = document.querySelectorAll('.enrollment-card');
            
            cards.forEach(card => {
                const header = card.querySelector('.card-header h5').textContent.trim();
                const studentId = card.querySelector('.card-header small').textContent.replace('Student ID: ', '');
                const program = card.querySelector('.enrollment-badge').textContent.trim();
                const semester = card.querySelector('.card-header .col-md-4 small').textContent.trim();
                
                const rows = card.querySelectorAll('tbody tr');
                if (rows.length > 0) {
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 7) {
                            csvContent += `"${studentId}","${header}","${program}","${semester}","${cells[0].textContent}","${cells[1].textContent}","${cells[2].textContent}","${cells[3].textContent}","${cells[5].textContent}","${cells[6].textContent}"\n`;
                        }
                    });
                } else {
                    csvContent += `"${studentId}","${header}","${program}","${semester}","","","","","",""\n`;
                }
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'enrollment_history_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
