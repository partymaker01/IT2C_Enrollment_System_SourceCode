<?php
// verify.php
include 'db.php';

$code = $_GET['code'] ?? '';
$status = '';
$message = '';
$redirect = 'logregfor/login.php';

if (!$code) {
    $status = 'error';
    $message = 'Invalid verification link.';
} else {
    $stmt = $conn->prepare("SELECT id, is_verified FROM students WHERE verification_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['is_verified']) {
            $status = 'info';
            $message = 'Your email is already verified.';
        } else {
            $update = $conn->prepare("UPDATE students SET is_verified = 1, verification_code = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            if ($update->execute()) {
                $status = 'success';
                $message = 'Your email has been successfully verified!';
            } else {
                $status = 'error';
                $message = 'Failed to verify your account. Please try again.';
            }
        }
    } else {
        $status = 'error';
        $message = 'Invalid or expired verification code.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    Swal.fire({
        icon: '<?= $status ?>',
        title: <?= json_encode($message) ?>,
        confirmButtonText: 'Go to Login',
    }).then(() => {
        window.location.href = '<?= $redirect ?>';
    });
</script>
</body>
</html>
