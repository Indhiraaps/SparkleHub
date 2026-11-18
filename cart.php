<?php
session_start();
require 'inc/db.php';

$session_id = session_id();

// Remove an item (sent as a POST form from the Remove button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {
    require_same_origin();
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
    $stmt->execute([(int)$_POST['remove_cart_id'], $session_id]);
    header("Location: cart.php");
    exit;
}

// Cart items with the CURRENT product price and stock
try {
    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.product_id, c.quantity,
               p.product_name, p.product_image_path, p.cost AS mrp, p.stock_status,
               cat.category_name
        FROM cart c
        JOIN product p ON c.product_id = p.product_id
        LEFT JOIN category cat ON p.category = cat.category_id
        WHERE c.session_id = ?
        ORDER BY c.id
    ");
    $stmt->execute([$session_id]);
    $cart_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Cart fetch failed: " . $e->getMessage());
    die("Sorry, we could not load your cart. Please try again.");
}

// Totals
$discounts = get_discounts($pdo);
$subtotal = 0;     // what the customer pays for items
$mrp_total = 0;    // the same items at full price
$item_count = 0;
foreach ($cart_items as &$item) {
    $item['percent']    = discount_percent((float)$item['mrp'], $discounts);
    $item['unit_price'] = selling_price((float)$item['mrp'], $discounts);
    $item['line_total'] = $item['unit_price'] * (int)$item['quantity'];
    $subtotal  += $item['line_total'];
    $mrp_total += (float)$item['mrp'] * (int)$item['quantity'];
    $item_count += (int)$item['quantity'];
}
unset($item);

$savings = $mrp_total - $subtotal;
$packing_cost = packing_cost();
$final_total = $subtotal + $packing_cost;

