<?php
session_start();
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
            // Update last login
            $stmt = $pdo->prepare("UPDATE Users SET LastLogin = NOW() WHERE UserID = ?");
            $stmt->execute([$user['UserID']]);
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

// Get current page for pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books = getBooks($current_page);
$total_books = getTotalBooks();
$total_pages = ceil($total_books / 10);
// Get current file for navbar highlighting
$navbar_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $navbar_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="books.php"><i class="fas fa-book"></i> Books</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="borrowings.php"><i class="fas fa-hand-holding"></i> Borrowings</a>
                        </li>
                        <!-- Book Clubs menu for all except vendors -->
                        <?php if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'vendor'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $navbar_page === 'book_clubs.php' ? 'active' : ''; ?>" href="book_clubs.php">
                                <i class="fas fa-users"></i> Book Clubs
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Admin Menu Items -->
                        <?php if ($navbar_page !== 'feedback.php' && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog"></i> Administration
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li>
                                    <a class="dropdown-item" href="admin.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="manage_users.php">
                                        <i class="fas fa-users-cog"></i> Manage Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_categories.php">
                                        <i class="fas fa-tags"></i> Manage Categories
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_statuses.php">
                                        <i class="fas fa-toggle-on"></i> Manage Statuses
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_vendors.php">
                                        <i class="fas fa-truck"></i> Manage Vendors
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="system_settings.php">
                                        <i class="fas fa-wrench"></i> System Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="generate_reports.php">
                                        <i class="fas fa-chart-bar"></i> Generate Reports
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php if (
                            isset($_SESSION['role']) && 
                            in_array(strtolower($_SESSION['role']), ['librarian', 'admin', 'assistant'])
                        ): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $navbar_page === 'feedback.php' ? 'active' : ''; ?>" href="feedback.php">
                                <i class="fas fa-comment-dots"></i> Feedback
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $navbar_page === 'pay_fine.php' ? 'active' : ''; ?>" href="pay_fine.php">
                                <i class="fas fa-money-bill-wave"></i> Fine Payments
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (
                            isset($_SESSION['role']) && 
                            in_array(strtolower($_SESSION['role']), ['admin', 'assistant'])
                        ): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $navbar_page === 'generate_reports.php' ? 'active' : ''; ?>" href="generate_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-id-card"></i> Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Login Form -->
        <?php if (!isset($_SESSION['user_id'])): ?>
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
                            <div class="mt-3 text-center">
                                <a href="register.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-user-plus"></i> Register Account
                                </a>
                            </div>
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
                        <div class="card h-100">
                            <?php if (!empty($book['CoverImage'])): ?>
                                <img src="<?php echo htmlspecialchars($book['CoverImage']); ?>" class="card-img-top book-cover" alt="Book cover">
                            <?php else: ?>
                                <div class="card-img-top book-cover d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-book fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['Title']); ?></h5>
                                <p class="card-text">
                                    <strong>Author:</strong> <?php echo htmlspecialchars($book['Author']); ?><br>
                                    <strong>Category:</strong> <?php echo htmlspecialchars($book['CategoryName'] ?? 'Uncategorized'); ?><br>
                                    <strong>Status:</strong> 
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
                                </p>
                                <a href="book_details.php?isbn=<?php echo htmlspecialchars($book['ISBN']); ?>" class="btn btn-primary">View Details</a>
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

        <!-- Features Section -->
        <div class="row mt-5 mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4"><i class="fas fa-star"></i> Key Features</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Book Management</h5>
                                <p class="card-text">Efficiently manage your library's collection with features for adding, editing, and tracking books.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Add new books</li>
                                    <li><i class="fas fa-check text-success"></i> Update book details</li>
                                    <li><i class="fas fa-check text-success"></i> Track book status</li>
                                    <li><i class="fas fa-check text-success"></i> Manage categories</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Member Management</h5>
                                <p class="card-text">Comprehensive member management system for tracking library users and their activities.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Member registration</li>
                                    <li><i class="fas fa-check text-success"></i> Role-based access</li>
                                    <li><i class="fas fa-check text-success"></i> Member history</li>
                                    <li><i class="fas fa-check text-success"></i> Fine management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-hand-holding fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Borrowing System</h5>
                                <p class="card-text">Streamlined process for managing book borrowings and returns with automated notifications.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Issue books</li>
                                    <li><i class="fas fa-check text-success"></i> Return tracking</li>
                                    <li><i class="fas fa-check text-success"></i> Due date reminders</li>
                                    <li><i class="fas fa-check text-success"></i> Overdue management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Reports & Analytics</h5>
                                <p class="card-text">Generate comprehensive reports and analytics to track library operations and usage.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Borrowing statistics</li>
                                    <li><i class="fas fa-check text-success"></i> Member activity</li>
                                    <li><i class="fas fa-check text-success"></i> Fine collection</li>
                                    <li><i class="fas fa-check text-success"></i> Inventory reports</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users-cog fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">User Management</h5>
                                <p class="card-text">Robust user management system with role-based access control and permissions.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Role management</li>
                                    <li><i class="fas fa-check text-success"></i> User permissions</li>
                                    <li><i class="fas fa-check text-success"></i> Activity logging</li>
                                    <li><i class="fas fa-check text-success"></i> Security features</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-comments fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Community Features</h5>
                                <p class="card-text">Engage with library members through various community features and feedback systems.</p>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check text-success"></i> Book clubs</li>
                                    <li><i class="fas fa-check text-success"></i> Feedback system</li>
                                    <li><i class="fas fa-check text-success"></i> Notifications</li>
                                    <li><i class="fas fa-check text-success"></i> Member interactions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 