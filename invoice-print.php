<?php
/**
 * Invoice Print Page - Self-contained version (Print-friendly)
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$pageTitle = 'Print Invoice - ' . APP_NAME;
$error = '';
$invoice = null;

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header('Location: invoices.php');
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT i.*, c.first_name, c.last_name, c.username, c.email, c.phone, c.address,
               p.name as package_name, p.price as package_price
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN packages p ON i.package_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        header('Location: invoices.php');
        exit;
    }
    
    // Get payments for this invoice
    $stmt = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $paid_amount = array_sum(array_column($payments, 'amount'));
    $due_amount = $invoice['total_amount'] - $paid_amount;
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-size: 14px; }
        .invoice-box { max-width: 800px; margin: 0 auto; padding: 20px; }
        .invoice-header { background: #2c3e50; color: white; padding: 30px; border-radius: 10px 10px 0 0; }
        .invoice-title { font-size: 28px; font-weight: bold; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .invoice-box { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="no-print text-end mb-3">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print</button>
            <a href="invoice-view.php?id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($invoice): ?>
        
        <div class="card">
            <div class="invoice-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="invoice-title">INVOICE</div>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0"><?php echo APP_NAME; ?></h4>
                        <?php if (defined('APP_ADDRESS')): ?>
                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars(APP_ADDRESS)); ?></p>
                        <?php endif; ?>
                        <?php if (defined('APP_PHONE')): ?>
                        <p class="mb-0 small">Phone: <?php echo htmlspecialchars(APP_PHONE); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Bill To:</h6>
                        <h5><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></h5>
                        <p class="mb-0">Username: <?php echo htmlspecialchars($invoice['username']); ?></p>
                        <?php if ($invoice['email']): ?>
                        <p class="mb-0"><?php echo htmlspecialchars($invoice['email']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['phone']): ?>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($invoice['phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['address']): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Invoice Details:</h6>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Invoice Number:</td>
                                <td class="fw-bold"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            </tr>
                            <tr>
                                <td>Invoice Date:</td>
                                <td><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td>Due Date:</td>
                                <td><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                            </tr>
                            <tr>
                                <td>Billing Period:</td>
                                <td><?php echo date('M Y', strtotime($invoice['billing_period_start'])); ?> - <?php echo date('M Y', strtotime($invoice['billing_period_end'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Package Price</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($invoice['package_name'] ?: 'Service'); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($invoice['billing_period_start']); ?> to <?php echo htmlspecialchars($invoice['billing_period_end']); ?></small>
                                </td>
                                <td class="text-end">৳ <?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="text-end">৳ <?php echo number_format($invoice['tax_amount'], 2); ?></td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            </tr>
                            <?php if ($paid_amount > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Paid:</td>
                                <td class="text-end text-success">- ৳ <?php echo number_format($paid_amount, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="<?php echo $due_amount > 0 ? 'table-warning' : 'table-success'; ?>">
                                <td colspan="3" class="text-end"><strong>Due Amount:</strong></td>
                                <td class="text-end fw-bold">৳ <?php echo number_format(max(0, $due_amount), 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if (!empty($payments)): ?>
                <h6>Payment History</h6>
                <table class="table table-sm table-bordered mb-4">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pay['payment_method']))); ?></td>
                            <td class="text-end">৳ <?php echo number_format($pay['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php if ($invoice['notes']): ?>
                <div class="alert alert-info mb-0">
                    <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="border-top pt-2">
                            <p class="mb-0">Customer Signature</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="border-top pt-2">
                            <p class="mb-0">Authorized Signature</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted small">
                    <p class="mb-0">Thank you for your business!</p>
                    <p class="mb-0">Generated on <?php echo date('d M Y h:i A'); ?></p>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>
