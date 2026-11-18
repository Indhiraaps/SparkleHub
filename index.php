<?php
session_start();
require 'inc/db.php';

$cart_count = cart_count($pdo);
$discounts = get_discounts($pdo);

// All products that are in stock, with their category names
try {
    $products = $pdo->query("
        SELECT p.*, c.category_name
        FROM product p
        JOIN category c ON p.category = c.category_id
        WHERE p.stock_status >= 1
        ORDER BY c.category_name, p.product_id ASC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Product fetch failed: " . $e->getMessage());
    $products = [];
}

// Category names for the search dropdown
$categories = array_values(array_unique(array_column($products, 'category_name')));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sparkle Hub | Premium Fireworks & Crackers</title>
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

        .badge {
            background: linear-gradient(45deg, var(--accent), #ff6b9d) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            padding: 4rem 0 2rem;
            position: relative;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 20px rgba(255, 107, 53, 0.5); }
            to { text-shadow: 0 0 30px rgba(255, 165, 0, 0.8), 0 0 40px rgba(255, 107, 53, 0.6); }
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
        }

        /* Search Box Styles */
        .search-container {
            position: relative;
            max-width: 400px;
            margin: 0 auto 2rem;
        }

        .search-box {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 0.8rem 1.5rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }

        .search-box:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            color: white;
        }

        .search-box::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }

        .category-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-top: 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .category-dropdown.show {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }

        .category-item {
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-item:hover {
            background: rgba(255, 107, 53, 0.2);
            color: var(--secondary);
        }

        .category-item.active {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
        }

        /* Category Section Styles */
        .category-section {
            margin-bottom: 1rem;
        }

        .category-header {
            background: linear-gradient(45deg, #ff5f00, #ff9100);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .category-header::before {
            content: '🎆';
            position: absolute;
            font-size: 2rem;
            opacity: 1;
            top: 15px;
            right: 20px;
        }

        .category-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0rem;
        }

        .category-description {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Product Cards: compact grid like most shopping sites */
        .products-container {
            padding: 0.25rem 0 0.5rem;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 0.6rem;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-glow);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
        }

        .product-image-wrap {
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.04);
            margin-bottom: 0.6rem;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            z-index: 2;
            top: 0.9rem;
            left: 0.9rem;
            background: #e53935;
            color: #fff;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .product-details {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product-title {
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.3;
            color: var(--light);
            margin-bottom: 0.35rem;
            /* keep long names to two lines */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.6em;
        }

        .price-container {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 0.15rem 0.45rem;
            margin-bottom: 0.2rem;
        }

        .discount-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffb74d;
        }

        .original-price {
            text-decoration: line-through;
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.8rem;
        }

        .save-text {
            color: #66bb6a;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .stock-line {
            font-size: 0.75rem;
            color: #81c784;
            margin-bottom: 0.6rem;
        }

        .stock-line.low {
            color: #ffb74d;
        }

        .buy-row {
            display: flex;
            gap: 0.4rem;
            margin-top: auto;
        }

        .qty-stepper {
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .qty-btn {
            background: transparent;
            border: none;
            color: var(--light);
            width: 26px;
            height: 34px;
            font-size: 1rem;
            line-height: 1;
        }

        .qty-btn:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .quantity-input {
            width: 30px;
            height: 34px;
            border: none;
            background: transparent;
            color: var(--light);
            text-align: center;
            font-size: 0.9rem;
            padding: 0;
            -moz-appearance: textfield;
        }

        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .quantity-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.08);
        }

        .add-to-cart-btn {
            flex: 1;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 0 0.5rem;
            height: 36px;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            color: #fff;
            filter: brightness(1.08);
        }

        .add-to-cart-btn:disabled {
            background: rgba(108, 117, 125, 0.5);
            color: #fff;
        }

        /* View Toggle */
        .view-toggle-container {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 0.5rem;
            display: inline-flex;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .view-toggle {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .view-toggle.active {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        /* Footer */
        footer {
            background: rgba(10, 10, 20, 0.92);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            padding: 2rem 0;
            margin-top: 3rem;
            color: rgba(255, 255, 255, 0.85);
        }

        footer h5 {
            color: #ffb74d;
            font-weight: 600;
        }

        /* Bootstrap's grey "muted" text is unreadable on this dark theme */
        .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        /* Modal */
        .modal-content {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: white;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-close {
            filter: invert(1);
        }

        /* List View */
        .list-view .products-container > .col {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .list-view .product-card {
            flex-direction: row;
            align-items: center;
            gap: 1rem;
        }

        .list-view .product-image-wrap {
            width: 110px;
            flex-shrink: 0;
            margin-bottom: 0;
        }

        .list-view .product-title {
            min-height: 0;
        }

        .list-view .buy-row {
            max-width: 260px;
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

        .stagger-animation > * {
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .category-title {
                font-size: 1.5rem;
            }

            .list-view .product-image-wrap {
                width: 80px;
            }

            .search-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="particles" id="particles"></div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalProductImage" src="" alt="Product Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

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
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                            <span class="badge"><?= $cart_count ?></span>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title fade-in-up">
                Ignite Your Celebrations
            </h1>
            <p class="hero-subtitle fade-in-up">
                Premium fireworks & crackers that light up your special moments
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_GET["order_success"])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.3);">
                <i class="fas fa-check-circle me-2"></i>
                🎉 Your order has been placed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-container fade-in-up">
            <input type="text"
                   class="form-control search-box"
                   placeholder="Search fireworks or categories..."
                   id="categorySearch">
            <i class="fas fa-search search-icon"></i>
            <div class="category-dropdown" id="categoryDropdown">
                <?php foreach ($categories as $category): ?>
                    <a href="#" class="category-item" data-category="<?= htmlspecialchars($category) ?>">
                        <i class="fas fa-fire me-2"></i><?= htmlspecialchars($category) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
            <div>
                <h2 class="fw-bold mb-1">🔥 Premium Collection</h2>
                <p class="text-muted">Discover our exclusive range of fireworks</p>
            </div>

            <div class="view-toggle-container">
                <button type="button" class="btn view-toggle active" data-view="grid" title="Grid View">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn view-toggle" data-view="list" title="List View">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>

        <!-- Products by Category -->
        <div id="noSearchResults" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
            <h4 class="text-muted">No fireworks match your search</h4>
        </div>

        <div id="product-list-container">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                    <h4 class="text-muted">No products available at the moment</h4>
                    <p class="text-muted">Check back soon for new arrivals!</p>
                </div>
            <?php else: ?>
                <?php
                // Group products by category
                $groupedCategories = [];
                foreach ($products as $product) {
                    $categoryName = $product['category_name'];
                    if (!isset($groupedCategories[$categoryName])) {
                        $groupedCategories[$categoryName] = [];
                    }
                    $groupedCategories[$categoryName][] = $product;
                }

                $categoryIndex = 0;
                foreach ($groupedCategories as $categoryName => $categoryProducts):
                    $categoryIndex++;
                ?>
                <div class="category-section" style="animation-delay: <?= $categoryIndex * 0.2 ?>s" data-category="<?= htmlspecialchars($categoryName) ?>">
                    <!-- Category Header -->
                    <div class="category-header fade-in-up">
                        <h3 class="category-title"><?= htmlspecialchars($categoryName) ?></h3>
                        <p class="category-description">
                            Explore our premium <?= htmlspecialchars($categoryName) ?> collection
                        </p>
                    </div>

                    <!-- Products Grid for this Category -->
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 products-container stagger-animation">
                        <?php foreach ($categoryProducts as $index => $p):
                            $price = (float)$p['cost'];
                            $percent = discount_percent($price, $discounts);
                            $finalPrice = selling_price($price, $discounts);
                            $stock_level = (int)$p['stock_status'];
                            $is_out_of_stock = $stock_level <= 0;
                        ?>
                        <div class="col product-item-wrapper" data-name="<?= e(strtolower($p['product_name'] . ' ' . $p['category_name'])) ?>">
                            <div class="product-card h-100">
                                <?php if ($percent > 0): ?>
                                    <span class="product-badge"><?= $percent ?>% OFF</span>
                                <?php endif; ?>

                                <div class="product-image-wrap">
                                    <img src="<?= e($p['product_image_path']) ?>"
                                         class="product-image product-image-click"
                                         alt="<?= e($p['product_name']) ?>"
                                         loading="lazy"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imageModal"
                                         data-image-url="<?= e($p['product_image_path']) ?>">
                                </div>

                                <div class="product-details">
                                    <h5 class="product-title" title="<?= e($p['product_name']) ?>"><?= e($p['product_name']) ?></h5>

                                    <div class="price-container">
                                        <span class="discount-price"><?= rupees($finalPrice) ?></span>
                                        <?php if ($percent > 0): ?>
                                            <span class="original-price"><?= rupees($price) ?></span>
                                            <span class="save-text"><?= $percent ?>% off</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="stock-line <?= $stock_level <= 5 ? 'low' : '' ?>">
                                        <?= $stock_level <= 5 ? "Only {$stock_level} left" : 'In stock' ?>
                                    </div>

                                    <div class="buy-row">
                                        <div class="qty-stepper">
                                            <button type="button" class="qty-btn" data-step="-1" aria-label="Decrease quantity">&minus;</button>
                                            <input type="number" value="1" min="1" max="<?= $stock_level ?>"
                                                   class="quantity-input" id="qty<?= (int)$p['product_id'] ?>"
                                                   aria-label="Quantity">
                                            <button type="button" class="qty-btn" data-step="1" aria-label="Increase quantity">+</button>
                                        </div>
                                        <button class="btn add-to-cart-btn add-btn" data-id="<?= (int)$p['product_id'] ?>">
                                            <i class="fas fa-cart-plus me-1"></i>Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start text-center">
                    <h5 class="mb-2">
                        <i class="fas fa-sparkles me-2"></i>Sparkle Hub
                    </h5>
                    <p class="mb-0">Lighting up your celebrations since 2024</p>
                </div>
                <div class="col-md-6 text-md-end text-center mt-3 mt-md-0">
                    <p class="mb-0">
                        © <?= date("Y") ?> Sparkle Hub | All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                // Random properties
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

        // Add to Cart Functionality
        $(document).ready(function() {
            createParticles();

            $(".add-btn").click(function() {
                const $btn = $(this);
                const pid = $btn.data("id");
                const qtyInput = $("#qty" + pid);
                const qty = Math.max(1, parseInt(qtyInput.val()));
                const maxStock = parseInt(qtyInput.attr('max'));

                // Validate quantity
                if (qty > maxStock) {
                    showNotification(`Only ${maxStock} items available in stock!`, 'danger');
                    qtyInput.val(maxStock);
                    return;
                }

                if (qty < 1) {
                    showNotification('Please enter a valid quantity!', 'danger');
                    qtyInput.val(1);
                    return;
                }

                // Add loading state
                $btn.html('<i class="fas fa-spinner fa-spin"></i>');
                $btn.prop('disabled', true);

                $.post("cart_action.php", {
                    action: "add",
                    product_id: pid,
                    quantity: qty
                }, function(response) {
                    // Reset button state
                    $btn.html('<i class="fas fa-cart-plus me-1"></i>Add');
                    $btn.prop('disabled', false);

                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            showNotification('Error: Invalid server response', 'danger');
                            return;
                        }
                    }

                    if (response && response.status === 'success') {
                        showNotification(response.message, 'success');
                        if (response.new_cart_count !== undefined) {
                            $(".navbar .badge").text(response.new_cart_count);
                        }
                    } else {
                        showNotification(response?.message || 'Error adding to cart', 'danger');
                    }
                }, "json").fail(function() {
                    $btn.html('<i class="fas fa-cart-plus me-1"></i>Add');
                    $btn.prop('disabled', false);
                    showNotification('Server error. Please try again.', 'danger');
                });
            });

            // Image Modal
            $('#imageModal').on('show.bs.modal', function(event) {
                const image = $(event.relatedTarget);
                const imageUrl = image.data('image-url');
                $(this).find('#modalProductImage').attr('src', imageUrl);
            });

            // View Toggle
            let storedView = localStorage.getItem('productView') || 'grid';

            function applyView(view) {
                $('.view-toggle').removeClass('active');
                $(`.view-toggle[data-view="${view}"]`).addClass('active');

                const $container = $("#product-list-container");
                const $items = $(".product-item-wrapper");

                $container.toggleClass('list-view', view === 'list');
            }

            applyView(storedView);

            $(".view-toggle").click(function() {
                const newView = $(this).data('view');
                localStorage.setItem('productView', newView);
                applyView(newView);
            });

            // Category Search Functionality
            const categorySearch = $('#categorySearch');
            const categoryDropdown = $('#categoryDropdown');
            const categoryItems = $('.category-item');
            const categorySections = $('.category-section');

            // Show dropdown on focus
            categorySearch.on('focus', function() {
                categoryDropdown.addClass('show');
                filterCategories();
            });

            // Filter categories based on search input
            categorySearch.on('input', function() {
                filterCategories();
            });

            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-container').length) {
                    categoryDropdown.removeClass('show');
                }
            });

            // Category selection
            categoryItems.on('click', function(e) {
                e.preventDefault();
                const selectedCategory = $(this).data('category');
                categorySearch.val('');
                filterProducts();
                categoryDropdown.removeClass('show');

                // Scroll to selected category
                const targetSection = $(`.category-section[data-category="${selectedCategory}"]`);
                if (targetSection.length) {
                    $('html, body').animate({
                        scrollTop: targetSection.offset().top - 100
                    }, 500);

                    // Highlight the section
                    categorySections.removeClass('highlight');
                    targetSection.addClass('highlight');

                    // Remove highlight after 2 seconds
                    setTimeout(() => {
                        targetSection.removeClass('highlight');
                    }, 2000);
                }
            });

            // Filter product cards by name/category as the user types
            function filterProducts() {
                const term = categorySearch.val().trim().toLowerCase();
                let visible = 0;
                $('.product-item-wrapper').each(function() {
                    const match = term === '' || String($(this).data('name')).includes(term);
                    $(this).toggle(match);
                    if (match) visible++;
                });
                $('.category-section').each(function() {
                    $(this).toggle($(this).find('.product-item-wrapper').filter(function() {
                        return $(this).css('display') !== 'none';
                    }).length > 0);
                });
                $('#noSearchResults').toggle(visible === 0);
            }

            function filterCategories() {
                const searchTerm = categorySearch.val().toLowerCase();
                filterProducts();

                if (searchTerm === '') {
                    categoryItems.show();
                    return;
                }

                categoryItems.each(function() {
                    const category = $(this).data('category').toLowerCase();
                    if (category.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            // Notification System
            function showNotification(message, type = 'info') {
                const notification = $(`<div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
                    <span class="notification-text"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`);

                notification.find('.notification-text').text(message);
                $('body').append(notification);
                setTimeout(() => notification.alert('close'), 5000);
            }

            // + / - buttons next to the quantity
            $('.qty-btn').on('click', function() {
                const $input = $(this).siblings('.quantity-input');
                const max = parseInt($input.attr('max'));
                let value = (parseInt($input.val()) || 1) + parseInt($(this).data('step'));
                value = Math.min(Math.max(value, 1), max);
                $input.val(value);
            });

            // Quantity input validation
            $('.quantity-input').on('change input', function() {
                const $input = $(this);
                const max = parseInt($input.attr('max'));
                const min = parseInt($input.attr('min'));
                let value = parseInt($input.val());

                if (isNaN(value) || value < min) {
                    $input.val(min);
                } else if (value > max) {
                    $input.val(max);
                    showNotification(`Maximum available quantity is ${max}`, 'warning');
                }
            });

            // Highlight animation for category sections
            const style = document.createElement('style');
            style.textContent = `
                .category-section.highlight .category-header {
                    background: linear-gradient(45deg, #ff3d00, #ff9100) !important;
                    animation: pulseHighlight 2s ease-in-out;
                }

                @keyframes pulseHighlight {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.02); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>