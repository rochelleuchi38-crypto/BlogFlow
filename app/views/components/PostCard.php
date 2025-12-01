<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

include_once __DIR__ . '/../../helpers/datetime_helper.php';
$isOwner = isset($post['user_id']) && isset($_SESSION['user']) && $_SESSION['user']['id'] == $post['user_id'];
$loggedInUser = $_SESSION['user'] ?? null;

/* -------------------------------
   MEDIA FILES EXTRACTION FUNCTION
--------------------------------*/
if (!function_exists('getMediaFiles')) {
    function getMediaFiles($mediaPath) {
        if (empty($mediaPath) || $mediaPath === '[]') return [];
        $decoded = json_decode($mediaPath, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, fn($p)=>!empty($p) && is_string($p)));
        }
        return [];
    }
}

/* -------------------------------
   FORMAT CONTENT
--------------------------------*/
if (!function_exists('formatContent')) {
    function formatContent($content) {
        return nl2br(htmlspecialchars($content ?? ''));
    }
}

/* -------------------------------
   MEDIA URL GENERATOR
--------------------------------*/
if (!function_exists('getMediaUrl')) {
    function getMediaUrl($path) {
        if (!$path || empty(trim($path))) return '';
        $path = ltrim($path, '/\\');
        if (strpos($path, 'public/') === 0) return '/' . $path;
        return '/public/' . ltrim($path, '/');
    }
}

/* -------------------------------
   VIDEO CHECKER
--------------------------------*/
if (!function_exists('isVideo')) {
    function isVideo($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4','mov','webm','ogg']);
    }
}
?>
<style>

    /* --- BUTTON STYLES FIX --- */

/* Action buttons (like, comment) */
/* CLEAN ACTION BUTTONS */
.action {
    background: rgba(59,130,246,0.10);
    border: 1px solid rgba(59,130,246,0.25);
    color: #bfdbfe;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s ease;
}

.action .icon svg {
    display: block;
}

.action:hover {
    background: rgba(59,130,246,0.25);
}

.action.active {
    background: rgba(59,130,246,0.35);
    color: #60a5fa;
}

.post-actions {
    display: flex;
    gap: 14px;
    margin-top: 14px;
}
/* --- UNIFIED ACTION BUTTON SIZE --- */
.post-actions .action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    padding: 10px 18px;  /* equal uniform padding */
    min-width: 105px;    /* ensures LIKE and COMMENT same width */

    height: 42px;        /* equal height */
    border-radius: 30px;
    box-sizing: border-box;

    background: rgba(59,130,246,0.10);
    border: 1px solid rgba(59,130,246,0.25);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: 0.2s ease;
}

.post-actions .action:hover {
    background: rgba(59,130,246,0.25);
}

.post-actions .action.active {
    background: rgba(59,130,246,0.35);
    color: #60a5fa;
}

/* Make SVG icons perfectly equal */
.post-actions .action .icon svg {
    width: 20px !important;
    height: 20px !important;
    flex-shrink: 0;
    display: block;
}


