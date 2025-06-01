<?php
// DB connection - adjust to your credentials
$host = 'localhost';
$db = 'enrollment_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('DB connection failed: ' . $e->getMessage());
}

$stmt = $pdo->query("SELECT * FROM tickets ORDER BY date_submitted DESC");
$tickets = $stmt->fetchAll();

// Fetch conversations grouped by ticket_id
$stmt2 = $pdo->query("SELECT * FROM conversations ORDER BY created_at ASC");
$conversationsRaw = $stmt2->fetchAll();

$conversations = [];
foreach ($conversationsRaw as $conv) {
    $conversations[$conv['ticket_id']][] = [
        'sender' => $conv['sender'],
        'message' => $conv['message']
    ];
}

$tickets = [
    'HD-2025-001' => [...],
    'HD-2025-002' => [...],
    'HD-2025-003' => [...],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Help Desk - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
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
    .badge-open { background-color: #0d6efd; }
    .badge-inprogress { background-color: #ffc107; color: #212529; }
    .badge-resolved { background-color: #198754; }
    .badge-closed { background-color: #6c757d; }
    thead th { position: sticky; top: 0; background-color: #d1e7dd; z-index: 10; }
    @media (max-width: 575.98px) { .row.mb-3 > div { margin-bottom: 1rem; } }
    button.btn { transition: background-color 0.3s ease; }
    button.btn-primary:hover { background-color: #004085; }
    button.btn-success:hover { background-color: #14532d; }
    .modal-header.bg-success { background-color: #198754 !important; }
    #conversationThread { scrollbar-width: thin; scrollbar-color: #198754 #e9ecef; }
    #conversationThread::-webkit-scrollbar { width: 8px; }
    #conversationThread::-webkit-scrollbar-thumb { background-color: #198754; border-radius: 4px; }
    #replyStatus { min-width: 140px; }

    @media (max-width: 575.98px) {
  th:nth-child(3), td:nth-child(3),
  th:nth-child(6), td:nth-child(6) {
    display: none;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.3em 0.4em;
  }

  table {
    font-size: 0.85rem;
  }
}


    @media (max-width: 575.98px) {
  .modal-lg {
    max-width: 95vw;
    margin: 0 10px;
  }
}


    @media (max-width: 575.98px) {
  .row.mb-3 > div {
    margin-bottom: 1rem;
  }
  #filterStatus, #searchInput, button.btn-success {
    width: 100% !important;
  }
}


    @media (max-width: 575.98px) {

  th:nth-child(3), td:nth-child(3),
  th:nth-child(6), td:nth-child(6) {
    display: none;
  }


  .badge {
    font-size: 0.75rem;
    padding: 0.3em 0.4em;
  }

  table {
    font-size: 0.85rem;
  }

  .modal-lg {
    max-width: 95vw;
    margin: 0 10px;
  }

  .row.mb-3 > div {
    margin-bottom: 1rem;
  }
  #filterStatus, #searchInput, button.btn-success {
    width: 100% !important;
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
  <div class="container my-4">
    <h2 class="mb-4 text-success">
      Help Desk - Admin Dashboard
    </h2>

    <div class="row mb-3 g-3">
      <div class="col-md-3 col-12">
        <select class="form-select" id="filterStatus" aria-label="Filter tickets by status">
          <option value="">
            Filter by Status
          </option>
          <option value="Open">
            Open
          </option>
          <option value="In Progress">
            In Progress
          </option>
          <option value="Resolved">
            Resolved
          </option>
          <option value="Closed">
            Closed
          </option>
        </select>
      </div>
      <div class="col-md-4 col-12">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by Student or Ticket ID" aria-label="Search tickets" />
      </div>
      <div class="col-md-2 col-12">
        <button class="btn btn-success w-100" onclick="resetFilters()" aria-label="Reset filters">
          Reset Filters
        </button>
      </div>
    </div>

    <div class="table-responsive shadow-sm rounded">
  <table class="table table-striped table-hover align-middle" id="ticketsTable">
        <thead class="table-success">
          <tr>
            <th scope="col">
              Ticket ID
            </th>
            <th scope="col">
              Student Name
            </th>
            <th scope="col">
              Category
            </th>
            <th scope="col">
              Priority
            </th>
            <th scope="col">
              Status
            </th>
            <th scope="col">
              Date Submitted
            </th>
            <th scope="col">
              Action
            </th>
          </tr>
        </thead>
<tbody>
<?php foreach ($tickets as $ticket): 
    $ticketID = htmlspecialchars($ticket['ticket_id']);
    $studentName = htmlspecialchars($ticket['student_name']);
    $category = htmlspecialchars($ticket['category']);
    $priority = htmlspecialchars($ticket['priority']);
    $status = htmlspecialchars($ticket['status']);
    $dateSubmitted = date('Y-m-d h:i A', strtotime($ticket['date_submitted']));
    $message = htmlspecialchars($ticket['message']);

    // Priority badge class
    switch ($priority) {
        case 'High': $priorityClass = 'bg-danger'; break;
        case 'Medium': $priorityClass = 'bg-warning text-dark'; break;
        default: $priorityClass = 'bg-secondary'; break;
    }
    // Status badge class
    switch ($status) {
        case 'Open': $statusClass = 'badge-open'; break;
        case 'In Progress': $statusClass = 'badge-inprogress'; break;
        case 'Resolved': $statusClass = 'badge-resolved'; break;
        case 'Closed': $statusClass = 'badge-closed'; break;
        default: $statusClass = ''; break;
    }

    // Prepare conversation HTML
    $convHTML = '';
    if (isset($conversations[$ticket['ticket_id']])) {
        foreach ($conversations[$ticket['ticket_id']] as $msg) {
            $convHTML .= '<p><strong>' . htmlspecialchars($msg['sender']) . ':</strong> ' . nl2br(htmlspecialchars($msg['message'])) . '</p>';
        }
    }
      ?>
      <tr>
        <td><?= $ticketID ?></td>
        <td><?= $studentName ?></td>
        <td><?= $category ?></td>
        <td><span class="badge <?= $priorityClass ?>"><?= $priority ?></span></td>
        <td><span class="badge <?= $statusClass ?>"><?= $status ?></span></td>
        <td><?= $dateSubmitted ?></td>
        <td>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticketModal"
            onclick='openTicketDetails("<?= addslashes($ticketID) ?>")' aria-label="View details of ticket <?= $ticketID ?>">View</button>
        </td>
      </tr>
      <script>
        tickets["<?= addslashes($ticketID) ?>"] = {
          studentName: "<?= addslashes($studentName) ?>",
          category: "<?= addslashes($category) ?>",
          priority: "<?= addslashes($priority) ?>",
          priorityClass: "<?= addslashes($priorityClass) ?>",
          status: "<?= addslashes($status) ?>",
          statusClass: "<?= addslashes($statusClass) ?>",
          date: "<?= addslashes($dateSubmitted) ?>",
          message: "<?= addslashes($message) ?>",
          conversation: `<?= addslashes($convHTML) ?>`
        };
      </script>
      <?php endforeach; ?>
      </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="ticketModalLabel">
            Ticket Details
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="ticketInfo">
            <p><strong>
              Ticket ID:
            </strong> <span id="modalTicketID"></span></p>
            <p><strong>
              Student Name:
            </strong> <span id="modalStudentName"></span></p>
            <p><strong>
              Category:
            </strong> <span id="modalCategory"></span></p>
            <p><strong>
              Priority:
            </strong> <span id="modalPriority"></span></p>
            <p><strong>
              Status:
            </strong> <span id="modalStatus"></span></p>
            <p><strong>
              Date Submitted:
            </strong> <span id="modalDate"></span></p>
            <hr />
            <p><strong>
              Message:
            </strong></p>
            <p id="modalMessage"></p>
          </div>

          <div id="conversationThread" class="mt-4" tabindex="0" aria-label="Conversation thread" style="max-height:300px; overflow-y:auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
            <h6>
              Conversation
            </h6>
          </div>

          <form id="replyForm" class="mt-3" aria-label="Reply to ticket form">
            <label for="replyMessage" class="form-label">
              Reply to Ticket
            </label>
            <textarea id="replyMessage" class="form-control" rows="3" placeholder="Type your reply here..." aria-required="true"></textarea>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mt-2 gap-2">
              <select id="replyStatus" class="form-select w-auto" aria-label="Select ticket status">
                <option value="Open">
                  Open
                </option>
                <option value="In Progress">
                  In Progress
                </option>
                <option value="Resolved">
                  Resolved
                </option>
                <option value="Closed">
                  Closed
                </option>
              </select>
              <button type="submit" class="btn btn-success">
                Send Reply
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const tickets = {};

    function openTicketDetails(ticketID) {
      const t = tickets[ticketID];
      if (!t) return;

      document.getElementById('modalTicketID').innerText = ticketID;
      document.getElementById('modalStudentName').innerText = t.studentName;
      document.getElementById('modalCategory').innerText = t.category;
      document.getElementById('modalPriority').innerHTML = `<span class="badge ${t.priorityClass}">${t.priority}</span>`;
      document.getElementById('modalStatus').innerHTML = `<span class="badge ${t.statusClass}">${t.status}</span>`;
      document.getElementById('modalDate').innerText = t.date;
      document.getElementById('modalMessage').innerText = t.message;
      document.getElementById('conversationThread').innerHTML = `<h6>Conversation</h6>` + t.conversation;

      document.getElementById('replyStatus').value = t.status;
    }

    document.getElementById('replyForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const message = document.getElementById('replyMessage').value.trim();
      if (!message) {
        alert('Please enter a reply message.');
        return;
      }
      alert('Reply sent! (Implement actual backend functionality)');
      document.getElementById('replyMessage').value = '';
    });

    document.getElementById('filterStatus').addEventListener('change', filterTickets);
    document.getElementById('searchInput').addEventListener('input', filterTickets);

    function filterTickets() {
      const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#ticketsTable tbody tr');

      rows.forEach(row => {
        const status = row.cells[4].innerText.toLowerCase();
        const studentName = row.cells[1].innerText.toLowerCase();
        const ticketID = row.cells[0].innerText.toLowerCase();

        const statusMatch = statusFilter === '' || status.includes(statusFilter);
        const searchMatch = studentName.includes(searchTerm) || ticketID.includes(searchTerm);

        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
      });
    }

    function resetFilters() {
      document.getElementById('filterStatus').value = '';
      document.getElementById('searchInput').value = '';
      filterTickets();
    }
  </script>
</body>
</html>
