<?php
// database/seed.php
require_once __DIR__ . '/../includes/db.php';

$pdo = getDBConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'sqlite') {
    // Read schema and run for SQLite
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);

    // Clear existing tables
    $tables = ['reports_logs', 'reviews', 'messages', 'notifications', 'payments', 'bookings', 'tutor_availability', 'tutor_subjects', 'subjects', 'tutors', 'students', 'users'];
    foreach ($tables as $tbl) {
        $pdo->exec("DELETE FROM $tbl;");
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$tbl';");
    }
} else {
    // Read schema and run for MySQL
    $sql = file_get_contents(__DIR__ . '/tutorlink.sql');
    $pdo->exec($sql);
    echo "MySQL database schema and seed data loaded successfully!\n";
    exit(0);
}

// Default Password for all demo accounts: 'password123'
$hashedPassword = password_hash('password123', PASSWORD_BCRYPT);

// 1. Insert Users
$usersData = [
    // Admin
    ['System Administrator', 'admin@tutorlink.com', $hashedPassword, 'admin', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=200'],
    // Tutors
    ['Maria Santos', 'maria.santos@tutorlink.com', $hashedPassword, 'tutor', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300'],
    ['Robert Smith', 'robert.smith@tutorlink.com', $hashedPassword, 'tutor', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300'],
    ['Jianli N.', 'jianli.n@tutorlink.com', $hashedPassword, 'tutor', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=300'],
    ['Juan Dela Cruz', 'juan.delacruz@tutorlink.com', $hashedPassword, 'tutor', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=300'],
    ['Door Rau', 'door.rau@tutorlink.com', $hashedPassword, 'tutor', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=300'],
    // Students
    ['John Doe', 'john.doe@student.com', $hashedPassword, 'student', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&q=80&w=200'],
    ['Kathryn Murphy', 'kathryn.m@student.com', $hashedPassword, 'student', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=200'],
    ['Mark Reyes', 'mark.reyes@student.com', $hashedPassword, 'student', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=200'],
];

$stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, profile_photo) VALUES (?, ?, ?, ?, ?)");
foreach ($usersData as $u) {
    $stmtUser->execute($u);
}

// 2. Insert Subjects
$subjectsData = [
    ['Mathematics', 'STEM', 'Algebra, Geometry, Calculus, Trigonometry, Statistics'],
    ['English', 'Languages', 'English Grammar, Spoken English, Literature Analysis, Writing'],
    ['Programming', 'Technology', 'Python, JavaScript, PHP, Data Structures, Web Development'],
    ['Science', 'STEM', 'Physics, Chemistry, Biology, General Science'],
    ['Accounting', 'Business', 'Financial Accounting, Managerial Accounting, Bookkeeping'],
];

$stmtSub = $pdo->prepare("INSERT INTO subjects (name, category, description) VALUES (?, ?, ?)");
foreach ($subjectsData as $s) {
    $stmtSub->execute($s);
}

// 3. Insert Tutors
$tutorsData = [
    // user_id, title, bio, experience, education, hourly_rate, rating, total_reviews, lessons_conducted, hours_taught, response_time, verification_status, featured
    [2, 'Mathematics & Statistics Specialist', 'Experienced mathematics tutor with 5+ years of helping high school and university students master algebra, calculus, and statistics through visual and step-by-step problem solving.', 5, 'BS Mathematics, University of the Philippines', 250.00, 4.9, 127, 210, 320, '1-hour response time', 'verified', 1],
    [3, 'Senior English Language Teacher', 'Enhance your language skills and master literary analysis with my courses. Gain the tools to excel in English studies, analyze texts effectively, and communicate your ideas with clarity.', 8, 'MA English Literature, Ateneo de Manila', 320.00, 4.9, 236, 232, 450, '4-hour response time', 'verified', 1],
    [4, 'International Language Teacher', 'International Chinese & Science teacher full of patience and enthusiasm. I love teaching and hope to help people from all over the world learn with ease.', 4, 'BA Linguistics, Beijing Language University', 280.00, 5.0, 56, 85, 120, '2-hour response time', 'verified', 1],
    [5, 'Full-Stack Software Engineer & Tech Tutor', 'Passionate about coding, algorithms, and web development. Teaching Python, Web Dev, and PHP step-by-step to aspiring developers.', 6, 'BS Computer Science, De La Salle University', 300.00, 4.8, 88, 140, 210, '30-minute response time', 'verified', 1],
    [6, 'Science & Multilingual Educator', 'Native educator specializing in General Science, Physics, and Multilingual studies. Our method makes you learn quickly and fluently.', 3, 'BS Physics & Education', 240.00, 4.7, 42, 60, 90, '5-hour response time', 'verified', 0],
];

$stmtTutor = $pdo->prepare("INSERT INTO tutors (user_id, title, bio, experience, education, hourly_rate, rating, total_reviews, lessons_conducted, hours_taught, response_time, verification_status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($tutorsData as $t) {
    $stmtTutor->execute($t);
}

// 4. Insert Students
$studentsData = [
    // user_id, education_level, learning_style, preferred_schedule, budget
    [7, 'Intermediate', 'Practice-based', 'Weekdays', 300.00],
    [8, 'Advanced', 'Discussion', 'Weekends', 350.00],
    [9, 'Beginner', 'Step-by-step', 'Flexible', 250.00],
];

$stmtStudent = $pdo->prepare("INSERT INTO students (user_id, education_level, learning_style, preferred_schedule, budget) VALUES (?, ?, ?, ?, ?)");
foreach ($studentsData as $st) {
    $stmtStudent->execute($st);
}

// 5. Link Tutors to Subjects
$tutorSubjectsData = [
    [1, 1, 'All Levels'], // Maria -> Math
    [2, 2, 'Advanced'],   // Robert -> English
    [3, 2, 'Intermediate'],// Jianli -> English
    [3, 4, 'Beginner'],    // Jianli -> Science
    [4, 3, 'All Levels'],  // Juan -> Programming
    [5, 4, 'Beginner'],    // Door -> Science
];
$stmtTS = $pdo->prepare("INSERT INTO tutor_subjects (tutor_id, subject_id, level) VALUES (?, ?, ?)");
foreach ($tutorSubjectsData as $ts) {
    $stmtTS->execute($ts);
}

// 6. Tutor Availability
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$stmtAvail = $pdo->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");

for ($tId = 1; $tId <= 5; $tId++) {
    foreach ($days as $day) {
        $stmtAvail->execute([$tId, $day, '09:00:00', '12:00:00']);
        $stmtAvail->execute([$tId, $day, '13:00:00', '17:00:00']);
    }
}

// 7. Seed Bookings & Payments
$bookingsData = [
    // student_id, tutor_id, subject_id, booking_date, start_time, end_time, duration_hours, hourly_rate, total_amount, status, notes
    [1, 1, 1, '2026-08-30', '15:00:00', '16:00:00', 1, 250.00, 250.00, 'confirmed', 'Focus on calculus derivatives.'],
    [1, 2, 2, '2026-09-02', '10:00:00', '11:00:00', 1, 320.00, 320.00, 'confirmed', 'English essay review and editing.'],
    [2, 2, 2, '2026-08-28', '14:00:00', '15:00:00', 1, 320.00, 320.00, 'completed', 'Spoken English practice.'],
    [3, 4, 3, '2026-08-29', '11:00:00', '13:00:00', 2, 300.00, 600.00, 'completed', 'Intro to PHP PDO and web forms.'],
];

$stmtBk = $pdo->prepare("INSERT INTO bookings (student_id, tutor_id, subject_id, booking_date, start_time, end_time, duration_hours, hourly_rate, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtPay = $pdo->prepare("INSERT INTO payments (booking_id, student_id, amount, service_fee, total_amount, payment_method, account_number, reference_number, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($bookingsData as $idx => $b) {
    $stmtBk->execute($b);
    $bookingId = $pdo->lastInsertId();
    $studentId = $b[0];
    $amount = $b[8];
    $serviceFee = 10.00;
    $totalAmount = $amount + $serviceFee;
    $txId = 'TL-20260830-000' . ($idx + 1);

    $stmtPay->execute([$bookingId, $studentId, $amount, $serviceFee, $totalAmount, 'GCash', '0917****123', 'REF-' . rand(100000, 999999), $txId, 'paid']);
}

// 8. Seed Reviews
$reviewsData = [
    [3, 2, 2, 5, "Absolutely brilliant course, trust me! Robert Smith's deep knowledge of English, coupled with his engaging teaching style, made every lesson a pleasure!", 4.9, 4.2, 5.0, 4.5],
    [4, 3, 4, 5, "Very patient and easy to understand. Helped me grasp PHP database logic in just one session!", 5.0, 5.0, 4.8, 5.0],
];
$stmtRev = $pdo->prepare("INSERT INTO reviews (booking_id, student_id, tutor_id, rating, comment, qualifications_rating, expertise_rating, communication_rating, value_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($reviewsData as $r) {
    $stmtRev->execute($r);
}

// 9. Seed Notifications
$notificationsData = [
    [7, 'Booking Confirmed', 'Your session with Maria Santos on Aug 30, 2026 at 3:00 PM is confirmed.', 'booking', 0],
    [7, 'GCash Payment Received', 'Payment of ₱260.00 (Transaction TL-20260830-0001) was successful.', 'payment', 1],
    [2, 'New Booking Request', 'John Doe booked a Mathematics session for Aug 30, 2026.', 'booking', 0],
];
$stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, ?)");
foreach ($notificationsData as $n) {
    $stmtNotif->execute($n);
}

// 10. Seed Admin Log
$pdo->exec("INSERT INTO reports_logs (action, details, user_id) VALUES ('SYSTEM_INIT', 'Database seeded with default records and verified tutors.', 1)");

echo "Database successfully initialized and seeded!\n";
