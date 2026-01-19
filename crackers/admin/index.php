<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require '../inc/db.php';

// Fetch data
try {
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
} catch (PDOException $e) {
    // Handle database connection or query error gracefully
    die("Database error while fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--amazon-orange);
        }
        
        .welcome-title {
            font-weight: 600;
            color: var(--amazon-text);
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            color: #565959;
            font-size: 1rem;
        }
        
        /* Stats Cards - Amazon Style */
        .stats-row {
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
            position: relative;
            padding: 1.5rem;
            height: 100%;
            border-top: 4px solid var(--amazon-orange);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--amazon-blue);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #565959;
            font-weight: 500;
        }
        
        .text-product {
            color: var(--amazon-blue);
        }
        
        .text-order {
            color: var(--amazon-orange);
        }
        
        /* Management Section - Amazon Grid Style */
        .section-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--amazon-text);
            position: relative;
            padding-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--amazon-orange);
            border-radius: 3px;
        }
        
        .manage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .manage-card {
            border: 1px solid #d5dbdb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
            height: 100%;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .manage-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-3px);
            border-color: var(--amazon-orange);
        }
        
        .manage-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--amazon-orange);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .manage-card:hover::before {
            transform: scaleX(1);
        }
        
        .manage-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--amazon-blue);
        }
        
        .manage-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--amazon-text);
            font-size: 1.2rem;
        }
        
        .manage-description {
            color: #565959;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .manage-link {
            background-color: var(--amazon-orange);
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border: 1px solid var(--amazon-orange);
        }
        
        .manage-link:hover {
            background-color: #e68900;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .manage-link i {
            margin-left: 8px;
            transition: transform 0.2s ease;
        }
        
        .manage-link:hover i {
            transform: translateX(3px);
        }
        
        /* Footer - Amazon Style */
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
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-card {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .card-1 { animation-delay: 0.1s; }
        .card-2 { animation-delay: 0.2s; }
        .card-3 { animation-delay: 0.3s; }
        .card-4 { animation-delay: 0.4s; }
        .card-5 { animation-delay: 0.5s; }
        .card-6 { animation-delay: 0.6s; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-value {
                font-size: 2rem;
            }
            
            .stat-icon {
                font-size: 1.8rem;
            }
            
            .manage-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar-nav {
                margin-top: 1rem;
            }
        }
        
        /* Amazon-style button */
        .btn-amazon {
            background-color: var(--amazon-orange);
            color: white;
            border: 1px solid var(--amazon-orange);
            font-weight: 500;
        }
        
        .btn-amazon:hover {
            background-color: #e68900;
            border-color: #e68900;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation - Amazon Style -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-store amazon-logo"></i>Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-basket me-1"></i> Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="fas fa-boxes me-1"></i> Products</a>
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, Admin! <i class="fas fa-smile"></i></h1>
            <p class="welcome-subtitle">Manage your store efficiently with our admin tools.</p>
        </div>

        <!-- Stats Section -->
        <div class="row stats-row">
            <div class="col-md-6 mb-4 animate-card card-1">
                <div class="stat-card text-center">
                    <i class="fas fa-boxes stat-icon"></i>
                    <div class="stat-value text-product"><?= $totalProducts ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4 animate-card card-2">
                <div class="stat-card text-center">
                    <i class="fas fa-shopping-bag stat-icon"></i>
                    <div class="stat-value text-order"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
        </div>

        <!-- Management Section -->
        <div class="row">
            <div class="col-12">
                <h3 class="section-title">Quick Actions</h3>
            </div>
            
            <div class="col-12">
                <div class="manage-grid">
                    <div class="manage-card animate-card card-3">
                        <i class="fas fa-folder-open manage-icon"></i>
                        <h5 class="manage-title">Categories</h5>
                        <p class="manage-description">Organize and manage your product categories for better navigation.</p>
                        <a href="categories.php" class="manage-link">
                            Manage Categories <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="manage-card animate-card card-4">
                        <i class="fas fa-boxes manage-icon"></i>
                        <h5 class="manage-title">Products</h5>
                        <p class="manage-description">Add new products, update existing ones, or remove discontinued items.</p>
                        <a href="products.php" class="manage-link">
                            Manage Products <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="manage-card animate-card card-5">
                        <i class="fas fa-percent manage-icon"></i>
                        <h5 class="manage-title">Discounts</h5>
                        <p class="manage-description">Create and manage promotional offers and discount codes.</p>
                        <a href="discount.php" class="manage-link">
                            Set Discounts <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="manage-card animate-card card-6">
                        <i class="fas fa-shopping-basket manage-icon"></i>
                        <h5 class="manage-title">Orders</h5>
                        <p class="manage-description">View, process, and track customer orders and shipments.</p>
                        <a href="orders.php" class="manage-link">
                            View Orders <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer - Amazon Style -->
    <footer class="footer">
        <div class="container">
            <div class="footer-links">
                <a href="#">Home</a>
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
        // Simple animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.animate-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>