<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Pass data to Header
$user = $user ?? null;
$unreadCount = $unreadCount ?? 0;
$notifications = $_SESSION['notifications'] ?? [];
include __DIR__ . '/../components/Header.php';
include_once __DIR__ . '/../../helpers/datetime_helper.php';

// Collect unique fonts from posts
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

<?php
foreach($fonts as $font):
    $fontName = trim(explode(',', $font)[0]); // keep full font name before comma
    $fontNameForLink = str_replace(' ', '+', $fontName);
?>
<link href="https://fonts.googleapis.com/css2?family=<?= $fontNameForLink ?>&display=swap" rel="stylesheet">
<?php endforeach; ?>


<meta charset="UTF-8">
<title>User Dashboard - BlogFlow</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<style>
/* --- Reset & Body --- */
html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  font-family: sans-serif;
  background: #0f172a;
  color: #f3f4f6;
  overflow-y: auto; /* allow scrolling on mobile */
}

/* --- Dashboard Grid --- */
.user-dashboard {
  display: grid;
  grid-template-columns: 320px 1fr 320px; /* Analytics | Feed | Profile */
  grid-template-rows: calc(100vh - 90px);
  gap: 1.5rem;
  padding: 1rem 2rem;
  box-sizing: border-box;
  width: 100%;
  margin: 0 auto;
}

