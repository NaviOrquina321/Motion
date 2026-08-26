<?php
// admin/students.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT s.*, u.name, u.email, u.profile_photo, u.status as user_status, u.created_at as joined_date,
           (SELECT COUNT(*) FROM bookings WHERE student_id = s.id) as sessions_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY u.created_at DESC
");
$students = $stmt->fetchAll();

$pageTitle = "Student Management";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-users me-1"></i> User Directory
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Student Management</h2>
            <p class="text-muted mb-0">View registered student accounts, learning preferences, and booking counts.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light font-size-xs text-muted">
                        <tr>
                            <th>STUDENT</th>
                            <th>EDUCATION LEVEL</th>
                            <th>LEARNING STYLE</th>
                            <th>BUDGET</th>
                            <th>SESSIONS</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($st['profile_photo']); ?>" class="rounded-circle object-fit-cover" width="40" height="40" alt="Student">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($st['name']); ?></h6>
                                            <span class="text-muted font-size-xs"><?php echo htmlspecialchars($st['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border font-size-xs"><?php echo htmlspecialchars($st['education_level']); ?></span></td>
                                <td><span class="badge bg-primary-subtle text-primary font-size-xs"><?php echo htmlspecialchars($st['learning_style']); ?></span></td>
                                <td class="fw-bold text-dark font-size-sm">₱<?php echo number_format($st['budget'], 2); ?>/hr</td>
                                <td><span class="fw-bold text-dark font-size-sm"><?php echo $st['sessions_count']; ?></span></td>
                                <td><span class="pill-badge pill-badge-success"><i class="fa-solid fa-circle me-1"></i> Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
