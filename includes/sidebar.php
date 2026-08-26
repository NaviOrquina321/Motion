<?php
// includes/sidebar.php
$user = getCurrentUser();
$role = $user['role'] ?? 'student';
$current_page = basename($_SERVER['PHP_SELF']);
$baseUrl = $baseUrl ?? '';

$unreadNotifCount = 0;
if ($user) {
    $pdo = getDBConnection();
    $stmtN = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtN->execute([$user['id']]);
    $unreadNotifCount = $stmtN->fetchColumn();
}
?>
<div class="sidebar-wrapper p-3">
    <div class="d-flex align-items-center gap-2 mb-4 px-2 pt-2">
        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <span class="fw-bold fs-4 text-dark">Tutor<span class="text-primary">Link</span></span>
    </div>

    <!-- User Mini Profile -->
    <div class="bg-light p-3 rounded-4 mb-4 d-flex align-items-center gap-3 border">
        <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200'); ?>"
             class="rounded-circle object-fit-cover" width="44" height="44" alt="User">
        <div class="overflow-hidden">
            <h6 class="mb-0 fw-bold text-truncate"><?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?></h6>
            <span class="badge bg-primary-subtle text-primary text-capitalize font-size-xs fw-semibold">
                <?php echo htmlspecialchars($role); ?>
            </span>
        </div>
    </div>

    <nav class="flex-grow-1">
        <?php if ($role === 'student'): ?>
            <a href="<?php echo $baseUrl; ?>/student/dashboard.php" class="nav-link-custom <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>/student/find-tutor.php" class="nav-link-custom <?php echo $current_page === 'find-tutor.php' || $current_page === 'tutor-profile.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-magnifying-glass"></i> Find Tutor
            </a>
            <a href="<?php echo $baseUrl; ?>/student/ai-matching.php" class="nav-link-custom <?php echo $current_page === 'ai-matching.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i> AI Matching
            </a>
            <a href="<?php echo $baseUrl; ?>/student/calendar.php" class="nav-link-custom <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-calendar"></i> Calendar
            </a>
            <a href="<?php echo $baseUrl; ?>/student/sessions.php" class="nav-link-custom <?php echo $current_page === 'sessions.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-book-open"></i> My Sessions
            </a>
            <a href="<?php echo $baseUrl; ?>/student/messages.php" class="nav-link-custom <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-comments"></i> Messages
            </a>
            <a href="<?php echo $baseUrl; ?>/student/notifications.php" class="nav-link-custom <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-bell"></i> Notifications
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unreadNotifCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/student/payments.php" class="nav-link-custom <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-credit-card"></i> Payments
            </a>

        <?php elseif ($role === 'tutor'): ?>
            <a href="<?php echo $baseUrl; ?>/tutor/dashboard.php" class="nav-link-custom <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/availability.php" class="nav-link-custom <?php echo $current_page === 'availability.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-clock"></i> My Availability
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/bookings.php" class="nav-link-custom <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i> Booking Requests
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/calendar.php" class="nav-link-custom <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-calendar"></i> Calendar
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/students.php" class="nav-link-custom <?php echo $current_page === 'students.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-graduate"></i> My Students
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/earnings.php" class="nav-link-custom <?php echo $current_page === 'earnings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-wallet"></i> Earnings
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/reviews.php" class="nav-link-custom <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-star"></i> Reviews
            </a>
            <a href="<?php echo $baseUrl; ?>/tutor/notifications.php" class="nav-link-custom <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-bell"></i> Notifications
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unreadNotifCount; ?></span>
                <?php endif; ?>
            </a>

        <?php elseif ($role === 'admin'): ?>
            <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="nav-link-custom <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i> Admin Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/students.php" class="nav-link-custom <?php echo $current_page === 'students.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Student Management
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/tutors.php" class="nav-link-custom <?php echo $current_page === 'tutors.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-tie"></i> Tutor Management
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/bookings.php" class="nav-link-custom <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
                <i class="fa-regular fa-calendar-check"></i> All Bookings
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/payments.php" class="nav-link-custom <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-receipt"></i> Payments
            </a>
            <a href="<?php echo $baseUrl; ?>/admin/reports.php" class="nav-link-custom <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> Monitoring Reports
            </a>
        <?php endif; ?>
    </nav>

    <div class="pt-3 border-top mt-auto">
        <a href="<?php echo $baseUrl; ?>/logout.php" class="nav-link-custom text-danger">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
        </a>
    </div>
</div>
