<?php
session_start();
require 'inc/db.php';

header('Content-Type: application/json');

if (!isset($_POST['action']) || !isset($_POST['product_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];
$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$session_id = session_id();

try {
    switch($action) {
        case 'add':
            // Get product information including price
            $product_stmt = $pdo->prepare("SELECT p.stock_status, p.product_name, p.cost, c.category_name 
                                         FROM product p 
                                         LEFT JOIN category c ON p.category = c.category_id 
                                         WHERE p.product_id = ?");
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo json_encode(['status' => 'error', 'message' => 'Product not found']);
                exit;
            }
            
            $total_stock = (int)$product['stock_status'];
            $product_name = $product['product_name'];
            $product_cost = (float)$product['cost'];
            $category_name = $product['category_name'];
            
            // Check if product is in stock
            if ($total_stock <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Product is out of stock']);
                exit;
            }
            
            // Check current cart quantity for this product
            $cart_stmt = $pdo->prepare("SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $cart_stmt->execute([$session_id, $product_id]);
            $cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC);
            
            $current_cart_quantity = $cart_item ? (int)$cart_item['quantity'] : 0;
            $requested_total = $current_cart_quantity + $quantity;
            
            // Check if requested quantity exceeds available stock
            if ($requested_total > $total_stock) {
                $available_to_add = $total_stock - $current_cart_quantity;
                
                if ($available_to_add <= 0) {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => "Stock limit reached. You have {$current_cart_quantity} in cart. No more available (Total stock: {$total_stock})."
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => "Only {$available_to_add} more available. You already have {$current_cart_quantity} in cart (Total stock: {$total_stock})."
                    ]);
                }
                exit;
            }
            
            // Calculate total price for this addition
            $subtotal = $product_cost * $quantity;
            
            // Add or update cart item
            if ($cart_item) {
                // Update existing cart item
                $update_stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ?, subtotal = subtotal + ? WHERE session_id = ? AND product_id = ?");
                $update_stmt->execute([$quantity, $subtotal, $session_id, $product_id]);
            } else {
                // Insert new cart item with price information
                $insert_stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, product_name, category, cost, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$session_id, $product_id, $product_name, $category_name, $product_cost, $quantity, $subtotal]);
            }
            
            // Get updated cart count and total
            $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total_quantity, SUM(subtotal) as cart_total FROM cart WHERE session_id = ?");
            $count_stmt->execute([$session_id]);
            $cart_data = $count_stmt->fetch(PDO::FETCH_ASSOC);
            
            $cart_count = $cart_data['total_quantity'] ?? 0;
            $cart_total = $cart_data['cart_total'] ?? 0;
            
            echo json_encode([
                'status' => 'success', 
                'message' => "{$quantity} x {$product_name} added to cart!",
                'new_cart_count' => $cart_count,
                'cart_total' => number_format($cart_total, 2)
            ]);
            break;
            
        case 'update':
            $cart_stmt = $pdo->prepare("SELECT quantity, cost FROM cart WHERE session_id = ? AND product_id = ?");
            $cart_stmt->execute([$session_id, $product_id]);
            $cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cart_item) {
                echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
                exit;
            }
            
            // Get product stock
            $product_stmt = $pdo->prepare("SELECT stock_status FROM product WHERE product_id = ?");
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_stock = (int)$product['stock_status'];
            
            // Validate new quantity against stock
            if ($quantity > $total_stock) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => "Cannot update. Only {$total_stock} items available in stock."
                ]);
                exit;
            }
            
            // Calculate new subtotal
            $product_cost = (float)$cart_item['cost'];
            $new_subtotal = $product_cost * $quantity;
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or less
                $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
                $delete_stmt->execute([$session_id, $product_id]);
                $message = "Item removed from cart";
            } else {
                // Update quantity and subtotal
                $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ?, subtotal = ? WHERE session_id = ? AND product_id = ?");
                $update_stmt->execute([$quantity, $new_subtotal, $session_id, $product_id]);
                $message = "Cart updated successfully";
            }
            
            // Get updated cart count and total
            $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total_quantity, SUM(subtotal) as cart_total FROM cart WHERE session_id = ?");
            $count_stmt->execute([$session_id]);
            $cart_data = $count_stmt->fetch(PDO::FETCH_ASSOC);
            
            $cart_count = $cart_data['total_quantity'] ?? 0;
            $cart_total = $cart_data['cart_total'] ?? 0;
            
            echo json_encode([
                'status' => 'success', 
                'message' => $message,
                'new_cart_count' => $cart_count,
                'cart_total' => number_format($cart_total, 2),
                'item_subtotal' => number_format($new_subtotal, 2)
            ]);
            break;
            
        case 'remove':
            $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
            $delete_stmt->execute([$session_id, $product_id]);
            
            // Get updated cart count and total
            $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total_quantity, SUM(subtotal) as cart_total FROM cart WHERE session_id = ?");
            $count_stmt->execute([$session_id]);
            $cart_data = $count_stmt->fetch(PDO::FETCH_ASSOC);
            
            $cart_count = $cart_data['total_quantity'] ?? 0;
            $cart_total = $cart_data['cart_total'] ?? 0;
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Item removed from cart',
                'new_cart_count' => $cart_count,
                'cart_total' => number_format($cart_total, 2)
            ]);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Cart action failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}