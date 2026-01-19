<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

// NOTE: The path to db.php assumes this file is in 'admin/' and db.php is in 'inc/'
require '../inc/db.php'; 

// --- 1. HANDLE ADD CATEGORY ---
if (isset($_POST["add_category"])) {
    $name = trim($_POST["category_name"]);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO category (category_name) VALUES (?)");
        $stmt->execute([$name]);
    }
    header("Location: categories.php");
    exit;
}

// --- 2. HANDLE EDIT/UPDATE CATEGORY ---
if (isset($_POST["update_category"])) {
    $id = $_POST["category_id"];
    $name = trim($_POST["category_name_edit"]); // Use a different name for the edit input
    
    // Check if the name is not empty before updating
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE category SET category_name=? WHERE category_id=?");
        $stmt->execute([$name, $id]);
    }
    header("Location: categories.php");
    exit;
}

// --- 3. HANDLE DELETE CATEGORY ---
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    // Optional: Add logic to prevent deleting a category if products are linked to it
    $pdo->prepare("DELETE FROM category WHERE category_id=?")->execute([$id]);
    header("Location: categories.php");
    exit;
}

// --- 4. FETCH ALL CATEGORIES ---
$categories = $pdo->query("SELECT * FROM category ORDER BY category_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
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
        
        /* Form Styling */
        .form-label {
            font-weight: 500;
            color: var(--amazon-text);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid #a6a6a6;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--amazon-orange);
            box-shadow: 0 0 0 3px rgba(255, 153, 0, 0.2);
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
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: all 0.2s ease;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
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
        
        /* Action Buttons in Table */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .table-responsive {
                font-size: 0.875rem;
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
                        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-basket me-1"></i> Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="fas fa-boxes me-1"></i> Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="categories.php"><i class="fas fa-folder-open me-1"></i> Categories</a>
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
                    <h1 class="page-title">Manage Categories</h1>
                    <p class="page-subtitle">Organize your product categories for better navigation</p>
                </div>
                <a href="index.php" class="btn btn-outline-amazon">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Add Category Card -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-plus-circle me-2"></i> Add New Category
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input required name="category_name" id="category_name" class="form-control" placeholder="Enter category name">
                        </div>
                        <div class="col-md-4">
                            <button name="add_category" class="btn btn-amazon w-100">
                                <i class="fas fa-plus me-1"></i> Add Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Categories Table Card -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-2"></i> All Categories
                    <span class="badge bg-amazon ms-2" style="background-color: var(--amazon-orange);"><?= count($categories) ?></span>
                </div>
                <div class="text-muted small">
                    Click Save to update a category name
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No categories found</h5>
                        <p class="text-muted">Start by adding your first category above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">ID</th>
                                    <th style="width: 50%;">Category Name</th>
                                    <th style="width: 40%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                <tr>
                                    <form method="POST">
                                        <td class="fw-bold"><?= $c['category_id'] ?></td>
                                        
                                        <td>
                                            <input name="category_name_edit" 
                                                   class="form-control" 
                                                   value="<?= htmlspecialchars($c['category_name']) ?>" 
                                                   required>
                                        </td>
                                        
                                        <td>
                                            <div class="action-buttons">
                                                <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                                                
                                                <button name="update_category" class="btn btn-amazon btn-sm">
                                                    <i class="fas fa-save me-1"></i> Save
                                                </button>
                                                
                                                <a href="categories.php?delete=<?= $c['category_id'] ?>" 
                                                   onclick="return confirm('WARNING: Are you sure you want to delete the category: <?= htmlspecialchars($c['category_name']) ?>?')"
                                                   class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </form>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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