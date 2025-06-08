<?php
session_start();
include '../../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $subjectCode = trim($_POST['subject_code'] ?? '');
        $subjectTitle = trim($_POST['subject_title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $units = floatval($_POST['units'] ?? 0);
        $lectureHours = floatval($_POST['lecture_hours'] ?? 0);
        $labHours = floatval($_POST['laboratory_hours'] ?? 0);
        $instructor = trim($_POST['instructor'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $yearLevel = trim($_POST['year_level'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $schoolYear = trim($_POST['school_year'] ?? '');
        $courseType = trim($_POST['course_type'] ?? '');
        $prerequisites = trim($_POST['prerequisites'] ?? '');
        $day = trim($_POST['day'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $room = trim($_POST['room'] ?? '');
        
        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_title, description, units, lecture_hours, laboratory_hours, instructor, program, year_level, semester, school_year, course_type, prerequisites, day, time, room, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssdddssssssssss", $subjectCode, $subjectTitle, $description, $units, $lectureHours, $labHours, $instructor, $program, $yearLevel, $semester, $schoolYear, $courseType, $prerequisites, $day, $time, $room);
                
                if ($stmt->execute()) {
                    $message = "Subject added successfully!";
                    $messageType = "success";
                } else {
                    throw new Exception("Failed to add subject");
                }
            } else {
                $subjectId = intval($_POST['subject_id']);
                $stmt = $conn->prepare("UPDATE subjects SET subject_code=?, subject_title=?, description=?, units=?, lecture_hours=?, laboratory_hours=?, instructor=?, program=?, year_level=?, semester=?, school_year=?, course_type=?, prerequisites=?, day=?, time=?, room=? WHERE id=?");
                $stmt->bind_param("sssdddsssssssssi", $subjectCode, $subjectTitle, $description, $units, $lectureHours, $labHours, $instructor, $program, $yearLevel, $semester, $schoolYear, $courseType, $prerequisites, $day, $time, $room, $subjectId);
                
                if ($stmt->execute()) {
                    $message = "Subject updated successfully!";
                    $messageType = "success";
                } else {
                    throw new Exception("Failed to update subject");
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    } elseif ($action === 'delete') {
        $subjectId = intval($_POST['subject_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->bind_param("i", $subjectId);
            if ($stmt->execute()) {
                $message = "Subject deleted successfully!";
                $messageType = "success";
            } else {
                throw new Exception("Failed to delete subject");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch all subjects with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$searchTerm = $_GET['search'] ?? '';
$filterProgram = $_GET['program'] ?? '';
$filterYear = $_GET['year'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];
$types = "";

if ($searchTerm) {
    $whereClause .= " AND (subject_code LIKE ? OR subject_title LIKE ? OR instructor LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

if ($filterProgram) {
    $whereClause .= " AND program = ?";
    $params[] = $filterProgram;
    $types .= "s";
}

if ($filterYear) {
    $whereClause .= " AND year_level = ?";
    $params[] = $filterYear;
    $types .= "s";
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM subjects $whereClause";
$stmt = $conn->prepare($countQuery);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalSubjects = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalSubjects / $limit);

// Get subjects
$query = "SELECT * FROM subjects $whereClause ORDER BY program, year_level, subject_code LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get programs for filter
$programs = ['IT', 'HRMT', 'ECT', 'HST', 'ET', 'TVET'];
$yearLevels = ['1st Year', '2nd Year', '3rd Year'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Subjects - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .main-card {
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid #fff;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
        }
        .subject-card {
            transition: transform 0.2s ease;
            border-radius: 10px;
        }
        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../admin-dashboard.php">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-book me-2"></i>Manage Subjects</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#subjectModal" onclick="openAddModal()">
                <i class="bi bi-plus-circle me-1"></i>Add New Subject
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card main-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search subjects..." value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="program">
                            <option value="">All Programs</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?= $prog ?>" <?= $filterProgram === $prog ? 'selected' : '' ?>><?= $prog ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="year">
                            <option value="">All Year Levels</option>
                            <?php foreach ($yearLevels as $year): ?>
                                <option value="<?= $year ?>" <?= $filterYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Subjects List -->
        <div class="card main-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Subjects List (<?= $totalSubjects ?> total)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($subjects)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No subjects found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Title</th>
                                    <th>Program</th>
                                    <th>Year Level</th>
                                    <th>Units</th>
                                    <th>Instructor</th>
                                    <th>Schedule</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($subject['subject_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($subject['subject_title']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($subject['program']) ?></span></td>
                                    <td><?= htmlspecialchars($subject['year_level']) ?></td>
                                    <td><?= $subject['units'] ?></td>
                                    <td><?= htmlspecialchars($subject['instructor']) ?></td>
                                    <td>
                                        <small>
                                            <?= htmlspecialchars($subject['day']) ?><br>
                                            <?= htmlspecialchars($subject['time']) ?><br>
                                            <?= htmlspecialchars($subject['room']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteSubject(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['subject_code']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>&program=<?= urlencode($filterProgram) ?>&year=<?= urlencode($filterYear) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Subject Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="subjectForm">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="modalTitle">Add New Subject</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="subject_id" id="subjectId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject Code *</label>
                                <input type="text" class="form-control" name="subject_code" id="subjectCode" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Units *</label>
                                <input type="number" class="form-control" name="units" id="units" step="0.5" min="0" max="10" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subject Title *</label>
                                <input type="text" class="form-control" name="subject_title" id="subjectTitle" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lecture Hours</label>
                                <input type="number" class="form-control" name="lecture_hours" id="lectureHours" step="0.5" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Laboratory Hours</label>
                                <input type="number" class="form-control" name="laboratory_hours" id="labHours" step="0.5" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course Type</label>
                                <select class="form-select" name="course_type" id="courseType">
                                    <option value="major">Major</option>
                                    <option value="minor">Minor</option>
                                    <option value="general_education">General Education</option>
                                    <option value="elective">Elective</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Program *</label>
                                <select class="form-select" name="program" id="program" required>
                                    <option value="">Select Program</option>
                                    <?php foreach ($programs as $prog): ?>
                                        <option value="<?= $prog ?>"><?= $prog ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Year Level *</label>
                                <select class="form-select" name="year_level" id="yearLevel" required>
                                    <option value="">Select Year Level</option>
                                    <?php foreach ($yearLevels as $year): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Semester</label>
                                <select class="form-select" name="semester" id="semester">
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">School Year</label>
                                <input type="text" class="form-control" name="school_year" id="schoolYear" placeholder="2024-2025">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Instructor</label>
                                <input type="text" class="form-control" name="instructor" id="instructor">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Day</label>
                                <input type="text" class="form-control" name="day" id="day" placeholder="Mon,Wed,Fri">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Time</label>
                                <input type="text" class="form-control" name="time" id="time" placeholder="8:00-9:30 AM">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room</label>
                                <input type="text" class="form-control" name="room" id="room" placeholder="Room 101">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Prerequisites</label>
                                <textarea class="form-control" name="prerequisites" id="prerequisites" rows="2" placeholder="List prerequisite subjects"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="submitBtn">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Subject';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Add Subject';
            document.getElementById('subjectForm').reset();
        }

        function editSubject(subject) {
            document.getElementById('modalTitle').textContent = 'Edit Subject';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('submitBtn').textContent = 'Update Subject';
            
            // Populate form fields
            document.getElementById('subjectId').value = subject.id;
            document.getElementById('subjectCode').value = subject.subject_code;
            document.getElementById('subjectTitle').value = subject.subject_title;
            document.getElementById('description').value = subject.description || '';
            document.getElementById('units').value = subject.units;
            document.getElementById('lectureHours').value = subject.lecture_hours || '';
            document.getElementById('labHours').value = subject.laboratory_hours || '';
            document.getElementById('courseType').value = subject.course_type || 'major';
            document.getElementById('program').value = subject.program;
            document.getElementById('yearLevel').value = subject.year_level;
            document.getElementById('semester').value = subject.semester || '';
            document.getElementById('schoolYear').value = subject.school_year || '';
            document.getElementById('instructor').value = subject.instructor || '';
            document.getElementById('day').value = subject.day || '';
            document.getElementById('time').value = subject.time || '';
            document.getElementById('room').value = subject.room || '';
            document.getElementById('prerequisites').value = subject.prerequisites || '';
            
            new bootstrap.Modal(document.getElementById('subjectModal')).show();
        }

        function deleteSubject(id, code) {
            if (confirm(`Are you sure you want to delete subject "${code}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="subject_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
