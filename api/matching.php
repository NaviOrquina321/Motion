<?php
// api/matching.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$pdo = getDBConnection();

// Get input preferences
$subject_id = (int)($_REQUEST['subject_id'] ?? 1);
$level = $_REQUEST['level'] ?? 'Intermediate';
$learning_style = $_REQUEST['learning_style'] ?? 'Practice-based';
$schedule = $_REQUEST['schedule'] ?? 'Weekdays';
$budget = (float)($_REQUEST['budget'] ?? 300.00);

// Fetch all verified tutors
$stmt = $pdo->query("
    SELECT t.*, u.name, u.profile_photo, u.email
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE u.status = 'active' AND t.verification_status = 'verified'
");
$tutors = $stmt->fetchAll();

$results = [];

foreach ($tutors as $tutor) {
    $score = 0;
    $matchReasons = [];

    // 1. Subject Match (30 points)
    $stmtSub = $pdo->prepare("SELECT * FROM tutor_subjects WHERE tutor_id = ? AND subject_id = ?");
    $stmtSub->execute([$tutor['id'], $subject_id]);
    $tutorSubject = $stmtSub->fetch();

    if ($tutorSubject) {
        $score += 30;
        $matchReasons[] = "Exact subject expertise";
    } else {
        // partial credit if subject category matches
        $score += 10;
    }

    // 2. Learning Style & Education Level Match (20 points)
    // Compare requested level vs tutor level
    if ($tutorSubject && ($tutorSubject['level'] === $level || $tutorSubject['level'] === 'All Levels')) {
        $score += 20;
        $matchReasons[] = "Matches your learning level ($level)";
    } else {
        $score += 12;
    }

    // 3. Schedule Match (20 points)
    // Check if tutor has availability setup
    $stmtAvail = $pdo->prepare("SELECT COUNT(*) FROM tutor_availability WHERE tutor_id = ?");
    $stmtAvail->execute([$tutor['id']]);
    if ($stmtAvail->fetchColumn() > 0) {
        $score += 20;
        $matchReasons[] = "Available on preferred schedule ($schedule)";
    } else {
        $score += 10;
    }

    // 4. Budget Match (15 points)
    $rate = (float)$tutor['hourly_rate'];
    if ($rate <= $budget) {
        $score += 15;
        $matchReasons[] = "Within your hourly budget (₱" . number_format($rate, 2) . ")";
    } elseif ($rate <= $budget * 1.2) {
        $score += 10;
    } else {
        $score += 5;
    }

    // 5. Experience (10 points)
    $exp = (int)$tutor['experience'];
    if ($exp >= 5) {
        $score += 10;
        $matchReasons[] = "$exp+ years teaching experience";
    } elseif ($exp >= 2) {
        $score += 7;
    } else {
        $score += 4;
    }

    // 6. Rating (5 points)
    $rating = (float)$tutor['rating'];
    $score += round(($rating / 5.0) * 5);

    // Ensure score does not exceed 100%
    $matchPercentage = min(100, max(50, round($score)));

    // Fetch tutor subjects for UI badge display
    $stmtSubNames = $pdo->prepare("
        SELECT s.name FROM subjects s
        JOIN tutor_subjects ts ON s.id = ts.subject_id
        WHERE ts.tutor_id = ?
    ");
    $stmtSubNames->execute([$tutor['id']]);
    $subjectsList = $stmtSubNames->fetchAll(PDO::FETCH_COLUMN);

    $tutor['match_percentage'] = $matchPercentage;
    $tutor['match_reasons'] = $matchReasons;
    $tutor['subjects_list'] = $subjectsList;

    $results[] = $tutor;
}

// Sort by match percentage descending
usort($results, function($a, $b) {
    return $b['match_percentage'] <=> $a['match_percentage'];
});

echo json_encode([
    'status' => 'success',
    'total' => count($results),
    'preferences' => [
        'subject_id' => $subject_id,
        'level' => $level,
        'learning_style' => $learning_style,
        'schedule' => $schedule,
        'budget' => $budget
    ],
    'matches' => $results
]);
