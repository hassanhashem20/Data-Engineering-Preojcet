<?php
require_once 'config.php';

// Debug: log the session role
error_log('Session role: ' . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));

// Only allow librarians to issue books (case-insensitive)
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'librarian') {
    header("Location: borrowings.php?message=" . urlencode("Only librarians can issue books."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $isbn = $_POST['isbn'];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the book is already borrowed and not returned
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Borrowings WHERE ISBN = ? AND ReturnDate IS NULL");
        $stmt->execute([$isbn]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: borrowings.php?message=" . urlencode("This book is already borrowed and not yet returned."));
            exit();
        }

        // Insert the borrowing record
        $stmt = $pdo->prepare("INSERT INTO Borrowings (ISBN, UserID, BorrowDate) VALUES (?, ?, CURDATE())");
        $stmt->execute([$isbn, $member_id]);

        // Update book status to 'Borrowed'
        $stmt = $pdo->prepare("UPDATE Book SET StatusID = (SELECT StatusID FROM Book_Status WHERE StatusName = 'Borrowed' LIMIT 1) WHERE ISBN = ?");
        $stmt->execute([$isbn]);

        header("Location: borrowings.php?message=Book issued successfully");
        exit();
    } catch(PDOException $e) {
        header("Location: borrowings.php?message=" . urlencode("Error issuing book: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: borrowings.php");
    exit();
} 