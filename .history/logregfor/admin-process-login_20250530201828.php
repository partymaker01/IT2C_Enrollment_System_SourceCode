<?php

session_start();
include '../db.php';
echo "<pre>";
print_r($admin);
echo "</pre>";
exit;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $admin = $res->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            header("Location: ../ADMIN/admin-dashboard.php");
            exit();
        }
    }

    // Login failed
    echo "<script>alert('Invalid email or password'); window.location.href='admin-login.php';</script>";
}
?>