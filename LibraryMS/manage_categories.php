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

    // Handle add category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        if ($name !== '') {
            $stmt = $pdo->prepare("INSERT INTO Book_Categories (CategoryName) VALUES (?)");
            $stmt->execute([$name]);
            header("Location: manage_categories.php?message=Category added successfully");
            exit();
        }
    }

    // Handle edit category
    if (isset($_POST['edit_category'])) {
        $id = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        if ($name !== '') {
            $stmt = $pdo->prepare("UPDATE Book_Categories SET CategoryName = ? WHERE CategoryID = ?");
            $stmt->execute([$name, $id]);
            header("Location: manage_categories.php?message=Category updated successfully");
            exit();
        }
    }

    // Handle delete category
    if (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        $stmt = $pdo->prepare("DELETE FROM Book_Categories WHERE CategoryID = ?");
        $stmt->execute([$id]);
        header("Location: manage_categories.php?message=Category deleted successfully");
        exit();
    }

    // Get all categories
    $categories = $pdo->query("SELECT * FROM Book_Categories ORDER BY CategoryName")->fetchAll();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Book Categories - Admin Panel</title>
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
        <h2>Manage Book Categories</h2>
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="category_name" placeholder="New category name" required>
                        <button class="btn btn-primary" type="submit" name="add_category">Add Category</button>
                    </div>
                </form>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <form method="POST">
                                <td>
                                    <input type="hidden" name="category_id" value="<?php echo $cat['CategoryID']; ?>">
                                    <input type="text" class="form-control" name="category_name" value="<?php echo htmlspecialchars($cat['CategoryName']); ?>" required>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" type="submit" name="edit_category">Save</button>
                                    <button class="btn btn-sm btn-danger" type="submit" name="delete_category" onclick="return confirm('Delete this category?');">Delete</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <a href="admin.php" class="btn btn-secondary mt-3">Back to Admin Panel</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 