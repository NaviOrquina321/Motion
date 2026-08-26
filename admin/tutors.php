<?php
// admin/tutors.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDBConnection();

// Action process (Verify / Unverify tutor)
$action = $_GET['action'] ?? '';
$tutorId = (int)($_GET['id'] ?? 0);

if ($tutorId > 0 && in_array($action, ['verify', 'unverify'])) {
    $status = ($action === 'verify') ? 'verified' : 'pending';
    $stmtUpd = $pdo->prepare("UPDATE tutors SET verification_status = ? WHERE id = ?");
    $stmtUpd->execute([$status, $tutorId]);

    $pdo->prepare("INSERT INTO reports_logs (action, details, user_id) VALUES ('TUTOR_VERIFICATION_UPDATE', 'Tutor ID $tutorId status set to $status', ?)")->execute([$_SESSION['user_id']]);

    header("Location: tutors.php");
    exit;
}

// Fetch tutors list
$stmt = $pdo->query("
    SELECT t.*, u.name, u.email, u.profile_photo, u.status as user_status
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");
$tutors = $stmt->fetchAll();

$pageTitle = "Tutor Management";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-user-shield me-1"></i> Educator Verification
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Tutor Management</h2>
            <p class="text-muted mb-0">Approve tutor qualifications and manage verified badges on TutorLink.</p>
        </div>

        <div class="card-static p-4 rounded-4 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light font-size-xs text-muted">
                        <tr>
                            <th>TUTOR</th>
                            <th>TITLE & EDUCATION</th>
                            <th>HOURLY RATE</th>
                            <th>RATING</th>
                            <th>VERIFICATION</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutors as $t): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($t['profile_photo']); ?>" class="rounded-circle object-fit-cover" width="40" height="40" alt="Tutor">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($t['name']); ?></h6>
                                            <span class="text-muted font-size-xs"><?php echo htmlspecialchars($t['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark font-size-sm d-block"><?php echo htmlspecialchars($t['title']); ?></span>
                                    <span class="text-muted font-size-xs"><?php echo htmlspecialchars($t['education']); ?></span>
                                </td>
                                <td class="fw-bold text-dark font-size-sm">₱<?php echo number_format($t['hourly_rate'], 2); ?>/hr</td>
                                <td class="text-warning font-size-sm fw-bold">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format($t['rating'], 1); ?>
                                </td>
                                <td>
                                    <?php if ($t['verification_status'] === 'verified'): ?>
                                        <span class="pill-badge pill-badge-success"><i class="fa-solid fa-circle-check me-1"></i> Verified Tutor</span>
                                    <?php else: ?>
                                        <span class="pill-badge pill-badge-warning"><i class="fa-solid fa-clock me-1"></i> Pending Verification</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['verification_status'] === 'verified'): ?>
                                        <a href="tutors.php?action=unverify&id=<?php echo $t['id']; ?>" class="btn btn-outline-custom btn-sm py-1 font-size-xs text-danger">Revoke Verification</a>
                                    <?php else: ?>
                                        <a href="tutors.php?action=verify&id=<?php echo $t['id']; ?>" class="btn btn-primary-custom btn-sm py-1 font-size-xs">Approve & Verify</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
