<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'libraryms');
define('DB_USER', 'root');
define('DB_PASS', '123456');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration - only if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    session_start();
}

function getReservationNotificationCount($pdo) {
    if (!isset($_SESSION['user_id'])) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservations WHERE UserID = ? AND Notified = 1 AND Status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
} 