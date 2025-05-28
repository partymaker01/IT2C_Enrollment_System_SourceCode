<?php
$enrollmentRequests = [
    [
        'student_id' => '2023001',
        'name' => 'JM Dela Cruz',
        'program' => 'IT',
        'year_level' => '1st Year',
        'date_submitted' => '2025-05-24',
    ],
    [
        'student_id' => '2023002',
        'name' => 'Joshua Santos',
        'program' => 'HRMT',
        'year_level' => '2nd Year',
        'date_submitted' => '2025-05-23',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Approve/Reject Enrollment</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #f9fbe7;
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      padding-bottom: 40px;
    }
    .navbar {
      background-color: #2e7d32;
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
      margin-top: 50px;
      max-width: 1000px;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .btn-approve {
      background-color: #4caf50;
      color: white;
      transition: background-color 0.3s;
    }
    .btn-approve:hover,
    .btn-approve:focus {
      background-color: #388e3c;
      color: white;
      outline: none;
      box-shadow: 0 0 8px #388e3c;
    }
    .btn-reject {
      background-color: #f44336;
      color: white;
      transition: background-color 0.3s;
    }
    .btn-reject:hover,
    .btn-reject:focus {
      background-color: #c62828;
      color: white;
      outline: none;
      box-shadow: 0 0 8px #c62828;
    }
    .badge-year {
      font-size: 0.9rem;
    }
    .table thead {
      background-color: #aed581;
    }
    table tbody tr:hover {
      background-color: #e6f2d9;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }
    .text-nowrap {
      white-space: nowrap;
    }
    @media (max-width: 575.98px) {
      .btn-approve, .btn-reject {
        width: 100%;
        margin-bottom: 0.5rem;
      }
      td > .btn + .btn {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="/IT2C_Enrollment_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
      Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
      </ul>
      </div>
    </div>
  </nav> 
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-success">
        Approve / Reject Enrollment Requests
      </h2>
      <p class="text-muted fs-6">
        Review student enrollment requests submitted to the registrar.
      </p>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-3 p-md-4">

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="text-center text-dark">
              <tr>
                <th class="text-nowrap">
                  Student ID
                </th>
                <th>
                  Name
                </th>
                <th class="text-nowrap">
                  Program
                </th>
                <th class="text-nowrap">
                  Year Level
                </th>
                <th class="text-nowrap">
                  Date Submitted
                </th>
                <th class="text-nowrap">
                  Action
                </th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php if (empty($enrollmentRequests)): ?>
                <tr>
                  <td colspan="6" class="text-muted text-center py-4">
                    No enrollment requests found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($enrollmentRequests as $request): ?>
                  <tr>
                    <td class="text-nowrap"><?= htmlspecialchars($request['student_id']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($request['name']) ?></td>
                    <td class="text-nowrap"><?= htmlspecialchars($request['program']) ?></td>
                    <td>
                      <span class="badge bg-success badge-year"><?= htmlspecialchars($request['year_level']) ?></span>
                    </td>
                    <td class="text-nowrap"><?= htmlspecialchars($request['date_submitted']) ?></td>
                    <td class="d-flex flex-wrap justify-content-center gap-2">
                      <button 
                        class="btn btn-sm btn-approve d-flex align-items-center" 
                        data-bs-toggle="modal" 
                        data-bs-target="#approveModal<?= $request['student_id'] ?>"
                        title="Approve Enrollment"
                      >
                        <i class="bi bi-check-circle me-1"></i>
                        Approve
                      </button>

                      <button 
                        class="btn btn-sm btn-reject d-flex align-items-center" 
                        data-bs-toggle="modal" 
                        data-bs-target="#rejectModal<?= $request['student_id'] ?>"
                        title="Reject Enrollment"
                      >
                        <i class="bi bi-x-circle me-1"></i>
                        Reject
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <?php foreach ($enrollmentRequests as $request): ?>
      <div class="modal fade" id="approveModal<?= $request['student_id'] ?>" tabindex="-1" aria-labelledby="approveLabel<?= $request['student_id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title" id="approveLabel<?= $request['student_id'] ?>">
                Confirm Approval
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              Are you sure you want to
              <strong>approve</strong>
              the enrollment of
              <strong><?= htmlspecialchars($request['name']) ?></strong>?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-success">
                Yes, Approve
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="rejectModal<?= $request['student_id'] ?>" tabindex="-1" aria-labelledby="rejectLabel<?= $request['student_id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title" id="rejectLabel<?= $request['student_id'] ?>">
                Confirm Rejection
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              Are you sure you want to
              <strong>
                reject
              </strong>
              the enrollment of
              <strong><?= htmlspecialchars($request['name']) ?></strong>?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                Cancel
              </button>
              <button type="button" class="btn btn-danger">
                Yes, Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  </script>

</body>
</html>