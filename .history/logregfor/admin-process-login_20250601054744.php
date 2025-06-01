<?php
session_start();

// Example credentials
$admin_email = "admin@example.com";
$admin_password = "admin123";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === $admin_email && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: ../ADMIN/admin-dashboard.php");
        exit();
    } else {
        header("Location: admin-login.php?error=1");
        exit();
    }
}
?>
