<?php
// student/notifications.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Mark all as read when opening notification page
$stmtRead = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmtRead->execute([$_SESSION['user_id']]);

$pageTitle = "Notifications Center";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">
                    <i class="fa-regular fa-bell me-1"></i> System Activity
                </span>
                <h2 class="fw-extrabold text-dark mb-1">Notifications Center</h2>
                <p class="text-muted mb-0">Stay updated on your session alerts, booking requests, and payments.</p>
            </div>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <?php if (count($notifications) > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($notifications as $n):
                        $icon = 'fa-bell text-primary';
                        if ($n['type'] === 'booking') $icon = 'fa-calendar-check text-success';
                        if ($n['type'] === 'payment') $icon = 'fa-credit-card text-info';
                    ?>
                        <div class="p-3 bg-light rounded-4 border d-flex align-items-start gap-3">
                            <div class="bg-white rounded-circle p-2 border shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid <?php echo $icon; ?> fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($n['title']); ?></h6>
                                    <span class="text-muted font-size-xs"><?php echo date('M d, Y • h:i A', strtotime($n['created_at'])); ?></span>
                                </div>
                                <p class="text-muted font-size-sm mb-0"><?php echo htmlspecialchars($n['message']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa-regular fa-bell-slash text-muted fs-1 mb-3"></i>
                    <h5 class="fw-bold">No Notifications</h5>
                    <p class="text-muted mb-0">You're all caught up! Booking alerts and payment updates will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
