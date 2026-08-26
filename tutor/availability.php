<?php
// tutor/availability.php
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

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save updated weekly schedule
    $pdo->prepare("DELETE FROM tutor_availability WHERE tutor_id = ?")->execute([$tutorId]);

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $stmtIns = $pdo->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");

    foreach ($days as $day) {
        if (isset($_POST['avail_' . $day])) {
            $startTime = $_POST['start_' . $day] ?? '09:00:00';
            $endTime = $_POST['end_' . $day] ?? '17:00:00';
            $stmtIns->execute([$tutorId, $day, $startTime, $endTime]);
        }
    }
    $msg = "Availability updated successfully!";
}

// Fetch availability
$stmtA = $pdo->prepare("SELECT * FROM tutor_availability WHERE tutor_id = ?");
$stmtA->execute([$tutorId]);
$avails = $stmtA->fetchAll();

$availMap = [];
foreach ($avails as $a) {
    $availMap[$a['day_of_week']] = $a;
}

$pageTitle = "My Availability";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-regular fa-clock me-1"></i> Weekly Schedule
            </span>
            <h2 class="fw-extrabold text-dark mb-1">My Availability</h2>
            <p class="text-muted mb-0">Set the days and hours when students can book sessions with you.</p>
        </div>

        <div class="card-static p-4 p-md-5 rounded-4 shadow-sm max-w-3xl">
            <?php if ($msg): ?>
                <div class="alert alert-success rounded-3 mb-4"><i class="fa-solid fa-check-circle me-1"></i> <?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST" action="availability.php">
                <?php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day):
                    $isAvail = isset($availMap[$day]);
                    $start = $isAvail ? $availMap[$day]['start_time'] : '09:00';
                    $end = $isAvail ? $availMap[$day]['end_time'] : '17:00';
                ?>
                    <div class="p-3 bg-light rounded-4 mb-3 border d-flex flex-wrap align-items-center justify-content-between g-3">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input" type="checkbox" name="avail_<?php echo $day; ?>" id="check_<?php echo $day; ?>" <?php echo $isAvail ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold text-dark ms-2" for="check_<?php echo $day; ?>"><?php echo $day; ?></label>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <input type="time" name="start_<?php echo $day; ?>" class="form-control form-control-sm rounded-3" value="<?php echo substr($start, 0, 5); ?>">
                            <span class="text-muted font-size-xs">to</span>
                            <input type="time" name="end_<?php echo $day; ?>" class="form-control form-control-sm rounded-3" value="<?php echo substr($end, 0, 5); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary-custom py-3 px-5 font-weight-bold mt-3">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Availability
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
