<?php
// Add this at the very top for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Fixed enrollment approval process
session_start();

// Add this to check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
        exit;
    }
    header("Location: ../../logregfor/admin-login.php");
    exit;
}

require_once '../../db.php';
require_once 'generate-student-id.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output buffer to ensure clean JSON response
    ob_clean();
    
    // Log the POST data for debugging
    error_log("POST Data: " . print_r($_POST, true));
    error_log("Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET'));
    
    $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    $response = ['success' => false, 'message' => 'Unknown error'];

    try {
        if ($enrollmentId > 0 && in_array($action, ['approve', 'reject', 'missing_documents'])) {
            // FIXED: Removed s.id as student_table_id since it doesn't exist
            $stmt = $conn->prepare("
                SELECT e.*, s.student_id as current_student_id, s.formatted_id, s.program
                FROM enrollments e 
                JOIN students s ON e.student_id = s.student_id 
                WHERE e.id = ?
            ");
            $stmt->bind_param("i", $enrollmentId);
            $stmt->execute();
            $enrollment = $stmt->get_result()->fetch_assoc();

            if ($enrollment) {
                $currentStudentId = $enrollment['current_student_id'];
                $needsNewId = empty($enrollment['formatted_id']) || !preg_match('/^TLGC-/', $enrollment['formatted_id']);

                if ($action === 'approve') {
                    if ($needsNewId) {
                        $newStudentId = generateStudentId($conn, $enrollment['program']);
                        error_log("Generated new ID: " . $newStudentId);
                        
                        if (updateStudentId($conn, $enrollment['current_student_id'], $newStudentId)) {
                            $stmt = $conn->prepare("UPDATE enrollments SET status = 'approved', processed_by = ?, date_processed = NOW(), remarks = ? WHERE id = ?");
                            $stmt->bind_param("isi", $_SESSION['admin_id'], $remarks, $enrollmentId);
                            $stmt->execute();
                            $message = "Enrollment approved and Student ID updated to: {$newStudentId}";
                            
                            // Update session if needed
                            if (isset($_SESSION['student_id']) && $_SESSION['student_id'] === $currentStudentId) {
                                $_SESSION['student_id'] = $newStudentId;
                            }
                        } else {
                            $response = ['success' => false, 'message' => 'Failed to update Student ID'];
                            header('Content-Type: application/json');
                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE enrollments SET status = 'approved', processed_by = ?, date_processed = NOW(), remarks = ? WHERE id = ?");
                        $stmt->bind_param("isi", $_SESSION['admin_id'], $remarks, $enrollmentId);
                        $stmt->execute();
                        $message = "Enrollment approved successfully!";
                    }
                    $response = ['success' => true, 'message' => $message];
                } elseif ($action === 'reject') {
                    $stmt = $conn->prepare("UPDATE enrollments SET status = 'rejected', processed_by = ?, date_processed = NOW(), remarks = ? WHERE id = ?");
                    $stmt->bind_param("isi", $_SESSION['admin_id'], $remarks, $enrollmentId);
                    $stmt->execute();
                    $response = ['success' => true, 'message' => 'Enrollment rejected.'];
                } elseif ($action === 'missing_documents') {
                    $stmt = $conn->prepare("UPDATE enrollments SET status = 'missing_documents', processed_by = ?, date_processed = NOW(), remarks = ? WHERE id = ?");
                    $stmt->bind_param("isi", $_SESSION['admin_id'], $remarks, $enrollmentId);
                    $stmt->execute();
                    $response = ['success' => true, 'message' => 'Enrollment marked as missing documents.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Enrollment not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid request'];
        }
    } catch (Exception $e) {
        error_log("Error in approval process: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
    
    // Ensure proper JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get pending enrollments with student details
$stmt = $conn->prepare("
    SELECT 
        e.*,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.email as student_email,
        s.contact_number,
        s.student_id as current_student_id
    FROM enrollments e 
    LEFT JOIN students s ON e.student_id = s.student_id
    WHERE e.status = 'pending'
    ORDER BY e.date_submitted DESC
");
$stmt->execute();
$pendingEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [];
$statuses = ['pending', 'approved', 'rejected', 'missing_documents'];
foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $stats[$status] = $stmt->get_result()->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Enrollments - TLGC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="../picture/tlgc_pic.jpg" type="image/x-icon">
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
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .student-id-preview {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-size: 0.9em;
        }
        .id-will-change {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../admin-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                TLGC Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../admin-dashboard.php">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-1">Pending Enrollments</h2>
                <p class="text-muted">Review and process student enrollment requests</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['pending'] ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['approved'] ?></h3>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['rejected'] ?></h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body text-center">
                        <h3><?= $stats['missing_documents'] ?></h3>
                        <p class="mb-0">Missing Documents</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollments Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Enrollment Requests</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pendingEnrollments)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <h4 class="text-muted mt-3">No Pending Enrollments</h4>
                                <p class="text-muted">All enrollment requests have been processed.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Current ID</th>
                                            <th>Program</th>
                                            <th>Year & Section</th>
                                            <th>Contact</th>
                                            <th>Date Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingEnrollments as $enrollment): ?>
                                        <?php 
                                            // Preview what the new student ID will be
                                            $previewId = generateStudentId($conn, $enrollment['program']);
                                            $needsNewId = !preg_match('/^TLGC-/', $enrollment['current_student_id']);
                                        ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($enrollment['student_name'] ?: 'Student ID: ' . $enrollment['student_id']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($enrollment['student_id']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="student-id-preview">
                                                    <?= htmlspecialchars($enrollment['current_student_id']) ?>
                                                </div>
                                                <?php if ($needsNewId): ?>
                                                    <small class="id-will-change">
                                                        <i class="bi bi-arrow-right"></i> Will become: <strong><?= $previewId ?></strong>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">✓ Already has new format</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($enrollment['program']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($enrollment['year_level']) ?><br>
                                                <small class="text-muted">Section: <?= htmlspecialchars($enrollment['section']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($enrollment['student_email'] ?: 'N/A') ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($enrollment['contact_number'] ?: 'N/A') ?></small>
                                            </td>
                                            <td><?= date('M d, Y g:i A', strtotime($enrollment['date_submitted'])) ?></td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <button class="btn btn-success btn-sm" onclick="processEnrollment(<?= $enrollment['id'] ?>, 'approve', '<?= htmlspecialchars($enrollment['student_name']) ?>')">
                                                        <i class="bi bi-check-circle me-1"></i>Approve
                                                        <?php if ($needsNewId): ?>
                                                            <br><small>& Update ID</small>
                                                        <?php endif; ?>
                                                    </button>
                                                    <button class="btn btn-warning btn-sm" onclick="processEnrollment(<?= $enrollment['id'] ?>, 'missing_documents', '<?= htmlspecialchars($enrollment['student_name']) ?>')">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Missing Docs
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="processEnrollment(<?= $enrollment['id'] ?>, 'reject', '<?= htmlspecialchars($enrollment['student_name']) ?>')">
                                                        <i class="bi bi-x-circle me-1"></i>Reject
                                                    </button>
                                                </div>
                                            </td>
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

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                    <div class="mb-3">
                        <label for="remarksInput" class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" id="remarksInput" rows="3" placeholder="Add any comments or reasons..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

<script>
    let currentEnrollmentId = null;
    let currentAction = null;

    function processEnrollment(enrollmentId, action, studentName) {
        currentEnrollmentId = enrollmentId;
        currentAction = action;
        
        const modal = new bootstrap.Modal(document.getElementById('actionModal'));
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        
        switch(action) {
            case 'approve':
                modalTitle.textContent = 'Approve Enrollment';
                modalMessage.textContent = `Are you sure you want to approve the enrollment for ${studentName}? This will also update their Student ID if needed.`;
                confirmBtn.className = 'btn btn-success';
                confirmBtn.textContent = 'Approve';
                break;
            case 'reject':
                modalTitle.textContent = 'Reject Enrollment';
                modalMessage.textContent = `Are you sure you want to reject the enrollment for ${studentName}?`;
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.textContent = 'Reject';
                break;
            case 'missing_documents':
                modalTitle.textContent = 'Mark as Missing Documents';
                modalMessage.textContent = `Mark ${studentName}'s enrollment as missing documents?`;
                confirmBtn.className = 'btn btn-warning';
                confirmBtn.textContent = 'Mark as Missing';
                break;
        }

        modal.show();
    }

    document.getElementById('confirmBtn').addEventListener('click', () => {
        const remarks = document.getElementById('remarksInput').value;
        const confirmBtn = document.getElementById('confirmBtn');
        
        // Disable button to prevent double clicks
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';

        console.log('Sending request with:', {
            enrollment_id: currentEnrollmentId,
            action: currentAction,
            remarks: remarks
        });

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                enrollment_id: currentEnrollmentId,
                action: currentAction,
                remarks: remarks
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text(); // Get as text first to see what we're getting
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                alert('Invalid response from server. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Network error: ' + error.message);
        })
        .finally(() => {
            // Re-enable button
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm';
        });
    });
</script>
</body>
</html>