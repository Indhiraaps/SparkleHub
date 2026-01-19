<?php
session_start();
// 1. Authentication Check
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require '../inc/db.php';

// Check for order ID in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['id'];
$order = null;
$order_items = [];
$error_message = '';

try {
    // 2. Fetch Main Order Details
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $error_message = "Order with ID #{$order_id} not found.";
    } else {
        // 3. Fetch Items associated with this Order
        // Note: Joining with the product table to get the product_image_path
        $items_stmt = $pdo->prepare("
            SELECT 
                oi.*, /* Ensure cost and total_cost are fetched */
                p.product_image_path
            FROM order_items oi
            LEFT JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = "A database error occurred: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= htmlspecialchars($order_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --amazon-orange: #ff9900;
            --amazon-dark: #232f3e;
            --amazon-blue: #146eb4;
            --amazon-light: #fafafa;
            --amazon-gray: #eaeded;
            --amazon-text: #0f1111;
        }
        
        body {
            background-color: var(--amazon-light);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--amazon-text);
        }
        
        /* Navbar Styling - Amazon Inspired */
        .navbar {
            background-color: var(--amazon-dark) !important;
            padding: 0.5rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
        }
        
        .amazon-logo {
            color: var(--amazon-orange);
            margin-right: 8px;
        }
        
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .user-info {
            color: white;
            font-weight: 500;
            margin-right: 15px;
        }
        
        /* Main Container */
        .admin-container {
            padding: 2rem 0;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--amazon-orange);
        }
        
        .page-title {
            font-weight: 600;
            color: var(--amazon-text);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #565959;
            font-size: 1rem;
        }
        
        /* Cards */
        .card {
            border: 1px solid #d5dbdb;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #d5dbdb;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn-amazon {
            background-color: var(--amazon-orange);
            color: white;
            border: 1px solid var(--amazon-orange);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-amazon:hover {
            background-color: #e68900;
            border-color: #e68900;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-outline-amazon {
            background-color: white;
            color: var(--amazon-orange);
            border: 1px solid var(--amazon-orange);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-outline-amazon:hover {
            background-color: var(--amazon-orange);
            color: white;
        }
        
        /* Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table thead th {
            background-color: var(--amazon-dark);
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.75rem 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        /* Product Image */
        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        /* Order Summary */
        .order-summary-box {
            background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid #28a745;
        }
        
        .customer-info-box {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid var(--amazon-blue);
        }
        
        /* Price styling */
        .price {
            font-weight: 600;
            color: #b12704;
        }
        
        .total-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #b12704;
        }
        
        /* Order ID Badge */
        .order-id-badge {
            background: linear-gradient(135deg, var(--amazon-orange), #ff6600);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--amazon-dark);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer-links a {
            color: #ddd;
            text-decoration: none;
            margin-right: 1.5rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .copyright {
            color: #999;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .product-image {
                width: 50px;
                height: 50px;
            }
        }
        
        /* Animation for new elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        /* Status indicators */
        .info-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navigation - Amazon Style -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store amazon-logo"></i>Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-basket me-1"></i> Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="fas fa-boxes me-1"></i> Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php"><i class="fas fa-folder-open me-1"></i> Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="discount.php"><i class="fas fa-percent me-1"></i> Discounts</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="user-info"><i class="fas fa-user-circle me-1"></i> Admin</span>
                    <a href="logout.php" class="btn btn-amazon btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-file-invoice me-2"></i>
                        Order Details
                        <span class="order-id-badge ms-2">#<?= htmlspecialchars($order_id) ?></span>
                    </h1>
                    <p class="page-subtitle">Complete order information and item details</p>
                </div>
                <a href="orders.php" class="btn btn-outline-amazon">
                    <i class="fas fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger fade-in" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
            </div>
        <?php elseif ($order): ?>

            <div class="row mb-4">
                <!-- Customer Information -->
                <div class="col-lg-6">
                    <div class="card fade-in">
                        <div class="card-header">
                            <i class="fas fa-user me-2"></i> Customer Information
                        </div>
                        <div class="card-body customer-info-box">
                            <div class="info-item">
                                <strong><i class="fas fa-user-circle me-2 text-primary"></i>Name:</strong>
                                <span class="float-end"><?= htmlspecialchars($order['customer_name']) ?></span>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-phone me-2 text-primary"></i>Contact:</strong>
                                <span class="float-end"><?= htmlspecialchars($order['contact_no']) ?></span>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-envelope me-2 text-primary"></i>Email:</strong>
                                <span class="float-end"><?= htmlspecialchars($order['mail_id']) ?: 'N/A' ?></span>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-map-marker-alt me-2 text-primary"></i>State:</strong>
                                <span class="float-end"><?= htmlspecialchars($order['state']) ?></span>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-home me-2 text-primary"></i>Address:</strong>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($order['address'])) ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-calendar me-2 text-primary"></i>Order Date:</strong>
                                <span class="float-end"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['order_date']))) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-6">
                    <div class="card fade-in">
                        <div class="card-header">
                            <i class="fas fa-receipt me-2"></i> Order Summary
                        </div>
                        <div class="card-body order-summary-box">
                            <div class="info-item">
                                <strong>Items Subtotal:</strong>
                                <span class="float-end price">₹<?= number_format($order['total'], 2) ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Packing Cost:</strong>
                                <span class="float-end price">₹<?= number_format($order['packing_cost'], 2) ?></span>
                            </div>
                            <hr>
                            <div class="info-item">
                                <strong class="h5">Grand Total:</strong>
                                <span class="float-end total-price">₹<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                            
                            <!-- Additional Order Metrics -->
                            <div class="mt-4 p-3 bg-white rounded">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="text-muted small">Items Count</div>
                                        <div class="h5 fw-bold text-primary"><?= count($order_items) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small">Avg. Item Price</div>
                                        <div class="h5 fw-bold text-success">
                                            ₹<?= count($order_items) > 0 ? number_format($order['total'] / count($order_items), 2) : '0.00' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-boxes me-2"></i> Order Items
                        <span class="badge bg-amazon ms-2" style="background-color: var(--amazon-orange);"><?= count($order_items) ?></span>
                    </div>
                    <div class="text-muted small">
                        Products purchased in this order
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($order_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No items found for this order</h5>
                            <p class="text-muted">This order doesn't contain any items.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">Image</th>
                                        <th style="width: 35%;">Product Name</th>
                                        <th style="width: 15%;">Unit Price</th>
                                        <th style="width: 15%;">Quantity</th>
                                        <th style="width: 25%;">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr class="fade-in">
                                            <td>
                                                <?php if (!empty($item['product_image_path'])): ?>
                                                    <img src="../<?= htmlspecialchars($item['product_image_path']) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                         class="product-image">
                                                <?php else: ?>
                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <small class="text-muted">Product ID: <?= $item['product_id'] ?></small>
                                            </td>
                                            <td class="price">₹<?= number_format($item['cost'] ?? 0, 2) ?></td>
                                            <td>
                                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($item['quantity']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold price">₹<?= number_format($item['total_cost'] ?? 0, 2) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($item['quantity']) ?> × ₹<?= number_format($item['cost'] ?? 0, 2) ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">Items Subtotal:</td>
                                        <td class="fw-bold price">₹<?= number_format($order['total'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <div class="btn-group" role="group">
                        <a href="orders.php" class="btn btn-outline-amazon">
                            <i class="fas fa-arrow-left me-1"></i> Back to Orders
                        </a>
                        <button type="button" class="btn btn-amazon" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print Order
                        </button>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Footer - Amazon Style -->
    <footer class="footer">
        <div class="container">
            <div class="footer-links">
                <a href="index.php">Dashboard</a>
                <a href="products.php">Products</a>
                <a href="orders.php">Orders</a>
                <a href="categories.php">Categories</a>
                <a href="discount.php">Discounts</a>
                <a href="#">Help</a>
            </div>
            <div class="copyright">
                &copy; <?= date('Y') ?> Admin Dashboard. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>