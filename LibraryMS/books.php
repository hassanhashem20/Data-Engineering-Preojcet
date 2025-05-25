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

// Handle book deletion
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    $stmt = $pdo->prepare("DELETE FROM Book WHERE ISBN = ?");
    $stmt->execute([$book_id]);
    header("Location: books.php?message=Book deleted successfully");
    exit();
}

// Get all books with their categories and status
$stmt = $pdo->query("
    SELECT b.*, c.CategoryName, s.StatusName 
    FROM Book b 
    LEFT JOIN Book_Categories c ON b.CategoryID = c.CategoryID 
    LEFT JOIN Book_Status s ON b.StatusID = s.StatusID 
    ORDER BY b.Title
");
$books = $stmt->fetchAll();

// Get all categories for the form
$categories = $pdo->query("SELECT * FROM Book_Categories ORDER BY CategoryName")->fetchAll();

// Get all statuses for the form
$statuses = $pdo->query("SELECT * FROM Book_Status ORDER BY StatusName")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Management - Library Management System</title>
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
                        <a class="nav-link active" href="books.php"><i class="fas fa-book"></i> Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php"><i class="fas fa-users"></i> Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="borrowings.php"><i class="fas fa-hand-holding"></i> Borrowings</a>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell"></i>
                                <?php $notifCount = getReservationNotificationCount($pdo); if ($notifCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $notifCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
            <h2><i class="fas fa-books"></i> Books Management</h2>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                    <i class="fas fa-plus-circle"></i> Add New Book
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-book"></i> Title</th>
                        <th><i class="fas fa-user-edit"></i> Author</th>
                        <th><i class="fas fa-barcode"></i> ISBN</th>
                        <th><i class="fas fa-tags"></i> Category</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-calendar"></i> Publication Year</th>
                        <th><i class="fas fa-building"></i> Publisher</th>
                        <th><i class="fas fa-image"></i> Cover</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['Title'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($book['Author'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($book['ISBN'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($book['CategoryName'] ?? ''); ?></td>
                            <td>
                                <?php
                                $status = $book['StatusName'] ?? '';
                                $statusText = $status ? htmlspecialchars($status) : 'Unknown';
                                $statusClass = 'secondary'; // gray by default
                                if (strtolower($status) === 'available') {
                                    $statusClass = 'success'; // green
                                } elseif ($status) {
                                    $statusClass = 'danger'; // red for any other known status
                                }
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($book['PublicationYear'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($book['Publisher'] ?? ''); ?></td>
                            <td>
                                <?php if (!empty($book['CoverImage'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['CoverImage']); ?>" alt="Cover" style="width:40px;height:60px;object-fit:cover;">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewBook('<?php echo $book['ISBN']; ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editBook('<?php echo $book['ISBN']; ?>')">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                        <input type="hidden" name="book_id" value="<?php echo $book['ISBN']; ?>">
                                        <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_book.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label"><i class="fas fa-book"></i> Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label"><i class="fas fa-user-edit"></i> Author</label>
                            <input type="text" class="form-control" id="author" name="author" required>
                        </div>
                        <div class="mb-3">
                            <label for="isbn" class="form-label"><i class="fas fa-barcode"></i> ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label"><i class="fas fa-tags"></i> Category</label>
                            <select class="form-select" id="category" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['CategoryID']; ?>">
                                        <?php echo htmlspecialchars($category['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="publication_year" class="form-label"><i class="fas fa-calendar"></i> Publication Year</label>
                            <input type="number" class="form-control" id="publication_year" name="publication_year" required>
                        </div>
                        <div class="mb-3">
                            <label for="publisher" class="form-label"><i class="fas fa-building"></i> Publisher</label>
                            <input type="text" class="form-control" id="publisher" name="publisher" required>
                        </div>
                        <div class="mb-3">
                            <label for="cover_image" class="form-label"><i class="fas fa-image"></i> Cover Image URL</label>
                            <input type="url" class="form-control" id="cover_image" name="cover_image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBook(isbn) {
            window.location.href = `book_details.php?isbn=${isbn}`;
        }

        function editBook(isbn) {
            window.location.href = `edit_book.php?isbn=${isbn}`;
        }
    </script>
</body>
</html> 