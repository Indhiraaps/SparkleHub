<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require '../inc/db.php';

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM category")->fetchAll(PDO::FETCH_ASSOC);

// --- ADD PRODUCT ---
if (isset($_POST["add_product"])) {
    
    $name = $_POST["product_name"];
    $cat  = $_POST["category"];
    $cost = $_POST["cost"];
    // Stock status is now actual quantity (not just 1 or 0)
    $stock = (int)$_POST["stock_quantity"]; 

    // Image upload
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $imgName = $_FILES["product_image"]["name"];
        $imgTmp = $_FILES["product_image"]["tmp_name"];
        $path = "../images/" . $imgName;
        move_uploaded_file($imgTmp, $path);
        $db_image_path = "images/" . $imgName;
    } else {
        $db_image_path = 'images/no-image.jpg'; // Default or error image
    }

    $stmt = $pdo->prepare("INSERT INTO product (product_name, category, product_image_path, cost, stock_status)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $cat, $db_image_path, $cost, $stock]);

    header("Location: products.php");
    exit;
}

// --- UPDATE PRODUCT ---
if (isset($_POST["update_product"])) {
    $id = $_POST["product_id"];
    $name = $_POST["product_name"];
    $cat  = $_POST["category"];
    $cost = $_POST["cost"];
    $stock = (int)$_POST["stock_quantity"];
    $current_image = $_POST["current_image_path"];

    // Handle new image upload
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $imgName = $_FILES["product_image"]["name"];
        $imgTmp = $_FILES["product_image"]["tmp_name"];
        $path = "../images/" . $imgName;
        move_uploaded_file($imgTmp, $path);
        $db_image_path = "images/" . $imgName;
    } else {
        $db_image_path = $current_image; // Keep existing image path
    }

    $stmt = $pdo->prepare("UPDATE product SET product_name=?, category=?, product_image_path=?, cost=?, stock_status=? WHERE product_id=?");
    $stmt->execute([$name, $cat, $db_image_path, $cost, $stock, $id]);

    header("Location: products.php");
    exit;
}

// --- DELETE PRODUCT ---
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $pdo->prepare("DELETE FROM product WHERE product_id=?")->execute([$id]);
    header("Location: products.php");
    exit;
}

