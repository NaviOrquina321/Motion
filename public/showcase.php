<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Services/VideoGeneratorService.php';
require_once __DIR__ . '/../app/Services/PresetLibraryService.php';
require_once __DIR__ . '/../app/Views/Layout.php';

$currentUser = AuthMiddleware::requireLogin();
$userProjects = Project::getByUser($currentUser['id']);

$selectedProjectId = (int)($_GET['project_id'] ?? ($userProjects[0]['id'] ?? 0));
$selectedPreset = $_GET['preset'] ?? 'liquid_glass';

$selectedProject = $selectedProjectId ? Project::findById($selectedProjectId) : null;
$presets = PresetLibraryService::getPresets();

$script = $selectedProject
    ? VideoGeneratorService::generateScript($selectedProject, $selectedPreset)
    : [
        'title' => 'CloudVault Project Showcase',
        'preset' => $selectedPreset,
        'scenes' => [
            ['id' => 1, 'heading' => 'My Software Project', 'subheading' => 'Backed up with CloudVault', 'start' => 0, 'end' => 3],
            ['id' => 2, 'heading' => 'Stack & Requirements', 'subheading' => 'Analyzed & SHA-256 Verified', 'start' => 3, 'end' => 7],
            ['id' => 3, 'heading' => 'Restore Anywhere', 'subheading' => 'Setup Assistant Ready', 'start' => 7, 'end' => 10]
        ]
    ];

renderHeader('AI Motion Graphics Showcase', 'showcase');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-light mb-1">
            <i class="bi bi-film text-gradient me-2"></i>AI Motion Graphics Showcase
        </h2>
        <p class="text-muted mb-0">Generate Kario-style animated product launch and demo videos for your programming projects.</p>
    </div>
    <a href="mcp.php" target="_blank" class="btn btn-outline-info rounded-pill fw-bold">
        <i class="bi bi-cpu me-1"></i> Connect Claude/ChatGPT over MCP
    </a>
</div>

