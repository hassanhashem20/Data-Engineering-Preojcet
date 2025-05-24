<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            INSERT INTO Book (Title, Author, ISBN, CategoryID, StatusID, PublicationYear, Publisher, CoverImage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Execute with form data, force StatusID to 1
        $stmt->execute([
            $_POST['title'],
            $_POST['author'],
            $_POST['isbn'],
            $_POST['category_id'],
            1, // StatusID set to 1 by default
            $_POST['publication_year'] ?? null,
            $_POST['publisher'] ?? null,
            $_POST['cover_image'] ?? null
        ]);
        
        // Redirect back to books page with success message
        header("Location: books.php?message=Book added successfully");
        exit();
        
    } catch(PDOException $e) {
        // If there's an error, redirect back with error message
        header("Location: books.php?error=" . urlencode("Error adding book: " . $e->getMessage()));
        exit();
    }
} else {
    // If not POST request, redirect to books page
    header("Location: books.php");
    exit();
} 