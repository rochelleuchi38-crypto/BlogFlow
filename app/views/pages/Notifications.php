<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Variables expected: $user, $notifications (array), $unreadCount (int)
// Fallback to session if not provided
$user ??= $_SESSION['logged_in_user'] ?? null;
$unreadCount ??= $_SESSION['unread_count'] ?? 0;
$notifications ??= $_SESSION['notifications'] ?? [];
?>

<?php include_once __DIR__ . '/../components/Header.php'; ?>

<?php
function formatTimeAgo($dateString) {
    if (!$dateString) return '';
    try {
        $date = new DateTime($dateString, new DateTimeZone('Asia/Manila'));
        $now  = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $diff = $now->getTimestamp() - $date->getTimestamp();

        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return $date->format('M j, Y');
    } catch (Exception $e) {
        return $dateString;
    }
}
?>

<section class="page-shell">
  <div class="page-card notifications-card">
    <div class="notifications-header">
      <div>
        <h1>Notifications</h1>
        <p>Stay in sync with what's happening.</p>
      </div>

      <?php if ($unreadCount > 0): ?>
      <form action="/notifications/mark_read" method="POST" id="markAllReadForm" style="margin:0;">
        <input type="hidden" name="mark_all" value="1">
        <button type="submit" class="btn-pill mark-all-read">Mark all as read</button>
      </form>
      <?php endif; ?>
    </div>

    <div class="notifications-list">
      <?php if (empty($notifications)): ?>
        <div class="no-notifications">No notifications yet.</div>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="notification-item <?= ($notif['is_read'] ?? 1) == 0 ? 'unread' : '' ?>">
          <div class="notification-content"
               onclick="goToNotif(<?= $notif['post_id'] ?? 'null' ?>, <?= $notif['comment_id'] ?? 'null' ?>, <?= $notif['reply_id'] ?? 'null' ?>, <?= $notif['notification_id'] ?? 'null' ?>, '<?= $notif['type'] ?>')">
            <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
            <div class="notification-time"><?= formatTimeAgo($notif['created_at']) ?></div>
          </div>

          <?php if (($notif['is_read'] ?? 1) == 0): ?>
          <button type="button" 
                  class="btn-pill mark-read-btn" 
                  onclick="markAsRead(event, <?= $notif['notification_id'] ?>)">
            Mark as read
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
// Handle notification click
function goToNotif(postId, commentId, replyId, notificationId, type) {
  // Mark as read if needed
  if (notificationId) {
    markAsRead(null, notificationId, false);
  }

  // Navigate to the relevant content
  let url = '/users/user-page';
  if (postId && postId !== 'null') {
    if (type === 'like') {
      // For likes, show all posts and scroll to the specific post
      url += `#post-${postId}`;
    } else {
      // For comments and replies, filter to the specific post
      url += `?post_id=${postId}`;
      if (replyId && replyId !== 'null') {
        url += `&comment=${commentId}&reply=${replyId}#reply-${replyId}`;
      } else if (commentId && commentId !== 'null') {
        url += `&comment=${commentId}#comment-${commentId}`;
      }
    }
  } else {
    // If no post ID, go to notifications page
    url = '/notifications';
  }
  window.location.href = url;
}

// Mark notification as read
function markAsRead(event, notificationId, stopPropagation = true) {
  if (stopPropagation && event) {
    event.stopPropagation();
  }
  
  fetch('/notifications/mark_read', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'notification_id=' + notificationId
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update UI
      const notificationItem = event ? event.target.closest('.notification-item') : null;
      if (notificationItem) {
        notificationItem.classList.remove('unread');
        const markReadBtn = notificationItem.querySelector('.mark-read-btn');
        if (markReadBtn) markReadBtn.remove();
        
        // Update unread count in header
        const badge = document.querySelector('.bell .badge');
        if (badge) {
          const count = parseInt(badge.textContent) - 1;
          if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
          } else {
            badge.remove();
          }
        }
      }
    }
  })
  .catch(error => console.error('Error marking notification as read:', error));
}

// Handle mark all as read form submission
document.getElementById('markAllReadForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  
  fetch('/notifications/mark_read', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'mark_all=1'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update UI
      document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.classList.remove('unread');
        const markReadBtn = item.querySelector('.mark-read-btn');
        if (markReadBtn) markReadBtn.remove();
      });
      
      // Remove all badges
      const badges = document.querySelectorAll('.bell .badge');
      badges.forEach(badge => badge.remove());
      
      // Hide the mark all button
      this.remove();
    }
  })
  .catch(error => console.error('Error marking all notifications as read:', error));
});
</script>

<style>
/* --- Base Font & Background --- */
body {
    font-family: 'Segoe UI', sans-serif;
    background: #0f172a;
    color: #f3f4f6;
    margin:0;
    padding:0;
}

/* Hide notification dropdown on notifications page */
.notif-dropdown {
    display: none !important;
}

/* --- Page Shell --- */
.page-shell {
    padding: 2rem;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}
/* --- Page Shell --- */
.page-shell {
    padding: 2rem;
    min-height: calc(100vh - 60px); /* leave space for header */
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

/* --- Card --- */
.page-card.notifications-card {
    width: 100%;
    max-width: 900px;
    background: rgba(15, 23, 42, 0.85);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(2,6,23,0.5);
    border:1px solid rgba(59,130,246,0.2);
}

/* --- Header --- */
.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.notifications-header h1 {
    margin:0;
    font-size:2rem;
    font-weight:700;
}

.notifications-header p {
    color: #94a3b8;
    margin-top: 0.35rem;
}

/* --- Buttons --- */
.btn-pill {
    border-radius: 999px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.mark-all-read {
    background: rgba(59,130,246,0.15);
    border: 1px solid rgba(59,130,246,0.4);
    color: #bfdbfe;
    padding: 0.6rem 1.2rem;
}

.mark-read-btn {
    background: transparent;
    border:1px solid rgba(148,163,184,0.4);
    color:#cbd5f5;
    padding:0.35rem 0.9rem;
    font-size:0.8rem;
}

/* --- Notification List --- */
.notifications-list {
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 20px;
    overflow: hidden;
}

.notification-item {
    padding:16px 20px;
    display:flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(148,163,184,0.2);
    gap:1rem;
    cursor:pointer;
    background: rgba(10,18,40,0.8);
    transition: background 0.2s;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover { background: rgba(59,130,246,0.1); }
.notification-item.unread { background: rgba(59,130,246,0.2); border-left:3px solid #3b82f6; }

.notification-message { font-size:1rem; margin-bottom:0.2rem; }
.notification-time { font-size:0.8rem; color:#94a3b8; }
.no-notifications { padding:40px; text-align:center; color:#94a3b8; }
</style>