// Fetch all products
$products = $pdo->query("
    SELECT p.*, c.category_name 
    FROM product p 
    JOIN category c ON p.category = c.category_id
    ORDER BY product_id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
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
        
        /* Product Image */
        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        /* Stock Badges */
        .stock-available {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stock-out {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stock-quantity {
            font-weight: 600;
            font-size: 0.9rem;
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
            
            .product-image {
                width: 50px;
                height: 50px;
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
        
        /* Price styling */
        .price {
            font-weight: 600;
            color: #b12704;
        }
        
        /* Modal styling */
        .modal-header {
            background-color: var(--amazon-dark);
            color: white;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        /* Badge fix */
        .badge.bg-amazon {
            background-color: var(--amazon-orange) !important;
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
                        <a class="nav-link active" href="products.php"><i class="fas fa-boxes me-1"></i> Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php"><i class="fas fa-folder-open me-1"></i> Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="discount.php"><i class="fas fa-percent me-1"></i> Discounts</a>
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
                    <h1 class="page-title">Manage Products</h1>
                    <p class="page-subtitle">Add, edit, and manage your product inventory with actual stock quantities</p>
                </div>
                <a href="index.php" class="btn btn-outline-amazon">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Add Product Card -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-plus-circle me-2"></i> Add New Product
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input required name="product_name" class="form-control" placeholder="Enter product name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select required name="category" class="form-control">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['category_id'] ?>"><?= $c['category_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input required type="number" name="cost" step="0.01" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input required type="file" name="product_image" class="form-control">
                                <div class="form-text">Recommended size: 300x300 pixels</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input required type="number" name="stock_quantity" min="0" class="form-control" value="10" placeholder="Enter quantity">
                                <div class="form-text">Enter the actual number of items in stock</div>
                            </div>

                            <div class="mb-3 text-end">
                                <button name="add_product" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Add Product
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table Card -->
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-boxes me-2"></i> All Products
                    <span class="badge bg-amazon ms-2"><?= count($products) ?></span>
                </div>
                <div class="text-muted small">
                    Click Edit to modify product details
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No products found</h5>
                        <p class="text-muted">Start by adding your first product above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Image</th>
                                    <th style="width: 25%;">Product Name</th>
                                    <th style="width: 15%;">Category</th>
                                    <th style="width: 15%;">Cost</th>
                                    <th style="width: 15%;">Stock Status</th>
                                    <th style="width: 20%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): 
                                    $stock_quantity = (int)$p['stock_status'];
                                    $is_out_of_stock = $stock_quantity <= 0;
                                    $is_low_stock = $stock_quantity > 0 && $stock_quantity <= 5;
                                ?>
                                <tr class="fade-in">
                                    <td>
                                        <img src="../<?= htmlspecialchars($p['product_image_path']) ?>" class="product-image" alt="<?= htmlspecialchars($p['product_name']) ?>">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td class="price">₹<?= number_format($p['cost'], 2) ?></td>
                                    <td>
                                        <?php if ($is_out_of_stock): ?>
                                            <span class="stock-out">
                                                <i class="fas fa-times me-1"></i> Out of Stock
                                            </span>
                                        <?php elseif ($is_low_stock): ?>
                                            <span class="stock-low">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Low Stock (<?= $stock_quantity ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-available">
                                                <i class="fas fa-check me-1"></i> In Stock (<?= $stock_quantity ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" 
                                                    class="btn btn-amazon btn-sm edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-id="<?= $p['product_id'] ?>"
                                                    data-name="<?= htmlspecialchars($p['product_name']) ?>"
                                                    data-cat="<?= $p['category'] ?>"
                                                    data-cost="<?= $p['cost'] ?>"
                                                    data-stock="<?= $p['stock_status'] ?>"
                                                    data-image="<?= htmlspecialchars($p['product_image_path']) ?>">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            
                                            <a href="products.php?delete=<?= $p['product_id'] ?>"
                                                onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($p['product_name']) ?>?')"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i>Edit Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" enctype="multipart/form-data">
              <div class="modal-body">
                  <input type="hidden" name="product_id" id="edit_product_id">
                  <input type="hidden" name="current_image_path" id="edit_current_image_path">

                  <div class="row">
                      <div class="col-md-6">
                          <div class="mb-3">
                              <label class="form-label">Product Name</label>
                              <input required name="product_name" id="edit_product_name" class="form-control">
                          </div>

                          <div class="mb-3">
                              <label class="form-label">Category</label>
                              <select required name="category" id="edit_category" class="form-control">
                                  <?php foreach ($categories as $c): ?>
                                      <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>

                          <div class="mb-3">
                              <label class="form-label">Cost</label>
                              <div class="input-group">
                                  <span class="input-group-text">₹</span>
                                  <input required type="number" name="cost" id="edit_cost" step="0.01" class="form-control">
                              </div>
                          </div>
                      </div>
                      
                      <div class="col-md-6">
                          <div class="mb-3">
                              <label class="form-label">Stock Quantity</label>
                              <input required type="number" name="stock_quantity" id="edit_stock_quantity" min="0" class="form-control">
                              <div class="form-text">Current stock quantity</div>
                          </div>

                          <div class="mb-3">
                              <label class="form-label">Current Image</label><br>
                              <img id="edit_current_image" src="" class="product-image mb-2">
                              <label class="form-label">Change Image (Optional)</label>
                              <input type="file" name="product_image" class="form-control">
                              <div class="form-text">Leave empty to keep current image</div>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-amazon" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="update_product" class="btn btn-amazon">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
              </div>
          </form>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        // Populate the modal fields when the Edit button is clicked
        $('.edit-btn').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var cat = $(this).data('cat');
            var cost = $(this).data('cost');
            var stock = $(this).data('stock');
            var image_path = $(this).data('image');

            $('#edit_product_id').val(id);
            $('#edit_product_name').val(name);
            $('#edit_category').val(cat);
            $('#edit_cost').val(cost);
            $('#edit_stock_quantity').val(stock);
            
            // Update image path fields
            $('#edit_current_image').attr('src', '../' + image_path);
            $('#edit_current_image_path').val(image_path);
        });

        // Add animation to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
            row.classList.add('fade-in');
        });
    });
    </script>
</body>
</html>