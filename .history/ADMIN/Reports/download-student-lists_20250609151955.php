<?php
session_start();
require_once '../../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $program = $_GET['program'] ?? '';
    $yearLevel = $_GET['yearLevel'] ?? '';
    $section = $_GET['section'] ?? '';
    $schoolYear = $_GET['schoolYear'] ?? '';
    
    // Build query for export
    $sql = "SELECT student_id, first_name, middle_name, last_name, program, year_level, section, school_year, email, contact_number FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($program)) {
        $sql .= " AND program = ?";
        $params[] = $program;
        $types .= "s";
    }
    if (!empty($yearLevel)) {
        $sql .= " AND year_level = ?";
        $params[] = $yearLevel;
        $types .= "s";
    }
    if (!empty($section)) {
        $sql .= " AND section = ?";
        $params[] = $section;
        $types .= "s";
    }
    if (!empty($schoolYear)) {
        $sql .= " AND school_year = ?";
        $params[] = $schoolYear;
        $types .= "s";
    }
    
    $sql .= " ORDER BY last_name, first_name";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="student_list_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student ID', 'Full Name', 'Program', 'Year Level', 'Section', 'School Year', 'Email', 'Contact Number']);
        
        foreach ($students as $student) {
            fputcsv($output, [
                $student['student_id'],
                $student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name'],
                $student['program'],
                $student['year_level'],
                $student['section'],
                $student['school_year'],
                $student['email'],
                $student['contact_number']
            ]);
        }
        fclose($output);
        exit();
    }
    
    if ($export_type === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="student_list_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th>Student ID</th><th>Full Name</th><th>Program</th><th>Year Level</th><th>Section</th><th>School Year</th><th>Email</th><th>Contact Number</th></tr>";
        
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['program']) . "</td>";
            echo "<td>" . htmlspecialchars($student['year_level']) . "</td>";
            echo "<td>" . htmlspecialchars($student['section']) . "</td>";
            echo "<td>" . htmlspecialchars($student['school_year']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['contact_number']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit();
    }
}

// Initialize filter variables
$program = $_POST['program'] ?? '';
$yearLevel = $_POST['yearLevel'] ?? '';
$section = $_POST['section'] ?? '';
$schoolYear = $_POST['schoolYear'] ?? '';

// Check if filters are applied
$hasFilter = !empty($program) || !empty($yearLevel) || !empty($section) || !empty($schoolYear);

// Fetch distinct options for dropdowns
function fetchOptions($conn, $column, $table = 'students') {
    $sql = "SELECT DISTINCT $column FROM $table WHERE $column IS NOT NULL AND $column != '' ORDER BY $column ASC";
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
$sql = "SELECT student_id, first_name, middle_name, last_name, program, year_level, section, school_year, email, contact_number, status FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($program)) {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= "s";
}
if (!empty($yearLevel)) {
    $sql .= " AND year_level = ?";
    $params[] = $yearLevel;
    $types .= "s";
}
if (!empty($section)) {
    $sql .= " AND section = ?";
    $params[] = $section;
    $types .= "s";
}
if (!empty($schoolYear)) {
    $sql .= " AND school_year = ?";
    $params[] = $schoolYear;
    $types .= "s";
}

