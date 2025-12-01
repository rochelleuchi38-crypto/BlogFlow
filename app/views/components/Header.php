<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Props: $user (array), $unreadCount (int), $notifications (array)
// Fallback to session if not provided
$user ??= $_SESSION['logged_in_user'] ?? null;
$unreadCount ??= $_SESSION['unread_count'] ?? 0;
$totalNotificationsCount ??= $_SESSION['total_notifications_count'] ?? 0;
$notifications ??= $_SESSION['notifications'] ?? [];
?>

<header class="header">
  <!-- Brand -->
  <div class="brand">
    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="brand-avatar" />
    <div class="brand-text">
      <div class="brand-title"><?= htmlspecialchars($user['username'] ?? 'Guest') ?></div>
      <div class="brand-sub">Green Community</div>
    </div>
  </div>

  <!-- Center Search -->
  <div class="center-controls">
    <div class="search">
      <i class="fa fa-search search-icon" onclick="doSearch()" title="Search"></i>
      <input type="text" id="searchInput" placeholder="Search posts, people..." 
             onkeypress="if(event.key==='Enter') doSearch()" />
      <i class="fa fa-times clear-icon" id="clearSearch" onclick="clearSearch()" title="Clear search"></i>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="nav">
    <a href="/users/user-page" class="nav-link">Home</a>
    <a href="/categories" class="nav-link">Categories</a>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
      <a href="/admin/members" class="nav-link">Member</a>
    <?php endif; ?>

    <!-- Notifications -->
    <?php if ($_SERVER['REQUEST_URI'] !== '/notifications'): ?>
    <div class="notification-container">
      <button class="bell" onclick="toggleDropdown()" aria-label="Notifications">
        <i class="fa fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
          <span class="badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
        <?php endif; ?>
      </button>

      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
          <span>Notifications</span>
          <a href="/notifications" class="view-all">View All</a>
        </div>
        <div class="notif-body">
          <?php if (empty($notifications)): ?>
            <div class="empty">No notifications</div>
          <?php else: ?>

<?php foreach ($notifications as $n): ?>
<div class="notif-item <?= ($n['is_read'] ?? 1) == 0 ? 'unread' : '' ?>" onclick="handleNotificationClick(<?= $n['notification_id'] ?>, <?= $n['post_id'] ?? 'null' ?>, <?= $n['comment_id'] ?? 'null' ?>, <?= $n['reply_id'] ?? 'null' ?>)">
    <div class="msg">
        <strong><?= htmlspecialchars($n['actor_username'] ?? 'Someone') ?></strong>
        <?= htmlspecialchars($n['message'] ?? '') ?>
    </div>
    <div class="ts"><?= htmlspecialchars($n['created_at'] ?? '') ?></div>
</div>
<?php endforeach; ?>

          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Profile Link -->
    <a href="/users/profile" class="nav-link profile-link">
      <i class="fa fa-user-circle"></i>
    </a>
  </nav>
</header>

<script>
const dropdown = document.getElementById('notifDropdown');
const searchInput = document.getElementById('searchInput');
const clearBtn = document.getElementById('clearSearch');

function toggleDropdown() {
  dropdown.classList.toggle('open');
}


// Simple redirect search
function doSearch() {
  const query = searchInput.value.trim();
  window.location.href = query 
      ? `/users/user-page?search=${encodeURIComponent(query)}`
      : `/users/user-page`;
}

function clearSearch() {
  searchInput.value = '';
  searchInput.focus();
}

searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') doSearch();
});
clearBtn.addEventListener('click', clearSearch);

// Close dropdown when clicking outside
window.addEventListener('click', (e) => {
  if (!e.target.closest('.notification-container')) {
    dropdown.classList.remove('open');
  }
});

