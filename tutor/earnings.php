<?php
// tutor/earnings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('tutor');

$pdo = getDBConnection();
$stmtT = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$stmtT->execute([$_SESSION['user_id']]);
$tutorId = $stmtT->fetchColumn();

// Fetch earnings transactions
$stmt = $pdo->prepare("
    SELECT p.*, b.booking_date, s.name as subject_name, u.name as student_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.tutor_id = ? AND p.status = 'paid'
    ORDER BY p.paid_at DESC
");
$stmt->execute([$tutorId]);
$earningsList = $stmt->fetchAll();

$totalEarned = 0;
foreach ($earningsList as $e) {
    $totalEarned += (float)$e['amount'];
}

$pageTitle = "Earnings & Financial Reports";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-wallet me-1"></i> Financial Summary
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Earnings & Payouts</h2>
            <p class="text-muted mb-0">Track your completed session revenue and GCash payout history.</p>
        </div>

        <!-- Earnings KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Total Net Earnings</span>
                    <h2 class="fw-extrabold text-success mb-0">₱<?php echo number_format($totalEarned, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Completed Transactions</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo count($earningsList); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Default Payout Method</span>
                    <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-mobile-screen me-1"></i> GCash Express</h5>
                </div>
            </div>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-receipt text-primary me-2"></i> Transaction History</h5>

            <?php if (count($earningsList) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light font-size-xs text-muted">
                            <tr>
                                <th>TRANSACTION ID</th>
                                <th>STUDENT</th>
                                <th>SUBJECT</th>
                                <th>DATE PAID</th>
                                <th>EARNED AMOUNT</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earningsList as $item): ?>
                                <tr>
                                    <td class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($item['transaction_id']); ?></td>
                                    <td class="fw-semibold text-dark font-size-sm"><?php echo htmlspecialchars($item['student_name']); ?></td>
                                    <td class="text-muted font-size-sm"><?php echo htmlspecialchars($item['subject_name']); ?></td>
                                    <td class="font-size-sm text-muted"><?php echo date('M d, Y • h:i A', strtotime($item['paid_at'])); ?></td>
                                    <td class="fw-bold text-success font-size-sm">₱<?php echo number_format($item['amount'], 2); ?></td>
                                    <td><span class="pill-badge pill-badge-success"><i class="fa-solid fa-check me-1"></i> Received</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No payment transactions recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
