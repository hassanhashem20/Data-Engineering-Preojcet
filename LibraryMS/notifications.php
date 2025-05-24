<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark all notifications as read if requested
    if (isset($_POST['mark_read'])) {
        $stmt = $pdo->prepare("UPDATE Reservations SET Notified = 0, Status = 'fulfilled' WHERE UserID = ? AND Notified = 1 AND Status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        header("Location: notifications.php?message=Notifications marked as read");
        exit();
    }

    // Get all active notifications for the user
    $stmt = $pdo->prepare("
        SELECT r.ReservationID, r.ReservationDate, b.Title, b.ISBN
        FROM Reservations r
        JOIN Book b ON r.ISBN = b.ISBN
        WHERE r.UserID = ? AND r.Notified = 1 AND r.Status = 'active'
        ORDER BY r.ReservationDate DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
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
        <h2>Notifications</h2>
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>
        <?php if (count($notifications) > 0): ?>
            <form method="POST" class="mb-3">
                <button type="submit" name="mark_read" class="btn btn-success">Mark all as read</button>
            </form>
            <ul class="list-group">
                <?php foreach ($notifications as $notif): ?>
                    <li class="list-group-item">
                        <strong><?php echo htmlspecialchars($notif['Title']); ?></strong> (ISBN: <?php echo htmlspecialchars($notif['ISBN']); ?>)
                        <br>Your reserved book is now available!
                        <br><small class="text-muted">Reserved on: <?php echo htmlspecialchars($notif['ReservationDate']); ?></small>
                        <a href="book_details.php?isbn=<?php echo urlencode($notif['ISBN']); ?>" class="btn btn-link btn-sm">View Book</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info">No new notifications.</div>
        <?php endif; ?>
        <a href="books.php" class="btn btn-secondary mt-3">Back to Books</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 