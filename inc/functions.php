<?php
/**
 * Shared helpers used by the shop and the admin panel.
 */

/** Escape a value for safe output in HTML. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Length of a text in characters (works even if the mbstring extension is off). */
function text_length(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen(utf8_decode_safe($text));
}

/** Counts each UTF-8 character as one byte, so strlen() gives the character count. */
function utf8_decode_safe(string $text): string
{
    return preg_replace('/[\x{80}-\x{10FFFF}]/u', '?', $text) ?? $text;
}

/** Format an amount as Indian rupees, e.g. ₹1,250.00 */
function rupees($amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

/** Flat packing charge, read from inc/config.php. */
function packing_cost(): float
{
    global $config;
    return (float)($config['packing_cost'] ?? 20.00);
}

/** All discount slabs, e.g. "0-60" => 7%. */
function get_discounts(PDO $pdo): array
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = $pdo->query('SELECT * FROM discount ORDER BY discount_id ASC')->fetchAll();
        } catch (PDOException $e) {
            error_log('Discount fetch failed: ' . $e->getMessage());
            $cache = [];
        }
    }
    return $cache;
}

/** Discount percentage for a product price, based on the discount slabs. */
function discount_percent(float $price, array $discounts): int
{
    foreach ($discounts as $d) {
        $parts = array_map('trim', explode('-', (string)$d['discount_range']));
        if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            continue;
        }
        if ($price >= (float)$parts[0] && $price <= (float)$parts[1]) {
            return (int)$d['percentage'];
        }
    }
    return 0;
}

/** Price after discount, rounded to paise. This is the price the customer pays. */
function selling_price(float $price, array $discounts): float
{
    $percent = discount_percent($price, $discounts);
    return round($price - ($price * $percent / 100), 2);
}

/** Total number of items (sum of quantities) in this visitor's cart. */
function cart_count(PDO $pdo): int
{
    try {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?');
        $stmt->execute([session_id()]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Cart count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Basic protection against cross-site form submissions (CSRF):
 * a form submission must come from a page on this same site.
 */
function require_same_origin(): void
{
    $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($source === '' || $source === 'null') {
        return; // some browsers/privacy tools strip both headers
    }
    $host = parse_url($source, PHP_URL_HOST);
    $port = parse_url($source, PHP_URL_PORT);
    $sourceHost = $host . ($port ? ':' . $port : '');
    $siteHost = $_SERVER['HTTP_HOST'] ?? '';
    if (strcasecmp($sourceHost, $siteHost) !== 0) {
        http_response_code(403);
        die('Request blocked: it did not come from this site.');
    }
}

/** Order statuses, in the order an order moves through them. */
const ORDER_STATUSES = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

/** Bootstrap badge class for an order status. */
function status_badge_class(?string $status): string
{
    switch ($status) {
        case 'Processing': return 'bg-info text-dark';
        case 'Shipped':    return 'bg-primary';
        case 'Delivered':  return 'bg-success';
        case 'Cancelled':  return 'bg-danger';
        default:           return 'bg-warning text-dark';
    }
}

/** Font Awesome icon for an order status. */
function status_icon(?string $status): string
{
    switch ($status) {
        case 'Processing': return 'fas fa-cog';
        case 'Shipped':    return 'fas fa-shipping-fast';
        case 'Delivered':  return 'fas fa-check-circle';
        case 'Cancelled':  return 'fas fa-times-circle';
        default:           return 'fas fa-clock';
    }
}

/** An order can be cancelled only before it is shipped. */
function can_cancel(?string $status): bool
{
    return in_array($status, ['Pending', 'Processing'], true);
}

/**
 * Cancel an order and put its items back in stock.
 * Returns true if the order was cancelled.
 * Pass $sessionId so a customer can only cancel their own order.
 */
function cancel_order(PDO $pdo, int $orderId, ?string $sessionId = null): bool
{
    $pdo->beginTransaction();
    try {
        $sql = 'SELECT order_status FROM orders WHERE order_id = ?';
        $params = [$orderId];
        if ($sessionId !== null) {
            $sql .= ' AND session_id = ?';
            $params[] = $sessionId;
        }
        $stmt = $pdo->prepare($sql . ' FOR UPDATE');
        $stmt->execute($params);
        $status = $stmt->fetchColumn();

        if ($status === false || !can_cancel($status)) {
            $pdo->rollBack();
            return false;
        }

        $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $items->execute([$orderId]);
        $restock = $pdo->prepare('UPDATE product SET stock_status = stock_status + ? WHERE product_id = ?');
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id'] !== null) {
                $restock->execute([(int)$item['quantity'], (int)$item['product_id']]);
            }
        }

        $pdo->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?")->execute([$orderId]);
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Order cancellation failed: ' . $e->getMessage());
        return false;
    }
}
