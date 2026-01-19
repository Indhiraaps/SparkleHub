<?php
session_start();
// 1. Authentication Check (Using your preferred session key)
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require '../inc/db.php';

// 2. Fetch all orders with robust error handling
try {
    // Note: If you ran the 'ALTER TABLE' command, 'order_date' is guaranteed to exist now.
    $orders = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Set a user-friendly error message if fetching fails
    $error_message = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders</title>
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
        
        .btn-primary {
            background-color: var(--amazon-blue);
            border-color: var(--amazon-blue);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #0d5a9e;
            border-color: #0d5a9e;
            transform: translateY(-1px);
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
        
        /* Order Status Badges */
        .order-id {
            font-weight: 700;
            color: var(--amazon-blue);
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--amazon-text);
        }
        
        .total-amount {
            font-weight: 700;
            color: #b12704;
            font-size: 1.1rem;
        }
        
        .date-badge {
            background-color: #f0f2f2;
            color: #565959;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
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
        
        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            border-top: 4px solid var(--amazon-orange);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--amazon-blue);
            margin: 0.5rem 0;
        }
        
        .stats-label {
            color: #565959;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
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
        
        /* Order summary styling */
        .order-summary {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link active" href="orders.php"><i class="fas fa-shopping-basket me-1"></i> Orders</a>
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
                    <h1 class="page-title"><i class="fas fa-shopping-basket me-2"></i>All Orders</h1>
                    <p class="page-subtitle">Manage and track customer orders efficiently</p>
                </div>
                <a href="index.php" class="btn btn-outline-amazon">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Order Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card fade-in">
                    <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                    <div class="stats-value"><?= count($orders) ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card fade-in">
                    <i class="fas fa-users fa-2x text-success mb-2"></i>
                    <div class="stats-value"><?= count(array_unique(array_column($orders, 'customer_name'))) ?></div>
                    <div class="stats-label">Unique Customers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card fade-in">
                    <i class="fas fa-indian-rupee-sign fa-2x text-warning mb-2"></i>
                    <div class="stats-value">₹<?= number_format(array_sum(array_column($orders, 'total_amount')), 2) ?></div>
                    <div class="stats-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card fade-in">
                    <i class="fas fa-box fa-2x text-info mb-2"></i>
                    <div class="stats-value">₹<?= number_format(array_sum(array_column($orders, 'packing_cost')), 2) ?></div>
                    <div class="stats-label">Total Packing Cost</div>
                </div>
            </div>
        </div>

        <!-- Orders Table Card -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-2"></i> Order Details
                    <span class="badge bg-amazon ms-2" style="background-color: var(--amazon-orange);"><?= count($orders) ?></span>
                </div>
                <div class="text-muted small">
                    Sorted by most recent orders first
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger m-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
                    </div>
                <?php elseif (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No orders have been placed yet</h5>
                        <p class="text-muted">Orders will appear here once customers start purchasing.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Order ID</th>
                                    <th style="width: 15%;">Date & Time</th> 
                                    <th style="width: 20%;">Customer Details</th>
                                    <th style="width: 15%;">Amount Details</th>
                                    <th style="width: 10%;">Final Total</th>
                                    <th style="width: 10%;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr class="fade-in">
                                    <td>
                                        <span class="order-id">#<?= htmlspecialchars($o['order_id']) ?></span>
                                    </td>
                                    
                                    <td>
                                        <span class="date-badge">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= isset($o['order_date']) ? date("M j, Y", strtotime($o['order_date'])) : 'N/A' ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?= isset($o['order_date']) ? date("g:i A", strtotime($o['order_date'])) : '' ?>
                                        </small>
                                    </td>

                                    <td>
                                        <div class="customer-name"><?= htmlspecialchars($o['customer_name']) ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($o['contact_no']) ?>
                                        </small>
                                    </td>
                                    
                                    <td>
                                        <div class="small">
                                            <div class="d-flex justify-content-between">
                                                <span>Subtotal:</span>
                                                <span>₹<?= number_format($o['total'], 2) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Packing:</span>
                                                <span>₹<?= number_format($o['packing_cost'], 2) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="total-amount">₹<?= number_format($o['total_amount'], 2) ?></div>
                                    </td>
                                    
                                    <td>
                                        <a href="order_items.php?id=<?= htmlspecialchars($o['order_id']) ?>" 
                                           class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-search me-1"></i> View Items
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Summary Card -->
        <?php if (!empty($orders)): ?>
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i> Order Summary
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="order-summary">
                            <h6 class="fw-bold text-primary">Revenue Breakdown</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Product Sales:</span>
                                    <span>₹<?= number_format(array_sum(array_column($orders, 'total')), 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Packing Revenue:</span>
                                    <span>₹<?= number_format(array_sum(array_column($orders, 'packing_cost')), 2) ?></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total Revenue:</span>
                                    <span>₹<?= number_format(array_sum(array_column($orders, 'total_amount')), 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="order-summary">
                            <h6 class="fw-bold text-success">Customer Insights</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Total Orders:</span>
                                    <span><?= count($orders) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Unique Customers:</span>
                                    <span><?= count(array_unique(array_column($orders, 'customer_name'))) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Avg Order Value:</span>
                                    <span>₹<?= number_format(array_sum(array_column($orders, 'total_amount')) / count($orders), 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="order-summary">
                            <h6 class="fw-bold text-warning">Recent Activity</h6>
                            <div class="small">
                                <?php if (count($orders) > 0): ?>
                                    <?php 
                                    $latestOrder = $orders[0];
                                    $latestDate = isset($latestOrder['order_date']) ? date("M j, Y g:i A", strtotime($latestOrder['order_date'])) : 'N/A';
                                    ?>
                                    <div>Latest Order: <strong>#<?= $latestOrder['order_id'] ?></strong></div>
                                    <div>Date: <?= $latestDate ?></div>
                                    <div>Customer: <?= htmlspecialchars($latestOrder['customer_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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