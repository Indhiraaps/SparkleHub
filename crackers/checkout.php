<?php
session_start();
// 1. Connection Setup
require 'inc/db.php'; 

$session_id = session_id();
$packing_cost = 20.00; // Must match the value in cart.php

// 2. Fetch Cart Contents and Calculate Totals (Read Only)
try {
    // Fetch all necessary details from cart, joining with product for names and stock
    $cart_items_stmt = $pdo->prepare("
        SELECT 
            c.*, 
            p.product_name, 
            p.cost AS product_original_cost, /* Fetch original product cost */
            p.stock_status
        FROM cart c
        JOIN product p ON c.product_id = p.product_id
        WHERE c.session_id = ?
    ");
    $cart_items_stmt->execute([$session_id]);
    $cart_items = $cart_items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // This will help diagnose the Parse error if the file is truly clean
    die("Database error while fetching cart for checkout: " . $e->getMessage());
}

// Check if cart is empty - crucial step
if (empty($cart_items)) {
    // Check if the order was just successfully placed (message in session)
    if (!isset($_SESSION['order_message']) || $_SESSION['order_message_type'] !== 'success') {
        header("Location: cart.php"); // Redirect if nothing to checkout AND no success message
        exit;
    }
}

// Check for stock availability before processing (front-end check)
$stock_error = false;
foreach ($cart_items as $item) {
    if ($item['stock_status'] < $item['quantity']) {
        $stock_error = true;
        break;
    }
}
if ($stock_error) {
    $_SESSION['order_message'] = "One or more items in your cart exceeded available stock. Please review your cart.";
    $_SESSION['order_message_type'] = 'danger';
    header("Location: cart.php");
    exit;
}

// FIXED: Calculate subtotal properly using cost and quantity
$subtotal = 0;
foreach ($cart_items as $item) {
    // Calculate subtotal based on unit price and quantity
    $item_subtotal = (float)$item['cost'] * (int)$item['quantity'];
    $subtotal += $item_subtotal;
}
$final_total = $subtotal + $packing_cost;

$message = '';
$message_type = '';

// 3. Process Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize user input
    $customer_name = trim($_POST['customer_name']);
    $contact_no = trim($_POST['contact_no']);
    $address = trim($_POST['address']);
    $mail_id = trim($_POST['mail_id']); 
    $state = trim($_POST['state']);

    // Basic Validation 
    if (empty($customer_name) || empty($contact_no) || empty($address) || empty($state)) {
        $message = "Please fill in all required fields.";
        $message_type = 'danger';
    } else {
        
        // Begin Transaction: Ensures all related database operations succeed or fail together
        $pdo->beginTransaction();
        try {
            // A. Insert into orders table
            $order_stmt = $pdo->prepare("
                INSERT INTO orders 
                (customer_name, contact_no, address, mail_id, state, total, packing_cost, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $order_stmt->execute([
                $customer_name, 
                $contact_no, 
                $address, 
                !empty($mail_id) ? $mail_id : null, 
                $state, 
                $subtotal, 
                $packing_cost, 
                $final_total
            ]);

            $order_id = $pdo->lastInsertId();

            // B. Prepare statements for inserting items and updating stock
            $item_insert_stmt = $pdo->prepare("
                INSERT INTO order_items 
                (order_id, product_id, product_name, cost, quantity, total_cost)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Note: The product's stock_status is updated by subtracting the order quantity
            $stock_update_stmt = $pdo->prepare("
                UPDATE product SET stock_status = stock_status - ? WHERE product_id = ? AND stock_status >= ?
            ");

            // Process each item in the cart
            foreach ($cart_items as $item) {
                // Calculate item total cost for order_items table
                $item_total_cost = (float)$item['cost'] * (int)$item['quantity'];
                
                // Insert into order_items
                $item_insert_stmt->execute([
                    $order_id, 
                    $item['product_id'], 
                    $item['product_name'], 
                    $item['cost'],         // Unit price from cart table
                    $item['quantity'], 
                    $item_total_cost       // Calculated total cost
                ]);
                
                // Update product stock 
                $stock_update_stmt->execute([
                    $item['quantity'], 
                    $item['product_id'], 
                    $item['quantity'] // Ensures stock is not negative
                ]);
            }

            // C. Clear the cart for the current session
            $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
            $clear_cart_stmt->execute([$session_id]);

            // Commit transaction
            $pdo->commit();

            $message = "Order placed successfully! Your Order ID is: **{$order_id}**";
            $message_type = 'success';

            // Store message in session and redirect to prevent form resubmission
            $_SESSION['order_message'] = $message;
            $_SESSION['order_message_type'] = $message_type;
            header("Location: checkout.php"); 
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Order placement failed: " . $e->getMessage());
            $message = "An error occurred while placing your order. Please try again. (Ref: " . $e->getMessage() . ")";
            $message_type = 'danger';
        }
    }
}

// Check for and display session message (for post-redirect-get pattern)
if (isset($_SESSION['order_message'])) {
    $message = $_SESSION['order_message'];
    $message_type = $_SESSION['order_message_type'];
    unset($_SESSION['order_message'], $_SESSION['order_message_type']);
    
    // If order was successful, empty the cart items array so the checkout form disappears
    if ($message_type === 'success') {
        $cart_items = [];
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Sparkle Hub - Ignite Your Celebrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --sparkle-orange: #ff6b35;
            --sparkle-red: #ff3d3d;
            --sparkle-gold: #ffd700;
            --sparkle-purple: #8b5cf6;
            --sparkle-dark: #1a1a2e;
            --sparkle-light: #fffaf0;
        }
        
        body {
            background: linear-gradient(135deg, var(--sparkle-dark) 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: white;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background effects */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 61, 61, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 215, 0, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        .sparkle-nav {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--sparkle-orange);
            padding: 1rem 0;
        }
        
        .nav-brand {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(45deg, var(--sparkle-gold), var(--sparkle-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }
        
        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--sparkle-gold) !important;
            transform: translateY(-2px);
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, var(--sparkle-orange), var(--sparkle-gold));
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .checkout-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 50px rgba(255, 107, 53, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
            position: relative;
        }
        
        .checkout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--sparkle-orange), var(--sparkle-red), var(--sparkle-gold), var(--sparkle-purple));
        }
        
        .checkout-header {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.9), rgba(255, 61, 61, 0.9));
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .checkout-header::before {
            content: '✨';
            position: absolute;
            font-size: 3rem;
            opacity: 0.1;
            top: 10px;
            left: 10px;
        }
        
        .checkout-header::after {
            content: '🎆';
            position: absolute;
            font-size: 3rem;
            opacity: 0.1;
            bottom: 10px;
            right: 10px;
        }
        
        .checkout-header h1 {
            margin: 0;
            font-weight: 800;
            font-size: 2.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .checkout-header p {
            margin: 15px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .checkout-body {
            padding: 40px;
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--sparkle-gold);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section-title i {
            font-size: 1.3rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--sparkle-orange);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--sparkle-gold);
            font-size: 1rem;
        }
        
        .required::after {
            content: " *";
            color: var(--sparkle-red);
        }
        
        .order-summary {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .product-item:hover {
            background: rgba(255, 107, 53, 0.1);
            border-radius: 8px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        
        .product-meta {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .product-price {
            font-weight: 700;
            color: var(--sparkle-gold);
            font-size: 1.1rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--sparkle-gold);
            background: linear-gradient(45deg, var(--sparkle-orange), var(--sparkle-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding-top: 20px;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--sparkle-orange), var(--sparkle-red));
            color: white;
            border: none;
            padding: 18px 35px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 15px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-checkout::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 10px 30px rgba(255, 107, 53, 0.4),
                0 0 20px rgba(255, 61, 61, 0.3);
        }
        
        .btn-checkout:hover::before {
            left: 100%;
        }
        
        .back-link {
            color: var(--sparkle-gold);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            padding: 10px 20px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .back-link:hover {
            color: var(--sparkle-orange);
            background: rgba(255, 107, 53, 0.2);
            transform: translateX(-5px);
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 25px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-left: 5px solid;
        }
        
        .alert-success {
            border-left-color: var(--sparkle-gold);
            background: rgba(255, 215, 0, 0.1);
        }
        
        .alert-danger {
            border-left-color: var(--sparkle-red);
            background: rgba(255, 61, 61, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            color: var(--sparkle-gold);
            opacity: 0.7;
        }
        
        .empty-state h3 {
            color: var(--sparkle-gold);
            margin-bottom: 15px;
        }
        
        .security-badge {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 107, 53, 0.1));
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            margin-top: 20px;
        }
        
        /* Sparkle animations */
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
        
        .sparkle {
            position: absolute;
            pointer-events: none;
            animation: sparkle 2s ease-in-out infinite;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg sparkle-nav">
    <div class="container">
        <a class="navbar-brand nav-brand" href="index.php">
            <i class="fas fa-fire"></i> Sparkle Hub
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
            <a class="nav-link" href="#"><i class="fas fa-info-circle"></i> About Us</a>
            <a class="nav-link" href="#"><i class="fas fa-phone"></i> Contact</a>
            <a class="nav-link" href="#"><i class="fas fa-clipboard-list"></i> My Orders</a>
            <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
            <a class="nav-link" href="#"><i class="fas fa-user-shield"></i> Admin</a>
        </div>
    </div>
</nav>

<div class="checkout-container">
    <a href="cart.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Back to Cart
    </a>

    <div class="checkout-card">
        <div class="checkout-header">
            <h1><i class="fas fa-bolt"></i> CHECKOUT</h1>
            <p>Complete your purchase and ignite your celebrations!</p>
        </div>
        
        <div class="checkout-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $message_type ?> alert-custom text-center" role="alert">
                    <div class="h4 mb-3">
                        <?php if ($message_type === 'success'): ?>
                            <i class="fas fa-firework fa-2x text-warning mb-3"></i><br>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i><br>
                        <?php endif; ?>
                        <?= $message ?>
                    </div>
                    <?php if ($message_type === 'success'): ?>
                        <a href="index.php" class="btn btn-warning btn-lg px-5">
                            <i class="fas fa-bolt"></i> Continue Shopping
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="row g-5">
                <!-- Shipping Details Column -->
                <div class="col-lg-7">
                    <?php if (!empty($cart_items)): ?>
                        <div class="mb-5">
                            <h3 class="section-title">
                                <i class="fas fa-shipping-fast"></i>
                                Shipping Information
                            </h3>
                            <form method="POST">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="customer_name" class="form-label required">Full Name</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                               required value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
                                               placeholder="Enter your full name">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_no" class="form-label required">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact_no" name="contact_no" 
                                               required value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>"
                                               placeholder="Your phone number">
                                    </div>
                                    <div class="col-12">
                                        <label for="mail_id" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="mail_id" name="mail_id" 
                                               value="<?= htmlspecialchars($_POST['mail_id'] ?? '') ?>"
                                               placeholder="your.email@example.com">
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label required">Shipping Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" 
                                                  required placeholder="Enter your complete shipping address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label required">State</label>
                                        <input type="text" class="form-control" id="state" name="state" 
                                               required value="<?= htmlspecialchars($_POST['state'] ?? '') ?>"
                                               placeholder="Your state">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-checkout">
                                    <i class="fas fa-bolt"></i> IGNITE YOUR ORDER
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <?php if ($message_type !== 'success'): ?>
                            <div class="empty-state">
                                <i class="fas fa-firework"></i>
                                <h3>Your cart is empty</h3>
                                <p>Add some premium fireworks to light up your celebrations!</p>
                                <a href="index.php" class="btn btn-warning btn-lg mt-3">
                                    <i class="fas fa-bolt"></i> Explore Premium Collection
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Order Summary Column -->
                <div class="col-lg-5">
                    <div class="order-summary">
                        <h3 class="section-title">
                            <i class="fas fa-receipt"></i>
                            Order Summary
                        </h3>
                        
                        <?php if (!empty($cart_items)): ?>
                            <div class="mb-4">
                                <?php foreach ($cart_items as $item): 
                                    $item_total = (float)$item['cost'] * (int)$item['quantity'];
                                ?>
                                    <div class="product-item">
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <div class="product-meta">
                                                Quantity: <?= $item['quantity'] ?> × ₹<?= number_format($item['cost'], 2) ?>
                                            </div>
                                        </div>
                                        <div class="product-price">₹<?= number_format($item_total, 2) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="summary-section">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span class="fw-semibold">₹<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Packing Cost:</span>
                                    <span class="fw-semibold">₹<?= number_format($packing_cost, 2) ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="h5">Grand Total:</span>
                                    <span class="h5 fw-bold">₹<?= number_format($final_total, 2) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state py-4">
                                <i class="fas fa-firework"></i>
                                <p class="text-muted">No items to summarize</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($cart_items)): ?>
                        <div class="security-badge">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-shield-alt fa-2x text-warning"></i>
                                <div>
                                    <h6 class="mb-1 text-warning">Secure Checkout</h6>
                                    <small class="text-white-50">Your information is protected with SSL encryption</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add sparkle effect on button hover
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.querySelector('.btn-checkout');
        if (btn) {
            btn.addEventListener('mouseenter', function(e) {
                createSparkle(e);
            });
        }
        
        function createSparkle(e) {
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';
            sparkle.innerHTML = '✨';
            sparkle.style.left = e.offsetX + 'px';
            sparkle.style.top = e.offsetY + 'px';
            btn.appendChild(sparkle);
            
            setTimeout(() => {
                sparkle.remove();
            }, 2000);
        }
    });
</script>

</body>
</html>