/* Form buttons (comment/reply) */
.btn {
    background: linear-gradient(90deg,#3b82f6,#6366f1);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 999px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    font-size: 0.9rem;
}
.btn:hover {
    background: linear-gradient(90deg,#2563eb,#4f46e5);
    transform: scale(1.05);
}

/* Delete buttons (menu & comment/reply) */
.delete-btn,
.delete {
    background: rgba(248,113,113,0.15);
    color: #f87171;
    border: none;
    padding: 6px 12px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
    transition: background 0.2s, color 0.2s;
}
.delete-btn:hover,
.delete:hover {
    background: rgba(248,113,113,0.3);
    color: #ef4444;
}

/* Menu buttons */
.menu-btn {
    background: rgba(59,130,246,0.15);
    border:none;
    border-radius:50%;
    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#bfdbfe;
    cursor:pointer;
    transition: background 0.2s, transform 0.15s;
}
.menu-btn:hover {
    background: rgba(59,130,246,0.3);
    transform: scale(1.1);
}

/* --- Styles (same as your code, plus minor improvements) --- */
.post-card { position: relative; background: rgba(10, 18, 40, 0.95); border-radius: 18px; padding: 1.25rem; box-shadow: 0 18px 40px rgba(2,6,23,0.65); color: #e2e8f0; overflow: hidden; }
.post-top { display:flex; justify-content:flex-start; align-items:center; gap:1rem; }
.row-left { display:flex; gap:0.9rem; align-items:center; }
.avatar { width:52px; height:52px; border-radius:50%; background: rgba(59,130,246,0.15); display:flex; align-items:center; justify-content:center; overflow:hidden; color:#60a5fa; }
.avatar img { width:100%; height:100%; object-fit:cover; }
.user-meta .username { font-weight:700; font-size:1.05rem; }
.user-meta .time { font-size: 0.8rem; color: #94a3b8; }
.location { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; display: flex; align-items: center; gap: 4px; }
.location i { font-size: 0.85rem; color: #60a5fa; }
/* Redesigned 3-dot menu */
.post-menu {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 20;
}

.menu-btn {
    background: rgba(59,130,246,0.18);
    border: none;
    border-radius: 14px;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #93c5fd;
    cursor: pointer;
    transition: 0.2s;
}

.menu-btn:hover {
    background: rgba(59,130,246,0.32);
    transform: scale(1.1);
}

/* New animated menu container */
.smooth-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 115%;
    background: #0f172a;
    border: 1px solid rgba(59,130,246,0.3);
    border-radius: 14px;
    padding: 6px 0;
    width: 170px;
    box-shadow: 0 15px 35px rgba(2,6,23,0.7);
    animation: slideFade 0.18s ease-out;
}

@keyframes slideFade {
    0% { opacity: 0; transform: translateY(-6px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Menu Container */
.menu-pop {
    display: none;
    position: absolute;
    right: 0;
    top: 110%;
    background: #0f172a;
    border: 1px solid rgba(59,130,246,0.25);
    border-radius: 14px;
    padding: 6px;            /* ← THIS fixes hover overflow */
    min-width: 165px;
    box-shadow: 0 12px 28px rgba(0,0,0,0.45);
    overflow: hidden;        /* ← Also prevents hover from escaping */
}

/* Menu buttons/links */
.menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 14px;
    background: transparent;
    color: #e2e8f0;
    font-size: 14px;
    font-weight: 600;
    border: none;
    text-align: left;
    cursor: pointer;
    border-radius: 8px;       /* ← smooth edges inside menu */
    transition: background 0.2s ease, padding-left 0.15s ease;
}

.menu-item:hover {
    background: rgba(59,130,246,0.18);
    padding-left: 16px; /* subtle animation, not sagad */
}

/* Delete item emphasis */
.delete-action {
    color: #f87171;
}

.delete-action:hover {
    background: rgba(248,113,113,0.15);
    color: #ef4444;
}
.post-category { font-weight:800; color:#93c5fd; margin-bottom:6px; text-transform: uppercase; font-size:0.85rem; letter-spacing:0.08em; }
.post-content { margin-bottom:10px; line-height:1.6; color:#e2e8f0; }
/* MEDIA GRID */
.media-grid { display:grid; gap:8px; margin-top:10px; }
.media-count-1 { grid-template-columns:1fr; }
.media-count-2 { grid-template-columns:repeat(2,1fr); }
.media-count-3 { grid-template-areas:"left right1" "left right2"; grid-template-columns:2fr 1fr; grid-template-rows:1fr 1fr; }
.media-count-3 .media-item:nth-child(1) { grid-area:left; }
.media-count-3 .media-item:nth-child(2) { grid-area:right1; }
.media-count-3 .media-item:nth-child(3) { grid-area:right2; }
.media-count-4 { grid-template-columns:repeat(2,1fr); grid-template-rows:repeat(2,1fr); }
.media-count-5, .media-count-6, .media-count-7, .media-count-8, .media-count-9, .media-count-10 { grid-template-columns:repeat(3,1fr); grid-auto-rows:160px; }
.media-item { width:100%; height:100%; border-radius:12px; object-fit:cover; border:1px solid rgba(59,130,246,0.2); }
.post-actions { display:flex; gap:12px; margin-top:12px; }
.action { background:rgba(59,130,246,0.1); border:none; cursor:pointer; font-weight:600; color:#bfdbfe; display:flex; gap:8px; align-items:center; padding:6px 10px; border-radius:999px; transition: background 0.2s, color 0.2s; }
.action:hover { background:rgba(59,130,246,0.25); }
.action.active { color:#60a5fa; background:rgba(59,130,246,0.35); }
/* comments */
.comments { margin-top:12px; border-top:1px solid rgba(148,163,184,0.15); padding-top:12px; display:none; transition: all 0.2s ease-in-out; min-height:80px; background: rgba(15,23,42,0.05); }
.comments.visible { display:block; }
.comment-form { display:flex; gap:8px; align-items:center; margin-bottom:12px; }
.comment-form input { flex:1; padding:10px 12px; border-radius:20px; border:1px solid rgba(148,163,184,0.25); outline:none; background:rgba(15,23,42,0.8); color:#e2e8f0; }
.btn { background:linear-gradient(90deg,#3b82f6,#6366f1); color:white; border:none; padding:8px 14px; border-radius:999px; font-weight:700; cursor:pointer; }
.comment-list .comment { background: rgba(15,23,42,0.8); padding:10px; border-radius:12px; margin-bottom:8px; border:1px solid rgba(59,130,246,0.15); }
.comment-top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.comment-actions { display:flex; gap:10px; margin-top:6px; color:#93c5fd; font-weight:600; cursor:pointer; }
.reply-form { display:flex; gap:8px; margin-top:10px; background:rgba(10,18,40,0.85); padding:10px; border-radius:14px; border:1px solid rgba(59,130,246,0.2); animation:fadeIn 0.25s ease-out; }
.reply-form input { flex:1; padding:10px 12px; border-radius:14px; border:1px solid rgba(148,163,184,0.25); outline:none; background:rgba(15,23,42,0.8); color:#e2e8f0; transition:0.3s; }
.reply-form .btn { padding:8px 16px; background:linear-gradient(90deg,#3b82f6,#6366f1); border-radius:14px; font-weight:700; border:none; cursor:pointer; transition:0.3s; }
.replies { margin-left:1.2rem; margin-top:10px; border-left:2px solid rgba(59,130,246,0.25); padding-left:14px; }
.reply-item { background:rgba(15,23,42,0.8); padding:10px; border-radius:12px; border:1px solid rgba(59,130,246,0.15); margin-bottom:8px; animation:fadeIn 0.25s ease-out; }
.reply-top { display:flex; align-items:center; gap:8px; font-weight:700; color:#bfdbfe; }
.reply-item .muted { font-size:0.8rem; color:#94a3b8; }
.reply-item .delete { margin-top:6px; color:#f87171; font-weight:600; font-size:0.85rem; cursor:pointer; transition:0.2s; }
.reply-item .delete:hover { color:#ef4444; }
@keyframes fadeIn { from { opacity:0; transform:translateY(5px);} to { opacity:1; transform:translateY(0);} }
.muted { color:#94a3b8; font-size:0.85rem; }
</style>

<article class="post-card" id="post-<?= $post['post_id'] ?>">
<?php if ($isOwner): ?>
<div class="post-menu">
    <button class="menu-btn" onclick="toggleMenu(this)">
        <i class="fa fa-ellipsis-v"></i>
    </button>

    <div class="menu-pop smooth-menu">
        <a href="/posts/edit/<?= $post['post_id'] ?>" class="menu-item">
            <i class="fa fa-edit"></i> Edit Post
        </a>

        <button class="menu-item delete-action"
            onclick="deletePost(<?= $post['post_id'] ?>)">
            <i class="fa fa-trash"></i> Delete Post
        </button>
    </div>
</div>

<?php endif; ?>

<header class="post-top">
    <div class="row-left">
        <div class="avatar">
            <?php if (!empty($post['avatar'])): ?>
                <img src="<?= htmlspecialchars($post['avatar']) ?>" alt="avatar">
            <?php else: ?>
                <i class="fa fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="user-meta">
            <div class="username"><?= htmlspecialchars($post['username'] ?? 'Unknown') ?></div>
            <div class="time"><?= format_manila_datetime($post['created_at']) ?></div>
            <?php if (!empty($post['city']) || !empty($post['country'])): ?>
                <div class="location">
                    <i class="fa fa-map-marker-alt"></i>
                    <?= htmlspecialchars(implode(', ', array_filter([$post['city'],$post['country']]))) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="post-body">
    <?php if (!empty($post['category'])): ?>
        <div class="post-category"><?= htmlspecialchars($post['category']) ?></div>
    <?php endif; ?>
    <div class="post-content" style="font-family:'<?= trim(explode(',', $post['font_family'] ?? 'Arial, sans-serif')[0]) ?>',sans-serif;">
        <?= formatContent($post['content'] ?? '') ?>
    </div>

    <?php $mediaFiles = getMediaFiles($post['media_path'] ?? '');
    if (!empty($mediaFiles)): ?>
    <div class="media-grid media-count-<?= count($mediaFiles) ?>">
        <?php foreach ($mediaFiles as $mediaPath):
            $ext=strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
            $isVideo=in_array($ext,['mp4','webm','ogg','mov']);
            $mediaUrl=getMediaUrl($mediaPath);
        ?>
        <?php if ($isVideo): ?>
            <video controls class="media-item"><source src="<?= $mediaUrl ?>" type="video/<?= $ext ?>">Your browser does not support video.</video>
        <?php else: ?>
            <img src="<?= $mediaUrl ?>" class="media-item" alt="Post media" onclick="window.open('<?= $mediaUrl ?>','_blank')">
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer class="post-actions">

    <!-- LIKE BUTTON -->
    <form method="POST" action="/posts/<?= $post['post_id'] ?>/like" style="display:inline">
        <button type="submit" class="action <?= !empty($post['is_liked'])?'active':'' ?>">
            <span class="icon">
                <?= !empty($post['is_liked'])
                    ? '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'18\' height=\'18\' fill=\'#60a5fa\' viewBox=\'0 0 24 24\'><path d=\'M14 9V5a3 3 0 0 0-6 0v4H4v12h14l2-9V9h-6z\'/></svg>'
                    : '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'18\' height=\'18\' fill=\'currentColor\' viewBox=\'0 0 24 24\'><path d=\'M14 9V5a3 3 0 0 0-6 0v4H4v12h14l2-9V9h-6z\'/></svg>' ?>
            </span>
            <span><?= $post['like_count'] ?? 0 ?></span>
        </button>
    </form>

    <!-- COMMENT BUTTON -->
    <button type="button" class="action comment-toggle" data-post-id="<?= $post['post_id'] ?>">
        <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                 viewBox="0 0 24 24">
                <path d="M21 6a2 2 0 0 0-2-2H5a2 
                         2 0 0 0-2 2v10a2 2 0 0 0 2 
                         2h4l3 3 3-3h4a2 2 0 0 0 2-2V6z"/>
            </svg>
        </span>
        <span><?= !empty($post['comments']) ? count($post['comments']) : 0 ?></span>
    </button>

</footer>


<!-- Comments -->
<div class="comments" id="comments-<?= $post['post_id'] ?>">
    <form class="comment-form" method="POST" action="/posts/<?= $post['post_id'] ?>/comment">
        <input type="text" name="content" placeholder="Write a comment..." required>
        <button type="submit" class="btn">Comment</button>
    </form>

    <?php if(!empty($post['comments'])): ?>
    <div class="comment-list">
        <?php foreach($post['comments'] as $c): ?>
        <div class="comment" id="comment-<?= $c['comment_id'] ?>">
            <div class="comment-top">
                <strong><?= htmlspecialchars($c['username']??'Unknown') ?></strong>
                <div class="muted"><?= format_manila_datetime($c['created_at']) ?></div>
            </div>
            <div class="comment-body"><?= formatContent($c['content']??'') ?></div>
            <div class="comment-actions">
                <span class="reply" onclick="openReplyForm(<?= $c['comment_id'] ?>)">Reply</span>
                <?php if(($c['user_id']??0)===($loggedInUser['id']??0) || ($loggedInUser['role']??'')==='admin'): ?>
                <form method="POST" action="/comments/<?= $c['comment_id'] ?>/delete" style="display:inline">
                    <button type="submit" class="delete" onclick="return confirm('Delete comment?')">Delete</button>
                </form>
                <?php endif; ?>
            </div>
            <?php if(!empty($c['replies'])): ?>
            <div class="replies">
                <?php foreach($c['replies'] as $r): ?>
                <div class="reply-item" id="reply-<?= $r['reply_id'] ?>">
                    <div class="reply-top">
                        <strong><?= htmlspecialchars($r['username']??'Unknown') ?></strong>
                        <span class="muted"><?= format_manila_datetime($r['created_at']) ?></span>
                    </div>
                    <div><?= formatContent($r['content']??'') ?></div>
                    <?php if(($r['user_id']??0)===($loggedInUser['id']??0) || ($loggedInUser['role']??'')==='admin'): ?>
                    <form method="POST" action="/replies/<?= $r['reply_id'] ?>/delete">
                        <button type="submit" class="delete" onclick="return confirm('Delete reply?')">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="comment-list"><div class="muted" style="padding:8px;">No comments yet. Be the first to comment!</div></div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-toggle')) {
        const postId = e.target.dataset.postId;
        const commentsDiv = document.getElementById('comments-' + postId);
        if (commentsDiv) {
            commentsDiv.classList.toggle('visible');
        }
    }
});

function openReplyForm(id){
    let box=document.getElementById('reply-box-'+id);
    if(!box){
        // create a temporary reply form if not exists
        box=document.createElement('form');
        box.id='reply-box-'+id;
        box.className='reply-form';
        box.method='POST';
        box.action='/comments/'+id+'/reply';
        box.innerHTML='<input type="text" name="content" placeholder="Write a reply..." required><button type="submit" class="btn">Reply</button>';
        const comment=document.getElementById('comment-'+id);
        comment.appendChild(box);
    }
    box.style.display=(box.style.display==='flex' || box.style.display==='')?'flex':'none';
}

function deletePost(postId){
    if(!confirm('Are you sure you want to delete this post?')) return;
    fetch('/posts/delete/'+postId,{
        method:'GET',
        credentials:'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res=>res.json()).then(data=>{
        if(data.success){
            alert(data.message);
            const postElem=document.getElementById('post-'+postId);
            if(postElem)postElem.remove();
            if(data.redirect)window.location.href=data.redirect;
        } else alert(data.message);
    }).catch(err=>{console.error(err);alert('Error deleting post');});
}

function toggleMenu(button){
    const menu=button.nextElementSibling;
    const isOpen=menu.style.display==='block';
    document.querySelectorAll('.menu-pop').forEach(m=>{if(m!==menu)m.style.display='none';});
    menu.style.display=isOpen?'none':'block';
}

document.addEventListener('click',function(e){
    if(!e.target.closest('.post-menu'))document.querySelectorAll('.menu-pop').forEach(menu=>menu.style.display='none');
});
</script>
