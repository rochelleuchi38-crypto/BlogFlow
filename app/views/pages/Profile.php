<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* --- Reset & Body --- */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: sans-serif;
}

html, body {
  width: 100%;
  min-height: 100%;
  background: #0f172a;
  color: #f3f4f6;
  line-height: 1.5;
}

/* --- Main Shell (same as Members page) --- */
.members-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* --- Page Layout --- */
.page-shell {
    padding: 2rem;
    width: 100%;
}

.page-card {
    background: rgba(15,23,42,0.85);
    padding: 2rem;
    border-radius: 24px;
    max-width: 700px;
    margin: 2rem auto;
    box-shadow: 0 20px 40px rgba(2,6,23,0.6);
    border: 1px solid rgba(59,130,246,0.15);
}

/* --- Profile Header --- */
.profile-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(148,163,184,0.2);
}

.profile-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg,#3b82f6,#6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  font-weight: 700;
  color: white;
}

.profile-info h2 {
  margin: 0;
  font-size: 1.8rem;
}

.profile-info p {
  margin: 0.35rem 0;
  color: #cbd5f5;
}

.role-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.9rem;
  border-radius: 999px;
  background: rgba(59,130,246,0.2);
  color: #93c5fd;
  font-weight: 600;
  font-size: 0.85rem;
}

/* --- Buttons --- */
.profile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  transition: all 0.2s;
}

.btn.primary {
  background: linear-gradient(135deg,#3b82f6,#6366f1);
  color: white;
}

.btn.primary:hover {
  opacity: 0.9;
}

.btn.ghost {
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(148,163,184,0.4);
  color: #cbd5f5;
}

.btn.ghost:hover {
  background: rgba(15,23,42,0.95);
}

/* Responsive */
@media(max-width:600px){
  .profile-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>
</head>

<body>

<div class="members-shell"> <!-- SAME wrapper as Members page -->

    <?php
    // Pass data to Header
    $user = $logged_in_user ?? null;
    $unreadCount = $unread_notifications ?? 0;
    $notifications = $_SESSION['notifications'] ?? [];
    include_once __DIR__ . '/../components/Header.php';
    ?>

    <div class="page-shell">

        <div class="page-card profile-card">

            <h1>Profile</h1>

            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                </div>

                <div class="profile-info">
                    <h2><?= htmlspecialchars($user['username'] ?? 'Unknown'); ?></h2>
                    <p><?= htmlspecialchars($user['email'] ?? ''); ?></p>
                    <span class="role-pill"><?= htmlspecialchars($user['role'] ?? 'User'); ?></span>
                </div>
            </div>

            <div class="profile-actions">
                <a href="/admin/members/<?= $user['id'] ?? 0; ?>/edit?self=1" class="btn primary">
                    <i class="fa fa-edit"></i> Edit Profile
                </a>
                <a href="/auth/logout" class="btn ghost" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>

        </div>

    </div>

</div>

</body>
</html>
