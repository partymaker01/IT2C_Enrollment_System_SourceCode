<?php
// DB Connection parameters
$host = "localhost";
$dbname = "enrollment_system";
$username = "root"; // change as per your config
$password = "";     // change as per your config

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// Initialize variables
$profileData = [
    "id" => 1,
    "name" => "",
    "email" => "",
    "image" => "img/default_admin.png"
];

// Fetch admin profile from DB (id=1 assumed)
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([1]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $profileData = $admin;
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['adminName']);
    $email = trim($_POST['adminEmail']);
    
    // Handle profile picture upload
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = 'img/admin_profile_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profilePic']['tmp_name'], $filename)) {
                $profileData['image'] = $filename;
            }
        }
    }

    // Update database
    $sql = "UPDATE admins SET name = ?, email = ?, image = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $profileData['image'], $profileData['id']]);

    $success = true;

    // Reload updated data
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([1]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
      /* keep your existing styles here */
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
<div class="d-flex justify-content-center align-items-center p-4" style="min-height: calc(100vh - 80px);">
  <div class="profile-card text-center shadow-sm">
    <h2 class="text-success mb-4">Admin Profile</h2>
    <label for="profilePic" tabindex="0" aria-label="Change profile picture">
      <img src="<?= htmlspecialchars($profileData['image']) ?>" alt="Profile Picture" class="profile-img" id="profileImage" title="Click to change picture" />
    </label>

    <?php if ($success): ?>
      <div class="alert alert-success">Profile updated successfully!</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="text-start mt-3" novalidate>
      <div class="mb-3">
        <label for="adminName" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="adminName" name="adminName" value="<?= htmlspecialchars($profileData['name']) ?>" required autocomplete="name" />
      </div>
      <div class="mb-3">
        <label for="adminEmail" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="adminEmail" name="adminEmail" value="<?= htmlspecialchars($profileData['email']) ?>" required autocomplete="email" />
      </div>
      <div class="mb-4">
        <input class="form-control d-none" type="file" name="profilePic" id="profilePic" accept="image/*" />
      </div>
      <button type="submit" class="btn btn-success w-100">Save Changes</button>
    </form>
  </div>
</div>

<script>
  const profilePicInput = document.getElementById('profilePic');
  const profileImage = document.getElementById('profileImage');

  profileImage.addEventListener('click', () => profilePicInput.click());
  profileImage.addEventListener('keypress', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      profilePicInput.click();
    }
  });

  profilePicInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        profileImage.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>
</body>
</html>
