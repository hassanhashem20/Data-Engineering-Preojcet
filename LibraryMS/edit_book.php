<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
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
    
    // Get book details
    $stmt = $pdo->prepare("SELECT * FROM Book WHERE ISBN = ?");
    $stmt->execute([$_GET['isbn']]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header("Location: books.php?error=Book not found");
        exit();
    }
    
    // Get all categories
    $categories = $pdo->query("SELECT * FROM Book_Categories ORDER BY CategoryName")->fetchAll();
    
    // Get all statuses
    $statuses = $pdo->query("SELECT * FROM Book_Status ORDER BY StatusName")->fetchAll();
    
} catch(PDOException $e) {
    header("Location: books.php?error=" . urlencode($e->getMessage()));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE Book 
            SET Title = ?, Author = ?, CategoryID = ?, StatusID = ?, 
                PublicationYear = ?, Publisher = ?
            WHERE ISBN = ?
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['author'],
            $_POST['category_id'],
            $_POST['status_id'],
            $_POST['publication_year'] ?? null,
            $_POST['publisher'] ?? null,
            $_GET['isbn']
        ]);
        
        header("Location: books.php?message=Book updated successfully");
        exit();
        
    } catch(PDOException $e) {
        $error = "Error updating book: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Book</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($book['Title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="author" name="author" 
                                       value="<?php echo htmlspecialchars($book['Author']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['CategoryID']; ?>" 
                                                <?php echo $category['CategoryID'] == $book['CategoryID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['CategoryName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status_id" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['StatusID']; ?>" 
                                                <?php echo $status['StatusID'] == $book['StatusID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['StatusName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="publication_year" class="form-label">Publication Year</label>
                                <input type="number" class="form-control" id="publication_year" name="publication_year" 
                                       value="<?php echo htmlspecialchars($book['PublicationYear'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="publisher" class="form-label">Publisher</label>
                                <input type="text" class="form-control" id="publisher" name="publisher" 
                                       value="<?php echo htmlspecialchars($book['Publisher'] ?? ''); ?>">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="books.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Book</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 