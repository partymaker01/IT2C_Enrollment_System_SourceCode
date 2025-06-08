<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../logregfor/admin-login.php");
    exit();
}

include '../../db.php';

// Fetch tickets from database
try {
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
} catch (PDOException $e) {
    // If tables don't exist yet, use sample data
    $tickets = [
        [
            'ticket_id' => 'HD-2025-001',
            'student_name' => 'John Lester Lina',
            'category' => 'Enrollment Issue',
            'priority' => 'High',
            'status' => 'Open',
            'date_submitted' => '2025-05-20 10:15:00',
            'message' => 'I am having trouble with my enrollment status. It shows pending but I already submitted all requirements.',
        ],
        [
            'ticket_id' => 'HD-2025-002',
            'student_name' => 'Maria Santos',
            'category' => 'Document Request',
            'priority' => 'Medium',
            'status' => 'In Progress',
            'date_submitted' => '2025-05-19 14:30:00',
            'message' => 'I need to request my transcript of records for scholarship application.',
        ],
        [
            'ticket_id' => 'HD-2025-003',
            'student_name' => 'James Rodriguez',
            'category' => 'Technical Issue',
            'priority' => 'Low',
            'status' => 'Resolved',
            'date_submitted' => '2025-05-18 09:45:00',
            'message' => 'I cannot access my student portal account. It says "invalid credentials".',
        ]
    ];
    
    $conversations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Help Desk - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
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
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        .navbar {
            background-color: var(--primary-green);
        }
        
        .navbar-brand, .nav-link {
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        
        .nav-link:hover {
            color: var(--hover-green) !important;
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
        
        thead th { 
            position: sticky; 
            top: 0; 
            background-color: #d1e7dd; 
            z-index: 10; 
        }
        
        button.btn { transition: background-color 0.3s ease; }
        button.btn-primary:hover { background-color: #004085; }
        button.btn-success:hover { background-color: #14532d; }
        
        .modal-header.bg-success { background-color: #198754 !important; }
        
        #conversationThread { 
            scrollbar-width: thin; 
            scrollbar-color: #198754 #e9ecef; 
        }
        
        #conversationThread::-webkit-scrollbar { width: 8px; }
        #conversationThread::-webkit-scrollbar-thumb { 
            background-color: #198754; 
            border-radius: 4px; 
        }
        
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
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../admin-dashboard.php">
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
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container my-4">
        <h2 class="mb-4 text-success">
            <i class="bi bi-headset me-2"></i>Help Desk - Admin Dashboard
        </h2>

        <div class="row mb-3 g-3">
            <div class="col-md-3 col-12">
                <select class="form-select" id="filterStatus" aria-label="Filter tickets by status">
                    <option value="">Filter by Status</option>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="col-md-4 col-12">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Student or Ticket ID" aria-label="Search tickets" />
            </div>
            <div class="col-md-2 col-12">
                <button class="btn btn-success w-100" onclick="resetFilters()" aria-label="Reset filters">
                    <i class="bi bi-arrow-repeat me-1"></i> Reset Filters
                </button>
            </div>
        </div>

        <div class="table-responsive shadow-sm rounded">
            <table class="table table-striped table-hover align-middle" id="ticketsTable">
                <thead class="table-success">
                    <tr>
                        <th scope="col">Ticket ID</th>
                        <th scope="col">Student Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date Submitted</th>
                        <th scope="col">Action</th>
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
                        switch (strtolower($status)) {
                            case 'open': $statusClass = 'badge-open'; break;
                            case 'in progress': $statusClass = 'badge-inprogress'; break;
                            case 'resolved': $statusClass = 'badge-resolved'; break;
                            case 'closed': $statusClass = 'badge-closed'; break;
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
                                onclick='openTicketDetails("<?= $ticketID ?>", "<?= addslashes($studentName) ?>", "<?= addslashes($category) ?>", "<?= addslashes($priority) ?>", "<?= addslashes($priorityClass) ?>", "<?= addslashes($status) ?>", "<?= addslashes($statusClass) ?>", "<?= addslashes($dateSubmitted) ?>", "<?= addslashes($message) ?>", `<?= isset($convHTML) ? addslashes($convHTML) : '' ?>")' 
                                aria-label="View details of ticket <?= $ticketID ?>">
                                <i class="bi bi-eye me-1"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="ticketModalLabel">Ticket Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ticket ID:</strong> <span id="modalTicketID"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Student Name:</strong> <span id="modalStudentName"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Category:</strong> <span id="modalCategory"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong> 
                            <span id="modalPriority" class="badge"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span id="modalStatus" class="badge"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Date Submitted:</strong> 
                            <span id="modalDateSubmitted"></span>
                        </div>
                    </div>
                    <hr />
                    <h5>Message</h5>
                    <p id="modalMessage"></p>

                    <hr />
                    <h5>Conversation Thread</h5>
                    <div id="conversationThread" style="max-height: 300px; overflow-y: auto;">
                        <!-- Conversation messages will be injected here -->
                    </div>

                    <hr />
                    <h5>Reply to Ticket</h5>
                    <div class="mb-3">
                        <textarea class="form-control" id="replyMessage" rows="3" placeholder="Type your reply here..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-success" id="sendReplyButton" onclick="sendReply()" aria-label="Send reply to ticket">
                            <i class="bi bi-send me-1"></i> Send Reply
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="closeTicketButton" onclick="closeTicket()"
                        aria-label="Close this ticket">Close Ticket</button>
                </div>
            </div>
        </div>
    </div>          
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to open ticket details in modal
        function openTicketDetails(ticketID, studentName, category, priority, priorityClass, status, statusClass, dateSubmitted, message, conversationHTML) {
            document.getElementById('modalTicketID').textContent = ticketID;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalCategory').textContent = category;
            document.getElementById('modalPriority').textContent = priority;
            document.getElementById('modalPriority').className = 'badge ' + priorityClass;
            document.getElementById('modalStatus').textContent = status;
            document.getElementById('modalStatus').className = 'badge ' + statusClass;
            document.getElementById('modalDateSubmitted').textContent = dateSubmitted;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('conversationThread').innerHTML = conversationHTML;

            // Clear reply textarea
            document.getElementById('replyMessage').value = '';
        }

        // Function to send reply
        function sendReply() {
            const replyMessage = document.getElementById('replyMessage').value.trim();
            if (!replyMessage) {
                alert("Please enter a reply message.");
                return;
            }

            // Here you would typically send the reply to the server via AJAX
            // For this example, we'll just append it to the conversation thread
            const ticketID = document.getElementById('modalTicketID').textContent;
            const newMessageHTML = `<p><strong>You:</strong> ${replyMessage}</p>`;
            const conversationThread = document.getElementById('conversationThread');
            conversationThread.innerHTML += newMessageHTML;

            // Clear the reply textarea
            document.getElementById('replyMessage').value = '';
        }

        // Function to close ticket
        function closeTicket() {
            const ticketID = document.getElementById('modalTicketID').textContent;
            if (confirm(`Are you sure you want to close ticket ${ticketID}?`)) {
                // Here you would typically send a request to the server to close the ticket
                alert(`Ticket ${ticketID} has been closed.`);
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('
ticketModal'));
                modal.hide();

                // Optionally, you could also remove the ticket from the table
                const row = document.querySelector(`tr:has(td:contains('${ticketID}'))`);
                if (row) {
                    row.remove();
                }
            }
        }

        // Function to reset filters
        function resetFilters() {
            document.getElementById('filterStatus').value = '';
            document.getElementById('searchInput').value = '';
            // Optionally, you could reload the page or re-fetch tickets
            location.reload();
        }

        // Filter tickets by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            const status = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ticketsTable tbody tr');
            rows.forEach(row => {
                const badge = row.querySelector('.badge');
                if (status === '' || badge.textContent.toLowerCase() === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Search tickets by student name or ticket ID
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ticketsTable tbody tr');
            rows.forEach(row => {
                const studentName = row.cells[1].textContent.toLowerCase();
                const ticketID = row.cells[0].textContent.toLowerCase();
                if (studentName.includes(searchTerm) || ticketID.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
<?php                                 