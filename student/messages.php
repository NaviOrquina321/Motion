<?php
// student/messages.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$partnerId = (int)($_GET['tutor_id'] ?? $_GET['partner_id'] ?? 0);

// Fetch recent conversation partners
$stmtPartners = $pdo->prepare("
    SELECT DISTINCT u.id, u.name, u.role, u.profile_photo
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
");
$stmtPartners->execute([$userId, $userId, $userId]);
$partners = $stmtPartners->fetchAll();

// If partnerId set, get partner info
$activePartner = null;
if ($partnerId > 0) {
    $stmtP = $pdo->prepare("SELECT id, name, role, profile_photo FROM users WHERE id = ?");
    $stmtP->execute([$partnerId]);
    $activePartner = $stmtP->fetch();
} elseif (count($partners) > 0) {
    $activePartner = $partners[0];
    $partnerId = $activePartner['id'];
}

$pageTitle = "Messages & Direct Chat";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <div class="mb-4">
            <span class="pill-badge pill-badge-primary mb-2">
                <i class="fa-regular fa-comments me-1"></i> Direct Communication
            </span>
            <h2 class="fw-extrabold text-dark mb-1">Messages & Chat</h2>
            <p class="text-muted mb-0">Chat directly with your assigned tutors regarding lessons and homework.</p>
        </div>

        <div class="card-static rounded-4 shadow-sm overflow-hidden" style="min-height: 500px;">
            <div class="row g-0">
                <!-- Chat Contacts List -->
                <div class="col-md-4 border-end bg-light p-3">
                    <h6 class="fw-bold text-dark mb-3">Recent Contacts</h6>

                    <?php if (count($partners) > 0 || $activePartner): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php if ($activePartner && !in_array($activePartner['id'], array_column($partners, 'id'))): ?>
                                <a href="messages.php?partner_id=<?php echo $activePartner['id']; ?>" class="p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 bg-white border">
                                    <img src="<?php echo htmlspecialchars($activePartner['profile_photo']); ?>" class="rounded-circle object-fit-cover" width="40" height="40" alt="Partner">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($activePartner['name']); ?></h6>
                                        <span class="text-muted font-size-xs text-capitalize"><?php echo htmlspecialchars($activePartner['role']); ?></span>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <?php foreach ($partners as $p): ?>
                                <a href="messages.php?partner_id=<?php echo $p['id']; ?>" class="p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 <?php echo $partnerId == $p['id'] ? 'bg-primary-light border-primary border' : 'bg-white border'; ?>">
                                    <img src="<?php echo htmlspecialchars($p['profile_photo']); ?>" class="rounded-circle object-fit-cover" width="40" height="40" alt="Partner">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark font-size-sm"><?php echo htmlspecialchars($p['name']); ?></h6>
                                        <span class="text-muted font-size-xs text-capitalize"><?php echo htmlspecialchars($p['role']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted font-size-xs">No active chats yet. Visit tutor profile pages to start messaging.</p>
                    <?php endif; ?>
                </div>

                <!-- Active Chat Thread -->
                <div class="col-md-8 d-flex flex-column" style="height: 500px;">
                    <?php if ($activePartner): ?>
                        <!-- Header -->
                        <div class="p-3 border-bottom d-flex align-items-center gap-2 bg-white">
                            <img src="<?php echo htmlspecialchars($activePartner['profile_photo']); ?>" class="rounded-circle object-fit-cover" width="40" height="40" alt="Partner">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($activePartner['name']); ?></h6>
                                <span class="badge bg-success-subtle text-success font-size-xs">Online</span>
                            </div>
                        </div>

                        <!-- Messages Thread Box -->
                        <div id="messagesThread" class="p-3 flex-grow-1 overflow-auto bg-light d-flex flex-column gap-2"></div>

                        <!-- Chat Input Form -->
                        <form id="chatForm" class="p-3 border-top bg-white d-flex gap-2">
                            <input type="hidden" name="receiver_id" value="<?php echo $activePartner['id']; ?>">
                            <input type="text" name="message" class="form-control rounded-3" placeholder="Type a message..." required autocomplete="off">
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center flex-grow-1 text-muted">
                            Select a contact to view conversation thread.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const thread = document.getElementById('messagesThread');
    const form = document.getElementById('chatForm');
    const activePartnerId = <?php echo (int)($activePartner['id'] ?? 0); ?>;
    const currentUserId = <?php echo (int)$userId; ?>;

    function loadMessages() {
        if (!activePartnerId) return;

        fetch(`../api/messages.php?partner_id=${activePartnerId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    thread.innerHTML = '';
                    data.messages.forEach(msg => {
                        const isMe = (parseInt(msg.sender_id) === currentUserId);
                        const alignClass = isMe ? 'align-self-end bg-primary text-white' : 'align-self-start bg-white text-dark border';

                        const msgBubble = `
                            <div class="p-2 px-3 rounded-4 max-w-xs ${alignClass} font-size-sm" style="max-width: 75%;">
                                <div>${msg.message}</div>
                                <div class="font-size-xs ${isMe ? 'text-white-50' : 'text-muted'} text-end mt-1">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            </div>
                        `;
                        thread.insertAdjacentHTML('beforeend', msgBubble);
                    });
                    thread.scrollTop = thread.scrollHeight;
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('action', 'send');

            fetch('../api/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    form.reset();
                    loadMessages();
                }
            });
        });

        loadMessages();
        setInterval(loadMessages, 3000);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