<div class="row g-4 mb-4">
    <!-- Left Controls & Preset Picker -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-sliders text-primary me-2"></i>Showcase Settings</h5>

            <form method="GET" action="showcase.php">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Project</label>
                    <select name="project_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($userProjects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] === $selectedProjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['project_name']) ?> (<?= htmlspecialchars($p['project_type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Motion Preset</label>
                    <select name="preset" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($presets as $key => $preset): ?>
                            <option value="<?= $key ?>" <?= $key === $selectedPreset ? 'selected' : '' ?>>
                                <?= htmlspecialchars($preset['name']) ?> (<?= htmlspecialchars($preset['badge']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <hr class="border-secondary my-4">

            <!-- Motion Presets Grid -->
            <h6 class="fw-bold text-light mb-3">Available Kario AI Motion Effects</h6>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($presets as $key => $preset): ?>
                    <a href="showcase.php?project_id=<?= $selectedProjectId ?>&preset=<?= $key ?>"
                       class="p-3 rounded-3 text-decoration-none border transition-all d-flex justify-content-between align-items-center <?= $key === $selectedPreset ? 'border-primary bg-primary bg-opacity-10 text-white' : 'border-secondary text-muted bg-dark' ?>">
                        <div>
                            <strong class="d-block text-light"><?= htmlspecialchars($preset['name']) ?></strong>
                            <small class="small text-muted"><?= htmlspecialchars($preset['description']) ?></small>
                        </div>
                        <span class="badge bg-secondary"><?= htmlspecialchars($preset['badge']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Interactive Motion Canvas Player -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-light mb-0"><i class="bi bi-play-circle text-gradient-cyan me-2"></i>Live Motion Graphics Render</h5>
                <span class="badge bg-success rounded-pill px-3 py-2">60 FPS Motion Canvas</span>
            </div>

            <!-- Animated Canvas Preview Box -->
            <div id="motionCanvasBox" class="position-relative overflow-hidden rounded-4 p-5 d-flex flex-column justify-content-center align-items-center text-center shadow-lg" style="height: 380px; background: <?= $presets[$selectedPreset]['preview_bg'] ?>;">
                <div id="sceneContent" class="transition-all" style="max-width: 600px;">
                    <div class="badge bg-info rounded-pill px-3 py-1 mb-3 text-dark fw-bold" id="sceneBadge">Scene 1 / 3</div>
                    <h1 class="display-4 fw-extrabold text-light mb-3 text-gradient" id="sceneHeading"><?= htmlspecialchars($script['scenes'][0]['heading']) ?></h1>
                    <p class="lead text-light opacity-90 fw-semibold" id="sceneSubheading"><?= htmlspecialchars($script['scenes'][0]['subheading']) ?></p>
                </div>

                <!-- Floating 3D/Glass Particle Effects Overlay -->
                <div class="position-absolute top-0 start-0 w-100 h-100 pointer-events-none opacity-30" style="background: radial-gradient(circle at 30% 30%, rgba(56, 189, 248, 0.4), transparent 60%);"></div>
            </div>

            <!-- Video Player Controls & Progress Bar -->
            <div class="mt-4 p-3 bg-dark rounded-3 border border-secondary d-flex align-items-center gap-3">
                <button id="playPauseBtn" class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-play-fill fs-4" id="playIcon"></i>
                </button>
                <div class="flex-grow-1">
                    <div class="progress progress-thin bg-secondary" style="cursor: pointer;" id="videoProgressBar">
                        <div class="progress-bar bg-info" id="videoProgress" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <span class="text-muted font-monospace small" id="videoTimer">00:00 / 00:10</span>
            </div>

            <!-- Export Options -->
            <div class="d-flex gap-2 mt-4">
                <button onclick="alert('Exporting 1080p MP4 Video with transparent motion layers...')" class="btn btn-primary fw-bold flex-grow-1">
                    <i class="bi bi-download me-1"></i> Export MP4 Video
                </button>
                <button onclick="alert('Exporting transparent WebM video for website backgrounds...')" class="btn btn-outline-info fw-bold">
                    <i class="bi bi-layers me-1"></i> WebM Layer
                </button>
                <button onclick="alert('Exporting animated GIF preview...')" class="btn btn-outline-secondary fw-bold">
                    <i class="bi bi-filetype-gif me-1"></i> GIF
                </button>
            </div>
        </div>

        <!-- Storyboard Scene Script -->
        <div class="card card-custom p-4">
            <h5 class="fw-bold text-light mb-3"><i class="bi bi-diagram-3 text-warning me-2"></i>Editable Storyboard Scenes</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>Time</th>
                            <th>Heading</th>
                            <th>Subheading</th>
                            <th>Motion Effect</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($script['scenes'] as $scene): ?>
                            <tr>
                                <td><span class="badge bg-secondary font-monospace">00:0<?= $scene['start'] ?>-00:<?= $scene['end'] ?></span></td>
                                <td class="fw-bold text-light"><?= htmlspecialchars($scene['heading']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($scene['subheading']) ?></small></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($presets[$selectedPreset]['name']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const scenes = <?= json_encode($script['scenes']) ?>;
    let currentSceneIdx = 0;
    let isPlaying = false;
    let progress = 0;
    let interval = null;

    const playPauseBtn = document.getElementById('playPauseBtn');
    const playIcon = document.getElementById('playIcon');
    const videoProgress = document.getElementById('videoProgress');
    const videoTimer = document.getElementById('videoTimer');
    const sceneBadge = document.getElementById('sceneBadge');
    const sceneHeading = document.getElementById('sceneHeading');
    const sceneSubheading = document.getElementById('sceneSubheading');

    function updateSceneDisplay(idx) {
        if (!scenes[idx]) return;
        sceneBadge.innerText = `Scene ${idx + 1} / ${scenes.length}`;
        sceneHeading.innerText = scenes[idx].heading;
        sceneSubheading.innerText = scenes[idx].subheading;
    }

    playPauseBtn.addEventListener('click', () => {
        isPlaying = !isPlaying;
        if (isPlaying) {
            playIcon.className = 'bi bi-pause-fill fs-4';
            interval = setInterval(() => {
                progress += 1;
                videoProgress.style.width = `${progress}%`;

                const sec = Math.floor((progress / 100) * 10);
                videoTimer.innerText = `00:0${sec} / 00:10`;

                if (progress < 33) {
                    if (currentSceneIdx !== 0) { currentSceneIdx = 0; updateSceneDisplay(0); }
                } else if (progress < 70) {
                    if (currentSceneIdx !== 1) { currentSceneIdx = 1; updateSceneDisplay(1); }
                } else {
                    if (currentSceneIdx !== 2) { currentSceneIdx = 2; updateSceneDisplay(2); }
                }

                if (progress >= 100) {
                    progress = 0;
                    isPlaying = false;
                    playIcon.className = 'bi bi-play-fill fs-4';
                    clearInterval(interval);
                }
            }, 100);
        } else {
            playIcon.className = 'bi bi-play-fill fs-4';
            clearInterval(interval);
        }
    });
});
</script>

<?php renderFooter(); ?>
