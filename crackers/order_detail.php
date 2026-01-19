<?php
session_start();
require 'inc/db.php'; 

$session_id = session_id();

// 1. Validate Order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view_orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$order_detail = null;
$order_items = [];
$error_message = null;

try {
    // 2. Fetch the specific order details (must belong to the current session)
    $order_stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE order_id = ? AND session_id = ?
    ");
    $order_stmt->execute([$order_id, $session_id]);
    $order_detail = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if the order was found
    if (!$order_detail) {
        $error_message = "Order #$order_id not found or does not belong to your session.";
    } else {
        // 3. Fetch the items for this order
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.product_name, p.product_image_path 
            FROM order_items oi
            JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error_message = "A database error occurred while fetching order details: " . $e->getMessage();
}

?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sparkle Hub | Order #<?= $order_id ?> Detail</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
/* Reuse existing styles for consistency */
body {
    background-image: 
        linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
        url('background.jpg');
    background-repeat: no-repeat, no-repeat;
    background-size: cover, cover;
    background-attachment: fixed, fixed;
}
.navbar.bg-dark {
    background-color: rgba(33, 37, 41, 0.4) !important; 
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px); 
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}
.container h2, .container p, .container h4 {
    color: white; 
    text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
}
.content-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 20px;
}
.item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
</style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container">
        <a class="navbar-brand" href="index.php">Sparkle Hub</a>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="view_orders.php">My Orders</a></li>
                <li class="nav-item">
                    <a class="btn btn-outline-light ms-2" href="admin/login.php">Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger mb-4"><?= $error_message ?></div>
        <a href="view_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
    <?php else: 
        $status = htmlspecialchars($order_detail['order_status'] ?? 'Processing'); 
        $badge_color = 'bg-warning text-dark';
        if ($status === 'Shipped') {
            $badge_color = 'bg-primary';
        } elseif ($status === 'Delivered') {
            $badge_color = 'bg-success';
        } elseif ($status === 'Cancelled') {
            $badge_color = 'bg-danger';
        }
    ?>
    
        <h2 class="fw-bold mb-3">Order Details: #<?= $order_id ?></h2>
        <a href="view_orders.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left"></i> Back to Orders</a>

        <div class="row">
            <div class="col-md-5">
                <div class="content-card mb-4">
                    <h4>Order Summary</h4>
                    <hr>
                    <p><strong>Status:</strong> <span class="badge <?= $badge_color ?>"><?= $status ?></span></p>
                    <p><strong>Order Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($order_detail['order_date']))) ?></p>
                    <p><strong>Total Amount:</strong> <span class="fw-bold text-success">₹<?= number_format($order_detail['total_amount'], 2) ?></span></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order_detail['payment_method']) ?></p>
                    <hr>
                    <p class="mb-1"><strong>Shipping Address:</strong></p>
                    <address class="small bg-light p-2 rounded"><?= nl2br(htmlspecialchars($order_detail['shipping_address'])) ?></address>
                </div>
            </div>

            <div class="col-md-7">
                <div class="content-card">
                    <h4>Items Purchased (<?= count($order_items) ?>)</h4>
                    <hr>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($order_items as $item): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center">
                                <img src="<?= htmlspecialchars($item['product_image_path']) ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                     class="item-image me-3">
                                <div>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($item['product_name']) ?></p>
                                    <small class="text-muted">Unit Price: ₹<?= number_format($item['price_at_order'], 2) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="mb-0">Qty: **<?= $item['quantity'] ?>**</p>
                                <p class="mb-0 fw-bold">₹<?= number_format($item['subtotal'], 2) ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>