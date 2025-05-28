<?php

$errors = [];
$successMessage = "";
$studentId = "";
$fullName = "";
$program = "";
$yearLevel = "";
$contactNumber = "";
$emailAddress = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = trim($_POST['studentId'] ?? '');
    $fullName = trim($_POST['fullName'] ?? '');
    $program = $_POST['program'] ?? '';
    $yearLevel = $_POST['yearLevel'] ?? '';
    $contactNumber = trim($_POST['contactNumber'] ?? '');
    $emailAddress = trim($_POST['emailAddress'] ?? '');

    if (strlen($studentId) < 5 || strlen($studentId) > 20) {
        $errors[] = "Student ID must be between 5 and 20 characters.";
    }

    if (strlen($fullName) < 3) {
        $errors[] = "Full Name must be at least 3 characters.";
    }

    $validPrograms = ['IT', 'HRMT', 'ECT', 'HST'];
    if (!in_array($program, $validPrograms)) {
        $errors[] = "Please select a valid program.";
    }

    $validYearLevels = ['1st Year', '2nd Year', '3rd Year'];
    if (!in_array($yearLevel, $validYearLevels)) {
        $errors[] = "Please select a valid year level.";
    }

    if (!preg_match('/^\d{11}$/', $contactNumber)) {
        $errors[] = "Contact Number must be exactly 11 digits.";
    }

    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        $successMessage = "Student successfully added (simulation only).";
        $studentId = $fullName = $program = $yearLevel = $contactNumber = $emailAddress = "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add New Student - Enrollment System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f4fdf4;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 2rem 0;
    }
    .form-container {
      background: #fff;
      padding: 2.5rem 2rem;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: auto;
      width: 100%;
    }
    h2 {
      color: #2e7d32;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    .form-label {
      font-weight: 600;
    }
    input:focus, select:focus {
      border-color: #2e7d32;
      box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
      transition: all 0.3s ease-in-out;
    }
    .btn-success {
      background-color: #2e7d32;
      border-color: #2e7d32;
      font-weight: 600;
      font-size: 1.1rem;
      padding: 0.65rem;
      transition: background-color 0.3s ease;
    }
    .btn-success:hover, .btn-success:focus {
      background-color: #246426;
      border-color: #246426;
      box-shadow: none;
    }
    .form-text {
      font-size: 0.85rem;
      color: #6c757d;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="form-container shadow-sm">

    <a href="/IT2C_Enrollment_System_SourceCode/ADMIN/admin-dashboard.php" class="btn btn-outline-secondary mb-3">
      back to dashboard
    </a>

    <h2>
      Add New Student
    </h2>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
      <?php endif; ?>

      <form method="post" id="addStudentForm" novalidate>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label for="studentId" class="form-label">
              Student ID
            </label>
            <input
              type="text"
              class="form-control"
              id="studentId"
              name="studentId"
              placeholder="e.g. 20250003"
              required
              minlength="5"
              maxlength="20"
              value="<?= htmlspecialchars($studentId) ?>"
            />
          </div>

          <div class="col-12 col-md-6">
            <label for="fullName" class="form-label">
              Full Name
            </label>
            <input
              type="text"
              class="form-control"
              id="fullName"
              name="fullName"
              placeholder="Jerick Dela Cruz Reyes"
              required
              minlength="3"
              value="<?= htmlspecialchars($fullName) ?>"
            />
          </div>

          <div class="col-12 col-md-6">
            <label for="program" class="form-label">
              Program
            </label>
            <select class="form-select" id="program" name="program" required>
              <option value="" disabled <?= $program === '' ? 'selected' : '' ?>>
                Select Program
                /option>
              <option value="IT" <?= $program === 'IT' ? 'selected' : '' ?>>
                IT
              </option>
              <option value="HRMT" <?= $program === 'HRMT' ? 'selected' : '' ?>>
                HRMT
              </option>
              <option value="ECT" <?= $program === 'ECT' ? 'selected' : '' ?>>
                ECT
              </option>
              <option value="HST" <?= $program === 'HST' ? 'selected' : '' ?>>
                HST
              </option>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="yearLevel" class="form-label">
              Year Level
            </label>
            <select class="form-select" id="yearLevel" name="yearLevel" required>
              <option value="" disabled <?= $yearLevel === '' ? 'selected' : '' ?>>
                Select Year Level
              </option>
              <option value="1st Year" <?= $yearLevel === '1st Year' ? 'selected' : '' ?>>
                1st Year
              </option>
              <option value="2nd Year" <?= $yearLevel === '2nd Year' ? 'selected' : '' ?>>
                2nd Year
              </option>
              <option value="3rd Year" <?= $yearLevel === '3rd Year' ? 'selected' : '' ?>>
                3rd Year
              </option>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="contactNumber" class="form-label">
              Contact Number
            </label>
            <input
              type="tel"
              class="form-control"
              id="contactNumber"
              name="contactNumber"
              placeholder="09171234567"
              pattern="^\d{11}$"
              required
              value="<?= htmlspecialchars($contactNumber) ?>"
            />
            <div class="form-text">
              Enter 11-digit mobile number.
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label for="emailAddress" class="form-label">
              Email Address
            </label>
            <input
              type="email"
              class="form-control"
              id="emailAddress"
              name="emailAddress"
              placeholder="jerick@email.com"
              required
              value="<?= htmlspecialchars($emailAddress) ?>"
            />
          </div>
        </div>

        <button type="submit" class="btn btn-success w-100 mt-4">
          Add Student
        </button>
      </form>
    </div>
  </div>
</body>
</html>
