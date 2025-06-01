<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!$id || !$action) {
        echo 'Invalid request.';
        exit;
    }

// Database connection (update $db if needed)
$host = 'localhost';
$db = 'enrollment_system'; // <-- make sure this matches your actual DB name
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch all pending enrollments with student info
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.student_id,
            s.first_name,
            s.last_name,
            e.year_level,
            e.program,
            e.date_submitted
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        WHERE e.status = 'pending'
        ORDER BY e.date_submitted DESC
    ");
    $stmt->execute();
    $pendingenrollments = $stmt->fetchAll();
} catch (PDOException $e) {
    $pendingenrollments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pending enrollments Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body {
      background-color: #f4f9f4;
    }
    .navbar {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
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
      min-width: 100px;
    }
    .btn-orange {
      background-color: #ff9800;
      border-color: #ff9800;
      color: #fff;
    }
    .btn-orange:hover {
      background-color: #fb8c00;
      border-color: #fb8c00;
    }
    .table-responsive {
      max-height: 500px;
      overflow-y: auto;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="/IT2C_enrollments_System_SourceCode/picture/tlgc_pic.jpg" alt="School Logo" class="school-logo">
      Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/IT2C_enrollments_System_SourceCode/ADMIN/admin-dashboard.php">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a></li>
      </ul>
    </div>
  </div>
</nav> 

<div class="container">
  <h2 class="mb-4 text-center">
    Pending enrollments Requests
  </h2>

  <div class="table-responsive shadow-sm rounded bg-white">
    <table class="table table-bordered table-hover mb-0">
      <thead class="table-success">
        <tr>
          <th>#</th>
          <th>Student Name</th>
          <th>Year Level</th>
          <th>Program</th>
          <th>Submitted On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingenrollments)): ?>
          <tr><td colspan="6" class="text-center">No pending enrollments found.</td></tr>
        <?php else: ?>
          <?php foreach ($pendingenrollments as $enrollments): ?>
          <tr>
            <td><?= htmlspecialchars($enrollments['student_id']) ?></td>
            <td><?= htmlspecialchars($enrollments['first_name'] . ' ' . $enrollments['last_name']) ?></td>
            <td><?= htmlspecialchars($enrollments['year_level']) ?></td>
            <td><?= htmlspecialchars($enrollments['program']) ?></td>
            <td><?= date('M d, Y', strtotime($enrollments['date_submitted'])) ?></td>
            <td class="d-flex flex-wrap gap-2 justify-content-center">
              <button class="btn btn-success btn-sm btn-action"
                data-action="approve"
                data-id="<?= $enrollments['id'] ?>"
                data-name="<?= htmlspecialchars($enrollments['first_name'] . ' ' . $enrollments['last_name']) ?>"
                data-program="<?= htmlspecialchars($enrollments['program']) ?>"
                data-year="<?= htmlspecialchars($enrollments['year_level']) ?>"
                data-date="<?= date('M d, Y', strtotime($enrollments['date_submitted'])) ?>"
              >✅ Approve</button>

              <button class="btn btn-orange btn-sm btn-action"
                data-action="missing"
                data-id="<?= $enrollments['id'] ?>"
                data-name="<?= htmlspecialchars($enrollments['first_name'] . ' ' . $enrollments['last_name']) ?>"
                data-program="<?= htmlspecialchars($enrollments['program']) ?>"
                data-year="<?= htmlspecialchars($enrollments['year_level']) ?>"
                data-date="<?= date('M d, Y', strtotime($enrollments['date_submitted'])) ?>"
              >⚠️ Missing</button>

              <button class="btn btn-danger btn-sm btn-action"
                data-action="reject"
                data-id="<?= $enrollments['id'] ?>"
                data-name="<?= htmlspecialchars($enrollments['first_name'] . ' ' . $enrollments['last_name']) ?>"
                data-program="<?= htmlspecialchars($enrollments['program']) ?>"
                data-year="<?= htmlspecialchars($enrollments['year_level']) ?>"
                data-date="<?= date('M d, Y', strtotime($enrollments['date_submitted'])) ?>"
              >❌ Reject</button>

              <button class="btn btn-secondary btn-sm btn-action"
                data-action="view"
                data-id="<?= $enrollments['id'] ?>"
                data-name="<?= htmlspecialchars($enrollments['first_name'] . ' ' . $enrollments['last_name']) ?>"
                data-program="<?= htmlspecialchars($enrollments['program']) ?>"
                data-year="<?= htmlspecialchars($enrollments['year_level']) ?>"
                data-date="<?= date('M d, Y', strtotime($enrollments['date_submitted'])) ?>"
              >👁️ View</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted mt-3 text-center" style="font-size: 0.9rem;">
    Note: Approve or reject each request, or view full details before deciding.
  </p>
</div>

<!-- Modal (same as your original modal) -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="actionModalLabel">Action Confirmation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalMessage">Are you sure?</p>
        <p><strong>Name:</strong> <span id="studentName"></span></p>
        <p><strong>Program:</strong> <span id="studentProgram"></span></p>
        <p><strong>Year:</strong> <span id="studentYear"></span></p>
        <p><strong>Date:</strong> <span id="studentDate"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmActionBtn" class="btn btn-primary">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
  const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));

  document.querySelectorAll('.btn-action').forEach(button => {
    button.addEventListener('click', function () {
      const action = this.getAttribute('data-action');
      const name = this.getAttribute('data-name');
      const program = this.getAttribute('data-program');
      const year = this.getAttribute('data-year');
      const date = this.getAttribute('data-date');
      const id = this.getAttribute('data-id');

      document.getElementById('studentName').textContent = name;
      document.getElementById('studentProgram').textContent = program;
      document.getElementById('studentYear').textContent = year;
      document.getElementById('studentDate').textContent = date;

      const modalMsg = {
        'approve': 'Do you want to approve this enrollments?',
        'missing': 'Mark this enrollments as missing documents?',
        'reject': 'Are you sure you want to reject this enrollments?',
        'view': 'Viewing full details below:'
      };

      document.getElementById('modalMessage').textContent = modalMsg[action] || 'Proceed with this action?';

      const confirmBtn = document.getElementById('confirmActionBtn');
      confirmBtn.textContent = action === 'view' ? 'Close' : 'Confirm';
      confirmBtn.className = `btn ${action === 'approve' ? 'btn-success' : action === 'missing' ? 'btn-warning' : action === 'reject' ? 'btn-danger' : 'btn-secondary'}`;
      
      confirmBtn.onclick = () => {
        if(action === 'view') {
          actionModal.hide();
          return;
        }
        
        alert(`${action.charAt(0).toUpperCase() + action.slice(1)} confirmed for ${name} (ID: ${id}).`);
        actionModal.hide();

      };

      actionModal.show();
    });
  });

  confirmBtn.onclick = () => {
  if (action === 'view') {
    actionModal.hide();
    return;
  }

  fetch('process_action.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      id: id,
      action: action
    })
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      alert(`${action.charAt(0).toUpperCase() + action.slice(1)} successful for ${name}.`);
      location.reload(); // Refresh the table
    } else {
      alert('Action failed: ' + response);
    }
    actionModal.hide();
  })
  .catch(err => {
    alert('Error: ' + err);
    actionModal.hide();
  });
};
</script>
</body>
</html>
