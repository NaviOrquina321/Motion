-- TutorLink MySQL / MariaDB Database Dump & Schema (phpMyAdmin compatible)
-- Database: `tutorlink`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for `users`
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `profile_photo` VARCHAR(255) DEFAULT 'default.png',
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `students`
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `education_level` VARCHAR(50) DEFAULT 'Intermediate',
    `learning_style` VARCHAR(50) DEFAULT 'Practice-based',
    `preferred_schedule` VARCHAR(100) DEFAULT 'Weekdays',
    `budget` DECIMAL(10,2) DEFAULT 300.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `tutors`
CREATE TABLE IF NOT EXISTS `tutors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `title` VARCHAR(100) DEFAULT 'Professional Tutor',
    `bio` TEXT,
    `experience` INT DEFAULT 1,
    `education` VARCHAR(255),
    `hourly_rate` DECIMAL(10,2) DEFAULT 250.00,
    `rating` DECIMAL(3,2) DEFAULT 5.00,
    `total_reviews` INT DEFAULT 0,
    `lessons_conducted` INT DEFAULT 0,
    `hours_taught` INT DEFAULT 0,
    `response_time` VARCHAR(50) DEFAULT '4-hour response time',
    `verification_status` VARCHAR(20) DEFAULT 'verified',
    `featured` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `subjects`
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `tutor_subjects`
CREATE TABLE IF NOT EXISTS `tutor_subjects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tutor_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `level` VARCHAR(50) DEFAULT 'All Levels',
    FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `tutor_availability`
CREATE TABLE IF NOT EXISTS `tutor_availability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tutor_id` INT NOT NULL,
    `day_of_week` VARCHAR(20) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `bookings`
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `duration_hours` INT DEFAULT 1,
    `hourly_rate` DECIMAL(10,2) NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'confirmed',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `payments`
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `service_fee` DECIMAL(10,2) DEFAULT 10.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'GCash',
    `account_number` VARCHAR(50),
    `reference_number` VARCHAR(100),
    `transaction_id` VARCHAR(100) UNIQUE NOT NULL,
    `status` VARCHAR(20) DEFAULT 'paid',
    `paid_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `notifications`
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) DEFAULT 'system',
    `is_read` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `messages`
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `reviews`
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT,
    `student_id` INT NOT NULL,
    `tutor_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `comment` TEXT,
    `qualifications_rating` DECIMAL(3,1) DEFAULT 5.0,
    `expertise_rating` DECIMAL(3,1) DEFAULT 5.0,
    `communication_rating` DECIMAL(3,1) DEFAULT 5.0,
    `value_rating` DECIMAL(3,1) DEFAULT 5.0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `reports_logs`
CREATE TABLE IF NOT EXISTS `reports_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `user_id` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Demo Data Seed (Password for all accounts: 'password123')
-- Hashed Password: $2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `profile_photo`, `status`) VALUES
(1, 'System Administrator', 'admin@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'admin', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=200', 'active'),
(2, 'Maria Santos', 'maria.santos@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'tutor', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300', 'active'),
(3, 'Robert Smith', 'robert.smith@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'tutor', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 'active'),
(4, 'Jianli N.', 'jianli.n@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'tutor', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=300', 'active'),
(5, 'Juan Dela Cruz', 'juan.delacruz@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'tutor', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=300', 'active'),
(6, 'Door Rau', 'door.rau@tutorlink.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'tutor', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=300', 'active'),
(7, 'John Doe', 'john.doe@student.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'student', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&q=80&w=200', 'active'),
(8, 'Kathryn Murphy', 'kathryn.m@student.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'student', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=200', 'active'),
(9, 'Mark Reyes', 'mark.reyes@student.com', '$2y$10$npNaMcfqakwsKpJH2cMe8e.qYcY2fNoksbxDbNHS.lINLllFc3chS', 'student', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=200', 'active');

INSERT IGNORE INTO `subjects` (`id`, `name`, `category`, `description`) VALUES
(1, 'Mathematics', 'STEM', 'Algebra, Geometry, Calculus, Trigonometry, Statistics'),
(2, 'English', 'Languages', 'English Grammar, Spoken English, Literature Analysis, Writing'),
(3, 'Programming', 'Technology', 'Python, JavaScript, PHP, Data Structures, Web Development'),
(4, 'Science', 'STEM', 'Physics, Chemistry, Biology, General Science'),
(5, 'Accounting', 'Business', 'Financial Accounting, Managerial Accounting, Bookkeeping');

INSERT IGNORE INTO `tutors` (`id`, `user_id`, `title`, `bio`, `experience`, `education`, `hourly_rate`, `rating`, `total_reviews`, `lessons_conducted`, `hours_taught`, `response_time`, `verification_status`, `featured`) VALUES
(1, 2, 'Mathematics & Statistics Specialist', 'Experienced mathematics tutor with 5+ years of helping high school and university students master algebra, calculus, and statistics through visual and step-by-step problem solving.', 5, 'BS Mathematics, University of the Philippines', 250.00, 4.90, 127, 210, 320, '1-hour response time', 'verified', 1),
(2, 3, 'Senior English Language Teacher', 'Enhance your language skills and master literary analysis with my courses. Gain the tools to excel in English studies, analyze texts effectively, and communicate your ideas with clarity.', 8, 'MA English Literature, Ateneo de Manila', 320.00, 4.90, 236, 232, 450, '4-hour response time', 'verified', 1),
(3, 4, 'International Language Teacher', 'International Chinese & Science teacher full of patience and enthusiasm. I love teaching and hope to help people from all over the world learn with ease.', 4, 'BA Linguistics, Beijing Language University', 280.00, 5.00, 56, 85, 120, '2-hour response time', 'verified', 1),
(4, 5, 'Full-Stack Software Engineer & Tech Tutor', 'Passionate about coding, algorithms, and web development. Teaching Python, Web Dev, and PHP step-by-step to aspiring developers.', 6, 'BS Computer Science, De La Salle University', 300.00, 4.80, 88, 140, 210, '30-minute response time', 'verified', 1),
(5, 6, 'Science & Multilingual Educator', 'Native educator specializing in General Science, Physics, and Multilingual studies. Our method makes you learn quickly and fluently.', 3, 'BS Physics & Education', 240.00, 4.70, 42, 60, 90, '5-hour response time', 'verified', 0);

INSERT IGNORE INTO `students` (`id`, `user_id`, `education_level`, `learning_style`, `preferred_schedule`, `budget`) VALUES
(1, 7, 'Intermediate', 'Practice-based', 'Weekdays', 300.00),
(2, 8, 'Advanced', 'Discussion', 'Weekends', 350.00),
(3, 9, 'Beginner', 'Step-by-step', 'Flexible', 250.00);

INSERT IGNORE INTO `tutor_subjects` (`id`, `tutor_id`, `subject_id`, `level`) VALUES
(1, 1, 1, 'All Levels'),
(2, 2, 2, 'Advanced'),
(3, 3, 2, 'Intermediate'),
(4, 3, 4, 'Beginner'),
(5, 4, 3, 'All Levels'),
(6, 5, 4, 'Beginner');

INSERT IGNORE INTO `tutor_availability` (`tutor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 'Monday', '09:00:00', '12:00:00'),
(1, 'Monday', '13:00:00', '17:00:00'),
(1, 'Tuesday', '09:00:00', '12:00:00'),
(1, 'Tuesday', '13:00:00', '17:00:00'),
(1, 'Wednesday', '09:00:00', '12:00:00'),
(1, 'Wednesday', '13:00:00', '17:00:00'),
(1, 'Thursday', '09:00:00', '12:00:00'),
(1, 'Thursday', '13:00:00', '17:00:00'),
(1, 'Friday', '09:00:00', '12:00:00'),
(1, 'Friday', '13:00:00', '17:00:00');

