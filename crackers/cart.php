<?php
session_start();
// 1. Connection Setup
require 'inc/db.php'; 

$session_id = session_id();

// --- START: Cart Item Removal Logic (Handling cart.php?remove=ID) ---
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    // We use the cart table's unique ID column 
    $cart_id_to_remove = $_GET['remove'];

    try {
        // Delete the item using both cart_id and session_id for security
        $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?"); 
        $delete_stmt->execute([$cart_id_to_remove, $session_id]);
        
        // Use Post-Redirect-Get pattern to clear the GET parameter and refresh
        header("Location: cart.php");
        exit;
    } catch (PDOException $e) {
        // In a production environment, you would log this error.
        // error_log("Cart removal error: " . $e->getMessage());
    }
}
// --- END: Cart Item Removal Logic ---

// 2. Fetch Cart Items
try {
    // Select cart items, joining with the product table to get names and images
    // Alias the cart's primary key 'id' as 'cart_id'
    $cart_items_stmt = $pdo->prepare("
        SELECT 
            c.*, 
            c.id AS cart_id, 
            p.product_name, 
            p.product_image_path
        FROM cart c
        JOIN product p ON c.product_id = p.product_id
        WHERE c.session_id = ?
    ");
    $cart_items_stmt->execute([$session_id]);
    $cart_items = $cart_items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // This will display the error message, useful for debugging
    die("Database error while fetching cart: " . $e->getMessage());
}

// 3. Calculate Cart Totals
$subtotal = 0;
foreach ($cart_items as $item) {
    // Recalculate subtotal based on current item cost and quantity
    $subtotal += $item['cost'] * $item['quantity'];
}

// Defined values (Must match checkout.php)
$packing_cost = 20.00; 

// Calculate final total
$final_total = $subtotal + $packing_cost;

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

        /* Main Container */
        .main-container {
            padding: 2rem 0;
            min-height: 70vh;
        }

        /* Ultra Glassmorphism Cart Card */
        .cart-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(30px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .cart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.05), transparent);
            transition: left 0.8s;
        }

        .cart-card:hover::before {
            left: 100%;
        }

        .cart-heading {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            text-align: center;
            text-shadow: 0 2px 10px rgba(255, 107, 53, 0.3);
        }

        /* Ultra Glassmorphism Cart Items */
        .cart-items-container {
            margin-bottom: 2rem;
        }

        .cart-item {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.03), transparent);
            transition: left 0.6s;
        }

        .cart-item:hover::before {
            left: 100%;
        }

        .cart-item:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-5px);
            border-color: var(--border-glow);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.1),
                0 0 30px rgba(255, 107, 53, 0.1);
        }

        .cart-item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
        }

        .cart-item:hover .cart-item-img {
            transform: scale(1.08);
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.2);
        }

        .cart-item-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .cart-item-price {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .quantity-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--light);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .cart-item:hover .quantity-display {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.05);
        }

        .item-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffa500;
            text-shadow: 0 2px 15px rgba(255, 165, 0, 0.4);
        }

        /* Ultra Glassmorphism Summary Box */
        .summary-box {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 2rem;
            position: sticky;
            top: 2rem;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .summary-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--light);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-total {
            border-top: 2px solid rgba(255, 255, 255, 0.15);
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.4rem;
            font-weight: 700;
        }

        /* Glassmorphism Buttons */
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
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-success {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 25px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 
                0 4px 15px rgba(255, 107, 53, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-success::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-success:hover::before {
            left: 100%;
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(255, 107, 53, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, var(--accent), #dc3545);
            border: none;
            border-radius: 20px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 71, 87, 0.4);
        }

        /* Ultra Glassmorphism Empty State */
        .empty-cart {
            text-align: center;
            padding: 4rem 1rem;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .empty-cart i {
            font-size: 5rem;
            color: rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .empty-cart h4 {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .empty-cart p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
        }

        /* Ultra Glassmorphism Footer */
        footer {
            background: rgba(10, 10, 20, 0.4);
            backdrop-filter: blur(25px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
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

        /* Responsive */
        @media (max-width: 768px) {
            .cart-heading {
                font-size: 2rem;
            }
            
            .cart-card {
                padding: 1.5rem;
            }
            
            .cart-item {
                text-align: center;
            }
            
            .cart-item-img {
                width: 80px;
                height: 80px;
                margin-bottom: 1rem;
            }
            
            .summary-box {
                position: static;
                margin-top: 2rem;
            }
            
            .col-mobile {
                margin-bottom: 1rem;
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
                        <a class="nav-link" href="view_orders.php">
                            <i class="fas fa-receipt me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart 
                            <span class="badge"><?= count($cart_items) ?></span>
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
    <div class="container main-container">
        <div class="cart-card fade-in-up">
            <h1 class="cart-heading">🛒 Your Shopping Cart</h1>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
                <span class="text-muted"><?= count($cart_items) ?> item(s) in cart</span>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Your cart is empty</h4>
                    <p>Browse our products and add some items to get started!</p>
                    <a href="index.php" class="btn btn-success mt-3">
                        <i class="fas fa-store me-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <div class="cart-items-container">
                            <?php foreach ($cart_items as $item): 
                                $correct_total = $item['cost'] * $item['quantity'];
                                $has_calculation_issue = $correct_total != $item['total_cost'];
                            ?>
                                <div class="cart-item fade-in-up">
                                    <div class="row align-items-center">
                                        <!-- Product Image -->
                                        <div class="col-md-2 text-center col-mobile">
                                            <img src="<?= htmlspecialchars($item['product_image_path']) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 class="cart-item-img">
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div class="col-md-3 col-mobile">
                                            <h5 class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></h5>
                                            <p class="cart-item-price">Unit Price: ₹<?= number_format($item['cost'], 2) ?></p>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="col-md-2 text-center col-mobile">
                                            <span class="text-muted d-block mb-1">Quantity</span>
                                            <div class="quantity-display">
                                                <?= $item['quantity'] ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Item Total -->
                                        <div class="col-md-3 text-center col-mobile">
                                            <span class="text-muted d-block mb-1">Item Total</span>
                                            <span class="item-total">₹<?= number_format($correct_total, 2) ?></span>
                                            
                                        </div>
                                        
                                        <!-- Remove Button -->
                                        <div class="col-md-2 text-center col-mobile">
                                            <a href="cart.php?remove=<?= $item['cart_id'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to remove this item?');">
                                                <i class="fas fa-trash me-1"></i>Remove
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="summary-box fade-in-up">
                            <h4 class="summary-title">Order Summary</h4>
                            
                            <div class="summary-row">
                                <span>Items (<?= count($cart_items) ?>):</span>
                                <span>₹<?= number_format($subtotal, 2) ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Packing Cost:</span>
                                <span>₹<?= number_format($packing_cost, 2) ?></span>
                            </div>
                            
                            <div class="summary-row summary-total">
                                <span>Grand Total:</span>
                                <span class="text-warning fw-bold">₹<?= number_format($final_total, 2) ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-success mt-4">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
            
            // Add staggered animation to cart items
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>