<?php
// Load fonts used in posts (same logic from your dashboard)
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
<title>Login - BlogFlow</title>
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

/* --- Original Login Page Styling --- */
.auth-shell {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at top, #1d3b74 0%, #0a1330 45%, #020617 100%);
  padding: 2rem;
}

.auth-card {
  width: min(420px, 100%);
  background: rgba(6, 12, 30, 0.9);
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
  font-size: 0.95rem;
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
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0 0.5rem;
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

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    if (input.type === "password") {
        input.type = "text";
        icon.className = "fa fa-eye-slash";
    } else {
        input.type = "password";
        icon.className = "fa fa-eye";
    }
}
</script>

</head>
<body>

<div class="auth-shell">
  <div class="auth-card">

    <div class="auth-brand">
      <div class="logo-circle">BF</div>
      <div>
        <h1>BlogFlow</h1>
        <p>Sign in to continue</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <form action="/" method="POST" class="auth-form">

      <label class="input-label">Username or Email</label>
      <div class="input-field">
        <i class="fa fa-user"></i>
        <input 
          type="text" 
          name="username"
          placeholder="Enter username or email"
          required
        />
      </div>

      <label class="input-label">Password</label>
      <div class="input-field">
        <i class="fa fa-lock"></i>
        <input
          id="password"
          type="password"
          name="password"
          placeholder="Enter password"
          required
        />
        <button type="button" class="eye-btn" onclick="togglePassword()">
          <i id="eyeIcon" class="fa fa-eye"></i>
        </button>
      </div>

      <button type="submit" class="primary-btn">
        Login
      </button>
    </form>

    <p class="switch-text">
      Don't have an account?
      <a href="/auth/register">Create one</a>
    </p>

  </div>
</div>

</body>
</html>
