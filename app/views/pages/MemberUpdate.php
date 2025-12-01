<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

$error = $error ?? null;
$success = $success ?? null;
$target = $target ?? null; // user to edit
if (!$target) {
    redirect('/admin/members');
    exit;
}

$is_self = isset($user['id']) && isset($target['id']) && ((int)$user['id'] === (int)$target['id']);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update User</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      min-height: 100vh;
      font-family: 'Segoe UI', sans-serif;
      background: #0f172a;
      color: #f3f4f6;
    }

    .member-form-shell {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: radial-gradient(circle at top, #1d3b74 0%, #0a1330 45%, #020617 100%);
      padding-bottom: 2rem;
    }

    .form-container {
      background: rgba(15,23,42,0.85);
      padding: 2.5rem;
      border-radius: 28px;
      width: 100%;
      max-width: 400px;
      margin: 2rem auto 0;
      box-shadow: 0 10px 30px rgba(2,6,23,0.5);
      border: 1px solid rgba(59,130,246,0.25);
      text-align: center;
      color: #f3f4f6;
    }

    h2 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 2rem;
      color: #3b82f6;
    }

    .error, .success {
      padding: 0.75rem 1rem;
      border-radius: 12px;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: left;
    }

    .error {
      background: rgba(239,68,68,0.12);
      color: #fecaca;
      border: 1px solid rgba(239,68,68,0.2);
    }

    .success {
      background: rgba(34,197,94,0.12);
      color: #86efac;
      border: 1px solid rgba(34,197,94,0.2);
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      text-align: left;
    }

    .form-group label {
      font-size: 0.9rem;
      font-weight: 600;
      color: #f3f4f6;
      margin-bottom: 0.4rem;
    }

    input,
    select {
      width: 100%;
      padding: 0.65rem 0.8rem;
      border-radius: 12px;
      border: 1px solid rgba(59,130,246,0.25);
      font-size: 1rem;
      outline: none;
      background: rgba(15,23,42,0.8);
      color: #f3f4f6;
      transition: border 0.3s, box-shadow 0.3s;
      box-sizing: border-box;
    }

    input::placeholder {
      color: #94a3b8;
      font-style: italic;
    }

    input:focus,
    select:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,0.25);
    }

    .btn {
      padding: 0.85rem;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .btn.primary {
      background: #2563eb;
      color: #fff;
      border: none;
    }

    .btn.primary:hover {
      filter: brightness(1.1);
    }

    .btn.secondary {
      background: #475569;
      color: #e2e8f0;
      margin-top: 0.8rem;
    }

    .btn.secondary:hover {
      background: #64748b;
      color: #f3f4f6;
      filter: brightness(1.1);
    }
  </style>
</head>
<body>

<div class="member-form-shell">
    <?php
    $user = $user ?? null;
    $unreadCount = $unreadCount ?? 0;
    $notifications = $notifications ?? [];
    include_once __DIR__ . '/../components/Header.php';
    ?>

    <section class="form-container">
      <h2>Update User</h2>

      <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" action="/admin/members/<?= (int)$target['id'] ?>/edit">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($target['username'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($target['email'] ?? '') ?>" placeholder="Enter email" required />
        </div>

        <div class="form-group">
          <label for="role">Role</label>
          <select id="role" name="role" <?= $is_self ? 'disabled' : 'required' ?>>
            <option value="user" <?= (($target['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= (($target['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          </select>
          <?php if ($is_self): ?>
            <small style="color: #94a3b8; font-size: 0.8rem;">You cannot change your own role.</small>
          <?php endif; ?>
        </div>

        <button class="btn primary" type="submit">Update User</button>
        <a class="btn secondary" href="/admin/members">Cancel</a>
      </form>
    </section>
</div>

</body>
</html>
