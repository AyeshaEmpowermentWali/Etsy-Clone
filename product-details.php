<?php
require_once 'db.php';

$productId = $_GET['id'] ?? 0;

if (!$productId) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, u.username as seller_name, u.full_name as seller_full_name, c.name as category_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// Update view count
$updateViews = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
$updateViews->execute([$productId]);

// Get related products
$relatedStmt = $pdo->prepare("
    SELECT p.*, u.username as seller_name, c.name as category_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
    ORDER BY RAND() 
    LIMIT 4
");
$relatedStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relatedStmt->fetchAll();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - EtsyClone</title>
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

        /* Header Styles (same as other pages) */
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

        /* Product Details */
        .product-details {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin: 2rem 0;
            overflow: hidden;
        }

        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 3rem;
        }

        .product-images {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .product-info {
            padding: 1rem 0;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
            line-height: 1.2;
        }

        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 1rem;
        }

        .product-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meta-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .meta-value {
            font-weight: 600;
            color: #333;
        }

        .seller-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .seller-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b35;
            margin-bottom: 0.5rem;
        }

        .product-description {
            margin-bottom: 2rem;
        }

        .description-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .description-text {
            line-height: 1.8;
            color: #555;
        }

        .purchase-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quantity-label {
            font-weight: 600;
            color: #333;
        }

        .quantity-input {
            width: 80px;
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .stock-info {
            color: #28a745;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .stock-low {
            color: #ffc107;
        }

        .stock-out {
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            min-width: 150px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #ff6b35;
            border: 2px solid #ff6b35;
        }

        .btn-secondary:hover {
            background: #ff6b35;
            color: white;
        }

        /* Related Products */
        .related-products {
            margin: 4rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 2px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            position: relative;
            overflow: hidden;
        }

        .product-image::before {
            content: '🎨';
            font-size: 3rem;
        }

        .card-info {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
        }

        .card-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b35;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem;
            }

            .product-title {
                font-size: 2rem;
            }

            .product-price {
                font-size: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                            <span class="cart-count" id="cartCount">0</span>
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
        <div class="product-details">
            <div class="product-main">
                <div class="product-images">
                    <div class="main-image">
                        <?php if ($product['image1']): ?>
                            <img src="<?php echo htmlspecialchars($product['image1']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    <div class="product-price"><?php echo formatPrice($product['price']); ?></div>

                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">Category</span>
                            <span class="meta-value"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Type</span>
                            <span class="meta-value"><?php echo ucfirst($product['product_type']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Views</span>
                            <span class="meta-value"><?php echo $product['views']; ?></span>
                        </div>
                    </div>

                    <div class="seller-info">
                        <div class="seller-name">👤 <?php echo htmlspecialchars($product['seller_full_name']); ?></div>
                        <div>@<?php echo htmlspecialchars($product['seller_name']); ?></div>
                    </div>

                    <div class="purchase-section">
                        <div class="quantity-selector">
                            <label class="quantity-label">Quantity:</label>
                            <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" id="quantity">
                        </div>

                        <div class="stock-info <?php echo $product['stock_quantity'] <= 5 ? ($product['stock_quantity'] == 0 ? 'stock-out' : 'stock-low') : ''; ?>">
                            <?php if ($product['stock_quantity'] == 0): ?>
                                ❌ Out of stock
                            <?php elseif ($product['stock_quantity'] <= 5): ?>
                                ⚠️ Only <?php echo $product['stock_quantity']; ?> left in stock
                            <?php else: ?>
                                ✅ In stock (<?php echo $product['stock_quantity']; ?> available)
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    🛒 Add to Cart
                                </button>
                                <button class="btn btn-secondary" onclick="buyNow(<?php echo $product['id']; ?>)">
                                    ⚡ Buy Now
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-description" style="padding: 0 3rem 3rem;">
                <h2 class="description-title">Description</h2>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
        </div>

        <?php if (!empty($relatedProducts)): ?>
        <section class="related-products">
            <h2 class="section-title">Related Products</h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="product-card" onclick="viewProduct(<?php echo $relatedProduct['id']; ?>)">
                        <div class="product-image">
                            <?php if ($relatedProduct['image1']): ?>
                                <img src="<?php echo htmlspecialchars($relatedProduct['image1']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title"><?php echo htmlspecialchars($relatedProduct['title']); ?></h3>
                            <p class="card-price"><?php echo formatPrice($relatedProduct['price']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script>
        // Update cart count on page load
        updateCartCount();

        function updateCartCount() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cartCount').textContent = data.count || 0;
                })
                .catch(error => console.error('Error:', error));
        }

        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                    updateCartCount();
                } else {
                    alert(data.message || 'Failed to add to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding to cart');
            });
        }

        function buyNow(productId) {
            const quantity = document.getElementById('quantity').value;
            window.location.href = `checkout.php?product_id=${productId}&quantity=${quantity}`;
        }

        function viewProduct(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }
    </script>
</body>
</html>
