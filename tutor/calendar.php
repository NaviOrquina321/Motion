<?php
// tutor/calendar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('tutor');

$pdo = getDBConnection();

// Fetch tutor record
$stmtT = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$stmtT->execute([$_SESSION['user_id']]);
$tutorId = $stmtT->fetchColumn();

// Current Month/Year selection
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$dayOfWeek = date('w', $firstDayOfMonth); // 0 (Sun) to 6 (Sat)
$monthName = date('F', $firstDayOfMonth);

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

// Fetch confirmed and pending bookings for this tutor (using standard SQL date range for MySQL and SQLite compatibility)
$stmtBk = $pdo->prepare("
    SELECT b.*, u.name as student_name, s.name as subject_name
    FROM bookings b
    JOIN students st ON b.student_id = st.id
    JOIN users u ON st.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.tutor_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
    ORDER BY b.booking_date ASC, b.start_time ASC
");
$stmtBk->execute([$tutorId, $startDate, $endDate]);
$bookings = $stmtBk->fetchAll();

// Group bookings by date string YYYY-MM-DD
$eventsByDate = [];
foreach ($bookings as $b) {
    $eventsByDate[$b['booking_date']][] = $b;
}

$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear = $month == 12 ? $year + 1 : $year;

$pageTitle = "My Tutor Calendar Schedule";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <!-- Header Controls -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">
                    <i class="fa-regular fa-calendar me-1"></i> Educator Schedule
                </span>
                <h2 class="fw-extrabold text-dark mb-1">My Tutoring Calendar</h2>
                <p class="text-muted mb-0">Track upcoming student sessions, confirmed schedules, and lesson times.</p>
            </div>

            <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                <a href="calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-custom py-2 px-3">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <span class="fw-bold fs-5 text-dark px-3"><?php echo $monthName . ' ' . $year; ?></span>
                <a href="calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-custom py-2 px-3">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Legend -->
        <div class="d-flex align-items-center gap-3 mb-3 font-size-sm">
            <span class="d-flex align-items-center gap-1"><span class="badge bg-primary rounded-circle p-1"></span> Confirmed</span>
            <span class="d-flex align-items-center gap-1"><span class="badge bg-success rounded-circle p-1"></span> Completed</span>
            <span class="d-flex align-items-center gap-1"><span class="badge bg-warning rounded-circle p-1"></span> Pending</span>
        </div>

        <!-- Calendar Container -->
        <div class="card-static p-4 rounded-4 shadow-sm">
            <div class="calendar-grid mb-2">
                <div class="calendar-day-head">SUN</div>
                <div class="calendar-day-head">MON</div>
                <div class="calendar-day-head">TUE</div>
                <div class="calendar-day-head">WED</div>
                <div class="calendar-day-head">THU</div>
                <div class="calendar-day-head">FRI</div>
                <div class="calendar-day-head">SAT</div>
            </div>

            <div class="calendar-grid">
                <?php
                // Blank cells before 1st day
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    echo '<div class="calendar-day-cell other-month"></div>';
                }

                // Days of current month
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $currentDateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $isToday = ($currentDateStr === date('Y-m-d'));

                    echo '<div class="calendar-day-cell ' . ($isToday ? 'border-primary bg-primary-subtle' : '') . '">';
                    echo '<span class="calendar-day-number">' . $d . '</span>';

                    if (isset($eventsByDate[$currentDateStr])) {
                        foreach ($eventsByDate[$currentDateStr] as $event) {
                            $timeStr = date('h:i A', strtotime($event['start_time']));
                            $badgeClass = 'bg-primary text-white';
                            if ($event['status'] === 'completed') $badgeClass = 'bg-success text-white';
                            if ($event['status'] === 'pending') $badgeClass = 'bg-warning text-dark';

                            echo '<div class="calendar-event ' . $badgeClass . '" title="' . htmlspecialchars($event['student_name'] . ' - ' . $event['subject_name']) . '">';
                            echo '<strong>' . $timeStr . '</strong> ' . htmlspecialchars($event['student_name']);
                            echo '</div>';
                        }
                    }

                    echo '</div>';
                }

                // Remaining empty cells to complete week row
                $totalCells = $dayOfWeek + $daysInMonth;
                $remaining = (7 - ($totalCells % 7)) % 7;
                for ($j = 0; $j < $remaining; $j++) {
                    echo '<div class="calendar-day-cell other-month"></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
