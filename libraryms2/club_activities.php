<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

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

// Fetch activities
$stmt = $pdo->prepare("SELECT * FROM Book_Club_Activities WHERE ClubID = ? ORDER BY ActivityDate DESC");
$stmt->execute([$club_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Club Activities - <?= htmlspecialchars($club['Name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-calendar-alt"></i> Activities for <?= htmlspecialchars($club['Name']) ?></h2>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-primary">
                    <tr>
                        <th><i class="fas fa-calendar-day"></i> Date</th>
                        <th><i class="fas fa-book-open"></i> Topic</th>
                        <th><i class="fas fa-sticky-note"></i> Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['ActivityDate']) ?></td>
                        <td><?= htmlspecialchars($activity['Topic']) ?></td>
                        <td><?= htmlspecialchars($activity['Notes']) ?></td>
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