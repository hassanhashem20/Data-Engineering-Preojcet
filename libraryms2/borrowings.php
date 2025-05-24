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

// Handle return book (admin/librarian/assistant)
if (isset($_POST['return_borrowing']) && in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])) {
    $borrowing_id = $_POST['borrowing_id'];
    // Get the ISBN and due date of the returned borrowing
    $stmt = $pdo->prepare("SELECT ISBN, BorrowDate, DueDate FROM Borrowing WHERE BorrowID = ?");
    $stmt->execute([$borrowing_id]);
    $row = $stmt->fetch();
    $isbn = $row ? $row['ISBN'] : null;

    // Mark the book as returned
    $stmt = $pdo->prepare("UPDATE Borrowing SET ActualReturnDate = CURDATE(), Status = 'Returned' WHERE BorrowID = ? AND ActualReturnDate IS NULL");
    $stmt->execute([$borrowing_id]);

    // Update book status to 'Available'
    if ($isbn) {
        $stmt = $pdo->prepare("UPDATE Book SET StatusID = (SELECT StatusID FROM Book_Status WHERE StatusName = 'Available' LIMIT 1) WHERE ISBN = ?");
        $stmt->execute([$isbn]);
    }

    // Notify the next user in the reservation queue (if any)
    if ($isbn) {
        $stmt = $pdo->prepare("SELECT ReservationID FROM Reservations WHERE ISBN = ? AND Status = 'active' AND Notified = 0 ORDER BY ReservationDate ASC LIMIT 1");
        $stmt->execute([$isbn]);
        $reservation = $stmt->fetch();
        if ($reservation) {
            $stmt = $pdo->prepare("UPDATE Reservations SET Notified = 1 WHERE ReservationID = ?");
            $stmt->execute([$reservation['ReservationID']]);
        }
    }

    header("Location: borrowings.php?message=Book marked as returned");
    exit();
}

// Get all borrowings (join with book and user)
$stmt = $pdo->query("
    SELECT br.BorrowID, br.BorrowDate, br.DueDate, br.ActualReturnDate, b.Title, b.ISBN, u.FirstName, u.LastName, u.Username
    FROM Borrowing br
    JOIN Book b ON br.ISBN = b.ISBN
    JOIN Users u ON br.MemberID = u.UserID
    ORDER BY br.BorrowDate DESC
");
$borrowings = $stmt->fetchAll();

// Get all members for the issue form
$members = $pdo->query("SELECT UserID, FirstName, LastName, Username FROM Users WHERE Role != 'Admin' ORDER BY FirstName, LastName")->fetchAll();
// Get all available books for the issue form
$books = $pdo->query("SELECT ISBN, Title FROM Book WHERE ISBN NOT IN (SELECT ISBN FROM Borrowing WHERE ActualReturnDate IS NULL)")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowings Management - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-book-reader"></i> Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="books.php"><i class="fas fa-book"></i> Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php"><i class="fas fa-users"></i> Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="borrowings.php"><i class="fas fa-hand-holding"></i> Borrowings</a>
                    </li>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php"><i class="fas fa-cog"></i> Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documentation.php"><i class="fas fa-file-alt"></i> Documentation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="api/documentation.php"><i class="fas fa-code"></i> API</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-hand-holding"></i> Borrowings Management</h2>
            <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian'])): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueBookModal">
                    <i class="fas fa-plus-circle"></i> Issue Book
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-book"></i> Book</th>
                        <th><i class="fas fa-barcode"></i> ISBN</th>
                        <th><i class="fas fa-user"></i> Member</th>
                        <th><i class="fas fa-calendar-plus"></i> Borrow Date</th>
                        <th><i class="fas fa-calendar-check"></i> Return Date</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-clock"></i> Overdue</th>
                        <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])): ?><th><i class="fas fa-cogs"></i> Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowings as $borrowing): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($borrowing['Title']); ?></td>
                            <td><?php echo htmlspecialchars($borrowing['ISBN']); ?></td>
                            <td><?php echo htmlspecialchars($borrowing['FirstName'] . ' ' . $borrowing['LastName']); ?> (<?php echo htmlspecialchars($borrowing['Username']); ?>)</td>
                            <td><?php echo htmlspecialchars($borrowing['BorrowDate']); ?></td>
                            <td><?php echo $borrowing['ActualReturnDate'] ? htmlspecialchars($borrowing['ActualReturnDate']) : '<span class="text-warning"><i class="fas fa-exclamation-circle"></i> Not returned</span>'; ?></td>
                            <td>
                                <?php if ($borrowing['ActualReturnDate']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Returned</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-book-reader"></i> Borrowed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$borrowing['ActualReturnDate'] && strtotime($borrowing['DueDate']) < strtotime(date('Y-m-d'))): ?>
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Overdue</span>
                                <?php elseif (!$borrowing['ActualReturnDate']): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-clock"></i> On Time</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> -</span>
                                <?php endif; ?>
                            </td>
                            <?php if (in_array($_SESSION['role'], ['Admin', 'Librarian', 'Assistant'])): ?>
                            <td>
                                <?php if (!$borrowing['ActualReturnDate']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Mark this book as returned?');">
                                    <input type="hidden" name="borrowing_id" value="<?php echo $borrowing['BorrowID']; ?>">
                                    <button type="submit" name="return_borrowing" class="btn btn-sm btn-success">
                                        <i class="fas fa-check-circle"></i> Return
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
                    <h5 class="modal-title"><i class="fas fa-book"></i> Issue Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="issue_book.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="member" class="form-label"><i class="fas fa-user"></i> Member</label>
                            <select class="form-select" id="member" name="member_id" required>
                                <option value="">Select Member</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['UserID']; ?>">
                                        <?php echo htmlspecialchars($member['FirstName'] . ' ' . $member['LastName'] . ' (' . $member['Username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="book" class="form-label"><i class="fas fa-book"></i> Book</label>
                            <select class="form-select" id="book" name="isbn" required>
                                <option value="">Select Book</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo $book['ISBN']; ?>">
                                        <?php echo htmlspecialchars($book['Title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label"><i class="fas fa-calendar"></i> Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Issue Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 