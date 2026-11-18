<?php
require __DIR__ . '/auth.php';

$error_message = '';

// Mark as read / unread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['is_read'])) {
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
    $stmt->execute([$_POST['is_read'] === '1' ? 1 : 0, (int)$_POST['message_id']]);
    header("Location: messages.php");
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: messages.php");
    exit;
}

try {
    $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    error_log("Messages fetch failed: " . $e->getMessage());
    $messages = [];
    $error_message = "Could not load messages. If you upgraded from the old database, run database/upgrade_existing_db.sql.";
}
$unread = count(array_filter($messages, fn($m) => !$m['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages | Sparkle Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root { --amazon-orange: #ff9900; --amazon-dark: #232f3e; }
        body { background: #fafafa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: var(--amazon-dark); }
        .btn-amazon { background: var(--amazon-orange); color: #fff; border: 1px solid var(--amazon-orange); }
        .btn-amazon:hover { background: #e68900; color: #fff; }
        .message-card { background: #fff; border: 1px solid #d5dbdb; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; }
        .message-card.unread { border-left: 4px solid var(--amazon-orange); }
        .message-body { white-space: pre-wrap; color: #333; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-store me-2" style="color: var(--amazon-orange)"></i>Admin Dashboard</a>
            <a href="logout.php" class="btn btn-amazon btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Customer Messages</h1>
                <p class="text-muted mb-0"><?= count($messages) ?> message(s), <?= $unread ?> unread</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= e($error_message) ?></div>
        <?php elseif (empty($messages)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No messages yet. Messages sent from the Contact Us page will appear here.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <div class="message-card <?= $m['is_read'] ? '' : 'unread' ?>">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <strong><?= e($m['subject']) ?></strong>
                        <?php if (!$m['is_read']): ?><span class="badge bg-warning text-dark ms-1">New</span><?php endif; ?>
                        <div class="small text-muted">
                            From <?= e($m['name']) ?> &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;
                            · <?= e(date('M j, Y g:i A', strtotime($m['created_at']))) ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <form method="POST">
                            <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                            <input type="hidden" name="is_read" value="<?= $m['is_read'] ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-secondary">
                                <?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?>
                            </button>
                        </form>
                        <a href="messages.php?delete=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <div class="message-body"><?= e($m['message']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
