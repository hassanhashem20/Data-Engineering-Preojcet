<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debug: Print all books
    echo "<h3>All Books:</h3>";
    $stmt = $pdo->query("
        SELECT b.*, s.StatusName 
        FROM Book b 
        LEFT JOIN Book_Status s ON b.StatusID = s.StatusID
    ");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Debug: Print current user info
    if (isset($_SESSION['user_id'])) {
        echo "<h3>Current User Info:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        echo "<pre>";
        print_r($stmt->fetch(PDO::FETCH_ASSOC));
        echo "</pre>";
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 