INSERT IGNORE INTO `bookings` (`id`, `student_id`, `tutor_id`, `subject_id`, `booking_date`, `start_time`, `end_time`, `duration_hours`, `hourly_rate`, `total_amount`, `status`, `notes`) VALUES
(1, 1, 1, 1, '2026-08-30', '15:00:00', '16:00:00', 1, 250.00, 250.00, 'confirmed', 'Focus on calculus derivatives.'),
(2, 1, 2, 2, '2026-09-02', '10:00:00', '11:00:00', 1, 320.00, 320.00, 'confirmed', 'English essay review and editing.'),
(3, 2, 2, 2, '2026-08-28', '14:00:00', '15:00:00', 1, 320.00, 320.00, 'completed', 'Spoken English practice.'),
(4, 3, 4, 3, '2026-08-29', '11:00:00', '13:00:00', 2, 300.00, 600.00, 'completed', 'Intro to PHP PDO and web forms.');

INSERT IGNORE INTO `payments` (`id`, `booking_id`, `student_id`, `amount`, `service_fee`, `total_amount`, `payment_method`, `account_number`, `reference_number`, `transaction_id`, `status`) VALUES
(1, 1, 1, 250.00, 10.00, 260.00, 'GCash', '0917****123', 'REF-849201', 'TL-20260830-0001', 'paid'),
(2, 2, 1, 320.00, 10.00, 330.00, 'GCash', '0917****123', 'REF-930129', 'TL-20260830-0002', 'paid'),
(3, 3, 2, 320.00, 10.00, 330.00, 'GCash', '0918****456', 'REF-284019', 'TL-20260830-0003', 'paid'),
(4, 4, 3, 600.00, 10.00, 610.00, 'GCash', '0919****789', 'REF-471092', 'TL-20260830-0004', 'paid');

INSERT IGNORE INTO `reviews` (`id`, `booking_id`, `student_id`, `tutor_id`, `rating`, `comment`, `qualifications_rating`, `expertise_rating`, `communication_rating`, `value_rating`) VALUES
(1, 3, 2, 2, 5, "Absolutely brilliant course! Robert Smith's deep knowledge of English and engaging style made every lesson a pleasure!", 4.9, 4.2, 5.0, 4.5),
(2, 4, 3, 4, 5, "Very patient and easy to understand. Helped me grasp PHP database logic in just one session!", 5.0, 5.0, 4.8, 5.0);

INSERT IGNORE INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 7, 'Booking Confirmed', 'Your session with Maria Santos on Aug 30, 2026 at 3:00 PM is confirmed.', 'booking', 0),
(2, 7, 'GCash Payment Received', 'Payment of ₱260.00 (Transaction TL-20260830-0001) was successful.', 'payment', 1),
(3, 2, 'New Booking Request', 'John Doe booked a Mathematics session for Aug 30, 2026.', 'booking', 0);

INSERT IGNORE INTO `reports_logs` (`id`, `action`, `details`, `user_id`) VALUES
(1, 'SYSTEM_INIT', 'Database seeded with default records and verified tutors.', 1);

COMMIT;
