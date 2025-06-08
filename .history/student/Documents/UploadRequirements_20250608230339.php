<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../logregfor/login.php");
    exit;
}

require_once '../../db.php';

$student_id = $_SESSION['student_id'];
$message = '';
$messageType = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $documentType = trim($_POST['document_type'] ?? '');
    $file = $_FILES['document_file'];

    if (empty($documentType)) {
        $message = "Please select a document type.";
        $messageType = "danger";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file. Please try again.";
        $messageType = "danger";
    } else {
        // Validate file
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            $message = "Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.";
            $messageType = "danger";
        } elseif ($file['size'] > $maxSize) {
            $message = "File is too large. Maximum size is 5MB.";
            $messageType = "danger";
        } else {
            // Create upload directory
            $uploadDir = __DIR__ . '/uploads/' . $student_id;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileName = uniqid() . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save to database
                try {
                    $stmt = $pdo->prepare("INSERT INTO uploaded_documents (student_id, doc_type, file_name, file_path, upload_date, status, remarks) VALUES (?, ?, ?, ?, NOW(), 'Uploaded', 'Waiting for verification')");
                    $stmt->execute([$student_id, $documentType, $file['name'], $fileName]);
                    
                    $message = "Document uploaded successfully! It will be reviewed by the registrar.";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Database error: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $message = "Failed to upload file. Please try again.";
                $messageType = "danger";
            }
        }
    }
}

// Fetch uploaded documents
$stmt = $pdo->prepare("SELECT * FROM uploaded_documents WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->execute([$student_id]);
$documents = $stmt->fetchAll();

function getBadgeClass($status) {
    return match(strtolower($status)) {
        'uploaded' => 'bg-primary',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger',
        'pending' => 'bg-warning text-dark',
        default => 'bg-secondary'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Requirements - Student Portal</title>
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

        .upload-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(46, 125, 50, 0.1);
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

        .form-label {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-select, .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 0.25rem rgba(67, 160, 71, 0.25);
            background-color: #fff;
        }

        .btn-upload {
            background: linear-gradient(135deg, var(--accent-green) 0%, var(--primary-green) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 160, 71, 0.4);
            color: white;
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

        .requirements-list {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .requirements-list h6 {
            color: #856404;
            font-weight: 700;
        }

        .requirements-list ul {
            color: #856404;
            margin-bottom: 0;
        }

        .file-preview {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="section-title">
            <i class="bi bi-cloud-upload"></i>
            Upload Requirements
        </h2>

        <!-- Requirements List -->
        <div class="requirements-list">
            <h6><i class="bi bi-info-circle"></i> Required Documents</h6>
            <ul>
                <li>3 pieces 1x1 ID Pictures (recent)</li>
                <li>4 pieces Passport Size Pictures (recent)</li>
                <li>Form 137 / SF10 (Original or Certified True Copy)</li>
                <li>Report Card / SF9 (Original or Certified True Copy)</li>
                <li>PSA Birth Certificate (Original or Certified True Copy)</li>
                <li>Diploma (Photocopy)</li>
                <li>Certificate of Good Moral Character</li>
            </ul>
            <small><strong>Note:</strong> All documents must be clear and readable. Maximum file size is 5MB per document.</small>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-card">
            <h4 class="section-title">Upload Document</h4>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="document_type" class="form-label">
                            <i class="bi bi-file-text"></i> Document Type
                        </label>
                        <select class="form-select" name="document_type" id="document_type" required>
                            <option value="">Select Document Type</option>
                            <option value="1x1 Pictures">3pcs 1x1 Pictures</option>
                            <option value="Passport Pictures">4pcs Passport Size Pictures</option>
                            <option value="Form 137">Form 137 / SF10</option>
                            <option value="Report Card">Report Card / SF9</option>
                            <option value="Birth Certificate">PSA Birth Certificate</option>
                            <option value="Diploma">Diploma (Photocopy)</option>
                            <option value="Good Moral">Certificate of Good Moral</option>
                            <option value="Other">Other Document</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="document_file" class="form-label">
                            <i class="bi bi-upload"></i> Choose File
                        </label>
                        <input type="file" class="form-control" name="document_file" id="document_file" 
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Accepted formats: PDF, JPG, JPEG, PNG (Max: 5MB)</div>
                    </div>
                </div>
                <button type="submit" class="btn-upload">
                    <i class="bi bi-cloud-upload"></i> Upload Document
                </button>
            </form>
        </div>

        <!-- Uploaded Documents -->
        <div class="documents-card">
            <h4 class="section-title">Uploaded Documents</h4>
            <?php if (empty($documents)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-file-earmark-x" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="text-muted mt-2">No documents uploaded yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
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
                                        <?= htmlspecialchars($doc['doc_type']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($doc['file_name']) ?></td>
                                    <td><?= date('M d, Y g:i A', strtotime($doc['upload_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= getBadgeClass($doc['status']) ?>">
                                            <?= htmlspecialchars($doc['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($doc['remarks']) ?></td>
                                    <td>
                                        <a href="uploads/<?= $student_id ?>/<?= htmlspecialchars($doc['file_path']) ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <?php if ($doc['status'] === 'Rejected'): ?>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="reuploadDocument('<?= htmlspecialchars($doc['doc_type']) ?>')">
                                                <i class="bi bi-arrow-clockwise"></i> Re-upload
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File validation
        document.getElementById('document_file').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                
                if (file.size > maxSize) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.');
                    this.value = '';
                    return;
                }
            }
        });

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const documentType = document.getElementById('document_type').value;
            const documentFile = document.getElementById('document_file').files[0];
            
            if (!documentType || !documentFile) {
                e.preventDefault();
                alert('Please select a document type and choose a file to upload.');
            }
        });

        // Re-upload function
        function reuploadDocument(docType) {
            document.getElementById('document_type').value = docType;
            document.getElementById('document_file').focus();
        }
    </script>
</body>
</html>
