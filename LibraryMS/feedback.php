<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }
function isLibrarian() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'librarian'; }

if (!isLibrarian() && !isAdmin() && !isAssistant()) {
    die('Access denied.');
}

$message = '';
$error = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLibrarian()) {
    $topic = trim($_POST['topic'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');
    $member_id = $_SESSION['user_id'];
    if ($topic && $rating >= 1 && $rating <= 5 && ($comments || $topic !== 'Other')) {
        $stmt = $pdo->prepare("INSERT INTO Feedback (MemberID, Topic, Rating, Comments, SubmissionDate) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$member_id, $topic, $rating, $comments]);
        $message = "Thank you for your feedback!";
        header("Location: index.php?msg=" . urlencode($message));
        exit();
    } else {
        $error = "Please fill in all required fields and provide a valid rating.";
    }
}

// Admin/Assistant: fetch all feedback
$all_feedback = [];
if (isAdmin() || isAssistant()) {
    $stmt = $pdo->query("SELECT f.*, u.Username FROM Feedback f JOIN Users u ON f.MemberID = u.UserID ORDER BY f.SubmissionDate DESC");
    $all_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <h2>Feedback</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <?php if (isLibrarian()): ?>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="topic" class="form-label">Topic</label>
            <select name="topic" id="topic" class="form-select" required>
                <option value="">Select topic</option>
                <option value="Book Quality">Book Quality</option>
                <option value="Service Experience">Service Experience</option>
                <option value="Facilities">Facilities</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="rating" class="form-label">Rating</label>
            <select name="rating" id="rating" class="form-select" required>
                <option value="">Select rating</option>
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">3 Stars</option>
                <option value="4">4 Stars</option>
                <option value="5">5 Stars</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="comments" class="form-label">Comments</label>
            <textarea name="comments" id="comments" class="form-control" rows="3" placeholder="Enter your comments..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Feedback</button>
    </form>
    <?php endif; ?>

    <?php if (isAdmin() || isAssistant()): ?>
    <h4>All Feedback</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Member</th>
                <th>Topic</th>
                <th>Rating</th>
                <th>Comments</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_feedback as $fb): ?>
            <tr>
                <td><?= htmlspecialchars($fb['Username']) ?></td>
                <td><?= htmlspecialchars($fb['Topic']) ?></td>
                <td><?= htmlspecialchars($fb['Rating']) ?></td>
                <td><?= htmlspecialchars($fb['Comments']) ?></td>
                <td><?= htmlspecialchars($fb['SubmissionDate']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html> 