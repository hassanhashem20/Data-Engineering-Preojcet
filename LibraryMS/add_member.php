<?php
require_once 'config.php';

// Only allow admins to add members
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check for duplicate username or email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ? OR Email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: members.php?message=" . urlencode("Username or email already exists."));
            exit();
        }

        // Insert the new member
        $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, Email, Role, FirstName, LastName) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $role, $first_name, $last_name]);

        header("Location: members.php?message=Member added successfully");
        exit();
    } catch(PDOException $e) {
        header("Location: members.php?message=" . urlencode("Error adding member: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: members.php");
    exit();
} 