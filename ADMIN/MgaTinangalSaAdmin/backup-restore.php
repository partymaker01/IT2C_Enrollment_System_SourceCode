<?php
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <strong>Backup started!</strong> (Simulated, no real backup.)
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === 0) {
        $fileName = htmlspecialchars($_FILES['sql_file']['name']);
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        File <strong>$fileName</strong> uploaded successfully. (Simulated restore, no real data changed.)
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        Error uploading file. Please try again.
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Backup and Restore</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f9fafb;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 2rem 1rem;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .container {
      max-width: 600px;
      width: 100%;
      padding-top: 3rem;
    }
    h2 {
      color: #198754;
      font-weight: 700;
      margin-bottom: 2rem;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
      transition: box-shadow 0.3s ease;
    }
    .card:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    .card-body h4 {
      color: #2e7d32;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    p {
      color: #555;
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }
    .btn-success {
      background-color: #198754;
      border: none;
      font-weight: 600;
      width: 100%;
      padding: 0.6rem 0;
      transition: background-color 0.3s ease;
    }
    .btn-success:hover {
      background-color: #146c43;
    }
    .btn-danger {
      background-color: #dc3545;
      border: none;
      font-weight: 600;
      width: 100%;
      padding: 0.6rem 0;
      transition: background-color 0.3s ease;
    }
    .btn-danger:hover {
      background-color: #b02a37;
    }
    input[type="file"] {
      border-radius: 6px;
      padding: 0.35rem 0.5rem;
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-success mb-3">
      &larr; Back to Dashboard
    </a>

    <h2 class="text-center">
      Backup and Restore Database
    </h2>

    <?= $message ?>

    <div class="card">
      <div class="card-body">
        <h4><i class="bi bi-cloud-arrow-down-fill me-2"></i>
        Backup Database
      </h4>
        <p>
          Click the button below to download the latest backup of the database.
        </p>
        <form method="post" class="d-grid">
          <button type="submit" name="backup" class="btn btn-success">
            <i class="bi bi-download me-2"></i>
            Download Backup
          </button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h4><i class="bi bi-cloud-arrow-up-fill me-2"></i>
        Restore Database
      </h4>
        <p>
          Upload a<code>
            .sql
          </code>
          file to restore the database. This will overwrite current data.
        </p>
        <form method="post" enctype="multipart/form-data" class="d-grid" 
              onsubmit="return confirm('Are you sure you want to restore the database? This will overwrite current data.')">
          <input type="file" name="sql_file" accept=".sql" class="form-control mb-3" required />
          <button type="submit" name="restore" class="btn btn-danger">
            <i class="bi bi-upload me-2"></i>
            Restore Database
          </button>
        </form>
      </div>
    </div>
  </div>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