/* --- Panels --- */
.panel {
  background: rgba(15, 23, 42, 0.65);
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.5);
  backdrop-filter: blur(12px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-scroll {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

/* --- Analytics Cards --- */
.analytics-cards {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.analytic-card {
  background: rgba(148, 163, 184, 0.08);
  border-radius: 16px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.analytic-card .label {
  color: #cbd5f5;
  font-size: 0.85rem;
}

.analytic-card .value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #f8fafc;
}

.analytic-card small {
  color: #94a3b8;
}

/* --- Top Categories --- */
.category-list {
  list-style: none;
  padding: 0;
  margin: 0.5rem 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.category-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(59, 130, 246, 0.1);
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  font-size: 0.9rem;
  color: #e2e8f0;
}

.category-list .badge {
  background: #3b82f6;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 8px;
}

/* --- Feed Panel --- */
.feed-panel {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.feed-panel .posts-wrapper {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.status-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(15, 23, 42, 0.85);
  border-radius: 18px;
  padding: 0.9rem 1.25rem;
  cursor: pointer;
  box-shadow: 0 20px 40px rgba(0,0,0,0.35);
  flex-wrap: nowrap; /* force all elements in one line */
}

.status-section .avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 2px solid rgba(59, 130, 246, 0.35);
  flex-shrink: 0; /* do not shrink */
}

.status-section input {
  flex: 1 1 auto; /* take remaining space */
  min-width: 0;   /* allow shrinking in small screens */
  border: none;
  border-radius: 999px;
  padding: 0.65rem 1rem;
  background: rgba(148, 163, 184, 0.15);
  color: #e2e8f0;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0; /* keep icons visible, don't wrap */
}

.status-actions i {
  font-size: 1.1rem;
  color: #60a5fa;
  cursor: pointer;
  transition: transform 0.15s;
}

.status-actions i:hover {
  transform: scale(1.2);
}

/* --- Profile Panel --- */
.profile-panel {
  display: flex;
  flex-direction: column;
}

.profile-card {
  background: rgba(148, 163, 184, 0.08);
  border-radius: 18px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.profile-avatar {
  width: 55px;
  height: 55px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  font-weight: 700;
}

.profile-header h3 {
  margin: 0;
  color: #f8fafc;
}

.profile-header p {
  margin: 0;
  font-size: 0.85rem;
  color: #94a3b8;
}

.profile-stats {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.profile-stats .label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
}

.profile-stats .value {
  display: block;
  margin-top: 0.15rem;
  font-weight: 600;
}

.profile-btn {
  display: block;
  text-align: center;
  background: #2563eb;
  color: white;
  padding: 0.75rem;
  border-radius: 12px;
  text-decoration: none;
  margin-top: 0.5rem;
  font-weight: 600;
}

/* --- Reminders --- */
.reminders {
  list-style: none;
  padding: 0;
  margin: 0.5rem 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  color: #cbd5f5;
}

.reminders li {
  padding-left: 1.25rem;
  position: relative;
  font-size: 0.9rem;
}

.reminders li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #3b82f6;
}

/* --- Posts --- */
.post-card {
  background: rgba(15,23,42,0.8);
  border-radius: 16px;
  padding: 1rem;
  color: #e2e8f0;
}

.post-actions {
  display: flex;
  gap: 1rem;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.post-actions span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* --- Feed Empty --- */
.feed-message {
  text-align: center;
  background: rgba(15, 23, 42, 0.8);
  padding: 2rem;
  border-radius: 16px;
  color: #e2e8f0;
}

/* --- Responsive --- */
@media (max-width: 1400px) {
  .user-dashboard {
    grid-template-columns: 240px 1fr 240px;
  }
}

/* --- Responsive --- */
@media (max-width: 1200px) {
  .user-dashboard {
    grid-template-columns: 1fr;
    grid-template-rows: auto; /* panels stack */
    padding: 1rem;
    gap: 1rem;
  }

 .panel, .feed-panel {
    height: auto;
    width: 100%;
    padding: 1rem;
  }

  .feed-panel .posts-wrapper {
    height: auto;
  }
}

/* --- Extra Small Devices --- */
@media (max-width: 600px) {
  .status-section .avatar {
    width: 38px;
    height: 38px;
  }

  .status-actions i {
    font-size: 1rem;
  }

  .profile-avatar {
    width: 50px;
    height: 50px;
    font-size: 1.1rem;
  }

  .analytic-card .value {
    font-size: 1.5rem;
  }

  .analytic-card .label {
    font-size: 0.8rem;
  }
}

/* --- Highlighted Post --- */
.post-card.highlighted {
  border: 2px solid #3b82f6;
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
  animation: highlight-pulse 2s ease-in-out;
}

@keyframes highlight-pulse {
  0% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
  50% { box-shadow: 0 0 30px rgba(59, 130, 246, 0.8); }
  100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
}
</style>

</head>
<body>


<div class="user-dashboard">

  <!-- Analytics Panel -->
  <aside class="panel analytics-panel">
    <div class="panel-scroll">
      <div class="panel-title">Your Analytics</div>
      <div class="analytics-cards">
        <div class="analytic-card">
          <span class="label">Total Posts</span>
          <span class="value"><?= $analytics['totalPosts'] ?? 0 ?></span>
          <small><?= $analytics['lastPostDate'] ?? 'No posts yet' ?></small>
        </div>
        <div class="analytic-card">
          <span class="label">Engagements</span>
          <span class="value"><?= $analytics['totalEngagements'] ?? 0 ?></span>
          <small><?= $analytics['totalLikes'] ?? 0 ?> likes • <?= $analytics['totalComments'] ?? 0 ?> comments</small>
        </div>
        <div class="analytic-card">
          <span class="label">Media Uploads</span>
          <span class="value"><?= $analytics['totalMedia'] ?? 0 ?></span>
          <small><?= $analytics['images'] ?? 0 ?> images • <?= $analytics['videos'] ?? 0 ?> videos</small>
        </div>
      </div>

      <div class="panel-title mt">Top Categories</div>
      <ul class="category-list">
        <?php if (!empty($topCategories)): ?>
          <?php foreach ($topCategories as $cat): ?>
            <li>
              <span><?= htmlspecialchars($cat['name']) ?></span>
              <span class="badge"><?= $cat['count'] ?></span>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="empty">Not enough posts yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </aside>

  <!-- Feed Panel -->
  <main class="feed-panel">
    <div class="status-section" onclick="window.location='/posts/create'">
      <img class="avatar" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="avatar">
      <input type="text" placeholder="What's on your mind?" readonly />
      <div class="status-actions">
        <i class="fa fa-image"></i>
        <i class="fa fa-video-camera"></i>
        <i class="fa fa-paper-plane"></i>
      </div>
    </div>

    <div class="posts-wrapper">
      <?php if (!empty($posts)): ?>
          <?php foreach ($posts as $post): ?>
<?php
// Prepare media paths for each post
$mediaFiles = [];

// First try to get media from media_path (new format)
if (!empty($post['media_path'])) {
    $decoded = json_decode($post['media_path'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $mediaFiles = $decoded;
    }
}

// Fallback to media_files if no media found
if (empty($mediaFiles) && !empty($post['media_files'])) {
    $decoded = json_decode($post['media_files'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $mediaFiles = $decoded;
    }
}

// Fallback to images if still no media found
if (empty($mediaFiles) && !empty($post['images'])) {
    $decoded = json_decode($post['images'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $mediaFiles = $decoded;
    }
}

// Clean and validate paths
$mediaFiles = array_values(array_filter(array_map(function($path) {
    $path = trim($path);
    // Remove any leading slashes or backslashes
    $path = ltrim($path, '/\\');
    // Ensure path starts with 'uploads/'
    if (strpos($path, 'uploads/') !== 0) {
        $path = 'uploads/' . $path;
    }
    return $path;
}, $mediaFiles)));

// Debug: Uncomment to see the processed media files
// error_log('Processed media files: ' . print_r($mediaFiles, true));
?>
<div class="post-card<?= (isset($_GET['post_id']) && $_GET['post_id'] == $post['post_id']) ? ' highlighted' : '' ?>" id="post-<?= $post['post_id'] ?>">
<?php include __DIR__ . '/../components/PostCard.php'; ?>
</div>

          <?php endforeach; ?>
      <?php else: ?>
          <div class="feed-message">No posts to show. Start creating content!</div>
      <?php endif; ?>
    </div>
    
  </main>

  <!-- Profile Panel -->
  <aside class="panel profile-panel">
    <?php if (!empty($user)): ?>
    <div class="panel-scroll">
      <div class="profile-card">
        <div class="profile-header">
          <div class="profile-avatar"><?= strtoupper($user['username'][0] ?? '?') ?></div>
          <div>
            <h3><?= htmlspecialchars($user['username']) ?></h3>
            <p><?= htmlspecialchars($user['email']) ?></p>
          </div>
        </div>
        <div class="profile-stats">
          <div>
            <span class="label">Role</span>
            <span class="value"><?= htmlspecialchars($user['role']) ?></span>
          </div>
          <div>
            <span class="label">Notifications</span>
            <span class="value"><?= $unreadCount ?></span>
          </div>
        </div>
        <a href="/users/profile" class="profile-btn">View full profile</a>
      </div>

      <div class="panel-title">Reminders</div>
      <ul class="reminders">
        <li>Stay active and engage with others.</li>
        <li>Share media-rich posts for better reach.</li>
        <li>Check notifications to stay updated.</li>
      </ul>
    </div>
    <?php endif; ?>
  </aside>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    let postId = urlParams.get('post_id');
    const commentId = urlParams.get('comment');
    const replyId = urlParams.get('reply');

    // Also check for post ID in hash (for like notifications)
    if (!postId && window.location.hash.startsWith('#post-')) {
        postId = window.location.hash.replace('#post-', '');
    }

    if (postId) {
        const postElement = document.getElementById('post-' + postId);
        if (postElement) {
            // Add highlighted class if not already present
            if (!postElement.classList.contains('highlighted')) {
                postElement.classList.add('highlighted');
            }

            // Expand comments section if we have a comment or reply to highlight
            if (commentId || replyId) {
                const commentsDiv = postElement.querySelector('.comments');
                if (commentsDiv && !commentsDiv.classList.contains('visible')) {
                    commentsDiv.classList.add('visible');
                }
            }

            // Scroll to the post first
            postElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Then scroll to the specific comment or reply after a short delay
            setTimeout(() => {
                let targetElement = null;
                if (replyId) {
                    targetElement = document.getElementById('reply-' + replyId);
                } else if (commentId) {
                    targetElement = document.getElementById('comment-' + commentId);
                }

                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add a temporary highlight effect
                    targetElement.style.backgroundColor = 'rgba(59, 130, 246, 0.2)';
                    targetElement.style.borderColor = '#3b82f6';
                    targetElement.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.5)';
                    setTimeout(() => {
                        targetElement.style.backgroundColor = '';
                        targetElement.style.borderColor = '';
                        targetElement.style.boxShadow = '';
                    }, 3000);
                }
            }, 500);
        }
    }
});
</script>

</body>
</html>
