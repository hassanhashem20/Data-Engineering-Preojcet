<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$borrow_id = $_GET['borrow_id'] ?? null;
$fine_id = $_GET['fine_id'] ?? null;
$error = '';
$success = '';

// Add default period options and their prices
$period_options = [
    ['label' => '7 days', 'value' => '7_days', 'price' => 10],
    ['label' => '14 days', 'value' => '14_days', 'price' => 18],
    ['label' => '1 month', 'value' => '1_month', 'price' => 32],
    ['label' => '2 months', 'value' => '2_months', 'price' => 60],
    ['label' => '3 months', 'value' => '3_months', 'price' => 85],
];
$selected_period = $_POST['borrow_period'] ?? '14_days';
$amount_to_pay = 18; // default for 14 days
foreach ($period_options as $option) {
    if ($option['value'] === $selected_period) {
        $amount_to_pay = $option['price'];
        break;
    }
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch borrowing, fine, and book details
    $stmt = $pdo->prepare("
        SELECT b.ISBN, b.BorrowDate, b.DueDate, f.Amount, f.Status as FineStatus, bk.Title, bk.Author, bk.Publisher
        FROM Borrowing b
        JOIN Fines f ON b.BorrowID = f.BorrowID
        JOIN Book bk ON b.ISBN = bk.ISBN
        WHERE b.BorrowID = ? AND f.FineID = ?
    ");
    $stmt->execute([$borrow_id, $fine_id]);
    $details = $stmt->fetch();

    if (!$details) {
        throw new Exception("Invalid borrowing or fine.");
    }

    // Handle payment confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_reference = $_POST['transaction_reference'] ?? '';
        $borrow_period = $_POST['borrow_period'] ?? '14_days';
        // Get amount to pay for selected period
        $amount_to_pay = 18;
        foreach ($period_options as $option) {
            if ($option['value'] === $borrow_period) {
                $amount_to_pay = $option['price'];
                break;
            }
        }

        if (!$payment_method || !$transaction_reference) {
            $error = "Please fill all payment details.";
        } else {
            $pdo->beginTransaction();

            // Update borrowing period (DueDate)
            $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 14 DAY)";
            if ($borrow_period === '7_days') {
                $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($borrow_period === '14_days') {
                $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 14 DAY)";
            } elseif ($borrow_period === '1_month') {
                $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
            } elseif ($borrow_period === '2_months') {
                $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 2 MONTH)";
            } elseif ($borrow_period === '3_months') {
                $due_date_sql = "DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
            }

            $stmt = $pdo->prepare("UPDATE Borrowing SET DueDate = $due_date_sql WHERE BorrowID = ?");
            $stmt->execute([$borrow_id]);

            // Update fine amount for this period
            $stmt = $pdo->prepare("UPDATE Fines SET Amount = ? WHERE FineID = ?");
            $stmt->execute([$amount_to_pay, $fine_id]);

            // Insert payment record
            $stmt = $pdo->prepare("INSERT INTO Fine_Payments (FineID, AmountPaid, PaymentDate, PaymentMethod, TransactionReference, ReceivedBy) VALUES (?, ?, NOW(), ?, ?, ?)");
            $stmt->execute([$fine_id, $amount_to_pay, $payment_method, $transaction_reference, $_SESSION['user_id']]);

            // Mark fine as paid
            $stmt = $pdo->prepare("UPDATE Fines SET Status = 'Paid' WHERE FineID = ?");
            $stmt->execute([$fine_id]);

            // Mark borrowing as active
            $stmt = $pdo->prepare("UPDATE Borrowing SET Status = 'Active' WHERE BorrowID = ?");
            $stmt->execute([$borrow_id]);

            // Change book status to Borrowed
            $stmt = $pdo->prepare("UPDATE Book SET StatusID = (SELECT StatusID FROM Book_Status WHERE StatusName = 'Borrowed') WHERE ISBN = ?");
            $stmt->execute([$details['ISBN']]);

            $pdo->commit();
            $success = "Payment successful! Book is now borrowed.";
        }
    }
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Borrow Payment - Library Management System</title>
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
            <ul class="navbar-nav">
                <li class="nav-item"><span class="nav-link"><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                <li class="nav-item"><a class="nav-link" href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">Borrow Payment</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
                        <a href="books.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left"></i> Back to Books</a>
                    <?php else: ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($details['Title']) ?></h5>
                                <p class="card-text">
                                    <strong>Author:</strong> <?= htmlspecialchars($details['Author']) ?><br>
                                    <strong>Publisher:</strong> <?= htmlspecialchars($details['Publisher']) ?><br>
                                    <strong>Borrowing Period:</strong> <?= htmlspecialchars($details['BorrowDate']) ?> to <?= htmlspecialchars($details['DueDate']) ?><br>
                                    <strong>Amount to Pay:</strong> $<?= number_format($amount_to_pay, 2) ?>
                                </p>
                            </div>
                        </div>
                        <form method="POST" id="paymentForm">
                            <div class="mb-3">
                                <label class="form-label">Borrowing Period</label>
                                <select name="borrow_period" id="borrow_period" class="form-select" required onchange="updateAmount()">
                                    <?php foreach ($period_options as $option): ?>
                                        <option value="<?= $option['value'] ?>" data-price="<?= $option['price'] ?>" <?= $selected_period === $option['value'] ? 'selected' : '' ?>><?= $option['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount to Pay</label>
                                <input type="text" id="amount_to_pay" class="form-control" value="$<?= number_format($amount_to_pay, 2) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Bank_Transfer">Bank Transfer</option>
                                    <option value="Online">Online Payment</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" name="transaction_reference" class="form-control" required>
                            </div>
                            <button type="submit" name="confirm_payment" class="btn btn-success"><i class="fas fa-check"></i> Confirm Payment</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateAmount() {
    var select = document.getElementById('borrow_period');
    var price = select.options[select.selectedIndex].getAttribute('data-price');
    document.getElementById('amount_to_pay').value = '$' + parseFloat(price).toFixed(2);
}
</script>
</body>
</html> 