<?php
// admin/reports.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// 1. Session Stats Calculation
$totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$completedSessions = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$confirmedSessions = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$pendingSessions = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$cancelledSessions = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

$completionRate = $totalSessions > 0 ? round(($completedSessions + $confirmedSessions) / $totalSessions * 100) : 100;

// 2. Financial Summary Calculation
$grossRevenue = (float)($pdo->query("SELECT SUM(total_amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0.00);
$tutorPayouts = (float)($pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0.00);
$platformFees = (float)($pdo->query("SELECT SUM(service_fee) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0.00);

$pageTitle = "Monitoring Reports & Analytics";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> System Governance & Tracking
                </span>
                <h2 class="fw-extrabold text-dark mb-1">Monitoring Reports</h2>
                <p class="text-muted mb-0">System performance reports, completion statistics, payment audit logs, and analytics.</p>
            </div>

            <button onclick="window.print()" class="btn btn-outline-custom">
                <i class="fa-solid fa-print me-1"></i> Export / Print Report
            </button>
        </div>

        <div class="row g-4">
            <!-- Session Report Card (Inspired by Section 19 of prompt) -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-column text-primary me-2"></i> Session Report</h5>
                        <span class="badge bg-primary-subtle text-primary fw-bold">Total: <?php echo $totalSessions; ?> Sessions</span>
                    </div>

                    <div class="row g-3 mb-4 text-center">
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted font-size-xs d-block">Completed</span>
                                <h4 class="fw-bold text-success mb-0"><?php echo $completedSessions; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted font-size-xs d-block">Confirmed</span>
                                <h4 class="fw-bold text-primary mb-0"><?php echo $confirmedSessions; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted font-size-xs d-block">Pending</span>
                                <h4 class="fw-bold text-warning mb-0"><?php echo $pendingSessions; ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted font-size-xs d-block">Cancelled</span>
                                <h4 class="fw-bold text-danger mb-0"><?php echo $cancelledSessions; ?></h4>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark font-size-sm mb-2">Completion Rate</h6>
                    <div class="progress mb-2" style="height: 12px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $completionRate; ?>%;"></div>
                    </div>
                    <span class="font-size-xs text-muted d-block text-end fw-bold"><?php echo $completionRate; ?>% Session Success Rate</span>
                </div>
            </div>

            <!-- Revenue Report Card -->
            <div class="col-lg-6">
                <div class="card-static p-4 rounded-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-wallet text-success me-2"></i> Financial & Revenue Report</h5>
                        <span class="badge bg-success-subtle text-success fw-bold">GCash Integrated</span>
                    </div>

                    <div class="d-flex flex-column gap-3 font-size-sm">
                        <div class="p-3 bg-light rounded-4 border d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted d-block font-size-xs">Gross GCash Collections</span>
                                <span class="fw-bold text-dark font-size-sm">Total payments processed</span>
                            </div>
                            <h4 class="fw-extrabold text-dark mb-0">₱<?php echo number_format($grossRevenue, 2); ?></h4>
                        </div>

                        <div class="p-3 bg-light rounded-4 border d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted d-block font-size-xs">Tutor Earnings Payouts</span>
                                <span class="fw-bold text-dark font-size-sm">Disbursed to educators</span>
                            </div>
                            <h4 class="fw-extrabold text-primary mb-0">₱<?php echo number_format($tutorPayouts, 2); ?></h4>
                        </div>

                        <div class="p-3 bg-light rounded-4 border d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted d-block font-size-xs">Platform Service Fee Revenue</span>
                                <span class="fw-bold text-dark font-size-sm">Net platform earnings</span>
                            </div>
                            <h4 class="fw-extrabold text-success mb-0">₱<?php echo number_format($platformFees, 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
