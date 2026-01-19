<?php
// Ensure session starts before any output
session_start();

// 1. Connection Setup
require 'inc/db.php'; 
$session_id = session_id();

// 2. Cart Count (for navbar)
try {
    $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total_quantity FROM cart WHERE session_id = ?");
    $count_stmt->execute([$session_id]);
    $cart_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $cart_count = $cart_result['total_quantity'] ?? 0;
} catch (PDOException $e) {
    error_log("Cart count fetch failed: " . $e->getMessage());
    $cart_count = 0; 
}

// 3. Fetch all products with category names
try {
    $products = $pdo->query("
        SELECT p.*, c.category_name 
        FROM product p 
        JOIN category c ON p.category = c.category_id
        WHERE p.stock_status >= 1 
        ORDER BY c.category_name, p.product_id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Product fetch failed: " . $e->getMessage());
    $products = []; 
}

// 4. Fetch categories for dropdown
try {
    $categories = $pdo->query("
        SELECT DISTINCT c.category_name 
        FROM category c 
        JOIN product p ON c.category_id = p.category 
        WHERE p.stock_status >= 1 
        ORDER BY c.category_name
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Category fetch failed: " . $e->getMessage());
    $categories = [];
}

// 5. Fetch and clean discount ranges
try {
    $discounts = $pdo->query("SELECT * FROM discount ORDER BY discount_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Discount fetch failed: " . $e->getMessage());
    $discounts = [];
}

// 6. Discount Function
function getDiscount($price, $discounts) {
    $price = (float)$price;
    foreach ($discounts as $d) {
        $range_parts = array_map('trim', explode("-", $d["discount_range"]));
        if (count($range_parts) !== 2 || !isset($d["percentage"])) continue;
        list($min, $max) = $range_parts;
        $min = (float)$min;
        $max = (float)$max;
        if ($price >= $min && $price <= $max) {
            return (int)$d["percentage"]; 
        }
    }
    return 0;
}
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
        
        /* Product Cards */
        .products-container {
            padding: 0.5rem 0;
        }
        
        .product-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .product-card:hover::before {
            left: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: var(--border-glow);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 80px rgba(255, 107, 53, 0.2);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            filter: brightness(0.9);
        }
        
        .product-card:hover .product-image {
            filter: brightness(1);
            transform: scale(1.05);
        }
        
        .product-badge {
            position: absolute;
            z-index: 999;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(45deg, var(--accent), #ff6b9d);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            animation: bounce 2s infinite;
            max-width: 80px;
            text-align: center;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .product-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--light);
        }
        
        .product-category {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .price-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .original-price {
            text-decoration: line-through;
            color: rgba(255, 255, 255, 0.5);
            font-size: 1rem;
        }
        
        .discount-price {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stock-badge {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .out-of-stock-badge {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .add-to-cart-btn {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 25px;
            padding: 0.8rem 1.5rem;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .add-to-cart-btn:disabled {
            background: rgba(108, 117, 125, 0.5);
            cursor: not-allowed;
            transform: none !important;
        }
        
        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .add-to-cart-btn:not(:disabled):hover::before {
            left: 100%;
        }
        
        .add-to-cart-btn:not(:disabled):hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }
        
        .quantity-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            color: white;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .quantity-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.3);
            color: white;
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
            background: rgba(10, 10, 20, 0.8);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
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
        .list-view .product-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            text-align: left;
        }
        
        .list-view .product-image {
            width: 200px;
            height: 200px;
            margin-right: 2rem;
            margin-bottom: 0;
        }
        
        .list-view .product-details {
            flex: 1;
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
            
            .list-view .product-card {
                flex-direction: column;
                text-align: center;
            }
            
            .list-view .product-image {
                width: 100%;
                margin-right: 0;
                margin-bottom: 1rem;
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
                   placeholder="Search categories..." 
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
                    <div class="row products-container stagger-animation">
                        <?php foreach ($categoryProducts as $index => $p): 
                            $price = (float)$p['cost'];
                            $percent = getDiscount($price, $discounts);
                            $discountAmount = ($price * $percent) / 100;
                            $finalPrice = $price - $discountAmount;
                            $stock_level = (int)$p['stock_status'];
                            $is_out_of_stock = $stock_level <= 0;
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 product-item-wrapper" style="animation-delay: <?= $index * 0.1 ?>s">
                            <div class="product-card h-100">
                                <?php if ($percent > 0): ?>
                                    <div class="product-badge"><?= $percent ?>% OFF</div>
                                <?php endif; ?>
                                
                                <img src="<?= htmlspecialchars($p['product_image_path']) ?>" 
                                     class="product-image product-image-click"
                                     alt="<?= htmlspecialchars($p['product_name']) ?>"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imageModal"
                                     data-image-url="<?= htmlspecialchars($p['product_image_path']) ?>">
                                
                                <div class="product-details">
                                    <h5 class="product-title"><?= htmlspecialchars($p['product_name']) ?></h5>
                                    <p class="product-category">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($p['category_name']) ?>
                                    </p>
                                    
                                    <div class="price-container">
                                        <?php if ($percent > 0): ?>
                                            <span class="original-price">₹<?= number_format($price, 2) ?></span>
                                            <span class="discount-price">₹<?= number_format($finalPrice, 2) ?></span>
                                        <?php else: ?>
                                            <span class="discount-price">₹<?= number_format($price, 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <?php if ($is_out_of_stock): ?>
                                            <span class="out-of-stock-badge">
                                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-badge">
                                                <i class="fas fa-box me-1"></i><?= $stock_level ?> in stock
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$is_out_of_stock): ?>
                                    <div class="quantity-section mb-3">
                                        <input type="number" 
                                               value="1" 
                                               min="1" 
                                               max="<?= $stock_level ?>" 
                                               class="form-control quantity-input"
                                               id="qty<?= $p['product_id'] ?>"
                                               <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                    </div>
                                    
                                    <button class="btn add-to-cart-btn add-btn"
                                            data-id="<?= $p['product_id'] ?>"
                                            <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                        <i class="fas fa-plus me-2"></i>
                                        Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn add-to-cart-btn" disabled>
                                        <i class="fas fa-times me-2"></i>
                                        Out of Stock
                                    </button>
                                    <?php endif; ?>
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
                    <p class="mb-0 text-muted">Lighting up your celebrations since 2024</p>
                </div>
                <div class="col-md-6 text-md-end text-center mt-3 mt-md-0">
                    <p class="mb-0 text-muted">
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
                    showNotification(`Only ${maxStock} items available in stock!`, 'error');
                    qtyInput.val(maxStock);
                    return;
                }
                
                if (qty < 1) {
                    showNotification('Please enter a valid quantity!', 'error');
                    qtyInput.val(1);
                    return;
                }
                
                // Add loading state
                $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Adding...');
                $btn.prop('disabled', true);
                
                $.post("cart_action.php", {
                    action: "add",
                    product_id: pid,
                    quantity: qty
                }, function(response) {
                    // Reset button state
                    $btn.html('<i class="fas fa-plus me-2"></i>Add to Cart');
                    $btn.prop('disabled', false);
                    
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            showNotification('Error: Invalid server response', 'error');
                            return;
                        }
                    }
                    
                    if (response && response.status === 'success') {
                        showNotification(response.message, 'success');
                        if (response.new_cart_count !== undefined) {
                            $(".badge").text(response.new_cart_count);
                        }
                    } else {
                        showNotification(response?.message || 'Error adding to cart', 'error');
                    }
                }, "json").fail(function() {
                    $btn.html('<i class="fas fa-plus me-2"></i>Add to Cart');
                    $btn.prop('disabled', false);
                    showNotification('Server error. Please try again.', 'error');
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
                
                if (view === 'list') {
                    $container.addClass('list-view');
                    $items.removeClass('col-xl-3 col-lg-4 col-md-6').addClass('col-12');
                } else {
                    $container.removeClass('list-view');
                    $items.removeClass('col-12').addClass('col-xl-3 col-lg-4 col-md-6');
                }
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
                categorySearch.val(selectedCategory);
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
            
            function filterCategories() {
                const searchTerm = categorySearch.val().toLowerCase();
                
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
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`);
                
                $('body').append(notification);
                setTimeout(() => notification.alert('close'), 5000);
            }
            
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