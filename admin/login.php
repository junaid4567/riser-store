<?php
require_once __DIR__ . '/auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (loginAttemptsRemaining() <= 0) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            clearLoginThrottle();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        } else {
            registerFailedLogin();
            $error = 'Invalid username or password.';
        }
    }
}
$B = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login | RISER</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= assetUrl('/css/style.css') ?>">
<link rel="stylesheet" href="<?= $B ?>/admin/admin.css">
</head>
<body class="admin-auth-body">
  <div class="admin-login-card">
    <a href="<?= $B ?>/index.php" class="logo" style="display:block; text-align:center; margin-bottom:8px;">RI<span style="color:var(--riser-red)">S</span>ER</a>
    <p class="text-center text-mute" style="font-family:var(--font-mono); font-size:0.78rem; margin-bottom:30px;">ADMIN PANEL</p>

    <?php if ($error): ?>
      <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $B ?>/admin/login.php">
      <?= csrfField() ?>
      <div class="field" style="margin-bottom:18px;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="field" style="margin-bottom:24px;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn--full">Log In</button>
    </form>
    <p class="text-center text-mute" style="font-family:var(--font-mono); font-size:0.72rem; margin-top:24px;">
      <a href="<?= $B ?>/index.php">&larr; Back to store</a>
    </p>
  </div>
</body>
</html>
