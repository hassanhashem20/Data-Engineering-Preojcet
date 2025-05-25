<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

// --- Authorization helpers ---
function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }
function isLibrarian() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'librarian'; }
function isBranchManager() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'branch_manager'; }
function isStaff() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'staff'; }
function isMember() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'member'; }
function isVendor() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'vendor'; }

if (isVendor()) {
    die('Access denied.');
}

// --- Handle new club creation (Admins/Branch Managers) ---
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isAdmin() || isBranchManager())) {
    $name = trim($_POST['name'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $topics = trim($_POST['topics'] ?? '');
    $branchId = isAdmin() ? ($_POST['branch_id'] ?? null) : ($_SESSION['branch_id'] ?? null);
    if ($name && $schedule && $topics && $branchId) {
        $stmt = $pdo->prepare("INSERT INTO Book_Clubs (Name, Schedule, Topics, BranchID, CreatedBy) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $schedule, $topics, $branchId, $_SESSION['user_id']]);
        $message = "Book club created successfully.";
    } else {
        $error = "Please fill in all required fields.";
    }
}

// --- Fetch clubs based on role ---
if (isAdmin() || isStaff()) {
    $stmt = $pdo->query("SELECT * FROM Book_Clubs");
} elseif (isBranchManager()) {
    $branchId = $_SESSION['branch_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM Book_Clubs WHERE BranchID = ?");
    $stmt->execute([$branchId]);
} else { // Member
    $stmt = $pdo->query("SELECT * FROM Book_Clubs");
}
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Clubs - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <h2>Book Clubs</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <?php if (isAdmin() || isBranchManager()): ?>
    <div class="card mb-4">
        <div class="card-header">Create New Club</div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Club Name" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="schedule" class="form-control" placeholder="Meeting Schedule" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="topics" class="form-control" placeholder="Discussion Topics" required>
                    </div>
                </div>
                <?php if (isAdmin()): ?>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="number" name="branch_id" class="form-control" placeholder="Branch ID" required>
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Create Club</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Club Name</th>
                <th>Schedule</th>
                <th>Topics</th>
                <th>Members</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($clubs as $club): ?>
            <tr>
                <td><?= htmlspecialchars($club['Name']) ?></td>
                <td><?= htmlspecialchars($club['Schedule']) ?></td>
                <td><?= htmlspecialchars($club['Topics']) ?></td>
                <td>
                    <?php
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM Book_Club_Members WHERE ClubID = ?");
                    $stmt2->execute([$club['ClubID']]);
                    echo $stmt2->fetchColumn();
                    ?>
                </td>
                <td>
                    <a href="club_activities.php?club_id=<?= $club['ClubID'] ?>" class="btn btn-info btn-sm">View Activities</a>
                    <?php if (isAssistant() || isAdmin()): ?>
                        <a href="club_details.php?club_id=<?= $club['ClubID'] ?>" class="btn btn-primary btn-sm">View Details</a>
                    <?php endif; ?>
                    <?php if (isMember() || isLibrarian()): ?>
                        <a href="join_club.php?club_id=<?= $club['ClubID'] ?>" class="btn btn-success btn-sm">Join</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html> 