<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

// Only allow assistants and administrators
function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }

if (!isAdmin() && !isAssistant()) {
    die('Access denied.');
}

$club_id = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;

if ($club_id <= 0) {
    die("Invalid club.");
}

// Fetch club info
$stmt = $pdo->prepare("SELECT * FROM Book_Clubs WHERE ClubID = ?");
$stmt->execute([$club_id]);
$club = $stmt->fetch();

if (!$club) {
    die("Club not found.");
}

// Fetch members
$stmt = $pdo->prepare("SELECT u.Username, u.FirstName, u.LastName, bcm.Role, bcm.JoinDate
    FROM Book_Club_Members bcm
    JOIN Users u ON bcm.UserID = u.UserID
    WHERE bcm.ClubID = ?");
$stmt->execute([$club_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Club Details - <?= htmlspecialchars($club['Name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> Club Details: <?= htmlspecialchars($club['Name']) ?></h2>
    <div class="card shadow-sm mb-4 w-50 mx-auto">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <tbody>
                    <tr>
                        <th><i class="fas fa-calendar-alt"></i> Schedule</th>
                        <td><?= htmlspecialchars($club['Schedule']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-book-open"></i> Topics</th>
                        <td><?= htmlspecialchars($club['Topics']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-code-branch"></i> Branch ID</th>
                        <td><?= htmlspecialchars($club['BranchID']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-user"></i> Created By (UserID)</th>
                        <td><?= htmlspecialchars($club['CreatedBy']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-calendar-plus"></i> Created Date</th>
                        <td><?= htmlspecialchars($club['CreatedDate']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <h4 class="mb-3"><i class="fas fa-users"></i> Members</h4>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-primary">
                    <tr>
                        <th><i class="fas fa-user"></i> Username</th>
                        <th><i class="fas fa-id-badge"></i> Full Name</th>
                        <th><i class="fas fa-user-tag"></i> Role</th>
                        <th><i class="fas fa-calendar-day"></i> Join Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['Username']) ?></td>
                        <td><?= htmlspecialchars($member['FirstName'] . ' ' . $member['LastName']) ?></td>
                        <td><?= htmlspecialchars($member['Role']) ?></td>
                        <td><?= htmlspecialchars($member['JoinDate']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="book_clubs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Book Clubs</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 