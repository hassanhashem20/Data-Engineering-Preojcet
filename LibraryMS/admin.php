<?php
require_once 'config.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get stats
    $total_books = $pdo->query("SELECT COUNT(*) FROM Book")->fetchColumn();
    $total_members = $pdo->query("SELECT COUNT(*) FROM Users WHERE Role != 'Admin'")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
    $total_borrowings = $pdo->query("SELECT COUNT(*) FROM Borrowings")->fetchColumn();
    $total_categories = $pdo->query("SELECT COUNT(*) FROM Book_Categories")->fetchColumn();
    $total_statuses = $pdo->query("SELECT COUNT(*) FROM Book_Status")->fetchColumn();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="borrowings.php">Borrowings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Admin Panel</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?logout=1">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Books</h5>
                        <p class="card-text display-6"><?php echo $total_books; ?></p>
                        <a href="books.php" class="btn btn-outline-primary btn-sm">Manage Books</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Members</h5>
                        <p class="card-text display-6"><?php echo $total_members; ?></p>
                        <a href="members.php" class="btn btn-outline-primary btn-sm">Manage Members</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Borrowings</h5>
                        <p class="card-text display-6"><?php echo $total_borrowings; ?></p>
                        <a href="borrowings.php" class="btn btn-outline-primary btn-sm">Manage Borrowings</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Categories</h5>
                        <p class="card-text display-6"><?php echo $total_categories; ?></p>
                        <a href="manage_categories.php" class="btn btn-outline-primary btn-sm">Manage Categories</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Statuses</h5>
                        <p class="card-text display-6"><?php echo $total_statuses; ?></p>
                        <a href="manage_statuses.php" class="btn btn-outline-primary btn-sm">Manage Statuses</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <p class="card-text display-6"><?php echo $total_users; ?></p>
                        <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <h4>Quick Links</h4>
        <ul>
            <li><a href="books.php">Books Management</a></li>
            <li><a href="members.php">Members Management</a></li>
            <li><a href="borrowings.php">Borrowings Management</a></li>
            <li><a href="manage_categories.php">Manage Book Categories</a></li>
            <li><a href="manage_statuses.php">Manage Book Statuses</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 