<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Passed from controller
$error = $error ?? '';
$success = $success ?? '';
$user = $user ?? [];
$post = $post ?? [];

// Set default values for header
$unreadCount = $unreadCount ?? 0;
$notifications = $notifications ?? [];

// Decode existing media
$media = [];
if (!empty($post['media_path'])) {
    $decoded = json_decode($post['media_path'], true);
    if (json_last_error() === JSON_ERROR_NONE) $media = $decoded;
}
?>

<?php include __DIR__ . '/../components/Header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Post</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto&family=Poppins&family=Lora&family=Montserrat&family=Playfair+Display&family=Open+Sans&display=swap" rel="stylesheet">

<style>
* { margin:0; padding:0; box-sizing:border-box; }
body, input, textarea, select, button, a { font-family:'Segoe UI', sans-serif; color:#f3f4f6; }
body { background:#0f172a; line-height:1.5; min-height:100vh; }
a { text-decoration:none; }

.page-shell { display:flex; justify-content:center; padding:2rem 1rem; }
.page-card {
    background: rgba(15,23,42,0.85);
    border-radius:24px;
    border:1px solid rgba(59,130,246,0.15);
    box-shadow:0 20px 40px rgba(2,6,23,0.6);
    max-width:1200px;
    width:100%;
    display:grid;
    grid-template-columns: 1fr 2fr;
    gap: clamp(1rem, 3vw, 2rem);
    padding:2rem;
}
@media(max-width:900px) { .page-card { grid-template-columns:1fr; padding:1.5rem; } }

.compose-info { display:flex; flex-direction:column; gap:1.5rem; }
.logo-banner { display:flex; gap:1rem; align-items:center; }
.logo-circle { width:56px; height:56px; border-radius:50%; background: linear-gradient(135deg,#3b82f6,#6366f1); display:flex; align-items:center; justify-content:center; font-weight:700; color:white; letter-spacing:0.05em; }
.tips-list { list-style:none; display:flex; flex-direction:column; gap:0.75rem; color:#cbd5f5; }

.compose-card { display:flex; flex-direction:column; gap:1rem; }
.error-message, .success-message { padding:0.75rem 1rem; border-radius:12px; }
.error-message { background: rgba(239,68,68,0.15); color:#fecaca; }
.success-message { background: rgba(16,185,129,0.15); color:#4ade80; }

.user-info { display:flex; flex-direction:column; gap:0.75rem; }
.user-header { display:flex; align-items:center; gap:1rem; }
.avatar { width:56px; height:56px; border-radius:50%; background: rgba(59,130,246,0.2); display:flex; align-items:center; justify-content:center; font-weight:700; color:#bfdbfe; font-size:1.2rem; }
.username { font-weight:600; }
.muted { font-size:0.85rem; color:#94a3b8; }

.category-select {
    margin-left:0; border-radius:999px; border:1px solid rgba(148,163,184,0.4);
    background:rgba(15,23,42,0.6); color:#e2e8f0; padding:0.35rem 0.9rem;
}

.post-input {
    width:100%; min-height:180px; border-radius:18px;
    border:1px solid rgba(148,163,184,0.3); background:rgba(15,23,42,0.7);
    color:#f8fafc; padding:1rem 1.25rem; font-size:1rem; resize:vertical;
}

.post-footer { display:flex; flex-wrap:wrap; align-items:center; gap:1rem; margin-top:1rem; }
.upload-chip { display:inline-flex; align-items:center; gap:0.5rem; padding:0.55rem 1.2rem; border-radius:999px; border:1px dashed rgba(148,163,184,0.6); color:#bfdbfe; cursor:pointer; }
.post-btn { margin-left:auto; background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; padding:0.75rem 1.8rem; border-radius:999px; color:#fff; font-weight:600; cursor:pointer; transition:0.2s; }
.post-btn:hover { filter:brightness(1.1); }

.media-preview { display:flex; gap:0.85rem; flex-wrap:wrap; margin-top:1rem; }
.media-preview-item { position:relative; width:120px; height:120px; border-radius:16px; overflow:hidden; border:1px solid rgba(59,130,246,0.3); }
.media-preview-item img, .media-preview-item video { width:100%; height:100%; object-fit:cover; }
.remove-btn { position:absolute; top:6px; right:6px; background:rgba(239,68,68,0.9); border:none; color:#fff; width:28px; height:28px; border-radius:50%; cursor:pointer; }

@media(max-width:600px) { .media-preview-item { width:100px; height:100px; } .logo-circle { width:48px; height:48px; font-size:1rem; } }
</style>
</head>
<body>

<section class="page-shell">
<div class="page-card">

    <!-- Info Panel -->
    <aside class="compose-info">
        <div class="logo-banner">
            <div class="logo-circle">BF</div>
            <div>
                <h2>Edit Post</h2>
                <p>Update your story, media, and formatting.</p>
            </div>
        </div>
        <ul class="tips-list">
            <li>Adjust content to keep followers engaged.</li>
            <li>Remove or replace uploaded media.</li>
            <li>Attach up to 5 images/videos.</li>
        </ul>
    </aside>

    <!-- Compose Card -->
    <div class="compose-card">
        <?php if($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- User Info -->
        <div class="user-info">
            <div class="user-header">
                <div class="avatar"><?= strtoupper($user['username'][0] ?? 'B') ?></div>
                <div>
                    <div class="username"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                    <div class="muted"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>

            <!-- Category & Font Selector -->
            <div style="display:flex; align-items:center; gap:1rem; margin-top:0.75rem; flex-wrap:wrap;">
                <select name="category" class="category-select" form="edit-post" required>
                    <?php $cats = ["Food","Travel","Technology","Lifestyle"];
                    foreach($cats as $c): ?>
                        <option value="<?= $c ?>" <?= ($post['category']??'') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="font_family" class="category-select" form="edit-post" onchange="handleFontChange(this.value)">
                    <?php
                    $fonts = ["Roboto","Poppins","Lora","Montserrat","Playfair Display","Open Sans"];
                    $selectedFont = $post['font_family'] ?? 'Roboto';
                    foreach($fonts as $f){
                        $sel = ($f === $selectedFont) ? 'selected' : '';
                        echo "<option value=\"$f\" $sel>$f</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Post Form -->
        <form id="edit-post" action="" method="POST" enctype="multipart/form-data">
            <textarea name="content" class="post-input" required><?= htmlspecialchars($post['content'] ?? '') ?></textarea>

            <!-- Media Preview -->
            <div id="media-preview" class="media-preview"></div>

            <!-- Existing Media Hidden -->
            <div class="media-preview-box">
                <?php foreach($media as $m): 
                    $isVideo = preg_match('/\.(mp4|mov|avi|webm)$/i',$m);
                    $url = '/'.$m;
                ?>
                <div class="media-preview-item">
                    <?php if($isVideo): ?>
                        <video src="<?= $url ?>" controls></video>
                    <?php else: ?>
                        <img src="<?= $url ?>">
                    <?php endif; ?>
                    <input type="checkbox" name="removed_media[]" value="<?= $m ?>" class="remove-hidden" style="display:none;">
                    <button type="button" class="remove-btn" onclick="removeExisting(this)">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Media Upload -->
            <div class="post-footer">
                <label class="upload-chip" for="media-input">
                    <i class="fa fa-paperclip"></i> Media (0/5)
                </label>
                <input id="media-input" type="file" name="media[]" multiple accept="image/*,video/*" style="display:none" onchange="handleNewFiles(event)">
                <button type="submit" class="post-btn">Update Post</button>
            </div>
        </form>
    </div>
</div>
</section>

<script>
// Media & font handling
let selectedFiles = [];

function removeExisting(btn){
    const parent = btn.closest('.media-preview-item');
    const hidden = parent.querySelector('.remove-hidden');
    hidden.checked = true;
    parent.style.display='none';
    updateMediaCounter();
}

function handleNewFiles(event){
    const files = Array.from(event.target.files);
    if(selectedFiles.length + files.length > 5){ alert('Maximum 5 media allowed'); return; }
    selectedFiles.push(...files);
    renderMediaPreview();
}

function renderMediaPreview(){
    const container = document.getElementById('media-preview');
    container.innerHTML='';
    selectedFiles.forEach((file,index)=>{
        const div=document.createElement('div'); div.className='media-preview-item';
        if(file.type.startsWith('video/')){ const v=document.createElement('video'); v.src=URL.createObjectURL(file); v.controls=true; div.appendChild(v); }
        else { const img=document.createElement('img'); img.src=URL.createObjectURL(file); div.appendChild(img); }
        const btn=document.createElement('button'); btn.type='button'; btn.className='remove-btn'; btn.innerHTML='<i class="fa fa-times"></i>';
        btn.onclick=()=>{ selectedFiles.splice(index,1); renderMediaPreview(); }
        div.appendChild(btn); container.appendChild(div);
    });
    updateMediaCounter();
    const dt=new DataTransfer(); selectedFiles.forEach(f=>dt.items.add(f)); document.getElementById('media-input').files=dt.files;
}

function updateMediaCounter(){
    const label = document.querySelector('.upload-chip');
    const existing = document.querySelectorAll('.media-preview-box input.remove-hidden:not(:checked)').length;
    label.innerHTML=`<i class="fa fa-paperclip"></i> Media (${existing + selectedFiles.length}/5)`;
}

// Font handling
function loadFonts(font){
    document.querySelectorAll('link[data-dynamic-font]').forEach(e=>e.remove());
    const safe=["Arial","Times New Roman","Courier New","Georgia","Verdana"];
    if(safe.includes(font)) return;
    const f=font.replace(/ /g,'+');
    const link=document.createElement('link'); link.setAttribute('data-dynamic-font','true'); link.rel='stylesheet';
    link.href=`https://fonts.googleapis.com/css2?family=${f}:wght@300;400;500;600;700&display=swap`;
    document.head.appendChild(link);
}
function handleFontChange(font){
    loadFonts(font);
    const textarea=document.querySelector('.post-input');
    if(textarea) textarea.style.fontFamily=font;
}

document.addEventListener("DOMContentLoaded",()=>{
    const defaultFont="<?= $post['font_family'] ?? 'Roboto' ?>";
    handleFontChange(defaultFont);
    updateMediaCounter();
});
</script>

</body>
</html>
