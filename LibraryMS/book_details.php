<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if ISBN is provided
if (!isset($_GET['isbn'])) {
    header("Location: books.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get book details with category and status
    $stmt = $pdo->prepare("
        SELECT b.*, c.CategoryName, s.StatusName 
        FROM Book b 
        LEFT JOIN Book_Categories c ON b.CategoryID = c.CategoryID 
        LEFT JOIN Book_Status s ON b.StatusID = s.StatusID 
        WHERE b.ISBN = ?
    ");
    $stmt->execute([$_GET['isbn']]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header("Location: books.php?error=Book not found");
        exit();
    }
    
    // Get borrowing history
    $stmt = $pdo->prepare("
        SELECT b.*, u.Username, u.FirstName, u.LastName
        FROM Borrowings b
        JOIN Users u ON b.UserID = u.UserID
        WHERE b.ISBN = ?
        ORDER BY b.BorrowDate DESC
    ");
    $stmt->execute([$_GET['isbn']]);
    $borrowings = $stmt->fetchAll();
    
    // Check if the book is currently borrowed
    $is_borrowed = ($book['StatusName'] !== 'Available');

    // Reservation logic
    $reservation_message = '';
    if (isLoggedIn()) {
        // Check if user already has an active reservation for this book
        $stmt = $pdo->prepare("SELECT * FROM Reservations WHERE ISBN = ? AND UserID = ? AND Status = 'active'");
        $stmt->execute([$book['ISBN'], $_SESSION['user_id']]);
        $user_reservation = $stmt->fetch();
        
        // Handle reservation request
        if (isset($_POST['reserve_book']) && $is_borrowed && !$user_reservation) {
            $stmt = $pdo->prepare("INSERT INTO Reservations (ISBN, UserID) VALUES (?, ?)");
            $stmt->execute([$book['ISBN'], $_SESSION['user_id']]);
            $reservation_message = 'Reservation placed! You will be notified when the book is available.';
            // Refresh to avoid resubmission
            header("Location: book_details.php?isbn=" . urlencode($book['ISBN']) . "&reserved=1");
            exit();
        }
        // Show message if just reserved
        if (isset($_GET['reserved'])) {
            $reservation_message = 'Reservation placed! You will be notified when the book is available.';
        }
        // Show message if already reserved
        if ($user_reservation) {
            $reservation_message = 'You have already reserved this book.';
        }
    }
    
} catch(PDOException $e) {
    header("Location: books.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['Title']); ?> - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
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
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
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
        <?php if ($reservation_message): ?>
            <div class="alert alert-info"><?php echo $reservation_message; ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php echo htmlspecialchars($book['Title']); ?></h4>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <a href="edit_book.php?isbn=<?php echo $book['ISBN']; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Author:</strong>
                            </div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($book['Author']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>ISBN:</strong>
                            </div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($book['ISBN']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Category:</strong>
                            </div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($book['CategoryName']); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Status:</strong>
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-<?php echo $book['StatusName'] === 'Available' ? 'success' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($book['StatusName']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($book['PublicationYear']): ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Publication Year:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php echo htmlspecialchars($book['PublicationYear']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($book['Publisher']): ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Publisher:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php echo htmlspecialchars($book['Publisher']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Borrowing History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($borrowings): ?>
                            <div class="list-group">
                                <?php foreach ($borrowings as $borrowing): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($borrowing['FirstName'] . ' ' . $borrowing['LastName']); ?>
                                            </h6>
                                            <small>
                                                <?php echo date('M d, Y', strtotime($borrowing['BorrowDate'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <?php if ($borrowing['ReturnDate']): ?>
                                                Returned on: <?php echo date('M d, Y', strtotime($borrowing['ReturnDate'])); ?>
                                            <?php else: ?>
                                                <span class="text-warning">Not returned yet</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No borrowing history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="books.php" class="btn btn-secondary">Back to Books</a>
        </div>
        <?php if (true): ?>
            <form method="POST" class="mt-3">
                <button type="submit" name="reserve_book" class="btn btn-warning">Reserve this Book</button>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 