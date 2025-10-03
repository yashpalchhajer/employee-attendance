<?php
require_once 'config.php';

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($token === '' || $password === '') {
        $message = "All fields are required.";
    } else {
        
        $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user && strtotime($user['reset_expires']) > time()) {
        
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->execute([$hash, $user['id']]);
            $message = "Password updated successfully. <a href='login.php'>Login</a>";
        } else {
            $message = "Invalid or expired token.";
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
<div class="card p-4" style="max-width:420px;width:100%">
  <h4 class="mb-3 text-center">Reset Password</h4>
  <?php if($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
  <?php endif; ?>

  <?php if (empty($message) || strpos($message, 'updated successfully') === false): ?>
    <form method="post">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Reset Password</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
