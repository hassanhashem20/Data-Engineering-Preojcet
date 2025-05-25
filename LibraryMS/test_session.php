<?php
session_start();
require_once 'config/database.php';

// Debug information
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "<p>User is logged in as: " . htmlspecialchars($_SESSION['username']) . "</p>";
    echo "<p>User role is: " . htmlspecialchars($_SESSION['role']) . "</p>";
    
    // Get detailed user information from database
    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Database User Information:</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Check if role matches
        if ($user['Role'] !== $_SESSION['role']) {
            echo "<div style='color: red;'>WARNING: Database role ({$user['Role']}) does not match session role ({$_SESSION['role']})</div>";
        }
    } catch (PDOException $e) {
        echo "<p>Database Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>No user is logged in</p>";
}

// Show all users in the system
try {
    $stmt = $pdo->query("SELECT UserID, Username, Role FROM Users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>All Users in System:</h2>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p>Error fetching users: " . $e->getMessage() . "</p>";
}
?> 