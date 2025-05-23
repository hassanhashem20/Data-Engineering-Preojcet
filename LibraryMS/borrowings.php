<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle return book (admin/librarian only)
if (isset($_POST['return_borrowing']) && in_array($_SESSION['role'], ['Admin', 'Librarian'])) {
    $borrowing_id = $_POST['borrowing_id'];
    // Get the ISBN of the returned borrowing
    $stmt = $pdo->prepare("SELECT ISBN FROM Borrowings WHERE BorrowingID = ?");
    $stmt->execute([$borrowing_id]);
    $row = $stmt->fetch();
    $isbn = $row ? $row['ISBN'] : null;

    // Mark the book as returned
    $stmt = $pdo->prepare("UPDATE Borrowings SET ReturnDate = CURDATE() WHERE BorrowingID = ? AND ReturnDate IS NULL");
    $stmt->execute([$borrowing_id]);

    // Update book status to 'Available'
    if ($isbn) {
        $stmt = $pdo->prepare("UPDATE Book SET StatusID = (SELECT StatusID FROM Book_Status WHERE StatusName = 'Available' LIMIT 1) WHERE ISBN = ?");
        $stmt->execute([$isbn]);
    }

    // Notify the next user in the reservation queue (if any)
    if ($isbn) {
        // Find the earliest active reservation for this ISBN
        $stmt = $pdo->prepare("SELECT ReservationID FROM Reservations WHERE ISBN = ? AND Status = 'active' AND Notified = 0 ORDER BY ReservationDate ASC LIMIT 1");
        $stmt->execute([$isbn]);
        $reservation = $stmt->fetch();
        if ($reservation) {
            // Set Notified = 1 for this reservation
            $stmt = $pdo->prepare("UPDATE Reservations SET Notified = 1 WHERE ReservationID = ?");
            $stmt->execute([$reservation['ReservationID']]);
        }
    }

    header("Location: borrowings.php?message=Book marked as returned");
    exit();
}

// Get all borrowings (join with book and user)
$stmt = $pdo->query("
    SELECT br.BorrowingID, br.BorrowDate, br.ReturnDate, b.Title, b.ISBN, u.FirstName, u.LastName, u.Username
    FROM Borrowings br
    JOIN Book b ON br.ISBN = b.ISBN
    JOIN Users u ON br.UserID = u.UserID
    ORDER BY br.BorrowDate DESC
");
$borrowings = $stmt->fetchAll();

// Get all members for the issue form
$members = $pdo->query("SELECT UserID, FirstName, LastName, Username FROM Users WHERE Role != 'Admin' ORDER BY FirstName, LastName")->fetchAll();
// Get all available books for the issue form
$books = $pdo->query("SELECT ISBN, Title FROM Book WHERE ISBN NOT IN (SELECT ISBN FROM Borrowings WHERE ReturnDate IS NULL)")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowings Management - Library Management System</title>
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
                        <a class="nav-link active" href="borrowings.php">Borrowings</a>
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
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Borrowings Management</h2>
            <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian'])): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueBookModal">
                    <i class="bi bi-plus-circle"></i> Issue Book
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>ISBN</th>
                        <th>Member</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian'])): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowings as $borrowing): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($borrowing['Title']); ?></td>
                            <td><?php echo htmlspecialchars($borrowing['ISBN']); ?></td>
                            <td><?php echo htmlspecialchars($borrowing['FirstName'] . ' ' . $borrowing['LastName']); ?> (<?php echo htmlspecialchars($borrowing['Username']); ?>)</td>
                            <td><?php echo htmlspecialchars($borrowing['BorrowDate']); ?></td>
                            <td><?php echo $borrowing['ReturnDate'] ? htmlspecialchars($borrowing['ReturnDate']) : '<span class="text-warning">Not returned</span>'; ?></td>
                            <td>
                                <?php if ($borrowing['ReturnDate']): ?>
                                    <span class="badge bg-success">Returned</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Borrowed</span>
                                <?php endif; ?>
                            </td>
                            <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian'])): ?>
                            <td>
                                <?php if (!$borrowing['ReturnDate']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Mark this book as returned?');">
                                    <input type="hidden" name="borrowing_id" value="<?php echo $borrowing['BorrowingID']; ?>">
                                    <button type="submit" name="return_borrowing" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-circle"></i> Return
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Issue Book Modal -->
    <div class="modal fade" id="issueBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Issue Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="issue_book.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="member_id" class="form-label">Member</label>
                            <select class="form-select" id="member_id" name="member_id" required>
                                <option value="">Select member</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['UserID']; ?>">
                                        <?php echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName'] . ' (' . $member['Username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="isbn" class="form-label">Book</label>
                            <select class="form-select" id="isbn" name="isbn" required>
                                <option value="">Select book</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo $book['ISBN']; ?>">
                                        <?php echo htmlspecialchars($book['Title'] . ' (' . $book['ISBN'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Issue Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 