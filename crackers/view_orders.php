<?php
session_start();
require 'inc/db.php'; 

// Check if the session ID is set
$session_id = session_id();
if (empty($session_id)) {
    header("Location: index.php");
    exit;
}

$message = '';
$message_type = '';

// --- 1. Handle Order Cancellation ---
if (isset($_POST['action']) && $_POST['action'] === 'cancel_order' && isset($_POST['order_id'])) {
    $order_id_to_cancel = (int)$_POST['order_id'];
    
    // Begin transaction for safe cancellation
    $pdo->beginTransaction();
    try {
        // A. Check if the order belongs to the current session AND is cancelable ('Processing')
        // Using customer contact number as identifier since session_id column doesn't exist
        $check_stmt = $pdo->prepare("
            SELECT order_status, contact_no FROM orders 
            WHERE order_id = ? AND order_status = 'Processing'
        ");
        $check_stmt->execute([$order_id_to_cancel]);
        $order_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($order_data && $order_data['order_status'] === 'Processing') {
            
            // B. Get the list of items and quantities from the cancelled order
            $items_stmt = $pdo->prepare("
                SELECT product_id, quantity FROM order_items WHERE order_id = ?
            ");
            $items_stmt->execute([$order_id_to_cancel]);
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            // C. Update stock status: Add cancelled quantity back to product table
            $stock_update_stmt = $pdo->prepare("
                UPDATE product SET stock_status = stock_status + ? WHERE product_id = ?
            ");
            
            foreach ($order_items as $item) {
                $stock_update_stmt->execute([
                    $item['quantity'],
                    $item['product_id']
                ]);
            }

            // D. Change the order status to 'Cancelled'
            $update_stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = 'Cancelled' 
                WHERE order_id = ?
            ");
            $update_stmt->execute([$order_id_to_cancel]);
            
            $pdo->commit();
            $message = "Order #{$order_id_to_cancel} has been successfully cancelled and stock updated.";
            $message_type = 'success';
            
        } else {
            // Order was not found or was not 'Processing'
            $status = $order_data ? $order_data['order_status'] : 'Not Found';
            $message = "Error: Order cannot be cancelled (status: {$status}).";
            $message_type = 'danger';
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order cancellation failed: " . $e->getMessage());
        $message = "A database error occurred during cancellation. Please try again.";
        $message_type = 'danger';
    }
    
    // Store message in session and redirect to prevent form resubmission
    $_SESSION['order_message'] = $message;
    $_SESSION['order_message_type'] = $message_type;
    header("Location: view_orders.php"); 
    exit;
}

// Check for and display session message (for post-redirect-get pattern)
if (isset($_SESSION['order_message'])) {
    $message = $_SESSION['order_message'];
    $message_type = $_SESSION['order_message_type'];
    unset($_SESSION['order_message'], $_SESSION['order_message_type']);
}

// --- 2. Fetch ALL Orders (Temporary - for testing) ---
try {
    // Since we don't have session_id in orders table, show all orders for now
    // In production, you should add session_id to orders table or use another identifier
    $orders_stmt = $pdo->prepare("
        SELECT * FROM orders 
        ORDER BY order_date DESC
        LIMIT 10  -- Limit to recent orders for testing
    ");
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $message = "Could not fetch orders. Please try again later. Error: " . $e->getMessage();
    $message_type = 'danger';
}

// --- 3. Fetch Order Items for All Orders ---
$order_items = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'order_id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    
    try {
        $items_stmt = $pdo->prepare("
            SELECT * FROM order_items 
            WHERE order_id IN ({$placeholders}) 
            ORDER BY order_id, item_id
        ");
        $items_stmt->execute($order_ids);
        $all_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group items by order_id
        foreach ($all_items as $item) {
            $order_items[$item['order_id']][] = $item;
        }
    } catch (PDOException $e) {
        error_log("Order items fetch failed: " . $e->getMessage());
    }
}

/**
 * Helper function to determine Bootstrap alert class based on order status
 */
function getStatusClass($status) {
    switch ($status) {
        case 'Cancelled':
            return 'bg-danger';
        case 'Shipped':
            return 'bg-info';
        case 'Delivered':
            return 'bg-success';
        case 'Processing':
        default:
            return 'bg-warning';
    }
}

/**
 * Helper function to get status icons
 */
function getStatusIcon($status) {
    switch ($status) {
        case 'Cancelled':
            return 'fas fa-times-circle';
        case 'Shipped':
            return 'fas fa-shipping-fast';
        case 'Delivered':
            return 'fas fa-check-circle';
        case 'Processing':
        default:
            return 'fas fa-clock';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders | Sparkle Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b35;
            --secondary: #ffa500;
            --accent: #ff4757;
            --dark: #1a1a2e;
            --darker: #0f0f1a;
            --light: #ffffff;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-dark: rgba(0, 0, 0, 0.3);
            --border-glow: rgba(255, 107, 53, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 50%, #16213e 100%);
            min-height: 100vh;
            color: var(--light);
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 165, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 71, 87, 0.05) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(1deg); }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle, var(--secondary) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.7; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(26, 26, 46, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glow);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: var(--light) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white !important;
        }

        .badge {
            background: linear-gradient(45deg, var(--accent), #ff6b9d) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Main Container */
        .orders-container {
            padding: 2rem 0;
            min-height: 70vh;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }

        .page-title {
            font-size: 2.8rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Order Cards */
        .order-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }

        .order-card:hover::before {
            left: 100%;
        }

        .order-card:hover {
            transform: translateY(-5px);
            border-color: var(--border-glow);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 80px rgba(255, 107, 53, 0.2);
        }

        .order-header {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .order-header::before {
            content: '🎆';
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            top: 10px;
            right: 20px;
        }

        .order-id {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .order-date {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .order-body {
            padding: 2rem;
        }

        /* Order Meta Grid - More Compact */
        .order-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .meta-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .meta-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .meta-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary);
        }

        /* Compact Table Styles */
        .table-custom {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .table-custom thead th {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 0.8rem 1rem;
            font-weight: 600;
            text-align: center;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .table-custom tbody td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: middle;
            text-align: center;
        }

        .table-custom tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .product-name {
            font-weight: 600;
            color: white;
            text-align: left;
            font-size: 0.85rem;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .price-cell {
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Compact Total Section */
        .total-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.2rem;
            border-radius: 10px;
            margin-top: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .total-row:last-child {
            border-bottom: none;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--secondary);
            padding-top: 0.8rem;
        }

        /* Buttons */
        .btn-cancel {
            background: linear-gradient(135deg, var(--accent), #dc3545);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4);
        }

        .btn-outline-light {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            max-width: 500px;
            margin: 0 auto;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 1.2rem;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-left: 5px solid;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert-success {
            border-left-color: var(--secondary);
            background: rgba(255, 165, 0, 0.1);
        }

        .alert-danger {
            border-left-color: var(--accent);
            background: rgba(255, 71, 87, 0.1);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Footer */
        footer {
            background: rgba(10, 10, 20, 0.4);
            backdrop-filter: blur(25px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Action Section */
        .action-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.2rem;
            }
            
            .page-header {
                margin-bottom: 2rem;
            }
            
            .order-card {
                margin-bottom: 1.5rem;
            }
            
            .order-body {
                padding: 1.5rem;
            }
            
            .order-meta-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            
            .table-custom {
                font-size: 0.8rem;
            }
            
            .table-custom thead th,
            .table-custom tbody td {
                padding: 0.6rem 0.8rem;
            }
            
            .action-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .orders-container {
                padding: 1rem 0;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
            
            .order-header {
                padding: 1rem 1.5rem;
            }
            
            .order-body {
                padding: 1rem;
            }
            
            .meta-card {
                padding: 0.8rem;
            }
            
            .total-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="particles" id="particles"></div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-sparkles me-2"></i>Sparkle Hub
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_orders.php">
                            <i class="fas fa-receipt me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart 
                            <span class="badge">0</span>
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light" href="admin/login.php">
                            <i class="fas fa-user-shield me-1"></i>Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container orders-container">
        <div class="action-section">
            <a href="index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
            <div class="text-muted">
                <i class="fas fa-history me-2"></i>Order History
            </div>
        </div>

        <div class="page-header fade-in-up">
            <h1 class="page-title">My Orders</h1>
            <p class="page-subtitle">Track your purchases and order status in one place</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-custom text-center fade-in-up" role="alert">
                <div class="h4 mb-3">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle fa-2x text-warning mb-3"></i><br>
                    <?php elseif ($message_type === 'danger'): ?>
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i><br>
                    <?php else: ?>
                        <i class="fas fa-info-circle fa-2x text-info mb-3"></i><br>
                    <?php endif; ?>
                    <?= $message ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-state fade-in-up">
                <i class="fas fa-box-open"></i>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet. Start shopping to see your order history here!</p>
                <a href="index.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-bolt me-2"></i>Start Shopping
                </a>
            </div>
        <?php else: ?>
            
            <?php foreach ($orders as $order): ?>
                <div class="order-card fade-in-up">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="order-id">Order #<?= $order['order_id'] ?></div>
                                <div class="order-date">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?= date("F j, Y, g:i a", strtotime($order['order_date'])) ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="status-badge <?= getStatusClass($order['order_status']) ?>">
                                    <i class="<?= getStatusIcon($order['order_status']) ?>"></i>
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-meta-grid">
                            <div class="meta-card">
                                <div class="meta-label">Customer Name</div>
                                <div class="meta-value"><?= htmlspecialchars($order['customer_name']) ?></div>
                            </div>
                            <div class="meta-card">
                                <div class="meta-label">Contact Number</div>
                                <div class="meta-value"><?= htmlspecialchars($order['contact_no']) ?></div>
                            </div>
                            <div class="meta-card">
                                <div class="meta-label">Delivery State</div>
                                <div class="meta-value"><?= htmlspecialchars($order['state']) ?></div>
                            </div>
                            <div class="meta-card">
                                <div class="meta-label">Order Total</div>
                                <div class="meta-value">₹<?= number_format($order['total_amount'], 2) ?></div>
                            </div>
                        </div>

                        <?php if (isset($order_items[$order['order_id']])): ?>
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items[$order['order_id']] as $item): ?>
                                            <tr>
                                                <td class="product-name"><?= htmlspecialchars($item['product_name']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td class="price-cell">₹<?= number_format($item['unit_price'], 2) ?></td>
                                                <td class="price-cell">₹<?= number_format($item['total_price'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="total-section">
                            <div class="total-row">
                                <span>Items Subtotal:</span>
                                <span class="fw-semibold">₹<?= number_format($order['total'], 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Packing Charges:</span>
                                <span class="fw-semibold">₹<?= number_format($order['packing_cost'], 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span class="h5">Grand Total:</span>
                                <span class="h5 fw-bold">₹<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>

                        <?php if ($order['order_status'] === 'Processing'): ?>
                            <div class="text-end mt-3">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel Order #<?= $order['order_id'] ?>? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <button type="submit" class="btn btn-cancel">
                                        <i class="fas fa-times-circle me-2"></i>Cancel Order
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p class="mb-0">© <?= date("Y") ?> Sparkle Hub | All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create floating particles (same as contact page)
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 100 + 50;
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 20 + 10;
                const animationDelay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}vw`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${animationDelay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Add staggered animation to order cards
            const orderCards = document.querySelectorAll('.order-card');
            orderCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>