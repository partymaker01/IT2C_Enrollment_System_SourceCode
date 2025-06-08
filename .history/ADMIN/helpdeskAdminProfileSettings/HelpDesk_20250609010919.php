<!-- <?php
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
                <div class="modal-body                       -->