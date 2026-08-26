<?php
// student/ai-matching.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDBConnection();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();

$pageTitle = "AI Tutor Matching";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="pill-badge pill-badge-primary mb-2">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Intelligent Recommendation Engine
                </span>
                <h2 class="fw-extrabold text-dark mb-1">AI Tutor Matching</h2>
                <p class="text-muted mb-0">Fill in your learning goals and our system will match you with the best compatible tutors.</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Questionnaire Form -->
            <div class="col-lg-4">
                <div class="card-static p-4 shadow-sm rounded-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i> Your Preferences</h5>

                    <form id="aiMatchForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">1. Subject Needed</label>
                            <select name="subject_id" class="form-select form-select-lg rounded-3" required>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> (<?php echo htmlspecialchars($s['category']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">2. Current Level</label>
                            <select name="level" class="form-select rounded-3">
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate" selected>Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">3. Preferred Learning Style</label>
                            <select name="learning_style" class="form-select rounded-3">
                                <option value="Practice-based" selected>Practice-based (Exercises & Problems)</option>
                                <option value="Visual">Visual (Diagrams & Concepts)</option>
                                <option value="Discussion">Discussion & Q&A</option>
                                <option value="Step-by-step">Step-by-step Guided</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">4. Preferred Schedule</label>
                            <select name="schedule" class="form-select rounded-3">
                                <option value="Weekdays" selected>Weekdays (Mon - Fri)</option>
                                <option value="Weekends">Weekends (Sat - Sun)</option>
                                <option value="Flexible">Flexible / Evening</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold d-flex justify-content-between">
                                <span>5. Hourly Budget</span>
                                <span class="text-primary fw-bold" id="budgetValue">₱300 / hr</span>
                            </label>
                            <input type="range" name="budget" class="form-range" min="150" max="600" step="25" value="300" oninput="document.getElementById('budgetValue').innerText = '₱' + this.value + ' / hr'">
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100 py-3 font-weight-bold">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Find My Best Tutor
                        </button>
                    </form>
                </div>
            </div>

            <!-- AI Results Container -->
            <div class="col-lg-8">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">Recommended AI Matches</h5>
                    <span id="resultsCount" class="badge bg-primary-subtle text-primary fw-bold font-size-sm">Calculating...</span>
                </div>

                <div id="loadingSpinner" class="card-static p-5 text-center my-3">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Calculating match score...</span>
                    </div>
                    <p class="text-muted mt-3 mb-0 fw-semibold">Analyzing learning preferences and scoring tutor compatibility...</p>
                </div>

                <div id="aiMatchResults" class="d-flex flex-column gap-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('aiMatchForm');
    const resultsContainer = document.getElementById('aiMatchResults');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const resultsCount = document.getElementById('resultsCount');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function fetchMatches() {
        loadingSpinner.classList.remove('d-none');
        resultsContainer.innerHTML = '';

        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        fetch(`../api/matching.php?${params}`)
            .then(res => res.json())
            .then(data => {
                loadingSpinner.classList.add('d-none');
                if (data.status === 'success' && data.matches.length > 0) {
                    resultsCount.innerText = `${data.total} Matched Tutors Found`;

                    data.matches.forEach(tutor => {
                        const score = tutor.match_percentage;
                        const safeName = escapeHtml(tutor.name);
                        const safeTitle = escapeHtml(tutor.title);
                        const safeBio = escapeHtml(tutor.bio);
                        const safePhoto = escapeHtml(tutor.profile_photo);

                        const reasonsHtml = tutor.match_reasons.map(r => `<span class="badge bg-light text-dark border me-1 mb-1"><i class="fa-solid fa-check text-success me-1"></i>${escapeHtml(r)}</span>`).join('');

                        const cardHtml = `
                            <div class="card-custom p-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-3 text-center text-md-start">
                                        <img src="${safePhoto}" class="rounded-circle object-fit-cover shadow-sm mb-2" width="80" height="80" alt="${safeName}">
                                        <div class="text-warning font-weight-bold font-size-sm mb-1">
                                            <i class="fa-solid fa-star"></i> ${parseFloat(tutor.rating).toFixed(1)}
                                            <span class="text-muted font-weight-normal">(${tutor.total_reviews})</span>
                                        </div>
                                        <span class="badge bg-light text-muted border">${parseInt(tutor.experience)} yrs exp</span>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h5 class="fw-bold mb-0 text-dark">${safeName}</h5>
                                            <i class="fa-solid fa-circle-check text-primary font-size-sm" title="Verified Tutor"></i>
                                            <span class="match-score-badge ms-auto d-md-none">${score}% Match</span>
                                        </div>
                                        <span class="text-primary fw-semibold font-size-sm d-block mb-2">${safeTitle}</span>
                                        <p class="text-muted font-size-sm mb-2 line-clamp-2">${safeBio}</p>
                                        <div class="d-flex flex-wrap align-items-center">
                                            ${reasonsHtml}
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-md-end border-start-md ps-md-4">
                                        <div class="d-none d-md-block mb-3">
                                            <span class="match-score-badge fs-6">
                                                <i class="fa-solid fa-bolt"></i> ${score}% Match
                                            </span>
                                        </div>
                                        <div class="mb-3">
                                            <span class="fw-extrabold fs-4 text-dark">₱${parseFloat(tutor.hourly_rate).toFixed(2)}</span>
                                            <span class="text-muted font-size-xs">/ hour</span>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <a href="tutor-profile.php?id=${tutor.id}" class="btn btn-outline-custom py-2 btn-sm">View Profile</a>
                                            <a href="booking.php?tutor_id=${tutor.id}&subject_id=${data.preferences.subject_id}" class="btn btn-primary-custom py-2 btn-sm">Book Session</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        resultsContainer.insertAdjacentHTML('beforeend', cardHtml);
                    });
                } else {
                    resultsCount.innerText = '0 Matches';
                    resultsContainer.innerHTML = '<div class="alert alert-warning text-center">No tutors match your exact filter. Try adjusting your preferences.</div>';
                }
            })
            .catch(err => {
                loadingSpinner.classList.add('d-none');
                resultsContainer.innerHTML = '<div class="alert alert-danger text-center">Failed to process matching engine query.</div>';
            });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchMatches();
    });

    // Initial fetch on page load
    fetchMatches();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
