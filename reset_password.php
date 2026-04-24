<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if ($token === '') {
    redirect('login.php');
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Verify token (valid for 1 hour)
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR LIMIT 1");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        $error = 'Your reset link is invalid or has expired. Please request a new one.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($password !== '' && $password === $confirm) {
            // Password requirements check (optional, but good practice)
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Update user password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $resetRequest['email']]);

                // Delete the used token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$resetRequest['email']]);

                $success = true;
            }
        } else {
            $error = 'Passwords do not match.';
        }
    }
} catch (PDOException $e) {
    $error = 'Database error. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinIQ – Reset Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/login.css" />
  <style>
    .success-card {
      text-align: center;
      padding: 20px 0;
    }
    .success-card h2 { color: #059669; margin-bottom: 10px; }
    .success-card p { margin-bottom: 25px; color: #4b5563; }
  </style>
</head>
<body>

  <div class="bg-blob blob-1"></div>
  <div class="bg-blob blob-2"></div>
  <div class="bg-blob blob-3"></div>

  <div class="page-wrapper">

    <div class="logo-top">
      <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
      </svg>
      <span class="logo-text">Clin<span class="iq">IQ</span></span>
    </div>

    <div class="card">
      <?php if ($success): ?>
        <div class="success-card">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <h2>Password Reset!</h2>
          <p>Your password has been changed successfully. You can now use your new password to sign in.</p>
          <a href="login.php" class="btn-primary" style="text-decoration:none; display:inline-block;">Go to Login</a>
        </div>
      <?php else: ?>
        <div class="card-header">
          <h1>New Password</h1>
          <p>Please enter your new secure password below.</p>
        </div>

        <?php if ($error): ?>
        <div class="error-msg">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= e($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($resetRequest): ?>
        <form class="login-form" method="POST" action="reset_password.php?token=<?= e($token) ?>">
          <div class="field-group">
            <label for="password">New Password</label>
            <div class="input-wrap">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input type="password" id="password" name="password" placeholder="Min. 6 characters" required autofocus />
            </div>
          </div>

          <div class="field-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your new password" required />
            </div>
          </div>

          <button type="submit" class="btn-primary">Update Password</button>
        </form>
        <?php else: ?>
          <div style="text-align: center; margin-top: 20px;">
            <a href="forgot_password.php" class="btn-primary" style="text-decoration:none; display:inline-block;">Request New Link</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>
