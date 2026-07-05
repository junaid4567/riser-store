<?php
/**
 * RISER — One-time Setup Script
 * Run this once after importing database.sql to set a working admin password
 * (the password_hash placeholder in database.sql is not a real bcrypt hash).
 *
 * Visit /setup.php in your browser once, then DELETE this file — it is
 * blocked automatically once an admin account exists, but leaving unused
 * files reachable in production is still a needless risk.
 */

require_once __DIR__ . '/includes/functions.php';

// Security gate: once at least one admin account exists, this script
// refuses to run unless the visitor is already a logged-in admin.
// Without this, anyone who finds /setup.php on a live site could silently
// overwrite the admin password and take over the store.
$adminExists = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn() > 0;
$isLoggedInAdmin = !empty($_SESSION['admin_id']);
$locked = $adminExists && !$isLoggedInAdmin;

$message = '';
$done = false;

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Please reload this page and try again.';
    } else {
        $username = trim($_POST['username'] ?? 'admin');
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
                $stmt->execute([$hash, $username]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
            }

            $done = true;
            $message = "Admin account ready! Username: $username — you can now log in at /admin/login.php. Delete setup.php now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<title>RISER Setup</title>
<link rel="stylesheet" href="<?= assetUrl('/css/style.css') ?>">
<style>
  body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--ink); }
  .box { background:var(--paper); padding:40px; max-width:420px; width:100%; box-shadow:8px 8px 0 var(--riser-red); }
  .field { margin-bottom:18px; display:flex; flex-direction:column; gap:8px; }
  .field label { font-family:var(--font-mono); font-size:0.74rem; text-transform:uppercase; color:var(--mute); }
  .field input { border:2px solid var(--line-dark); padding:12px; font-size:0.95rem; }
</style>
</head>
<body>
  <div class="box">
    <h2 style="margin-bottom:8px;">RISER Setup</h2>

    <?php if ($locked): ?>
      <p class="text-mute" style="font-size:0.85rem; margin-bottom:24px;">
        An admin account already exists. For security, this script is locked unless you're logged in as an admin.
      </p>
      <div class="alert alert--error">Setup is locked. Log in to the admin panel, or delete this file from the server.</div>
      <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn--full" style="margin-top:16px;">Go to Admin Login</a>
    <?php else: ?>
      <p class="text-mute" style="font-size:0.85rem; margin-bottom:24px;">Create your admin login. Delete this file after use.</p>

      <?php if ($message): ?>
        <div class="alert <?= $done ? 'alert--success' : 'alert--error' ?>"><?= e($message) ?></div>
      <?php endif; ?>

      <?php if (!$done): ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" value="admin" required>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" required minlength="8">
        </div>
        <button type="submit" class="btn btn--full">Create Admin Account</button>
      </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn--full">Go to Admin Login</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
