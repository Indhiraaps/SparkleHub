<?php
session_start();
require 'inc/db.php';

$session_id = session_id();

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: view_orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$just_placed = isset($_GET['placed']);
$order = null;
$order_items = [];
$error_message = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    // Customers can only see their own orders
    $my_orders = array_map('intval', $_SESSION['my_orders'] ?? []);
    $is_mine = $order && ($order['session_id'] === $session_id || in_array($order_id, $my_orders, true));

    if (!$is_mine) {
        $order = null;
        $error_message = "Order #{$order_id} was not found. If you placed it on another device, use \"Track an order\" on the My Orders page.";
    } else {
        $items = $pdo->prepare("
            SELECT oi.*, p.product_image_path
            FROM order_items oi
            LEFT JOIN product p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $items->execute([$order_id]);
        $order_items = $items->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Order detail fetch failed: " . $e->getMessage());
    $error_message = "A database error occurred while loading this order.";
}

$cart_count = cart_count($pdo);

// Progress steps shown on the tracker
$steps = ['Pending' => 'Order placed', 'Processing' => 'Packing', 'Shipped' => 'Shipped', 'Delivered' => 'Delivered'];
$current_step = $order ? array_search($order['order_status'], array_keys($steps), true) : false;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sparkle Hub | Order #<?= $order_id ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
body {
    min-height: 100vh;
    background:
        linear-gradient(rgba(10, 10, 20, 0.82), rgba(10, 10, 20, 0.92)),
        url('colorful_fireworks.jpg') center / cover fixed;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.navbar.bg-dark {
    background-color: rgba(33, 37, 41, 0.6) !important;
    backdrop-filter: blur(8px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}
.page-title { color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.8); }
.content-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    padding: 20px;
}
.item-image { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
.tracker { display: flex; justify-content: space-between; position: relative; margin: 10px 0 5px; }
.tracker::before {
    content: ''; position: absolute; top: 17px; left: 12%; right: 12%;
    height: 4px; background: #dee2e6; z-index: 0;
}
.tracker .step { text-align: center; flex: 1; position: relative; z-index: 1; font-size: .85rem; color: #6c757d; }
.tracker .dot {
    width: 38px; height: 38px; border-radius: 50%; margin: 0 auto 6px;
    display: flex; align-items: center; justify-content: center;
    background: #dee2e6; color: #fff;
}
.tracker .step.done { color: #198754; font-weight: 600; }
.tracker .step.done .dot { background: #198754; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container">
        <a class="navbar-brand" href="index.php">Sparkle Hub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="view_orders.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Cart <span class="badge bg-warning text-dark"><?= $cart_count ?></span></a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">

    <?php if ($error_message): ?>
        <div class="alert alert-danger mb-4"><?= e($error_message) ?></div>
        <a href="view_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to My Orders</a>
    <?php else: ?>

        <?php if ($just_placed): ?>
            <div class="alert alert-success mb-4">
                <h4 class="alert-heading mb-1"><i class="fas fa-check-circle me-2"></i>Thank you! Your order has been placed.</h4>
                <p class="mb-0">Your order number is <strong>#<?= $order_id ?></strong>. Keep it with your phone number to track this order from any device.</p>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h2 class="fw-bold page-title mb-0">Order #<?= $order_id ?></h2>
            <div>
                <a href="view_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> My Orders</a>
                <a href="index.php" class="btn btn-warning"><i class="fas fa-bolt"></i> Continue Shopping</a>
            </div>
        </div>

        <!-- Status tracker -->
        <div class="content-card mb-4">
            <?php if ($order['order_status'] === 'Cancelled'): ?>
                <p class="mb-0 text-danger fw-bold"><i class="fas fa-times-circle me-2"></i>This order was cancelled.</p>
            <?php else: ?>
                <div class="tracker">
                    <?php $i = 0; foreach ($steps as $label): ?>
                        <div class="step <?= $current_step !== false && $i <= $current_step ? 'done' : '' ?>">
                            <div class="dot"><i class="fas fa-check"></i></div>
                            <?= e($label) ?>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="content-card mb-4">
                    <h4>Order Summary</h4>
                    <hr>
                    <p><strong>Status:</strong>
                        <span class="badge <?= status_badge_class($order['order_status']) ?>">
                            <i class="<?= status_icon($order['order_status']) ?> me-1"></i><?= e($order['order_status']) ?>
                        </span>
                    </p>
                    <p><strong>Order Date:</strong> <?= e(date('M d, Y g:i A', strtotime($order['order_date']))) ?></p>
                    <p><strong>Payment:</strong> Cash on Delivery</p>
                    <hr>
                    <p class="mb-1"><strong>Deliver to:</strong></p>
                    <address class="small bg-light p-2 rounded mb-3">
                        <strong><?= e($order['customer_name']) ?></strong><br>
                        <?= nl2br(e($order['address'])) ?><br>
                        <?= e($order['state']) ?><br>
                        <i class="fas fa-phone me-1"></i><?= e($order['contact_no']) ?>
                        <?php if (!empty($order['mail_id'])): ?><br><i class="fas fa-envelope me-1"></i><?= e($order['mail_id']) ?><?php endif; ?>
                    </address>
                    <div class="d-flex justify-content-between"><span>Items subtotal</span><span><?= rupees($order['total']) ?></span></div>
                    <div class="d-flex justify-content-between"><span>Packing</span><span><?= rupees($order['packing_cost']) ?></span></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="text-success"><?= rupees($order['total_amount']) ?></span></div>

                    <?php if (can_cancel($order['order_status'])): ?>
                        <form method="POST" action="view_orders.php" class="mt-3"
                              onsubmit="return confirm('Cancel order #<?= $order_id ?>? This cannot be undone.');">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                            <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-times-circle me-1"></i>Cancel Order</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-7">
                <div class="content-card">
                    <h4>Items (<?= count($order_items) ?>)</h4>
                    <hr>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($order_items as $item): ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center">
                                <img src="<?= e($item['product_image_path'] ?: 'images/no-image.svg') ?>"
                                     alt="<?= e($item['product_name']) ?>" class="item-image me-3">
                                <div>
                                    <p class="mb-0 fw-bold"><?= e($item['product_name']) ?></p>
                                    <small class="text-muted">Unit price: <?= rupees($item['cost']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="mb-0">Qty: <strong><?= (int)$item['quantity'] ?></strong></p>
                                <p class="mb-0 fw-bold"><?= rupees($item['total_cost']) ?></p>
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
