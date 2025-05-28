<?php
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $token = bin2hex(random_bytes(16));
    $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $update = $conn->prepare("UPDATE users SET reset_token=?, token_expiry=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'youremail@gmail.com'; // Palitan ng email mo
            $mail->Password = 'your_app_password';   // Palitan ng app password mo
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('youremail@gmail.com', 'Enrollment System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body = "Hi,<br><br>Click the link below to reset your password:<br><a href='http://localhost/IT2C_Enrollment_System_SourceCode/reset-password.php?token=$token'>Reset Password</a><br><br>If you didn't request this, ignore this email.";

            $mail->send();
            echo "✅ Reset link sent to your email.";
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "❌ Email not found.";
    }
}
?>
