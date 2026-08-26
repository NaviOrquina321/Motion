<?php
// admin/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// System KPIs
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTutors = $pdo->query("SELECT COUNT(*) FROM tutors")->fetchColumn();
$totalSessions = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = (float)($pdo->query("SELECT SUM(total_amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0.00);

// Recent activity logs
$logs = $pdo->query("SELECT * FROM reports_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = "Admin Dashboard & Overview";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">
                    <i class="fa-solid fa-chart-pie me-1"></i> System Administration
                </span>
                <h2 class="fw-extrabold text-dark mb-1">Administrator Dashboard</h2>
                <p class="text-muted mb-0">Platform overview, user statistics, session analytics, and revenue metrics.</p>
            </div>
            <a href="reports.php" class="btn btn-primary-custom">
                <i class="fa-solid fa-file-export me-1"></i> Full Monitoring Reports
            </a>
        </div>

        <!-- 4 KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-primary">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Total Students</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $totalStudents; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-info">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Total Tutors</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $totalTutors; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-warning">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Tutoring Sessions</span>
                    <h2 class="fw-extrabold text-dark mb-0"><?php echo $totalSessions; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-static p-4 rounded-4 shadow-sm border-start border-4 border-success">
                    <span class="text-muted font-size-xs fw-semibold text-uppercase d-block mb-1">Platform Revenue</span>
                    <h2 class="fw-extrabold text-success mb-0">₱<?php echo number_format($totalRevenue, 2); ?></h2>
                </div>
            </div>
        </div>

        <!-- Chart.js Analytics Section -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i> Monthly Sessions Growth</h5>
                        <span class="badge bg-light text-muted border">2026 Trend</span>
                    </div>
                    <div style="height: 260px;">
                        <canvas id="sessionsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-chart-donut text-primary me-2"></i> Subject Distribution</h5>
                    <div style="height: 260px;">
                        <canvas id="subjectDoughnut"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Audit Logs -->
        <div class="card-static p-4 rounded-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Recent System Activity</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light font-size-xs text-muted">
                        <tr>
                            <th>ACTION</th>
                            <th>DETAILS</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><span class="badge bg-primary-subtle text-primary font-size-xs"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td class="font-size-sm text-dark"><?php echo htmlspecialchars($log['details']); ?></td>
                                <td class="font-size-xs text-muted"><?php echo date('M d, Y • h:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sessions Trend Line Chart
    const ctxLine = document.getElementById('sessionsChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
            datasets: [{
                label: 'Sessions Conducted',
                data: [65, 85, 120, 150, 190, 240, 310, 420],
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79, 70, 229, 0.08)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Subject Breakdown Doughnut Chart
    const ctxPie = document.getElementById('subjectDoughnut').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Mathematics', 'English', 'Programming', 'Science', 'Accounting'],
            datasets: [{
                data: [35, 25, 20, 12, 8],
                backgroundColor: ['#4F46E5', '#6366F1', '#22C55E', '#F59E0B', '#EF4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
