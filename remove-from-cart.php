<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_POST) {
    $cartId = $_POST['cart_id'] ?? 0;
    $userId = $_SESSION['user_id'];

    // Verify cart item belongs to user and delete
    $deleteStmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    if ($deleteStmt->execute([$cartId, $userId])) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
