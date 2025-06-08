<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];

// Fetch uploaded documents
$stmt = $pdo->prepare("SELECT * FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->execute([$student_id]);
$documents = $stmt->fetchAll();

// Helper functions
function getStatusClass($status) {
    return match(strtolower($status)) {
        'uploaded' => 'bg-primary',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending' => 'bg-warning text-dark',
        default => 'bg-secondary'
    };
}

function getStatusIcon($status) {
    return match(strtolower($status)) {
        'uploaded' => 'bi-upload',
        'verified' => 'bi-check-circle',
        'rejected' => 'bi-x-circle',
        'pending' => 'bi-clock',
        default => 'bi-file'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Uploaded Files - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../../picture/tlgc_pic.jpg" type="image/x-icon">
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #e8f5e9;
            --accent-green: #43a047;
            --hover-green: #c8e6c9;
            --dark-green: #1b5e20;
        }

        body {
            background: linear-gradient(135deg, var(--light-green) 0%, #f1f8e9 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            box-shadow: 0 2px 10px rgba(46, 125, 50, 0.3);
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-link:hover {
            color: var(--hover-green) !important;
        }

        .school-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid #fff;
        }

        .documents-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            padding: 2rem;
            border: 1px solid rgba(46, 125, 50, 0.1);
        }

        .section-title {
            color: var(--primary-green);
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green), var(--primary-green));
            border-radius: 2px;
        }

        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            color: white;
            border: none;
            font-weight: 600;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: var(--light-green);
            transform: scale(1.01);
        }

        .document-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../student-dashboard.php">
                <img src="../../picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
                <span>Student Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../student-dashboard.php">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </li>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="section-title">
            <i class="bi bi-files"></i>
            My Uploaded Documents
        </h2>

        <div class="documents-card">
            <?php if (empty($documents)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="text-muted mt-3">No Documents Found</h4>
                    <p class="text-muted">You haven't uploaded any documents yet.</p>
                    <a href="UploadRequirements.php" class="btn-custom">
                        <i class="bi bi-upload me-2"></i>Upload Your First Document
                    </a>
                </div>
            <?php else: ?>
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        <strong><?= htmlspecialchars($doc['doc_type']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($doc['file_name']) ?></td>
                                    <td><?= date('M d, Y g:i A', strtotime($doc['upload_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= getStatusClass($doc['status']) ?>">
                                            <i class="bi <?= getStatusIcon($doc['status']) ?> me-1"></i>
                                            <?= htmlspecialchars($doc['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($doc['remarks']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($doc['file_path']): ?>
                                            <a href="uploads/<?= $student_id ?>/<?= htmlspecialchars($doc['file_path']) ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($doc['status'] === 'Rejected'): ?>
                                            <a href="UploadRequirements.php" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-arrow-clockwise"></i> Re-upload
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="d-md-none">
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?= htmlspecialchars($doc['doc_type']) ?>
                                </h6>
                                <span class="badge <?= getStatusClass($doc['status']) ?>">
                                    <i class="bi <?= getStatusIcon($doc['status']) ?> me-1"></i>
                                    <?= htmlspecialchars($doc['status']) ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2">
                                <small><strong>File:</strong> <?= htmlspecialchars($doc['file_name']) ?></small>
                            </p>
                            <p class="text-muted mb-2">
                                <small><strong>Uploaded:</strong> <?= date('M d, Y g:i A', strtotime($doc['upload_date'])) ?></small>
                            </p>
                            <p class="text-muted mb-3">
                                <small><strong>Remarks:</strong> <?= htmlspecialchars($doc['remarks']) ?></small>
                            </p>
                            <div class="d-flex gap-2">
                                <?php if ($doc['file_path']): ?>
                                    <a href="uploads/<?= $student_id ?>/<?= htmlspecialchars($doc['file_path']) ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                <?php endif; ?>
                                <?php if ($doc['status'] === 'Rejected'): ?>
                                    <a href="UploadRequirements.php" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-arrow-clockwise"></i> Re-upload
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <h5 class="text-primary"><?= count($documents) ?></h5>
                            <small class="text-muted">Total Documents</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <h5 class="text-success"><?= count(array_filter($documents, fn($d) => $d['status'] === 'Verified')) ?></h5>
                            <small class="text-muted">Verified</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <h5 class="text-warning"><?= count(array_filter($documents, fn($d) => $d['status'] === 'Uploaded')) ?></h5>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center">
                            <h5 class="text-danger"><?= count(array_filter($documents, fn($d) => $d['status'] === 'Rejected')) ?></h5>
                            <small class="text-muted">Rejected</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
