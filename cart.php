<?php
require_once 'db.php';

// Get cart items
$cartItems = [];
$total = 0;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT c.*, p.title, p.price, p.image1, p.stock_quantity, u.username as seller_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN users u ON p.seller_id = u.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - EtsyClone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .search-container {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-bar {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #ff6b35;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .cart-icon {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Cart Styles */
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .cart-items {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .cart-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #333;
            border-bottom: 2px solid #ff6b35;
            padding-bottom: 1rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            overflow: hidden;
        }

        .item-details h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .item-seller {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .item-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ff6b35;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 2px solid #ff6b35;
            background: white;
            color: #ff6b35;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #ff6b35;
            color: white;
        }

        .qty-input {
            width: 50px;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            padding: 5px;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .summary-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #ff6b35;
            padding-bottom: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .summary-total {
            font-size: 1.3rem;
            font-weight: bold;
            border-top: 2px solid #e9ecef;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-cart h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #999;
        }

        .empty-cart p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .continue-shopping {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .login-prompt {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }

        .login-prompt h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .login-prompt p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: #666;
        }

        .login-btn {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin-right: 1rem;
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 1rem;
            }

            .quantity-controls,
            .remove-btn {
                grid-column: 1 / -1;
                justify-self: start;
                margin-top: 1rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .search-container {
                order: 3;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">EtsyClone</a>
                
                <div class="search-container">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" class="search-bar" placeholder="Search for handmade items, vintage goods...">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>

                <nav class="nav-links">
                    <?php if ($currentUser): ?>
                        <a href="profile.php">👤 <?php echo htmlspecialchars($currentUser['username']); ?></a>
                        <a href="add-product.php">+ Sell</a>
                        <a href="cart.php" class="cart-icon">
                            🛒 Cart
                            <span class="cart-count" id="cartCount"><?php echo count($cartItems); ?></span>
                        </a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Sign In</a>
                        <a href="register.php">Register</a>
                        <a href="cart.php" class="cart-icon">
                            🛒 Cart
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <div class="login-prompt">
                <h2>Sign in to view your cart</h2>
                <p>You need to be logged in to add items to your cart and make purchases.</p>
                <a href="login.php" class="login-btn">Sign In</a>
                <a href="register.php" class="login-btn">Register</a>
            </div>
        <?php elseif (empty($cartItems)): ?>
            <div class="empty-cart">
                <h2>🛒</h2>
                <h2>Your cart is empty</h2>
                <p>Discover amazing handmade and vintage items from our talented sellers</p>
                <a href="search.php" class="continue-shopping">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <h1 class="cart-title">Shopping Cart (<?php echo count($cartItems); ?> items)</h1>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php if ($item['image1']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image1']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    🎨
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="item-seller">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                <p class="item-price"><?php echo formatPrice($item['price']); ?> each</p>
                            </div>
                            
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                            </div>
                            
                            <button class="remove-btn" onclick="removeFromCart(<?php echo $item['id']; ?>)">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span><?php echo formatPrice($total * 0.08); ?></span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span><?php echo formatPrice($total * 1.08); ?></span>
                    </div>
                    
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;
            
            fetch('update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating quantity');
            });
        }

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('remove-from-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to remove item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }

        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>
