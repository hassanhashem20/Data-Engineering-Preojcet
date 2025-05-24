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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $vendorName = filter_input(INPUT_POST, 'vendor_name', FILTER_SANITIZE_STRING);
    $contactPerson = filter_input(INPUT_POST, 'contact_person', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
    $postalCode = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING);
    $contractStartDate = filter_input(INPUT_POST, 'contract_start_date', FILTER_SANITIZE_STRING);
    $contractEndDate = filter_input(INPUT_POST, 'contract_end_date', FILTER_SANITIZE_STRING);
    $paymentTerms = filter_input(INPUT_POST, 'payment_terms', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Vendors (
                VendorName, ContactPerson, Email, Phone, Address, 
                City, State, Country, PostalCode, 
                ContractStartDate, ContractEndDate, PaymentTerms, Notes, Status
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, 'Active'
            )
        ");

        $stmt->execute([
            $vendorName, $contactPerson, $email, $phone, $address,
            $city, $state, $country, $postalCode,
            $contractStartDate, $contractEndDate, $paymentTerms, $notes
        ]);

        $message = "Vendor added successfully!";
    } catch (PDOException $e) {
        $error = "Error adding vendor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vendor - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-plus-circle"></i> Add New Vendor
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vendor_name" class="form-label">
                                        <i class="fas fa-building"></i> Vendor Name *
                                    </label>
                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contact_person" class="form-label">
                                        <i class="fas fa-user"></i> Contact Person
                                    </label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone"></i> Phone
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">
                                        <i class="fas fa-city"></i> City
                                    </label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">
                                        <i class="fas fa-map"></i> State/Province
                                    </label>
                                    <input type="text" class="form-control" id="state" name="state">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="postal_code" class="form-label">
                                        <i class="fas fa-mail-bulk"></i> Postal Code
                                    </label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contract_start_date" class="form-label">
                                        <i class="fas fa-calendar-plus"></i> Contract Start Date
                                    </label>
                                    <input type="date" class="form-control" id="contract_start_date" name="contract_start_date">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contract_end_date" class="form-label">
                                        <i class="fas fa-calendar-minus"></i> Contract End Date
                                    </label>
                                    <input type="date" class="form-control" id="contract_end_date" name="contract_end_date">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">
                                    <i class="fas fa-file-invoice-dollar"></i> Payment Terms
                                </label>
                                <input type="text" class="form-control" id="payment_terms" name="payment_terms">
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-sticky-note"></i> Notes
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage_vendors.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left"></i> Back to Vendor List
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Vendor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 