$sql .= " ORDER BY last_name, first_name";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Student Lists - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
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
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        .page-title {
            color: #198754;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        .filter-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        .btn-apply {
            background: linear-gradient(135deg, #198754, #20c997);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-apply:hover {
            background: linear-gradient(135deg, #157347, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        .results-section {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .table thead {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody tr:hover {
            background-color: #e8f5e8;
            transition: background-color 0.3s ease;
        }
        .export-section {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #e9ecef;
        }
        .export-btn {
            min-width: 150px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .stats-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-left: 4px solid #198754;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .filter-summary {
            background: linear-gradient(135deg, #d1e7dd, #f8f9fa);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #198754;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            .export-btn {
                width: 100%;
                margin-bottom: 0.5rem;
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

    <div class="main-container">
        <div class="page-header">
            <h2 class="page-title">
                <i class="bi bi-download"></i> Download Student Lists
            </h2>
            <p class="text-muted">Filter and export student data for reporting</p>
        </div>

        <!-- Statistics Card -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h4 class="text-success"><?= count($students) ?></h4>
                    <small class="text-muted">Students Found</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h4 class="text-primary"><?= count($programOptions) ?></h4>
                    <small class="text-muted">Programs Available</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h4 class="text-info"><?= count($yearLevelOptions) ?></h4>
                    <small class="text-muted">Year Levels</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h4 class="text-warning"><?= count($sectionOptions) ?></h4>
                    <small class="text-muted">Sections</small>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">
                <i class="bi bi-funnel"></i> Filter Students
            </h5>
            <form method="post" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="program" class="filter-label">Program</label>
                    <select id="program" class="form-select" name="program">
                        <option value="">All Programs</option>
                        <?php foreach ($programOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $program === $opt ? "selected" : "" ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="yearLevel" class="filter-label">Year Level</label>
                    <select id="yearLevel" class="form-select" name="yearLevel">
                        <option value="">All Years</option>
                        <?php foreach ($yearLevelOptions as $opt): ?>
                            <?php if ($opt !== null && $opt !== ""): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= $yearLevel === $opt ? "selected" : "" ?>>
                                    <?= htmlspecialchars($opt) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="section" class="filter-label">Section</label>
                    <select id="section" class="form-select" name="section">
                        <option value="">All Sections</option>
                        <?php foreach ($sectionOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $section === $opt ? "selected" : "" ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="schoolYear" class="filter-label">School Year</label>
                    <select id="schoolYear" class="form-select" name="schoolYear">
                        <option value="">All School Years</option>
                        <?php foreach ($schoolYearOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $schoolYear === $opt ? "selected" : "" ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-apply w-100">
                        <i class="bi bi-search"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Summary -->
        <?php if ($hasFilter): ?>
            <div class="filter-summary">
                <h6><i class="bi bi-info-circle"></i> Active Filters:</h6>
                <div class="row">
                    <?php if (!empty($program)): ?>
                        <div class="col-md-3">
                            <strong>Program:</strong> <span class="text-success"><?= htmlspecialchars($program) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($yearLevel)): ?>
                        <div class="col-md-3">
                            <strong>Year Level:</strong> <span class="text-success"><?= htmlspecialchars($yearLevel) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($section)): ?>
                        <div class="col-md-3">
                            <strong>Section:</strong> <span class="text-success"><?= htmlspecialchars($section) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($schoolYear)): ?>
                        <div class="col-md-3">
                            <strong>School Year:</strong> <span class="text-success"><?= htmlspecialchars($schoolYear) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Results Table -->
        <div class="results-section">
            <div class="table-container">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Section</th>
                            <th>School Year</th>
                            <th>Status</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($student['program']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($student['year_level']) ?></td>
                                    <td><?= htmlspecialchars($student['section']) ?></td>
                                    <td><?= htmlspecialchars($student['school_year']) ?></td>
                                    <td>
                                        <span class="badge status-badge <?= 
                                            $student['status'] == 'active' ? 'bg-success' : 
                                            ($student['status'] == 'pending' ? 'bg-warning' : 'bg-secondary') 
                                        ?>">
                                            <?= ucfirst($student['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($student['email']) ?><br>
                                            <i class="bi bi-phone"></i> <?= htmlspecialchars($student['contact_number']) ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-search display-4 text-muted"></i>
                                    <h5 class="text-muted mt-2">No students found</h5>
                                    <p class="text-muted">Try adjusting your filter criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Section -->
        <?php if (count($students) > 0): ?>
            <div class="export-section">
                <h5 class="mb-3">
                    <i class="bi bi-download"></i> Export Options
                </h5>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="?export=csv&program=<?= urlencode($program) ?>&yearLevel=<?= urlencode($yearLevel) ?>&section=<?= urlencode($section) ?>&schoolYear=<?= urlencode($schoolYear) ?>" 
                       class="btn btn-outline-success export-btn">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
                    </a>
                    <a href="?export=excel&program=<?= urlencode($program) ?>&yearLevel=<?= urlencode($yearLevel) ?>&section=<?= urlencode($section) ?>&schoolYear=<?= urlencode($schoolYear) ?>" 
                       class="btn btn-outline-primary export-btn">
                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                    </a>
                    <button class="btn btn-outline-danger export-btn" onclick="generatePDF()">
                        <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(20);
            doc.text('Student List Report', 20, 20);
            
            // Add filters info
            let yPos = 40;
            doc.setFontSize(12);
            <?php if ($hasFilter): ?>
                doc.text('Filters Applied:', 20, yPos);
                yPos += 10;
                <?php if (!empty($program)): ?>
                    doc.text('Program: <?= addslashes($program) ?>', 30, yPos);
                    yPos += 8;
                <?php endif; ?>
                <?php if (!empty($yearLevel)): ?>
                    doc.text('Year Level: <?= addslashes($yearLevel) ?>', 30, yPos);
                    yPos += 8;
                <?php endif; ?>
                <?php if (!empty($section)): ?>
                    doc.text('Section: <?= addslashes($section) ?>', 30, yPos);
                    yPos += 8;
                <?php endif; ?>
                <?php if (!empty($schoolYear)): ?>
                    doc.text('School Year: <?= addslashes($schoolYear) ?>', 30, yPos);
                    yPos += 8;
                <?php endif; ?>
                yPos += 10;
            <?php endif; ?>
            
            // Prepare table data
            const tableData = [
                <?php foreach ($students as $student): ?>
                [
                    '<?= addslashes($student['student_id']) ?>',
                    '<?= addslashes($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?>',
                    '<?= addslashes($student['program']) ?>',
                    '<?= addslashes($student['year_level']) ?>',
                    '<?= addslashes($student['section']) ?>',
                    '<?= addslashes($student['school_year']) ?>'
                ],
                <?php endforeach; ?>
            ];
            
            // Add table
            doc.autoTable({
                head: [['Student ID', 'Full Name', 'Program', 'Year Level', 'Section', 'School Year']],
                body: tableData,
                startY: yPos,
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [25, 135, 84],
                    textColor: 255
                }
            });
            
            // Save the PDF
            doc.save('student_list_' + new Date().toISOString().split('T')[0] + '.pdf');
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