async function handleNotificationClick(notificationId, postId, commentId, replyId) {
    // Mark as read via AJAX
    try {
        const response = await fetch('/notifications/mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        });

        if (!response.ok) {
            throw new Error('Failed to mark notification as read');
        }

        // Update the notification count
        const badge = document.querySelector('.badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
                badge.textContent = currentCount - 1;
            } else {
                badge.remove();
            }
        }
        
        // Remove unread class
        const notifItem = document.querySelector(`[onclick*="${notificationId}"]`);
        if (notifItem) {
            notifItem.classList.remove('unread');
        }

        // Navigate to the appropriate URL
        let url = '/users/user-page';
        if (postId != null) {
            url += `?post_id=${postId}`;
            if (replyId && replyId !== 'null') {
                url += `&comment=${commentId}&reply=${replyId}#reply-${replyId}`;
            } else if (commentId && commentId !== 'null') {
                url += `&comment=${commentId}#comment-${commentId}`;
            }
        } else {
            // If no post ID, go to notifications page
            url = '/notifications';
        }
        window.location.href = url;

    } catch (error) {
        console.error('Error marking notification as read:', error);
        // Still navigate even if marking as read fails
        let url = '/users/user-page';
        if (postId && postId !== 0) {
            url += `?post_id=${postId}`;
            if (replyId && replyId !== 'null') {
                url += `&comment=${commentId}&reply=${replyId}#reply-${replyId}`;
            } else if (commentId && commentId !== 'null') {
                url += `&comment=${commentId}#comment-${commentId}`;
            }
        } else {
            url = '/notifications';
        }
        window.location.href = url;
    }
}
</script>

<style>
/* --- Header styles remain unchanged --- */
.header { position: sticky; top:0; z-index:200; width:100%; display:flex; align-items:center; justify-content:space-between; gap:1rem; height:80px; padding:0 2rem; background:rgba(8,15,35,0.96); border-bottom:1px solid rgba(59,130,246,0.25); box-shadow:0 20px 40px rgba(2,6,23,0.75); backdrop-filter:blur(14px); box-sizing:border-box; }
.brand { display:flex; align-items:center; gap:0.85rem; }
.brand-avatar { width:44px; height:44px; border-radius:50%; border:2px solid rgba(96,165,250,0.4); background:#0f172a; padding:4px; }
.brand-title { font-weight:700; font-size:1rem; }
.brand-sub { font-size:0.78rem; color:rgba(148,163,184,0.85); }
.center-controls { flex:1; display:flex; justify-content:center; }
.search { background: rgba(15,23,42,0.8); border-radius:22px; padding:6px 10px; display:flex; align-items:center; width:60%; max-width:520px; border:1px solid rgba(59,130,246,0.25); position:relative; }
.search input { border:none; outline:none; background:transparent; margin-left:8px; width:100%; color:#e2e8f0; }
.search-icon, .clear-icon { cursor:pointer; }
.clear-icon { margin-left:8px; color:#f43f5e; }
.nav { display:flex; align-items:center; gap:0.75rem; }
.nav-link { color:#e2e8f0; text-decoration:none; font-weight:600; padding:6px 10px; border-radius:8px; transition:background 0.2s; }
.notification-container { position:relative; }
.bell { background:transparent; border:none; color:#e2e8f0; font-size:1.1rem; position:relative; cursor:pointer; }
.badge { position:absolute; top:-8px; right:-8px; background:#f43f5e; color:#fff; border-radius:50%; padding:3px 6px; font-size:11px; box-shadow:0 6px 12px rgba(0,0,0,0.2); }
.notif-dropdown { position:absolute; right:0; top:calc(100% + 10px); width:360px; background:#0f172a; color:#e2e8f0; border-radius:12px; border:1px solid rgba(59,130,246,0.3); box-shadow:0 20px 45px rgba(2,6,23,0.6); display:none; overflow:hidden; z-index:30; }
.notif-dropdown.open { display:block; }
.notif-header { display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:rgba(13,26,54,0.85); font-weight:700; }
.notif-body { max-height:320px; overflow:auto; }
.notif-item { padding:12px 14px; border-bottom:1px solid rgba(148,163,184,0.2); cursor:pointer; }
.notif-item.unread { background:rgba(59,130,246,0.15); }
.empty { padding:20px; text-align:center; color:#94a3b8; }
.profile-link { font-size:1.1rem; color:#e2e8f0; }
.view-all { font-size:0.85rem; color:#60a5fa; text-decoration:none; font-weight:600; }
@media (max-width: 900px) {
  .header { flex-wrap:wrap; height:auto; padding:1rem; }
  .center-controls { order:3; width:100%; }
  .search { width:100%; }
}
</style>
