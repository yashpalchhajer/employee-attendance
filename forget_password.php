<?php
require_once 'config.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); 

    if ($identifier === '') {
        $message = "Please enter your username or email.";
    } else {
        
        $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && !empty($user['email'])) {
        
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

        
            $resetLink = BASE_URL . "/reset_password.php?token=" . urlencode($token);

        
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = 'tls';
                $mail->Port = MAIL_PORT;

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($user['email'], $user['full_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hi " . htmlspecialchars($user['full_name']) . ",<br><br>"
                    . "Click the link below to reset your password (valid 1 hour):<br>"
                    . "<a href='" . $resetLink . "'>" . $resetLink . "</a><br><br>"
                    . "If you didn't request this, ignore this email.";

                $mail->send();
                $message = "If an account exists, a reset link has been sent to the registered email.";
            } catch (Exception $e) {
                $message = "Mailer Error: " . $mail->ErrorInfo;
            }
        } else {
            $message = "If an account exists, a reset link has been sent to the registered email.";
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
<div class="card p-4" style="max-width:420px;width:100%">
  <h4 class="mb-3 text-center">Forgot Password</h4>
  <?php if($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Username or Email</label>
      <input type="text" name="identifier" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
    <div class="text-center mt-3"><a href="login.php">Back to Login</a></div>
  </form>
</div>
</body>
</html>
