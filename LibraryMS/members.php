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

// Handle member deletion (admin only)
if (isset($_POST['delete_member']) && $_SESSION['role'] === 'Admin') {
    $member_id = $_POST['member_id'];
    $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ? AND Role != 'Admin'");
    $stmt->execute([$member_id]);
    header("Location: members.php?message=Member deleted successfully");
    exit();
}

// Get all members (exclude admins)
$stmt = $pdo->query("SELECT UserID, Username, Email, FirstName, LastName, Role FROM Users WHERE Role != 'Admin' ORDER BY FirstName, LastName");
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Library Management System</title>
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
                        <a class="nav-link active" href="members.php"><i class="fas fa-users"></i> Members</a>
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
            <h2><i class="fas fa-users"></i> Members Management</h2>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="fas fa-user-plus"></i> Add New Member
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> First Name</th>
                        <th><i class="fas fa-user"></i> Last Name</th>
                        <th><i class="fas fa-user-tag"></i> Username</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-user-shield"></i> Role</th>
                        <?php if ($_SESSION['role'] === 'Admin'): ?><th><i class="fas fa-cogs"></i> Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($member['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($member['Username']); ?></td>
                            <td><?php echo htmlspecialchars($member['Email']); ?></td>
                            <td><?php echo htmlspecialchars($member['Role']); ?></td>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this member?');">
                                    <input type="hidden" name="member_id" value="<?php echo $member['UserID']; ?>">
                                    <button type="submit" name="delete_member" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_member.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user-tag"></i> Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label"><i class="fas fa-user-shield"></i> Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="Assistant">Assistant</option>
                                <option value="Librarian">Librarian</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 