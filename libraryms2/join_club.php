<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$club_id = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;
$user_id = $_SESSION['user_id'];

// Validate that the user is an active member
$stmt = $pdo->prepare("SELECT IsActive FROM Users WHERE UserID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !$user['IsActive']) {
    header("Location: book_clubs.php?msg=" . urlencode("You must be an active member to join a club."));
    exit();
}

if ($club_id > 0) {
    // Check if already a member
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Book_Club_Members WHERE ClubID = ? AND UserID = ?");
    $stmt->execute([$club_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Book_Club_Members (ClubID, UserID, Role) VALUES (?, ?, 'member')");
            $stmt->execute([$club_id, $user_id]);
            $msg = "Successfully joined the club.";
        } catch (PDOException $e) {
            $msg = "Database error: " . $e->getMessage();
        }
    } else {
        $msg = "You are already a member of this club.";
    }
    header("Location: book_clubs.php?msg=" . urlencode($msg));
    exit();
} else {
    header("Location: book_clubs.php?msg=" . urlencode("Invalid club."));
    exit();
} 