<?php
// student/payments.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pdo = getDBConnection();

// Fetch student record
$stmtSt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmtSt->execute([$_SESSION['user_id']]);
$student = $stmtSt->fetch();

$selectedBookingId = (int)($_GET['booking_id'] ?? 0);
$selectedBooking = null;

if ($selectedBookingId > 0) {
    $stmtB = $pdo->prepare("
        SELECT b.*, u.name as tutor_name, s.name as subject_name
        FROM bookings b
        JOIN tutors t ON b.tutor_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN subjects s ON b.subject_id = s.id
        WHERE b.id = ? AND b.student_id = ?
    ");
    $stmtB->execute([$selectedBookingId, $student['id']]);
    $selectedBooking = $stmtB->fetch();
}

// Fetch all payment records for history
$stmtPayList = $pdo->prepare("
    SELECT p.*, b.booking_date, b.start_time, s.name as subject_name, u.name as tutor_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN tutors t ON b.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE p.student_id = ?
    ORDER BY p.paid_at DESC
");
$stmtPayList->execute([$student['id']]);
$paymentsHistory = $stmtPayList->fetchAll();

$pageTitle = "GCash Payment Integration";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-solid fa-credit-card me-1"></i> Secure Payment Gateway
            </span>
            <h2 class="fw-extrabold text-dark mb-1">GCash Payment Integration</h2>
            <p class="text-muted mb-0">Pay for your tutoring sessions securely using GCash digital wallet.</p>
        </div>

        <?php if ($selectedBooking): ?>
            <!-- Active Booking Checkout Card -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-6">
                    <div class="card-static p-4 p-md-5 rounded-4 shadow-lg border-0">
                        <div class="text-center mb-4">
                            <!-- GCash Logo Header Simulation -->
                            <div class="bg-primary text-white p-3 rounded-4 d-inline-block mb-3 px-4">
                                <h4 class="fw-extrabold mb-0 tracking-tight"><i class="fa-solid fa-wallet me-2"></i> GCash Pay</h4>
                            </div>
                            <h4 class="fw-bold text-dark">Confirm & Pay Session</h4>
                            <p class="text-muted font-size-sm">Verify your booking items before submitting GCash payment</p>
                        </div>

                        <div class="bg-light p-3 rounded-4 mb-4 border font-size-sm">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tutor Name:</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($selectedBooking['tutor_name']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subject Course:</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($selectedBooking['subject_name']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Date & Time:</span>
                                <span class="fw-semibold text-dark"><?php echo date('M d, Y', strtotime($selectedBooking['booking_date'])); ?> @ <?php echo date('h:i A', strtotime($selectedBooking['start_time'])); ?></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Tutor Hourly Fee:</span>
                                <span>₱<?php echo number_format($selectedBooking['total_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Platform Service Fee:</span>
                                <span>₱10.00</span>
                            </div>
                            <div class="d-flex justify-content-between fs-5 fw-extrabold text-dark pt-2 border-top">
                                <span>TOTAL AMOUNT:</span>
                                <span class="text-primary">₱<?php echo number_format($selectedBooking['total_amount'] + 10.00, 2); ?></span>
                            </div>
                        </div>

                        <div id="paymentAlert" class="alert d-none rounded-3 mb-3"></div>

                        <form id="gcashPaymentForm">
                            <input type="hidden" name="booking_id" value="<?php echo $selectedBooking['id']; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">GCash Mobile Number</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0 fw-bold text-primary">+63</span>
                                    <input type="text" name="gcash_number" class="form-control rounded-end-3" placeholder="917 123 4567" value="9178889999" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Reference No. (Optional / Auto Generated)</label>
                                <input type="text" name="reference_number" class="form-control form-control-lg rounded-3" placeholder="GCASH-REF-XXXXXX">
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-3 font-weight-bold fs-5 shadow">
                                <i class="fa-solid fa-lock me-2"></i> Pay ₱<?php echo number_format($selectedBooking['total_amount'] + 10.00, 2); ?> via GCash
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Receipt Success Modal -->
        <div class="modal fade" id="receiptModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-body p-4 p-md-5 text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-check fs-1"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Payment Successful!</h3>
                        <p class="text-muted font-size-sm">Your GCash transaction has been verified and confirmed.</p>

                        <div class="bg-light p-3 rounded-4 text-start font-size-sm border my-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Transaction ID:</span>
                                <strong class="text-dark" id="receiptTxId">TL-20260830-0001</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Reference No:</span>
                                <span id="receiptRefNo">GCASH-123456</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Payment Method:</span>
                                <span class="fw-semibold">GCash Mobile Wallet</span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top fw-bold fs-6">
                                <span>Amount Paid:</span>
                                <span class="text-success" id="receiptAmount">₱260.00</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="calendar.php" class="btn btn-primary-custom py-2">View Session in Calendar</a>
                            <button type="button" class="btn btn-outline-custom py-2" onclick="window.print()">
                                <i class="fa-solid fa-print me-1"></i> Download / Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Table -->
        <div class="card-static p-4 rounded-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Payment History & Receipts</h5>

            <?php if (count($paymentsHistory) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light font-size-xs text-muted">
                            <tr>
                                <th>TRANSACTION ID</th>
                                <th>TUTOR & SUBJECT</th>
                                <th>DATE & TIME</th>
                                <th>TOTAL PAID</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentsHistory as $pay): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark font-size-sm"><?php echo htmlspecialchars($pay['transaction_id']); ?></span>
                                        <span class="text-muted font-size-xs d-block"><?php echo htmlspecialchars($pay['reference_number']); ?></span>
                                    </td>
                                    <td>
                                        <h6 class="fw-bold mb-0 font-size-sm text-dark"><?php echo htmlspecialchars($pay['tutor_name']); ?></h6>
                                        <span class="text-muted font-size-xs"><?php echo htmlspecialchars($pay['subject_name']); ?></span>
                                    </td>
                                    <td class="font-size-sm text-muted">
                                        <?php echo date('M d, Y', strtotime($pay['booking_date'])); ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark">₱<?php echo number_format($pay['total_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-badge-success"><i class="fa-solid fa-check me-1"></i> Paid</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-custom btn-sm py-1 px-2 font-size-xs" onclick="alert('Transaction ID: <?php echo $pay['transaction_id']; ?>\nStatus: Paid\nAmount: ₱<?php echo number_format($pay['total_amount'], 2); ?>');">
                                            <i class="fa-solid fa-receipt me-1"></i> Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No past transactions found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('gcashPaymentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('paymentAlert');
            alertBox.classList.add('d-none');

            const formData = new FormData(form);

            fetch('../api/payment.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('receiptTxId').innerText = data.transaction_id;
                    document.getElementById('receiptRefNo').innerText = data.reference_number;
                    document.getElementById('receiptAmount').innerText = '₱' + parseFloat(data.total_amount).toFixed(2);

                    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
                    modal.show();
                } else {
                    alertBox.className = 'alert alert-danger rounded-3';
                    alertBox.innerText = data.message;
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(err => {
                alertBox.className = 'alert alert-danger rounded-3';
                alertBox.innerText = 'Server error processing payment.';
                alertBox.classList.remove('d-none');
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
