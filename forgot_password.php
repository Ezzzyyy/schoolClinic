<?php
declare(strict_types=1);
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$message = '';
$error = '';
$simulationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        try {
            $db = new Database();
            $conn = $db->connect();

            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                
                // Save to password_resets (delete old ones first for this email)
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);
                
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
                $stmt->execute([$email, $token]);

                // Simulation: Build link
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                // Assuming the root is /school-clinic-main/
                $simulationLink = "$protocol://$host/school-clinic-main/reset_password.php?token=$token";
                
                $message = 'Password reset instructions ready.';
            } else {
                // Standard security practice: don't reveal if email exists, 
                // but we'll show a generic success message.
                $message = 'If that email is in our system, you will receive a reset link.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    } else {
        $error = 'Please enter your email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinIQ – Forgot Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/login.css" />
  <style>
    .simulation-box {
      margin-top: 20px;
      padding: 15px;
      background: #fdf2f2;
      border: 1px dashed #ef4444;
      border-radius: 8px;
      font-size: 0.85rem;
    }
    .simulation-box strong { color: #b91c1c; }
    .simulation-link {
      display: block;
      margin-top: 8px;
      word-break: break-all;
      color: #3b82f6;
      text-decoration: underline;
    }
    .success-msg {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px;
      background: #ecfdf5;
      color: #065f46;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
    }
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
      <div class="card-header">
        <h1>Forgot Password?</h1>
        <p>No worries, we'll help you reset it.</p>
      </div>

      <?php if ($message): ?>
      <div class="success-msg">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= e($message) ?>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="error-msg">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <?php if (!$simulationLink): ?>
      <form class="login-form" method="POST" action="forgot_password.php">
        <div class="field-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            <input type="email" id="email" name="email" placeholder="Enter your registered email" required autofocus />
          </div>
        </div>

        <button type="submit" class="btn-primary">Send Reset Link</button>
        
        <div style="text-align: center; margin-top: 20px;">
          <a href="login.php" class="forgot-link">Back to Login</a>
        </div>
      </form>
      <?php else: ?>
        <div class="simulation-box">
          <strong>DEBUG: Simulation Mode</strong><br>
          An email was "sent" to your address. Click the link below to continue the test:
          <a href="<?= $simulationLink ?>" class="simulation-link"><?= $simulationLink ?></a>
        </div>
        <div style="text-align: center; margin-top: 30px;">
          <a href="login.php" class="btn-primary" style="text-decoration:none; display:inline-block;">Return to Login</a>
        </div>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>
