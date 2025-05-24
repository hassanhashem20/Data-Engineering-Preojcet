<?php
// Database configuration
$host = '127.0.0.1';
$dbname = 'libraryms';
$username = 'root';
$password = '123456'; // Default XAMPP MySQL password is empty

// Create connection using mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\nPlease check your MySQL root password in XAMPP.");
}

// Set charset to utf8
$conn->set_charset("utf8");

// Create PDO connection for backward compatibility
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\nPlease check your MySQL root password in XAMPP.");
}
?> 