// Message passed from checkout (e.g. an item ran out of stock)
$cart_message = $_SESSION['order_message'] ?? '';
$cart_message_type = $_SESSION['order_message_type'] ?? 'info';
unset($_SESSION['order_message'], $_SESSION['order_message_type']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Cart | Sparkle Hub</title>
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

        /* ---------- Cart page ---------- */
        .cart-page {
            padding-top: 2rem;
            padding-bottom: 1rem;
        }

        .cart-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .cart-title small {
            font-size: 1rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.7);
        }

        .continue-link {
            color: #ffb74d;
            text-decoration: none;
            font-weight: 500;
        }

        .continue-link:hover {
            color: #ffd08a;
        }

        .panel {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
        }

        /* One row per product */
        .cart-row {
            display: grid;
            grid-template-columns: 84px 1fr auto auto;
            grid-template-areas: "img info qty total";
            gap: 0.4rem 1.25rem;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cart-row:last-child {
            border-bottom: none;
        }

        .cart-img {
            grid-area: img;
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
        }

        .cart-info {
            grid-area: info;
            min-width: 0;
        }

        .cart-name {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.15rem;
            color: #fff;
        }

        .cart-category {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .cart-price {
            font-size: 0.9rem;
            margin-top: 0.25rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .cart-price .mrp {
            text-decoration: line-through;
            color: rgba(255, 255, 255, 0.5);
            margin-left: 0.35rem;
            font-size: 0.8rem;
        }

        .cart-price .off {
            color: #66bb6a;
            font-weight: 600;
            margin-left: 0.35rem;
            font-size: 0.8rem;
        }

        .cart-stock {
            font-size: 0.75rem;
            color: #ffb74d;
            margin-top: 0.15rem;
        }

        .remove-btn {
            background: none;
            border: none;
            padding: 0;
            margin-top: 0.4rem;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.8rem;
        }

        .remove-btn:hover {
            color: #ff6b6b;
        }

        .cart-qty {
            grid-area: qty;
        }

        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            overflow: hidden;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: #fff;
            width: 32px;
            height: 34px;
            font-size: 1.05rem;
        }

        .qty-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.12);
        }

        .qty-btn:disabled {
            opacity: 0.35;
        }

        .qty-value {
            min-width: 34px;
            text-align: center;
            font-weight: 600;
        }

        .cart-line-total {
            grid-area: total;
            text-align: right;
            font-weight: 700;
            font-size: 1.05rem;
            color: #ffb74d;
            min-width: 90px;
        }

        .cart-row.updating {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Price summary */
        .summary {
            padding: 1.25rem;
            position: sticky;
            top: 100px;
        }

        .summary h2 {
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 1rem;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.6rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .summary-line .green {
            color: #66bb6a;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            border-top: 1px dashed rgba(255, 255, 255, 0.25);
            padding-top: 0.8rem;
            margin-top: 0.8rem;
        }

        .savings-note {
            background: rgba(102, 187, 106, 0.15);
            color: #81c784;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 1rem;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            margin-top: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            color: #fff;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
        }

        .checkout-btn:hover {
            color: #fff;
            filter: brightness(1.08);
        }

        .trust-notes {
            list-style: none;
            padding: 0;
            margin: 1rem 0 0;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.72);
        }

        .trust-notes li {
            margin-bottom: 0.35rem;
        }

        .trust-notes i {
            color: #ffb74d;
            width: 18px;
        }

        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 3.5rem 1rem;
        }

        .empty-cart i {
            font-size: 3.5rem;
            color: #ffb74d;
            margin-bottom: 1rem;
        }

        .empty-cart p {
            color: rgba(255, 255, 255, 0.75);
        }

        footer {
            background: rgba(10, 10, 20, 0.92);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            padding: 1.5rem 0;
            margin-top: 3rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.85);
        }

        .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        /* Phones: image + details on top, quantity and total underneath */
        @media (max-width: 576px) {
            .cart-row {
                grid-template-columns: 72px 1fr auto;
                grid-template-areas:
                    "img info info"
                    "img qty total";
                padding: 0.9rem;
                gap: 0.6rem 0.9rem;
            }

            .cart-img {
                width: 72px;
                height: 72px;
                align-self: start;
            }

            .cart-line-total {
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>

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
                        <a class="nav-link" href="view_orders.php">
                            <i class="fas fa-receipt me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                            <span class="badge"><?= $item_count ?></span>
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
    <main class="container cart-page">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
            <h1 class="cart-title">Shopping Cart <small>(<?= $item_count ?> item<?= $item_count === 1 ? '' : 's' ?>)</small></h1>
            <a href="index.php" class="continue-link"><i class="fas fa-arrow-left me-1"></i>Continue shopping</a>
        </div>

        <?php if ($cart_message): ?>
            <div class="alert alert-<?= e($cart_message_type) ?>"><?= e($cart_message) ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="panel empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2 class="h4">Your cart is empty</h2>
                <p>Browse our fireworks and add something to light up your celebration.</p>
                <a href="index.php" class="checkout-btn d-inline-block w-auto px-4">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Items -->
                <div class="col-lg-8">
                    <div class="panel">
                        <?php foreach ($cart_items as $item):
                            $qty = (int)$item['quantity'];
                            $stock = (int)$item['stock_status'];
                        ?>
                            <div class="cart-row" data-product-id="<?= (int)$item['product_id'] ?>">
                                <img src="<?= e($item['product_image_path'] ?: 'images/no-image.svg') ?>"
                                     alt="<?= e($item['product_name']) ?>" class="cart-img">

                                <div class="cart-info">
                                    <?php if (!empty($item['category_name'])): ?>
                                        <div class="cart-category"><?= e($item['category_name']) ?></div>
                                    <?php endif; ?>
                                    <h3 class="cart-name"><?= e($item['product_name']) ?></h3>
                                    <div class="cart-price">
                                        <?= rupees($item['unit_price']) ?> each
                                        <?php if ($item['percent'] > 0): ?>
                                            <span class="mrp"><?= rupees($item['mrp']) ?></span>
                                            <span class="off"><?= (int)$item['percent'] ?>% off</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($stock <= 5): ?>
                                        <div class="cart-stock">Only <?= $stock ?> left in stock</div>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Remove this item from your cart?');">
                                        <input type="hidden" name="remove_cart_id" value="<?= (int)$item['cart_id'] ?>">
                                        <button type="submit" class="remove-btn"><i class="fas fa-trash-alt me-1"></i>Remove</button>
                                    </form>
                                </div>

                                <div class="cart-qty">
                                    <div class="qty-stepper">
                                        <button type="button" class="qty-btn" data-step="-1" aria-label="Decrease quantity" <?= $qty <= 1 ? 'disabled' : '' ?>>&minus;</button>
                                        <span class="qty-value"><?= $qty ?></span>
                                        <button type="button" class="qty-btn" data-step="1" aria-label="Increase quantity" <?= $qty >= $stock ? 'disabled' : '' ?>>+</button>
                                    </div>
                                </div>

                                <div class="cart-line-total"><?= rupees($item['line_total']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price summary -->
                <div class="col-lg-4">
                    <div class="panel summary">
                        <h2>Price details</h2>
                        <div class="summary-line">
                            <span>Price (<?= $item_count ?> item<?= $item_count === 1 ? '' : 's' ?>)</span>
                            <span><?= rupees($mrp_total) ?></span>
                        </div>
                        <?php if ($savings > 0): ?>
                            <div class="summary-line">
                                <span>Discount</span>
                                <span class="green">&minus; <?= rupees($savings) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-line">
                            <span>Packing charge</span>
                            <span><?= rupees($packing_cost) ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span><?= rupees($final_total) ?></span>
                        </div>

                        <?php if ($savings > 0): ?>
                            <div class="savings-note"><i class="fas fa-tag me-1"></i>You save <?= rupees($savings) ?> on this order</div>
                        <?php endif; ?>

                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout <i class="fas fa-arrow-right ms-1"></i></a>

                        <ul class="trust-notes">
                            <li><i class="fas fa-money-bill-wave"></i> Cash on Delivery</li>
                            <li><i class="fas fa-undo"></i> Cancel any time before shipping</li>
                            <li><i class="fas fa-box"></i> Safely packed for transport</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© <?= date("Y") ?> Sparkle Hub | All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // + / - buttons: save the new quantity, then refresh the totals
        document.querySelectorAll('.cart-row .qty-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('.cart-row');
                const current = parseInt(row.querySelector('.qty-value').textContent, 10);
                const quantity = current + parseInt(btn.dataset.step, 10);
                if (quantity < 1) return;

                row.classList.add('updating');
                try {
                    const res = await fetch('cart_action.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'update', product_id: row.dataset.productId, quantity })
                    });
                    const data = await res.json();
                    if (data.status !== 'success') {
                        alert(data.message);
                        row.classList.remove('updating');
                        return;
                    }
                } catch (e) {
                    alert('Could not update your cart. Please try again.');
                    row.classList.remove('updating');
                    return;
                }
                location.reload();
            });
        });
    </script>
</body>
</html>
