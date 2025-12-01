<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Props passed from Controller
$user ??= null;
$unreadCount ??= 0;
$categories ??= [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Categories</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* --- Reset & Body --- */
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    background: #0f172a;
    color: #f3f4f6;
}

/* --- Shell --- */
.page-shell {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* --- Card Container (same as admin .card) --- */
.card {
    background: rgba(15, 23, 42, 0.85);
    border-radius: 20px;
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem 2.5rem;
    box-shadow: 0 10px 30px rgba(2,6,23,0.5);
    border: 1px solid rgba(59,130,246,0.20);
}

/* --- Header --- */
.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.85rem;
    font-weight: 700;
    color: #f3f4f6;
}

.page-header p {
    margin-top: 0.45rem;
    font-size: 0.95rem;
    color: #94a3b8;
}

/* --- Category Grid --- */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1.5rem;
}

/* --- Category Card --- */
.category-card {
    background: rgba(30, 41, 59, 0.55);
    border-radius: 18px;
    padding: 1.25rem 1.6rem;
    display: flex;
    justify-content: space-between;
    align-items: center;

    color: #f3f4f6;
    font-size: 1rem;
    font-weight: 600;

    text-decoration: none;
    border: 1px solid rgba(59,130,246,0.20);
    transition: all 0.25s ease;
    box-shadow: 0 4px 18px rgba(0,0,0,0.35);
}

.category-card i {
    opacity: 0.7;
    transition: 0.2s ease-in-out;
}

/* --- Hover Effects --- */
.category-card:hover {
    transform: translateY(-4px);
    border-color: rgba(59,130,246,0.45);
    box-shadow: 0 8px 28px rgba(59,130,246,0.25);
}

.category-card:hover i {
    opacity: 1;
    transform: translateX(4px);
}
</style>
</head>
<body>

<div class="page-shell">
    <?php include __DIR__ . '/../components/Header.php'; ?>

    <section class="card">
        <div class="page-header">
            <h1>Explore Categories</h1>
            <p>Choose a topic to filter your feed.</p>
        </div>

        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <a href="/categories/filter/<?= urlencode($category) ?>" class="category-card">
                        <span><?= htmlspecialchars($category) ?></span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty" style="grid-column:1/-1; text-align:center; padding:2rem 0; color:#94a3b8;">
                    No categories found.
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

</body>
</html>
