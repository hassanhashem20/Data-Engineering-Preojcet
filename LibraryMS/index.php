<?php
require_once 'config.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Authentication check
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Check if user is admin
function isAdmin() {
    return getUserRole() === 'Admin';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Debug information
    error_log("Login attempt - Username: " . $username);
    
    $stmt = $pdo->prepare("SELECT UserID, Username, Password, Role FROM Users WHERE Username = ? AND IsActive = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        error_log("User found in database");
        error_log("Stored password hash: " . $user['Password']);
        error_log("Verifying password...");
        
        if (password_verify($password, $user['Password'])) {
            error_log("Password verified successfully");
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            error_log("Session variables set: " . print_r($_SESSION, true));
            header("Location: index.php");
            exit();
        } else {
            error_log("Password verification failed");
            $error = "Invalid password for user: " . htmlspecialchars($username);
        }
    } else {
        error_log("User not found in database");
        $error = "User not found: " . htmlspecialchars($username);
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get books with pagination
function getBooks($page = 1, $limit = 10) {
    global $pdo;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.CategoryName, s.StatusName 
        FROM Book b 
        LEFT JOIN Book_Categories c ON b.CategoryID = c.CategoryID 
        LEFT JOIN Book_Status s ON b.StatusID = s.StatusID 
        ORDER BY b.Title 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get total number of books
function getTotalBooks() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM Book");
    return $stmt->fetchColumn();
}

// Get current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books = getBooks($current_page);
$total_books = getTotalBooks();
$total_pages = ceil($total_books / 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <style>
        .book-card {
            height: 100%;
        }
        .book-cover {
            height: 200px;
            object-fit: cover;
        }
    </style>
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="books.php">Books</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members.php">Members</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="borrowings.php">Borrowings</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documentation.php">Documentation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="api/documentation.php">API</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?logout=1">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!isLoggedIn()): ?>
            <!-- Login Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Login</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Book List -->
            <h2>Available Books</h2>
            <div class="row row-cols-1 row-cols-md-4 g-4">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card">
                            <?php if ($book['CoverImage']): ?>
                                <img src="<?php echo htmlspecialchars($book['CoverImage']); ?>" class="card-img-top book-cover" alt="Book cover">
                            <?php else: ?>
                                <div class="card-img-top book-cover bg-light d-flex align-items-center justify-content-center">
                                    <span class="text-muted">No cover available</span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['Title']); ?></h5>
                                <p class="card-text">
                                    <strong>Author:</strong> <?php echo htmlspecialchars($book['Author']); ?><br>
                                    <strong>Category:</strong> <?php echo htmlspecialchars($book['CategoryName']); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($book['StatusName']); ?>
                                </p>
                                <a href="book_details.php?isbn=<?php echo urlencode($book['ISBN']); ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 