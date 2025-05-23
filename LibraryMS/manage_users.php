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

    // Handle search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = "WHERE Username LIKE ? OR Email LIKE ? OR FirstName LIKE ? OR LastName LIKE ?";
        $params = array_fill(0, 4, "%$search%");
    }

    // Handle update user
    if (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE Users SET Role = ?, IsActive = ? WHERE UserID = ?");
        $stmt->execute([$role, $is_active, $id]);
        header("Location: manage_users.php?message=User updated successfully");
        exit();
    }

    // Handle reset password
    if (isset($_POST['reset_password'])) {
        $id = $_POST['user_id'];
        $new_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE UserID = ?");
        $stmt->execute([$new_password, $id]);
        header("Location: manage_users.php?message=Password reset to 'password123'");
        exit();
    }

    // Handle delete user
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ? AND Role != 'Admin'");
        $stmt->execute([$id]);
        header("Location: manage_users.php?message=User deleted successfully");
        exit();
    }

    // Handle add user
    if (isset($_POST['add_user'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, Email, Role, FirstName, LastName, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $role, $first_name, $last_name, $is_active]);
        header("Location: manage_users.php?message=User added successfully");
        exit();
    }

    // Handle export CSV
    if (isset($_GET['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=users.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['UserID', 'Username', 'Email', 'FirstName', 'LastName', 'Role', 'IsActive', 'CreatedDate']);
        $sql = "SELECT UserID, Username, Email, FirstName, LastName, Role, IsActive, CreatedDate FROM Users ORDER BY FirstName, LastName";
        foreach ($pdo->query($sql) as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit();
    }

    // Get all users
    $sql = "SELECT UserID, Username, Email, FirstName, LastName, Role, IsActive FROM Users $where ORDER BY FirstName, LastName";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        <h2>Manage Users</h2>
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" method="GET">
                <input type="text" class="form-control" name="search" placeholder="Search by name, username, or email" value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-select" name="role_filter">
                    <option value="">All Roles</option>
                    <option value="Admin">Admin</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Assistant">Assistant</option>
                </select>
                <select class="form-select" name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <input type="date" class="form-control" name="reg_from" placeholder="From">
                <input type="date" class="form-control" name="reg_to" placeholder="To">
                <button class="btn btn-outline-secondary" type="submit">Filter</button>
            </form>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
                <a href="?export_csv=1" class="btn btn-outline-success">Export CSV</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <form method="POST">
                            <td><?php echo htmlspecialchars($user['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($user['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($user['Username']); ?></td>
                            <td><?php echo htmlspecialchars($user['Email']); ?></td>
                            <td>
                                <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                <select name="role" class="form-select form-select-sm">
                                    <option value="Admin" <?php if ($user['Role'] === 'Admin') echo 'selected'; ?>>Admin</option>
                                    <option value="Librarian" <?php if ($user['Role'] === 'Librarian') echo 'selected'; ?>>Librarian</option>
                                    <option value="Assistant" <?php if ($user['Role'] === 'Assistant') echo 'selected'; ?>>Assistant</option>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="is_active" value="1" <?php if ($user['IsActive']) echo 'checked'; ?>>
                            </td>
                            <td class="d-flex gap-1">
                                <button class="btn btn-sm btn-success" type="submit" name="update_user">Save</button>
                                <button class="btn btn-sm btn-warning" type="submit" name="reset_password" onclick="return confirm('Reset password to password123?');">Reset Password</button>
                                <button class="btn btn-sm btn-danger" type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#activityModal<?php echo $user['UserID']; ?>">View Activity</button>
                            </td>
                        </form>
                    </tr>
                    <!-- User Activity Modal -->
                    <div class="modal fade" id="activityModal<?php echo $user['UserID']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">User Activity: <?php echo htmlspecialchars($user['Username']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <h6>Current Borrowings</h6>
                                    <ul>
                                    <?php
                                    $stmtB = $pdo->prepare("SELECT b.Title, br.BorrowDate, br.ReturnDate FROM Borrowings br JOIN Book b ON br.ISBN = b.ISBN WHERE br.UserID = ? AND br.ReturnDate IS NULL");
                                    $stmtB->execute([$user['UserID']]);
                                    foreach ($stmtB->fetchAll() as $b) {
                                        echo '<li>' . htmlspecialchars($b['Title']) . ' (Borrowed: ' . htmlspecialchars($b['BorrowDate']) . ')</li>';
                                    }
                                    ?>
                                    </ul>
                                    <h6>Borrowing History</h6>
                                    <ul>
                                    <?php
                                    $stmtH = $pdo->prepare("SELECT b.Title, br.BorrowDate, br.ReturnDate FROM Borrowings br JOIN Book b ON br.ISBN = b.ISBN WHERE br.UserID = ?");
                                    $stmtH->execute([$user['UserID']]);
                                    foreach ($stmtH->fetchAll() as $h) {
                                        echo '<li>' . htmlspecialchars($h['Title']) . ' (Borrowed: ' . htmlspecialchars($h['BorrowDate']) . ', Returned: ' . ($h['ReturnDate'] ? htmlspecialchars($h['ReturnDate']) : 'Not returned') . ')</li>';
                                    }
                                    ?>
                                    </ul>
                                    <h6>Reservations</h6>
                                    <ul>
                                    <?php
                                    $stmtR = $pdo->prepare("SELECT r.ReservationDate, r.Status, b.Title FROM Reservations r JOIN Book b ON r.ISBN = b.ISBN WHERE r.UserID = ?");
                                    $stmtR->execute([$user['UserID']]);
                                    foreach ($stmtR->fetchAll() as $r) {
                                        echo '<li>' . htmlspecialchars($r['Title']) . ' (Reserved: ' . htmlspecialchars($r['ReservationDate']) . ', Status: ' . htmlspecialchars($r['Status']) . ')</li>';
                                    }
                                    ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="Admin">Admin</option>
                                    <option value="Librarian">Librarian</option>
                                    <option value="Assistant">Assistant</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Active</label>
                                <input type="checkbox" name="is_active" value="1" checked>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" name="add_user">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <a href="admin.php" class="btn btn-secondary mt-3">Back to Admin Panel</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 