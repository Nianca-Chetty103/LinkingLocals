<?php
session_start();
include "database.php";

// Helper functions for role verification
function normalizeRole($role) {
    return strtolower(trim((string) $role));
}

function isAdminRole($role) {
    $normalized = normalizeRole($role);
    return $normalized === 'admin' || strpos($normalized, 'admin') !== false;
}

// Security Check
$currentRole = '';
if (!empty($_SESSION['role'])) {
    $currentRole = normalizeRole($_SESSION['role']);
} elseif (!empty($_SESSION['user']['role'])) {
    $currentRole = normalizeRole($_SESSION['user']['role']);
}

if (!isAdminRole($currentRole)) {
    header('Location: login.php');
    exit;
}

// Check if admin user is blocked
if (!empty($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userRecord) {
        $status = isset($userRecord['status']) ? strtolower(trim($userRecord['status'])) : '';
        if ($status === 'blocked') {
            session_destroy();
            header('Location: login.php?blocked=1');
            exit;
        }
    }
}

// View handling
$view = $_GET['view'] ?? 'users';
$validViews = ['users', 'services', 'reviews'];
$view = in_array($view, $validViews, true) ? $view : 'users';

$pageTitles = [
    'users'    => 'Users Management',
    'services' => 'Service Listings',
    'reviews'  => 'Reviews Management',
];

$pageHeading = $pageTitles[$view];
$result = null;
$error = null;

// Database queries based on selected view
if ($view === 'users') {
    $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
    if (!$result) $error = $conn->error;
} elseif ($view === 'services') {
    $result = $conn->query(
        "SELECT p.id, p.title, p.description, p.price, p.image, p.created_at, u.name AS provider_name
         FROM posts p
         LEFT JOIN users u ON p.user_id = u.id
         ORDER BY p.created_at DESC"
    );
    if (!$result) $error = $conn->error;
} elseif ($view === 'reviews') {
    $result = $conn->query(
        "SELECT r.id, r.rating, r.review, r.created_at,
                u.name AS reviewer_name,
                p.title AS post_title
         FROM reviews r
         LEFT JOIN users u ON r.user_id = u.id
         LEFT JOIN posts p ON r.post_id = p.id
         ORDER BY r.created_at DESC"
    );
    if (!$result) $error = $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LinkingLocals - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="?view=users" class="<?= $view === 'users' ? 'active' : '' ?>">Users</a>
    <a href="?view=services" class="<?= $view === 'services' ? 'active' : '' ?>">Services</a>
    <a href="?view=reviews" class="<?= $view === 'reviews' ? 'active' : '' ?>">Reviews</a>
    <a href="home.php?action=logout">Logout</a>
</div>

<div class="main">
    <h1><?= htmlspecialchars($pageHeading) ?></h1>

    <?php if ($error): ?>
        <div class="message error" style="background:#fee2e2; padding:10px; color:#b91c1c; border-radius:8px; margin-bottom:20px;">
            Database error: <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <?php if ($view === 'users'): ?>
                        <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th>
                    <?php elseif ($view === 'services'): ?>
                        <th>Title</th><th>Provider</th><th>Price</th><th>Created</th>
                    <?php else: ?>
                        <th>Reviewer</th><th>Service</th><th>Rating</th><th>Comment</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php if ($view === 'users'): ?>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td><span class="status <?= htmlspecialchars($row['status'] ?? 'active') ?>"><?= htmlspecialchars($row['status'] ?? 'active') ?></span></td>
                            <td>
                                <button class="btn-approve" onclick="approve(<?= (int)$row['id'] ?>)">Approve</button>
                                <button class="btn-block" onclick="blockUser(<?= (int)$row['id'] ?>)">Block</button>
                            </td>
                        <?php elseif ($view === 'services'): ?>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['provider_name']) ?></td>
                            <td>R <?= htmlspecialchars($row['price']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <?php else: ?>
                            <td><?= htmlspecialchars($row['reviewer_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($row['post_title'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($row['rating']) ?></td>
                            <td><?= htmlspecialchars(substr($row['review'], 0, 50)) ?>...</td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function approve(id) { fetch("Approved.php?id=" + id).then(() => location.reload()); }
    function blockUser(id) { fetch("block.php?id=" + id).then(() => location.reload()); }
</script>
</body>
</html>