<?php
/**
 * AJAX endpoint for the cart: add / update / remove.
 * Always responds with JSON. Prices are calculated here on the server
 * (MRP minus the discount slab), so the browser can't change them.
 */
session_start();
require 'inc/db.php';

header('Content-Type: application/json');

function respond(array $data): void
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'], $_POST['product_id'])) {
    respond(['status' => 'error', 'message' => 'Invalid request']);
}
require_same_origin();

$action     = $_POST['action'];
$product_id = (int)$_POST['product_id'];
$quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$session_id = session_id();

/** Current cart totals for this visitor. */
function cart_totals(PDO $pdo, string $session_id): array
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS qty, COALESCE(SUM(subtotal), 0) AS total FROM cart WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $row = $stmt->fetch();
    return ['new_cart_count' => (int)$row['qty'], 'cart_total' => number_format((float)$row['total'], 2)];
}

try {
    // Product details (also confirms the product exists)
    $product_stmt = $pdo->prepare("SELECT p.stock_status, p.product_name, p.cost, c.category_name
                                   FROM product p
                                   LEFT JOIN category c ON p.category = c.category_id
                                   WHERE p.product_id = ?");
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch();

    $cart_stmt = $pdo->prepare("SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?");
    $cart_stmt->execute([$session_id, $product_id]);
    $cart_item = $cart_stmt->fetch();
    $in_cart = $cart_item ? (int)$cart_item['quantity'] : 0;

    switch ($action) {
        case 'add':
            if (!$product) {
                respond(['status' => 'error', 'message' => 'Product not found']);
            }
            if ($quantity < 1) {
                respond(['status' => 'error', 'message' => 'Please choose a quantity of at least 1']);
            }
            $stock = (int)$product['stock_status'];
            if ($stock <= 0) {
                respond(['status' => 'error', 'message' => 'Product is out of stock']);
            }
            if ($in_cart + $quantity > $stock) {
                $available = $stock - $in_cart;
                respond(['status' => 'error', 'message' => $available <= 0
                    ? "You already have all {$stock} available in your cart."
                    : "Only {$available} more available (you have {$in_cart} in your cart)."]);
            }

            $unit_price = selling_price((float)$product['cost'], get_discounts($pdo));
            $new_qty = $in_cart + $quantity;

            if ($cart_item) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, cost = ?, subtotal = ?, total_cost = ? WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$new_qty, $unit_price, $unit_price * $new_qty, $unit_price * $new_qty, $session_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, product_name, category, cost, quantity, subtotal, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$session_id, $product_id, $product['product_name'], $product['category_name'], $unit_price, $new_qty, $unit_price * $new_qty, $unit_price * $new_qty]);
            }

            respond(['status' => 'success', 'message' => "{$quantity} x {$product['product_name']} added to cart!"] + cart_totals($pdo, $session_id));

        case 'update':
            if (!$cart_item || !$product) {
                respond(['status' => 'error', 'message' => 'Item not found in cart']);
            }
            if ($quantity <= 0) {
                $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?")->execute([$session_id, $product_id]);
                respond(['status' => 'success', 'message' => 'Item removed from cart', 'item_subtotal' => '0.00'] + cart_totals($pdo, $session_id));
            }
            $stock = (int)$product['stock_status'];
            if ($quantity > $stock) {
                respond(['status' => 'error', 'message' => "Only {$stock} available in stock."]);
            }
            $unit_price = selling_price((float)$product['cost'], get_discounts($pdo));
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, cost = ?, subtotal = ?, total_cost = ? WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $unit_price, $unit_price * $quantity, $unit_price * $quantity, $session_id, $product_id]);
            respond(['status' => 'success', 'message' => 'Cart updated', 'item_subtotal' => number_format($unit_price * $quantity, 2)] + cart_totals($pdo, $session_id));

        case 'remove':
            $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?")->execute([$session_id, $product_id]);
            respond(['status' => 'success', 'message' => 'Item removed from cart'] + cart_totals($pdo, $session_id));

        default:
            respond(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Cart action failed: " . $e->getMessage());
    respond(['status' => 'error', 'message' => 'Database error occurred']);
}
