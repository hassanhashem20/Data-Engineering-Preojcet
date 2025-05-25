<?php
require_once 'config.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: index.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get stats with error handling
    $stats = [];
    $queries = [
        'total_books' => "SELECT COUNT(*) FROM Book",
        'total_members' => "SELECT COUNT(*) FROM Users WHERE LOWER(Role) != 'admin'",
        'total_users' => "SELECT COUNT(*) FROM Users",
        'total_borrowings' => "SELECT COUNT(*) FROM Borrowing",
        'total_categories' => "SELECT COUNT(*) FROM Book_Categories",
        'total_statuses' => "SELECT COUNT(*) FROM Book_Status"
    ];

    foreach ($queries as $key => $query) {
        try {
            $stats[$key] = $pdo->query($query)->fetchColumn();
        } catch (PDOException $e) {
            $stats[$key] = 0;
            error_log("Error fetching $key: " . $e->getMessage());
        }
    }
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Books</h5>
                        <p class="card-text display-6"><?php echo $stats['total_books']; ?></p>
                        <a href="books.php" class="btn btn-outline-primary btn-sm">Manage Books</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Members</h5>
                        <p class="card-text display-6"><?php echo $stats['total_members']; ?></p>
                        <a href="members.php" class="btn btn-outline-primary btn-sm">Manage Members</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Borrowings</h5>
                        <p class="card-text display-6"><?php echo $stats['total_borrowings']; ?></p>
                        <a href="borrowings.php" class="btn btn-outline-primary btn-sm">Manage Borrowings</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Categories</h5>
                        <p class="card-text display-6"><?php echo $stats['total_categories']; ?></p>
                        <a href="manage_categories.php" class="btn btn-outline-primary btn-sm">Manage Categories</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Statuses</h5>
                        <p class="card-text display-6"><?php echo $stats['total_statuses']; ?></p>
                        <a href="manage_statuses.php" class="btn btn-outline-primary btn-sm">Manage Statuses</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <p class="card-text display-6"><?php echo $stats['total_users']; ?></p>
                        <a href="manage_users.php" class="btn btn-outline-primary btn-sm">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <h4>Quick Links</h4>
        <div class="list-group">
            <a href="books.php" class="list-group-item list-group-item-action">
                <i class="fas fa-book"></i> Books Management
            </a>
            <a href="members.php" class="list-group-item list-group-item-action">
                <i class="fas fa-users"></i> Members Management
            </a>
            <a href="borrowings.php" class="list-group-item list-group-item-action">
                <i class="fas fa-hand-holding"></i> Borrowings Management
            </a>
            <a href="manage_categories.php" class="list-group-item list-group-item-action">
                <i class="fas fa-tags"></i> Manage Book Categories
            </a>
            <a href="manage_statuses.php" class="list-group-item list-group-item-action">
                <i class="fas fa-info-circle"></i> Manage Book Statuses
            </a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">
                <i class="fas fa-user-cog"></i> Manage Users
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 