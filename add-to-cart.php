<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

if ($_POST) {
    $productId = $_POST['product_id'] ?? 0;
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $userId = $_SESSION['user_id'];

    // Check if product exists and is available
    $productStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit();
    }

    // Check if item already in cart
    $cartStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $cartStmt->execute([$userId, $productId]);
    $cartItem = $cartStmt->fetch();

    if ($cartItem) {
        // Update quantity
        $newQuantity = $cartItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more items than available stock']);
            exit();
        }
        
        $updateStmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $updateStmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        // Add new item
        $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insertStmt->execute([$userId, $productId, $quantity]);
    }

    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
