<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }

if (!isAdmin() && !isAssistant()) {
    die('Access denied.');
}

$type = $_GET['type'] ?? '';

if ($type === 'all') {
    // Export all reports in a single CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=library_reports.csv');
    $out = fopen('php://output', 'w');
    
    // Borrowing Trends
    fputcsv($out, ['Borrowing Trends (Last 12 Months)']);
    fputcsv($out, ['Month', 'Borrowings']);
    $stmt = $pdo->query("SELECT DATE_FORMAT(BorrowDate, '%Y-%m') as Month, COUNT(*) as Borrowings FROM Borrowing GROUP BY Month ORDER BY Month DESC LIMIT 12");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        fputcsv($out, $row);
    }
    fputcsv($out, []); // Empty row for separation
    
    // Fine Stats
    fputcsv($out, ['Fine Collection Statistics (Last 12 Months)']);
    fputcsv($out, ['Month', 'TotalFines']);
    $stmt = $pdo->query("SELECT DATE_FORMAT(PaymentDate, '%Y-%m') as Month, SUM(AmountPaid) as TotalFines FROM Fine_Payments GROUP BY Month ORDER BY Month DESC LIMIT 12");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        fputcsv($out, $row);
    }
    fputcsv($out, []); // Empty row for separation
    
    // Book Popularity
    fputcsv($out, ['Book Popularity (Top 10 Most Borrowed Books)']);
    fputcsv($out, ['Title', 'TimesBorrowed']);
    $stmt = $pdo->query("SELECT b.Title, COUNT(br.BorrowID) as TimesBorrowed FROM Book b JOIN Borrowing br ON b.ISBN = br.ISBN GROUP BY b.Title ORDER BY TimesBorrowed DESC LIMIT 10");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        fputcsv($out, $row);
    }
    fputcsv($out, []); // Empty row for separation
    
    // Member Engagement
    fputcsv($out, ['Member Engagement (Top 10 Members by Borrowings)']);
    fputcsv($out, ['Username', 'Borrowings']);
    $stmt = $pdo->query("SELECT u.Username, COUNT(br.BorrowID) as Borrowings FROM Users u JOIN Borrowing br ON u.UserID = br.MemberID GROUP BY u.Username ORDER BY Borrowings DESC LIMIT 10");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        fputcsv($out, $row);
    }
    
    fclose($out);
    exit;
}

// Handle individual report exports
switch ($type) {
    case 'borrowing_trends':
        $filename = 'borrowing_trends.csv';
        $stmt = $pdo->query("SELECT DATE_FORMAT(BorrowDate, '%Y-%m') as Month, COUNT(*) as Borrowings FROM Borrowing GROUP BY Month ORDER BY Month DESC LIMIT 12");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Month', 'Borrowings'];
        break;
    case 'fine_stats':
        $filename = 'fine_stats.csv';
        $stmt = $pdo->query("SELECT DATE_FORMAT(PaymentDate, '%Y-%m') as Month, SUM(AmountPaid) as TotalFines FROM Fine_Payments GROUP BY Month ORDER BY Month DESC LIMIT 12");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Month', 'TotalFines'];
        break;
    case 'book_popularity':
        $filename = 'book_popularity.csv';
        $stmt = $pdo->query("SELECT b.Title, COUNT(br.BorrowID) as TimesBorrowed FROM Book b JOIN Borrowing br ON b.ISBN = br.ISBN GROUP BY b.Title ORDER BY TimesBorrowed DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Title', 'TimesBorrowed'];
        break;
    case 'member_engagement':
        $filename = 'member_engagement.csv';
        $stmt = $pdo->query("SELECT u.Username, COUNT(br.BorrowID) as Borrowings FROM Users u JOIN Borrowing br ON u.UserID = br.MemberID GROUP BY u.Username ORDER BY Borrowings DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Username', 'Borrowings'];
        break;
    default:
        die('Invalid report type.');
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=' . $filename);
$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit; 