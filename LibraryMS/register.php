<?php
require_once 'config/database.php';

$error = '';
$success = '';
$account_types = ['Librarian', 'Assistant', 'Vendor', 'Member'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');

    if (!$account_type || !$username || !$password || !$confirm_password || !$email || !$first_name || !$last_name || ($account_type === 'Vendor' && !$company_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($account_type, $account_types)) {
        $error = 'Invalid account type.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ? OR Email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username or email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                // Insert into Users
                $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, Email, Role, FirstName, LastName, IsActive, CreatedDate) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$username, $hashed_password, $email, $account_type, $first_name, $last_name]);
                $user_id = $pdo->lastInsertId();
                if ($account_type === 'Member' || $account_type === 'Librarian' || $account_type === 'Assistant') {
                    // Insert into Member table
                    $stmt = $pdo->prepare("INSERT INTO Member (FirstName, LastName, Email, MemberSince, IsActive, CreatedDate) VALUES (?, ?, ?, CURDATE(), 1, NOW())");
                    $stmt->execute([$first_name, $last_name, $email]);
                } elseif ($account_type === 'Vendor') {
                    // Insert into Vendor table
                    $stmt = $pdo->prepare("INSERT INTO Vendor (CompanyName, ContactName, Email, IsActive, CreatedDate) VALUES (?, ?, ?, 1, NOW())");
                    $stmt->execute([$company_name, $first_name . ' ' . $last_name, $email]);
                }
                $success = 'Registration successful! You can now log in.';
            }
        } catch (PDOException $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library Management System</title>
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
                <li class="nav-item"><a class="nav-link" href="books.php"><i class="fas fa-book"></i> Books</a></li>
                <li class="nav-item"><a class="nav-link" href="members.php"><i class="fas fa-users"></i> Members</a></li>
                <li class="nav-item"><a class="nav-link" href="borrowings.php"><i class="fas fa-hand-holding"></i> Borrowings</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li class="nav-item"><a class="nav-link" href="documentation.php"><i class="fas fa-file-alt"></i> Documentation</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg rounded-3">
                <div class="card-header bg-primary text-white text-center">
                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                    <h2 class="mb-0">Register</h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
                        </div>
                    <?php else: ?>
                    <form method="POST" action="" id="registerForm">
                        <div class="mb-3">
                            <label for="account_type" class="form-label">Account Type</label>
                            <select class="form-select" id="account_type" name="account_type" required onchange="toggleCompanyField()">
                                <option value="">Select account type</option>
                                <?php foreach ($account_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php if (isset($_POST['account_type']) && $_POST['account_type'] === $type) echo 'selected'; ?>><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3 input-group" id="companyField" style="display: none;">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Company Name">
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleCompanyField() {
    var type = document.getElementById('account_type').value;
    var companyField = document.getElementById('companyField');
    if (type === 'Vendor') {
        companyField.style.display = '';
        document.getElementById('company_name').required = true;
    } else {
        companyField.style.display = 'none';
        document.getElementById('company_name').required = false;
    }
}
// On page load, set the company field visibility
window.onload = function() {
    toggleCompanyField();
};
</script>
</body>
</html> 