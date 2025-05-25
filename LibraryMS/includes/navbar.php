<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session (remove in production)
// echo "<pre>"; print_r($_SESSION); echo "</pre>";
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-book-reader"></i> Library Management System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>" href="books.php">
                            <i class="fas fa-book"></i> Books
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'borrowings.php' ? 'active' : ''; ?>" href="borrowings.php">
                            <i class="fas fa-hand-holding"></i> Borrowings
                        </a>
                    </li>

                    <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                        <!-- Admin Menu Items -->
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
                                <li>
                                    <a class="dropdown-item" href="manage_users.php">
                                        <i class="fas fa-users"></i> Manage Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_categories.php">
                                        <i class="fas fa-tags"></i> Manage Categories
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_statuses.php">
                                        <i class="fas fa-info-circle"></i> Manage Statuses
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage_vendors.php">
                                        <i class="fas fa-truck"></i> Manage Vendors
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

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'book_clubs.php' ? 'active' : ''; ?>" href="book_clubs.php">
                            <i class="fas fa-users"></i> Book Clubs
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'feedback.php' ? 'active' : ''; ?>" href="feedback.php">
                            <i class="fas fa-comment"></i> Feedback
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Add notification count logic here if needed
                            ?>
                        </a>
                    </li>
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
                    <li class="nav-item">
                        <a class="nav-link" href="register_librarian.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Add Bootstrap JS for dropdown functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script> 