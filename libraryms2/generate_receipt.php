<?php
session_start();
require_once 'config.php';
require_once 'config/database.php';
require_once 'vendor/autoload.php'; // You'll need to install TCPDF via Composer

use TCPDF;

// Only allow librarians to generate receipts
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'librarian') {
    die('Access denied.');
}

$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id) {
    die('Payment ID not provided.');
}

try {
    // Fetch payment details
    $stmt = $pdo->prepare("
        SELECT 
            fp.*,
            f.MemberID,
            f.BorrowID,
            b.Title,
            b.ISBN,
            u.FirstName,
            u.LastName,
            u.Email
        FROM Fine_Payments fp
        JOIN Fines f ON fp.FineID = f.FineID
        JOIN Borrowing br ON f.BorrowID = br.BorrowID
        JOIN Book b ON br.ISBN = b.ISBN
        JOIN Users u ON f.MemberID = u.UserID
        WHERE fp.PaymentID = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        die('Payment not found.');
    }

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Library Management System');
    $pdf->SetTitle('Payment Receipt');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add content
    $pdf->Cell(0, 10, 'LIBRARY PAYMENT RECEIPT', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->Cell(0, 10, 'Receipt #: ' . $payment['PaymentID'], 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . $payment['PaymentDate'], 0, 1);
    $pdf->Ln(5);

    $pdf->Cell(0, 10, 'Member Information:', 0, 1);
    $pdf->Cell(0, 10, 'Name: ' . $payment['FirstName'] . ' ' . $payment['LastName'], 0, 1);
    $pdf->Cell(0, 10, 'Email: ' . $payment['Email'], 0, 1);
    $pdf->Ln(5);

    $pdf->Cell(0, 10, 'Payment Details:', 0, 1);
    $pdf->Cell(0, 10, 'Book: ' . $payment['Title'], 0, 1);
    $pdf->Cell(0, 10, 'ISBN: ' . $payment['ISBN'], 0, 1);
    $pdf->Cell(0, 10, 'Amount Paid: $' . number_format($payment['AmountPaid'], 2), 0, 1);
    $pdf->Cell(0, 10, 'Payment Method: ' . $payment['PaymentMethod'], 0, 1);
    $pdf->Cell(0, 10, 'Transaction Reference: ' . $payment['TransactionReference'], 0, 1);
    $pdf->Ln(10);

    $pdf->Cell(0, 10, 'Thank you for your payment!', 0, 1, 'C');

    // Output PDF
    $pdf->Output('receipt_' . $payment_id . '.pdf', 'D');
} catch (Exception $e) {
    die('Error generating receipt: ' . $e->getMessage());
} 