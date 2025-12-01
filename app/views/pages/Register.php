<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Capture session messages
$error = $_SESSION['register_error'] ?? '';
$success = $_SESSION['register_success'] ?? '';

// Unset after capturing to avoid losing them
if (isset($_SESSION['register_error'])) unset($_SESSION['register_error']);
if (isset($_SESSION['register_success'])) unset($_SESSION['register_success']);

// Load fonts used in posts (like Dashboard)
$fonts = [];
if (!empty($posts)) {
    foreach ($posts as $post) {
        if (!empty($post['font_family'])) {
            $fonts[] = $post['font_family'];
        }
    }
}
$fonts = array_unique($fonts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - BlogFlow</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Load Google Fonts dynamically -->
<?php foreach ($fonts as $font): 
    $fontName = trim(explode(',', $font)[0]);
    $fontNameForLink = str_replace(' ', '+', $fontName);
?>
<link href="https://fonts.googleapis.com/css2?family=<?= $fontNameForLink ?>&display=swap" rel="stylesheet">
<?php endforeach; ?>

<style>
/* Apply dynamic font globally */
body, input, button, h1, p, label {
  font-family: 
    <?php foreach ($fonts as $font): 
        $main = trim(explode(',', $font)[0]);
        echo "'{$main}', ";
    endforeach; ?>
    'Inter', 'Poppins', sans-serif;
}

/* --- Original Register Page Styling --- */
.auth-shell {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at top, #1d3b74 0%, #0a1330 45%, #020617 100%);
  padding: 2rem;
}

.auth-card {
  width: min(460px, 100%);
  background: rgba(6, 12, 30, 0.92);
  border-radius: 28px;
  padding: 2.5rem;
  box-shadow: 0 30px 60px rgba(2, 6, 23, 0.65);
  border: 1px solid rgba(59,130,246,0.25);
  color: #e2e8f0;
}

.auth-brand {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1.5rem;
}

.logo-circle {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: linear-gradient(135deg,#3b82f6,#6366f1);
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  letter-spacing:0.08em;
}

.alert {
  padding: 0.85rem 1rem;
  border-radius: 12px;
  margin-bottom: 1rem;
}

.alert.error {
  background: rgba(239,68,68,0.15);
  color: #fecaca;
}

.alert.success {
  background: rgba(34,197,94,0.15);
  color: #bbf7d0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.input-label {
  font-size: 0.9rem;
  color: #cbd5f5;
}

.input-field {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(59,130,246,0.25);
  border-radius: 14px;
  padding: 0.1rem 0.75rem;
}

.input-field input {
  width: 100%;
  border: none;
  background: transparent;
  color: #f8fafc;
  padding: 0.85rem;
  font-size: 1rem;
  outline: none;
}

.input-field i {
  color: #94a3b8;
}

.eye-btn {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
}

.primary-btn {
  margin-top: 0.5rem;
  border: none;
  border-radius: 16px;
  padding: 0.9rem;
  background: linear-gradient(135deg,#3b82f6,#6366f1);
  color: white;
  font-weight: 600;
  cursor: pointer;
  position: relative;
  z-index: 1;
}

.primary-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.switch-text {
  margin-top: 1.5rem;
  text-align: center;
  color: #94a3b8;
}

.switch-text a {
  color: #93c5fd;
}
</style>
</head>
<body>

<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="logo-circle">BF</div>
      <div>
        <h1>Join BlogFlow</h1>
        <p>Create your account</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="/auth/register" class="auth-form">
      <label class="input-label">Username</label>
      <div class="input-field">
        <i class="fa fa-user"></i>
        <input name="username" type="text" placeholder="Enter username" required />
      </div>

      <label class="input-label">Email</label>
      <div class="input-field">
        <i class="fa fa-envelope"></i>
        <input name="email" type="email" placeholder="Enter email" required />
      </div>

      <label class="input-label">Password</label>
      <div class="input-field">
        <i class="fa fa-lock"></i>
        <input name="password" type="password" placeholder="Choose a password" required />
        <button type="button" class="eye-btn" onclick="togglePassword(this)">
          <i class="fa fa-eye"></i>
        </button>
      </div>

      <button type="submit" class="primary-btn">Create account</button>
    </form>

    <p class="switch-text">
      Already have an account?
      <a href="/">Login</a>
    </p>
  </div>
</div>

<script>
function togglePassword(button) {
  const input = button.previousElementSibling;
  if (input.type === 'password') {
    input.type = 'text';
    button.innerHTML = '<i class="fa fa-eye-slash"></i>';
  } else {
    input.type = 'password';
    button.innerHTML = '<i class="fa fa-eye"></i>';
  }
}
</script>

</body>
</html>