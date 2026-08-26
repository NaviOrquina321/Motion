<?php
// admin/payments.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT p.*, u.name as student_name, u_tu.name as tutor_name, s.name as subject_name
    FROM payments p
    JOIN students st ON p.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN bookings b ON p.booking_id = b.id
    JOIN tutors tu ON b.tutor_id = tu.id
    JOIN users u_tu ON tu.user_id = u_tu.id
    JOIN subjects s ON b.subject_id = s.id
    ORDER BY p.paid_at DESC
");
$payments = $stmt->fetchAll();

$pageTitle = "All Payment Transactions";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-receipt me-1"></i> Financial Audit
            </span>
            <h2 class="fw-extrabold text-dark mb-1">GCash Payment Transactions</h2>
            <p class="text-muted mb-0">Master audit ledger of all GCash payments and platform fee collections.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light font-size-xs text-muted">
                        <tr>
                            <th>TRANSACTION ID</th>
                            <th>STUDENT</th>
                            <th>TUTOR & SUBJECT</th>
                            <th>GCASH ACCOUNT</th>
                            <th>TUTOR FEE</th>
                            <th>SERVICE FEE</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($pay['transaction_id']); ?></span>
                                    <span class="text-muted font-size-xs d-block"><?php echo htmlspecialchars($pay['reference_number']); ?></span>
                                </td>
                                <td class="fw-semibold text-dark font-size-sm"><?php echo htmlspecialchars($pay['student_name']); ?></td>
                                <td>
                                    <span class="fw-semibold text-primary font-size-sm d-block"><?php echo htmlspecialchars($pay['tutor_name']); ?></span>
                                    <span class="text-muted font-size-xs"><?php echo htmlspecialchars($pay['subject_name']); ?></span>
                                </td>
                                <td class="font-size-sm text-muted"><?php echo htmlspecialchars($pay['account_number']); ?></td>
                                <td class="font-size-sm">₱<?php echo number_format($pay['amount'], 2); ?></td>
                                <td class="font-size-sm text-muted">₱<?php echo number_format($pay['service_fee'], 2); ?></td>
                                <td class="fw-extrabold text-dark font-size-sm">₱<?php echo number_format($pay['total_amount'], 2); ?></td>
                                <td><span class="pill-badge pill-badge-success"><i class="fa-solid fa-check me-1"></i> Paid</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
