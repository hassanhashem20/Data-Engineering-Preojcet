<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }

if (!isAdmin() && !isAssistant()) {
    die('Access denied.');
}

// Borrowing trends: count of borrowings per month (last 12 months)
$borrowing_trends = [];
$stmt = $pdo->query("SELECT DATE_FORMAT(BorrowDate, '%Y-%m') as Month, COUNT(*) as Borrowings FROM Borrowing GROUP BY Month ORDER BY Month DESC LIMIT 12");
$borrowing_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fine collection statistics: total fines collected per month (last 12 months)
$fine_stats = [];
$stmt = $pdo->query("SELECT DATE_FORMAT(PaymentDate, '%Y-%m') as Month, SUM(AmountPaid) as TotalFines FROM Fine_Payments GROUP BY Month ORDER BY Month DESC LIMIT 12");
$fine_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Book popularity: most borrowed books (top 10)
$book_popularity = [];
$stmt = $pdo->query("SELECT b.Title, COUNT(br.BorrowID) as TimesBorrowed FROM Book b JOIN Borrowing br ON b.ISBN = br.ISBN GROUP BY b.Title ORDER BY TimesBorrowed DESC LIMIT 10");
$book_popularity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Member engagement: members with most borrowings (top 10)
$member_engagement = [];
$stmt = $pdo->query("SELECT u.Username, COUNT(br.BorrowID) as Borrowings FROM Users u JOIN Borrowing br ON u.UserID = br.MemberID GROUP BY u.Username ORDER BY Borrowings DESC LIMIT 10");
$member_engagement = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Reports - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
        .report-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .report-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .export-buttons .btn {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar"></i> Library Reports</h2>
        <div class="no-print export-buttons">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </button>
            <a class="btn btn-success" href="export_report.php?type=all" target="_blank">
                <i class="fas fa-file-excel"></i> Export as Excel
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Borrowing Trends -->
        <div class="col-md-6 mb-4">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-chart-line report-icon text-primary"></i>
                        <h4 class="card-title">Borrowing Trends</h4>
                        <p class="text-muted">Last 12 Months</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="borrowing_trends_table">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Month</th>
                                    <th><i class="fas fa-book-reader"></i> Borrowings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowing_trends as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Month']) ?></td>
                                    <td><?= htmlspecialchars($row['Borrowings']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fine Collection Statistics -->
        <div class="col-md-6 mb-4">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-money-bill-wave report-icon text-success"></i>
                        <h4 class="card-title">Fine Collection</h4>
                        <p class="text-muted">Last 12 Months</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="fine_stats_table">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Month</th>
                                    <th><i class="fas fa-dollar-sign"></i> Total Fines</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fine_stats as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Month']) ?></td>
                                    <td>$<?= number_format($row['TotalFines'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Popularity -->
        <div class="col-md-6 mb-4">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-book report-icon text-info"></i>
                        <h4 class="card-title">Book Popularity</h4>
                        <p class="text-muted">Top 10 Most Borrowed Books</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="book_popularity_table">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-book"></i> Title</th>
                                    <th><i class="fas fa-chart-bar"></i> Times Borrowed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($book_popularity as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Title']) ?></td>
                                    <td><?= htmlspecialchars($row['TimesBorrowed']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Engagement -->
        <div class="col-md-6 mb-4">
            <div class="card report-card h-100">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-users report-icon text-warning"></i>
                        <h4 class="card-title">Member Engagement</h4>
                        <p class="text-muted">Top 10 Members by Borrowings</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="member_engagement_table">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user"></i> Username</th>
                                    <th><i class="fas fa-book-reader"></i> Borrowings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($member_engagement as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Username']) ?></td>
                                    <td><?= htmlspecialchars($row['Borrowings']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 