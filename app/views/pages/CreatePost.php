<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

$error = $error ?? '';
$success = $success ?? '';
$user = $user ?? [];
$unreadCount = $unreadCount ?? 0;
$notifications = $notifications ?? [];
?>

<?php include __DIR__ . '/../components/Header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Post</title>
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

.user-info { display:flex; align-items:flex-start; gap:1rem; margin-bottom:1rem; flex-direction:column; }
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
    <!-- Info panel -->
    <aside class="compose-info">
        <div class="logo-banner">
            <div class="logo-circle">BF</div>
            <div>
                <h2>BlogFlow</h2>
                <p>Share photos, stories, and inspiration.</p>
            </div>
        </div>
        <ul class="tips-list">
            <li>Attach up to five images or videos per post.</li>
            <li>Use categories so others can find your content.</li>
            <li>Keep captions friendly and engaging.</li>
        </ul>
    </aside>

    <!-- Compose Card -->
    <div class="compose-card">
        <?php if($error): ?><div class="error-message"><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="success-message"><?= $success ?></div><?php endif; ?>

        <!-- User Info -->
        <div class="user-info">
            <div class="user-header">
                <div class="avatar"><?= strtoupper($user['username'][0] ?? 'B') ?></div>
                <div>
                    <div class="username"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                    <div class="muted"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>

            <!-- Category & Font Selector under username/email -->
            <div style="display:flex; align-items:center; gap:1rem; margin-top:0.75rem; flex-wrap:wrap;">
                <select name="category" class="category-select" form="create-post" required>
                    <option value="">Select Category</option>
                    <option value="Food" <?= (($_POST['category'] ?? '') === 'Food') ? 'selected' : '' ?>>Food</option>
                    <option value="Travel" <?= (($_POST['category'] ?? '') === 'Travel') ? 'selected' : '' ?>>Travel</option>
                    <option value="Technology" <?= (($_POST['category'] ?? '') === 'Technology') ? 'selected' : '' ?>>Technology</option>
                    <option value="Lifestyle" <?= (($_POST['category'] ?? '') === 'Lifestyle') ? 'selected' : '' ?>>Lifestyle</option>
                </select>

                <select name="font_family" class="category-select" form="create-post" onchange="handleFontChange(this.value)">
                    <?php
                    $fonts = ['Roboto','Poppins','Lora','Montserrat','Playfair Display','Open Sans'];
                    $selectedFont = $_POST['font_family'] ?? 'Roboto';
                    foreach($fonts as $f){
                        $sel = ($f === $selectedFont) ? 'selected' : '';
                        echo "<option value=\"$f\" $sel>$f</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Post Form -->
        <form id="create-post" action="/posts/create" method="POST" enctype="multipart/form-data">
            <textarea name="content" class="post-input" placeholder="What would you like to share today?" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>

            <!-- Location -->
            <div style="margin-top:1rem;">
                <button type="button" onclick="getLocation()" class="post-btn" id="location-btn">Get My Location</button>
                <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                <div id="location-error" style="color:#f87171; margin-top:0.5rem;"></div>
            </div>

            <!-- Media Upload -->
            <div class="post-footer">
                <label class="upload-chip" for="media-input">
                    <i class="fa fa-paperclip"></i> Media (0/5)
                </label>
                <input id="media-input" type="file" name="media[]" multiple accept="image/*,video/*" style="display:none" onchange="updateMediaPreview(event)">
                <button type="submit" class="post-btn">Publish</button>
            </div>

            <!-- Media Preview -->
            <div id="media-preview" class="media-preview"></div>
        </form>
    </div>
</div>
</section>

<script>
const composeCard = document.querySelector('.compose-card');

function handleFontChange(font) {
    if (!composeCard) return;
    composeCard.style.fontFamily = `'${font}', sans-serif`;
    const textarea = composeCard.querySelector('.post-input');
    if (textarea) textarea.style.fontFamily = `'${font}', sans-serif`;
    const textElements = composeCard.querySelectorAll('.username, .muted, .error-message, .success-message, p, h2, li');
    textElements.forEach(el => el.style.fontFamily = `'${font}', sans-serif`);
}

const fontSelect = document.querySelector('select[name="font_family"]');
fontSelect.addEventListener('change', (e) => handleFontChange(e.target.value));
handleFontChange(fontSelect.value);

function getLocation() {
    const errorEl = document.getElementById('location-error');
    if (!navigator.geolocation) { errorEl.textContent = "Geolocation not supported."; return; }
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('latitude').value = pos.coords.latitude;
        document.getElementById('longitude').value = pos.coords.longitude;
        document.getElementById('location-btn').textContent = 'Location Captured';
        errorEl.textContent = '';
    }, () => { errorEl.textContent = 'Failed to get location.'; });
}

let selectedFiles = [];
function updateMediaPreview(event) {
    let files = event.target.files ? Array.from(event.target.files) : [];
    if (files.length) { 
        if(selectedFiles.length + files.length > 5){ alert('Maximum 5 files'); return; }
        selectedFiles.push(...files); 
    }
    const previewEl = document.getElementById('media-preview'); previewEl.innerHTML='';
    selectedFiles.forEach((file,index)=>{
        const div=document.createElement('div'); div.className='media-preview-item';
        if(file.type.startsWith('image/')){ const img=document.createElement('img'); img.src=URL.createObjectURL(file); div.appendChild(img); }
        else { const vid=document.createElement('video'); vid.src=URL.createObjectURL(file); vid.controls=true; div.appendChild(vid); }
        const btn=document.createElement('button'); btn.type='button'; btn.className='remove-btn'; btn.innerHTML='<i class="fa fa-times"></i>';
        btn.onclick=()=>{ selectedFiles.splice(index,1); updateMediaPreview({target:{files:[]}}); };
        div.appendChild(btn); previewEl.appendChild(div);
    });
    document.querySelector('.upload-chip').innerHTML=`<i class="fa fa-paperclip"></i> Media (${selectedFiles.length}/5)`;
    const dt=new DataTransfer(); selectedFiles.forEach(f=>dt.items.add(f)); document.getElementById('media-input').files=dt.files;
}
</script>
</body>
</html>
