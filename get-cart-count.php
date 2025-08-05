<?php
require_once 'db.php';

header('Content-Type: application/json');

$count = 0;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $count = $result['total'] ?? 0;
}

echo json_encode(['count' => (int)$count]);
?>
