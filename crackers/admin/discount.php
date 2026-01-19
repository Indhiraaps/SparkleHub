<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require '../inc/db.php';

// Initialize message variables
$message = '';
$error = '';

// --- 1. HANDLE ADD NEW DISCOUNT ---
if (isset($_POST["add_discount"])) {
    $range = trim($_POST["new_discount_range"]);
    $percent = (int)$_POST["new_percentage"];

    if (!empty($range) && $percent > 0 && $percent <= 100) {
        // Check for duplicate discount ranges
        $check = $pdo->prepare("SELECT COUNT(*) FROM discount WHERE discount_range = ?");
        $check->execute([$range]);
        
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Discount range already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO discount (discount_range, percentage) VALUES (?, ?)");
            if ($stmt->execute([$range, $percent])) {
                $_SESSION['message'] = "Discount range added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add discount range!";
            }
        }
    } else {
        $_SESSION['error'] = "Please enter valid discount range and percentage (1-100)!";
    }
    header("Location: discount.php");
    exit;
}

// --- 2. HANDLE UPDATE DISCOUNT ---
if (isset($_POST["update_discount"])) {
    $id = (int)$_POST["id"];
    $range = trim($_POST["discount_range"]);
    $percent = (int)$_POST["percentage"];

    if ($id > 0 && !empty($range) && $percent > 0 && $percent <= 100) {
        // Check for duplicate discount ranges (excluding current one)
        $check = $pdo->prepare("SELECT COUNT(*) FROM discount WHERE discount_range = ? AND discount_id != ?");
        $check->execute([$range, $id]);
        
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Discount range already exists!";
        } else {
            $stmt = $pdo->prepare("UPDATE discount SET discount_range=?, percentage=? WHERE discount_id=?");
            if ($stmt->execute([$range, $percent, $id])) {
                $_SESSION['message'] = "Discount updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update discount!";
            }
        }
    } else {
        $_SESSION['error'] = "Please enter valid discount range and percentage!";
    }
    header("Location: discount.php");
    exit;
}

// --- 3. HANDLE DELETE DISCOUNT ---
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM discount WHERE discount_id=?");
        if ($stmt->execute([$id])) {
            $_SESSION['message'] = "Discount deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete discount!";
        }
    } else {
        $_SESSION['error'] = "Invalid discount ID!";
    }
    header("Location: discount.php");
    exit;
}

// --- 4. FETCH DISCOUNTS (for display) ---
$discounts = $pdo->query("SELECT * FROM discount ORDER BY discount_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get messages from session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discounts</title>
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
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            transition: all 0.2s ease;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
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
        
        /* Discount Badge */
        .discount-badge {
            background: linear-gradient(135deg, var(--amazon-orange), #ff6600);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Badge fix */
        .badge.bg-amazon {
            background-color: var(--amazon-orange) !important;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
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
                        <a class="nav-link" href="categories.php"><i class="fas fa-folder-open me-1"></i> Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="discount.php"><i class="fas fa-percent me-1"></i> Discounts</a>
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
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Manage Discounts</h1>
                    <p class="page-subtitle">Set up special offers and discount ranges for your products</p>
                </div>
                <a href="index.php" class="btn btn-outline-amazon">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Add Discount Card -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-plus-circle me-2"></i> Add New Discount Range
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="new_discount_range" class="form-label">Price Range</label>
                        <input type="text" name="new_discount_range" id="new_discount_range" class="form-control" required placeholder="e.g., 201-500">
                        <div class="form-text">Enter the price range in format: min-max</div>
                    </div>
                    <div class="col-md-4">
                        <label for="new_percentage" class="form-label">Discount Percentage</label>
                        <div class="input-group">
                            <input type="number" name="new_percentage" id="new_percentage" class="form-control" min="1" max="100" required placeholder="e.g., 15">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button name="add_discount" class="btn btn-success w-100">
                            <i class="fas fa-plus me-1"></i> Add Discount
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Discounts Table Card -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-percent me-2"></i> Existing Discount Ranges
                    <span class="badge bg-amazon ms-2"><?= count($discounts) ?></span>
                </div>
                <div class="text-muted small">
                    Edit ranges and percentages as needed
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($discounts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-percent fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No discount ranges found</h5>
                        <p class="text-muted">Start by adding your first discount range above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">ID</th>
                                    <th style="width: 35%;">Price Range</th>
                                    <th style="width: 25%;">Discount</th>
                                    <th style="width: 30%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discounts as $d): ?>
                                <tr class="fade-in">
                                    <form method="POST">
                                        <td class="fw-bold"><?= htmlspecialchars($d['discount_id']) ?></td>
                                        <td>
                                            <input name="discount_range" class="form-control" value="<?= htmlspecialchars($d['discount_range']) ?>" required onkeypress="return event.keyCode != 13;">
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" name="percentage" class="form-control" value="<?= htmlspecialchars($d['percentage']) ?>" min="1" max="100" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($d['discount_id']) ?>">
                                                
                                                <button name="update_discount" class="btn btn-amazon btn-sm">
                                                    <i class="fas fa-save me-1"></i> Save
                                                </button>
                                                
                                                <a href="discount.php?delete=<?= $d['discount_id'] ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this discount range?')"
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

        <!-- Info Card -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> How Discounts Work
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Best Practices</h6>
                        <ul class="small text-muted">
                            <li>Create logical price ranges that don't overlap</li>
                            <li>Higher price ranges should have better discounts</li>
                            <li>Test discounts before applying to all products</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Important Notes</h6>
                        <ul class="small text-muted">
                            <li>Changes take effect immediately</li>
                            <li>Discounts apply automatically based on price ranges</li>
                            <li>Delete unused ranges to keep things organized</li>
                        </ul>
                    </div>
                </div>
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
            
            // Prevent form submission on Enter key in input fields
            document.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.keyCode === 13) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });
    </script>
</body>
</html>