<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_POST) {
    $cartId = $_POST['cart_id'] ?? 0;
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $userId = $_SESSION['user_id'];

    // Verify cart item belongs to user
    $cartStmt = $pdo->prepare("SELECT c.*, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $cartStmt->execute([$cartId, $userId]);
    $cartItem = $cartStmt->fetch();

    if (!$cartItem) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }

    if ($quantity > $cartItem['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit();
    }

    // Update quantity
    $updateStmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    if ($updateStmt->execute([$quantity, $cartId])) {
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
