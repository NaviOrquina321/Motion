<?php
// student/booking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo = getDBConnection();
$tutorId = (int)($_GET['tutor_id'] ?? 1);
$subjectId = (int)($_GET['subject_id'] ?? 0);

// Fetch tutor details
$stmt = $pdo->prepare("
    SELECT t.*, u.name, u.profile_photo, u.email
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$tutorId]);
$tutor = $stmt->fetch();

if (!$tutor) {
    die("Selected tutor is unavailable.");
}

// Fetch tutor subjects
$stmtSub = $pdo->prepare("
    SELECT s.* FROM subjects s
    JOIN tutor_subjects ts ON s.id = ts.subject_id
    WHERE ts.tutor_id = ?
");
$stmtSub->execute([$tutorId]);
$tutorSubjects = $stmtSub->fetchAll();

$pageTitle = "Book Session with " . htmlspecialchars($tutor['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-calendar-check me-1"></i> Scheduling & Booking
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Book a Tutoring Session</h2>
            <p class="text-muted mb-0">Select your subject, date, and preferred time slot for 1-on-1 learning.</p>
        </div>

        <div class="row g-4">
            <!-- Tutor Summary Card -->
            <div class="col-lg-4">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($tutor['profile_photo']); ?>" class="rounded-circle object-fit-cover shadow-sm mb-3" width="90" height="90" alt="Tutor">
                        <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($tutor['name']); ?></h5>
                        <span class="text-primary font-size-sm fw-semibold"><?php echo htmlspecialchars($tutor['title']); ?></span>
                        <div class="text-warning font-size-xs mt-1">
                            <i class="fa-solid fa-star"></i> <?php echo number_format($tutor['rating'], 1); ?> (<?php echo $tutor['total_reviews']; ?> reviews)
                        </div>
                    </div>

                    <div class="border-top pt-3 font-size-sm">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Hourly Rate:</span>
                            <span class="fw-bold text-dark">₱<?php echo number_format($tutor['hourly_rate'], 2); ?> / hr</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Response Time:</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($tutor['response_time']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Verification:</span>
                            <span class="text-success fw-bold"><i class="fa-solid fa-check-circle"></i> Verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="col-lg-8">
                <div class="card-static p-4 rounded-4 shadow-sm">
                    <div id="bookingAlert" class="alert d-none rounded-3" role="alert"></div>

                    <form id="bookingForm">
                        <input type="hidden" name="tutor_id" value="<?php echo $tutor['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Subject</label>
                            <select name="subject_id" class="form-select form-select-lg rounded-3" required>
                                <?php foreach ($tutorSubjects as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>" <?php echo $subjectId === $sub['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub['name']); ?> (<?php echo htmlspecialchars($sub['category']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Select Date</label>
                                <input type="date" name="booking_date" class="form-control form-control-lg rounded-3"
                                       min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Session Duration</label>
                                <select name="duration_hours" id="durationSelect" class="form-select form-select-lg rounded-3">
                                    <option value="1" selected>1 Hour</option>
                                    <option value="2">2 Hours</option>
                                    <option value="3">3 Hours</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Available Time Slots</label>
                            <div class="d-flex flex-wrap gap-2" id="timeSlotsContainer">
                                <input type="radio" class="btn-check" name="start_time" id="time_0900" value="09:00:00" checked>
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_0900">09:00 AM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1000" value="10:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1000">10:00 AM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1100" value="11:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1100">11:00 AM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1300" value="13:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1300">01:00 PM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1400" value="14:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1400">02:00 PM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1500" value="15:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1500">03:00 PM</label>

                                <input type="radio" class="btn-check" name="start_time" id="time_1600" value="16:00:00">
                                <label class="btn btn-outline-primary rounded-3 px-3 py-2 font-weight-semibold" for="time_1600">04:00 PM</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Session Topic / Learning Notes (Optional)</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Tell the tutor what specific topics or exam prep you would like to cover..."></textarea>
                        </div>

                        <!-- Price summary calculation -->
                        <div class="p-3 bg-light rounded-4 mb-4 d-flex align-items-center justify-content-between border">
                            <div>
                                <span class="text-muted font-size-sm d-block">Total Session Fee</span>
                                <span class="font-size-xs text-muted">Includes free schedule confirmation & calendar sync</span>
                            </div>
                            <div class="text-end">
                                <span class="fw-extrabold fs-2 text-primary" id="calculatedTotal">₱<?php echo number_format($tutor['hourly_rate'], 2); ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100 py-3 font-weight-bold">
                            Proceed to Payment <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hourlyRate = <?php echo (float)$tutor['hourly_rate']; ?>;
    const durationSelect = document.getElementById('durationSelect');
    const calculatedTotal = document.getElementById('calculatedTotal');
    const form = document.getElementById('bookingForm');
    const alertBox = document.getElementById('bookingAlert');

    durationSelect.addEventListener('change', function() {
        const hours = parseInt(this.value);
        const total = (hourlyRate * hours).toFixed(2);
        calculatedTotal.innerText = '₱' + total;
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        alertBox.classList.add('d-none');

        const formData = new FormData(form);

        fetch('../api/booking.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alertBox.className = 'alert alert-success rounded-3';
                alertBox.innerText = 'Booking requested successfully! Redirecting to GCash Payment...';
                alertBox.classList.remove('d-none');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                alertBox.className = 'alert alert-danger rounded-3';
                alertBox.innerText = data.message;
                alertBox.classList.remove('d-none');
            }
        })
        .catch(err => {
            alertBox.className = 'alert alert-danger rounded-3';
            alertBox.innerText = 'Server error processing booking.';
            alertBox.classList.remove('d-none');
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
