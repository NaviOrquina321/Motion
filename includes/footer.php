<?php
// includes/footer.php
$baseUrl = $baseUrl ?? '';
?>
<footer class="bg-white border-top py-4 mt-5">
    <div class="container text-center text-muted">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <div class="bg-primary text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                <i class="fa-solid fa-graduation-cap font-size-xs"></i>
            </div>
            <span class="fw-bold text-dark">TutorLink</span>
        </div>
        <p class="mb-0 font-size-sm">&copy; <?php echo date('Y'); ?> TutorLink: An Intelligent Tutor-Student Matching System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>
