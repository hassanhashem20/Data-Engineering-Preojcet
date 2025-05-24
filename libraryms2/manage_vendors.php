<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle vendor status updates
if (isset($_POST['update_status'])) {
    $vendorId = filter_input(INPUT_POST, 'vendor_id', FILTER_SANITIZE_NUMBER_INT);
    $newStatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    try {
        $stmt = $pdo->prepare("UPDATE Vendors SET Status = ? WHERE VendorID = ?");
        $stmt->execute([$newStatus, $vendorId]);
        $message = "Vendor status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating vendor status: " . $e->getMessage();
    }
}

// Handle vendor deletion
if (isset($_POST['delete_vendor'])) {
    $vendorId = filter_input(INPUT_POST, 'vendor_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // First check if vendor has any associated records
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM Vendor_Products WHERE VendorID = ?) as product_count,
                (SELECT COUNT(*) FROM Vendor_Transactions WHERE VendorID = ?) as transaction_count,
                (SELECT COUNT(*) FROM Vendor_Purchase_Orders WHERE VendorID = ?) as order_count
        ");
        $stmt->execute([$vendorId, $vendorId, $vendorId]);
        $counts = $stmt->fetch();
        
        if ($counts['product_count'] > 0 || $counts['transaction_count'] > 0 || $counts['order_count'] > 0) {
            $error = "Cannot delete vendor: They have associated products, transactions, or orders.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM Vendors WHERE VendorID = ?");
            $stmt->execute([$vendorId]);
            $message = "Vendor deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error deleting vendor: " . $e->getMessage();
    }
}

// Fetch all vendors with their product counts and total transactions
try {
    $stmt = $pdo->query("
        SELECT 
            v.*,
            COUNT(DISTINCT vp.ProductID) as ProductCount,
            COUNT(DISTINCT vt.TransactionID) as TransactionCount,
            SUM(CASE WHEN vt.TransactionType = 'Payment' THEN vt.Amount ELSE 0 END) as TotalPayments,
            SUM(CASE WHEN vt.TransactionType = 'Purchase' THEN vt.Amount ELSE 0 END) as TotalPurchases
        FROM Vendors v
        LEFT JOIN Vendor_Products vp ON v.VendorID = vp.VendorID
        LEFT JOIN Vendor_Transactions vt ON v.VendorID = vt.VendorID
        GROUP BY v.VendorID
        ORDER BY v.VendorName
    ");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching vendors: " . $e->getMessage();
    $vendors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vendors - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-truck"></i> Vendor Management</h2>
            <a href="add_vendor.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Vendor
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <table id="vendorsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Products</th>
                            <th>Transactions</th>
                            <th>Total Purchases</th>
                            <th>Total Payments</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['VendorName']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['ContactPerson'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($vendor['Email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($vendor['Phone'] ?? '-'); ?></td>
                                <td><?php echo $vendor['ProductCount']; ?></td>
                                <td><?php echo $vendor['TransactionCount']; ?></td>
                                <td>$<?php echo number_format($vendor['TotalPurchases'] ?? 0, 2); ?></td>
                                <td>$<?php echo number_format($vendor['TotalPayments'] ?? 0, 2); ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['VendorID']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Active" <?php echo $vendor['Status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $vendor['Status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="Suspended" <?php echo $vendor['Status'] === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_vendor.php?id=<?php echo $vendor['VendorID']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_vendor.php?id=<?php echo $vendor['VendorID']; ?>" class="btn btn-sm btn-warning" title="Edit Vendor">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="vendor_products.php?id=<?php echo $vendor['VendorID']; ?>" class="btn btn-sm btn-success" title="Manage Products">
                                            <i class="fas fa-box"></i>
                                        </a>
                                        <a href="vendor_transactions.php?id=<?php echo $vendor['VendorID']; ?>" class="btn btn-sm btn-primary" title="View Transactions">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['VendorID']; ?>">
                                            <button type="submit" name="delete_vendor" class="btn btn-sm btn-danger" title="Delete Vendor">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#vendorsTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                language: {
                    search: "Search vendors:"
                }
            });
        });
    </script>
</body>
</html> 