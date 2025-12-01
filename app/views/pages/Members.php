<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Variables expected: $user, $users (array), $pagination (array), $search (string), $unreadCount, $notifications
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Members - Admin Panel</title>
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

/* --- Header & Layout --- */
.members-shell {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* --- Card Container --- */
.card {
    background: rgba(15, 23, 42, 0.85);
    border-radius: 20px;
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(2,6,23,0.5);
}

/* --- Actions Row --- */
.actions-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.actions-row input[type="text"] {
    padding: 0.6rem 1rem;
    border-radius: 999px;
    border: 1px solid rgba(59,130,246,0.3);
    background: rgba(148,163,184,0.1);
    color: #f3f4f6;
    outline: none;
    font-size: 0.9rem;
}

/* --- Buttons --- */
.btn {
    border: none;
    border-radius: 12px;
    padding: 0.6rem 1.25rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #2563eb;
    color: #fff;
}

.add-btn {
    min-width: 150px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn:hover {
    filter: brightness(1.1);
}

/* --- Table --- */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border-radius: 16px;
    border: 1px solid rgba(59,130,246,0.2);
    margin-bottom: 1rem;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: rgba(13,26,54,0.85);
}

th {
    text-align: left;
    padding: 0.75rem 1rem;
    color: #f3f4f6;
    font-size: 0.85rem;
    text-transform: uppercase;
}

td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(59,130,246,0.2);
    color: #f3f4f6;
}

/* --- Actions inside table --- */
.row-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-pill {
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid rgba(59,130,246,0.3);
    background: rgba(59,130,246,0.1);
    color: #f3f4f6;
    transition: 0.2s;
}

.btn-pill:hover {
    background: rgba(59,130,246,0.2);
}

.btn-pill.danger {
    border-color: #f87171;
    color: #f87171;
}

.btn-pill.danger:disabled {
    opacity: 0.5;
    cursor: default;
}

/* --- Tags --- */
.tag {
    padding: 0.2rem 0.5rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #f3f4f6; /* keep text color */
    background: transparent; /* removed background */
}

.tag-admin,
.tag-user {
    background: transparent; /* override colored backgrounds */
    color: #f3f4f6; /* keep same text color for both */
}


/* --- Flash Messages --- */
.flash {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    margin: 0.75rem 0;
    font-weight: 600;
}

.flash-success {
    background: rgba(34,197,94,0.12);
    color: #86efac;
    border: 1px solid rgba(34,197,94,0.2);
}

.flash-error {
    background: rgba(239,68,68,0.12);
    color: #fecaca;
    border: 1px solid rgba(239,68,68,0.2);
}

/* --- Pagination --- */
.pagination {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

.page-btn {
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    background: transparent;
    color: #f3f4f6;
    border: 1px solid rgba(59,130,246,0.3);
    text-decoration: none;
    font-size: 0.85rem;
}

.page-btn.active {
    background: rgba(59,130,246,0.25);
}

/* --- Empty table --- */
.empty {
    text-align: center;
    padding: 1rem;
    color: #94a3b8;
}
</style>
</head>
<body>

<div class="members-shell">
    <?php
    $user = $user ?? null;
    $unreadCount = $unreadCount ?? 0;
    $notifications = $notifications ?? [];
    include_once __DIR__ . '/../components/Header.php';
    ?>

    <section class="card">
        <div class="actions-row">
            <form class="search-form" method="get" action="">
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search users..." />
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <a href="/admin/members/create" class="btn btn-primary add-btn">Add Account</a>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($error)): ?>
          <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="4" class="empty">No user records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($member['username'] ?? '') ?></td>
                                <td><?= htmlspecialchars($member['email'] ?? '') ?></td>
                                <td>
                                    <span class="tag <?= ($member['role'] ?? '') === 'admin' ? 'tag-admin' : 'tag-user' ?>">
                                        <?= htmlspecialchars(ucfirst($member['role'] ?? 'user')) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="row-actions">
                                        <a class="btn-pill" href="/admin/members/<?= (int)($member['id'] ?? 0) ?>/edit">Edit</a>
                                        <?php if (($user['id'] ?? 0) !== ($member['id'] ?? 0)): ?>
                                            <form method="post" action="/admin/members/<?= (int)($member['id'] ?? 0) ?>/delete" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <button type="submit" class="btn-pill danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-pill danger" disabled>Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <div class="pagination">
                <?php $cur = (int)($pagination['page'] ?? 1); $tp = (int)($pagination['total_pages'] ?? 1); ?>
                <a class="page-btn" href="?page=<?= max(1, $cur-1) ?><?= $search ? '&q='.urlencode($search) : '' ?>" <?= $cur===1 ? 'disabled' : '' ?>>← Prev</a>
                <?php for ($p=1;$p<=$tp;$p++): ?>
                    <a class="page-btn <?= $p===$cur ? 'active' : '' ?>" href="?page=<?= $p ?><?= $search ? '&q='.urlencode($search) : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="?page=<?= min($tp, $cur+1) ?><?= $search ? '&q='.urlencode($search) : '' ?>" <?= $cur===$tp ? 'disabled' : '' ?>>Next →</a>
            </div>
        <?php endif; ?>

    </section>
</div>

</body>
</html>
