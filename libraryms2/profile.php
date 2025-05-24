<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT Username, Email, Role, FirstName, LastName, CreatedDate, LastLogin FROM Users WHERE UserID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-user"></i> My Profile</h2>
    <div class="card shadow-sm mb-4 w-50 mx-auto">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <tbody>
                    <tr>
                        <th><i class="fas fa-user"></i> Username</th>
                        <td><?= htmlspecialchars($user['Username']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-id-badge"></i> Full Name</th>
                        <td><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <td><?= htmlspecialchars($user['Email']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-user-tag"></i> Role</th>
                        <td><?= htmlspecialchars($user['Role']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-calendar-plus"></i> Account Created</th>
                        <td><?= htmlspecialchars($user['CreatedDate']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-sign-in-alt"></i> Last Login</th>
                        <td><?= htmlspecialchars($user['LastLogin'] ?? 'Never') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 