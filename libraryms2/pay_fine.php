<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

// Role-based access control
function isLibrarian() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'librarian'; }
function isAssistant() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'assistant'; }
function isAdmin() { return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'; }

if (!isLibrarian() && !isAssistant() && !isAdmin()) {
    die('Access denied.');
}

$message = '';
$error = '';
$member_id = $_GET['member_id'] ?? null;

// Handle fine payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLibrarian()) {
    $fine_id = $_POST['fine_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $transaction_reference = $_POST['transaction_reference'] ?? '';
    
    if ($fine_id && $payment_method && $amount > 0) {
        try {
            $pdo->beginTransaction();
            
            // Record payment
            $stmt = $pdo->prepare("INSERT INTO Fine_Payments (FineID, AmountPaid, PaymentDate, PaymentMethod, TransactionReference, ReceivedBy) VALUES (?, ?, NOW(), ?, ?, ?)");
            $stmt->execute([$fine_id, $amount, $payment_method, $transaction_reference, $_SESSION['user_id']]);
            $payment_id = $pdo->lastInsertId();
            
            // Update fine status
            $stmt = $pdo->prepare("UPDATE Fines SET Status = 'Paid' WHERE FineID = ?");
            $stmt->execute([$fine_id]);
            
            $pdo->commit();
            $message = "Payment processed successfully!";
            
            // Generate receipt
            header("Location: generate_receipt.php?payment_id=" . $payment_id);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error processing payment: " . $e->getMessage();
        }
    }
}

// Fetch member's outstanding fines
$fines = [];
if ($member_id) {
    $stmt = $pdo->prepare("
        SELECT f.*, b.Title, b.ISBN, 
               DATEDIFF(CURRENT_DATE, f.DueDate) as DaysOverdue,
               (DATEDIFF(CURRENT_DATE, f.DueDate) * (SELECT SettingValue FROM System_Settings WHERE SettingKey = 'fine_per_day')) as CalculatedFine
        FROM Fines f
        JOIN Borrowing br ON f.BorrowID = br.BorrowID
        JOIN Book b ON br.ISBN = b.ISBN
        WHERE f.MemberID = ? AND f.Status = 'Pending'
        ORDER BY f.DueDate DESC
    ");
    $stmt->execute([$member_id]);
    $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// For assistants: fetch payment status for all members
$payment_status = [];
if (isAssistant()) {
    $stmt = $pdo->query("
        SELECT 
            u.Username,
            u.FirstName,
            u.LastName,
            COUNT(f.FineID) as TotalFines,
            SUM(CASE WHEN f.Status = 'Paid' THEN 1 ELSE 0 END) as PaidFines,
            SUM(CASE WHEN f.Status = 'Pending' THEN 1 ELSE 0 END) as PendingFines
        FROM Users u
        LEFT JOIN Fines f ON u.UserID = f.MemberID
        GROUP BY u.UserID
        ORDER BY u.Username
    ");
    $payment_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// For admins: fetch payment method statistics
$payment_stats = [];
if (isAdmin()) {
    $stmt = $pdo->query("
        SELECT 
            PaymentMethod,
            COUNT(*) as TransactionCount,
            SUM(AmountPaid) as TotalAmount
        FROM Fine_Payments
        GROUP BY PaymentMethod
        ORDER BY TotalAmount DESC
    ");
    $payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Payment - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fine-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .payment-method-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        .fine-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }
        .payment-modal .modal-content {
            border-radius: 15px;
        }
        .payment-modal .modal-header {
            background-color: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }
        .payment-modal .modal-footer {
            background-color: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }
        .stats-card {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-card h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        .stats-card p {
            margin: 0;
            opacity: 0.8;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-money-bill-wave"></i> Fine Payment System</h2>
        <?php if (isLibrarian()): ?>
            <a href="search_member.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Search Member
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isLibrarian()): ?>
        <!-- Librarian View: Process Payments -->
        <div class="fine-card">
            <h4><i class="fas fa-cash-register"></i> Process Fine Payment</h4>
            <?php if ($member_id && !empty($fines)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-book"></i> Book Title</th>
                                <th><i class="fas fa-barcode"></i> ISBN</th>
                                <th><i class="fas fa-calendar"></i> Due Date</th>
                                <th><i class="fas fa-clock"></i> Days Overdue</th>
                                <th><i class="fas fa-dollar-sign"></i> Fine Amount</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines as $fine): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fine['Title']) ?></td>
                                    <td><?= htmlspecialchars($fine['ISBN']) ?></td>
                                    <td><?= htmlspecialchars($fine['DueDate']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $fine['DaysOverdue'] > 30 ? 'danger' : 'warning' ?>">
                                            <?= htmlspecialchars($fine['DaysOverdue']) ?> days
                                        </span>
                                    </td>
                                    <td class="fine-amount">$<?= number_format($fine['CalculatedFine'], 2) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#paymentModal<?= $fine['FineID'] ?>">
                                            <i class="fas fa-credit-card"></i> Pay Fine
                                        </button>
                                        
                                        <!-- Payment Modal -->
                                        <div class="modal fade payment-modal" id="paymentModal<?= $fine['FineID'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-money-check-alt"></i> Process Payment
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="fine_id" value="<?= $fine['FineID'] ?>">
                                                            <input type="hidden" name="amount" value="<?= $fine['CalculatedFine'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    <i class="fas fa-credit-card"></i> Payment Method
                                                                </label>
                                                                <select name="payment_method" class="form-select" required>
                                                                    <option value="Cash">
                                                                        <i class="fas fa-money-bill"></i> Cash
                                                                    </option>
                                                                    <option value="Card">
                                                                        <i class="fas fa-credit-card"></i> Card
                                                                    </option>
                                                                    <option value="Bank_Transfer">
                                                                        <i class="fas fa-university"></i> Bank Transfer
                                                                    </option>
                                                                    <option value="Online">
                                                                        <i class="fas fa-globe"></i> Online Payment
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    <i class="fas fa-hashtag"></i> Transaction Reference
                                                                </label>
                                                                <input type="text" name="transaction_reference" class="form-control" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">
                                                                    <i class="fas fa-dollar-sign"></i> Amount to Pay
                                                                </label>
                                                                <input type="text" class="form-control fine-amount" value="$<?= number_format($fine['CalculatedFine'], 2) ?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times"></i> Close
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-check"></i> Process Payment
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No outstanding fines found for this member.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isAssistant()): ?>
        <!-- Assistant View: Payment Status -->
        <div class="mb-4">
            <h4>Member Payment Status</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Total Fines</th>
                        <th>Paid Fines</th>
                        <th>Pending Fines</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_status as $status): ?>
                        <tr>
                            <td><?= htmlspecialchars($status['FirstName'] . ' ' . $status['LastName']) ?></td>
                            <td><?= $status['TotalFines'] ?></td>
                            <td><?= $status['PaidFines'] ?></td>
                            <td><?= $status['PendingFines'] ?></td>
                            <td>
                                <?php if ($status['PendingFines'] > 0): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Clear</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
        <!-- Admin View: Payment Statistics -->
        <div class="mb-4">
            <h4>Payment Method Statistics</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Number of Transactions</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['PaymentMethod']) ?></td>
                            <td><?= $stat['TransactionCount'] ?></td>
                            <td>$<?= number_format($stat['TotalAmount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 