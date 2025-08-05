<?php
require_once 'db.php';

// Get featured products
$featuredStmt = $pdo->prepare("
    SELECT p.*, u.username as seller_name, c.name as category_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND p.featured = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$featuredStmt->execute();
$featuredProducts = $featuredStmt->fetchAll();

// Get trending products (most viewed)
$trendingStmt = $pdo->prepare("
    SELECT p.*, u.username as seller_name, c.name as category_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.views DESC 
    LIMIT 8
");
$trendingStmt->execute();
$trendingProducts = $trendingStmt->fetchAll();

// Get categories
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EtsyClone - Handmade & Vintage Marketplace</title>
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

        .search-btn:hover {
            background: #e55a2b;
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23ff6b35" width="1200" height="600"/><circle fill="%23f7931e" cx="200" cy="150" r="100" opacity="0.3"/><circle fill="%23ff8c42" cx="800" cy="400" r="150" opacity="0.2"/><circle fill="%23ffa726" cx="1000" cy="200" r="80" opacity="0.4"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 0;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-primary {
            background: #fff;
            color: #ff6b35;
        }

        .btn-primary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #ff6b35;
        }

        /* Categories Section */
        .categories {
            padding: 80px 0;
            background: white;
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

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            border-color: #ff6b35;
        }

        .category-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        /* Products Section */
        .products-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
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

        .product-info {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
        }

        .product-seller {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 1rem;
        }

        .product-category {
            background: #e9ecef;
            color: #666;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #ff6b35;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: #ff6b35;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #ccc;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .search-container {
                order: 3;
                width: 100%;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 200px;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .hero {
                padding: 60px 0;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .categories, .products-section {
                padding: 40px 0;
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

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h1>Discover Unique Handmade Treasures</h1>
                <p>Find one-of-a-kind items, vintage pieces, and digital creations from talented artisans around the world</p>
                <div class="cta-buttons">
                    <a href="search.php" class="btn btn-primary">Start Shopping</a>
                    <a href="register.php" class="btn btn-secondary">Start Selling</a>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="categories">
            <div class="container">
                <h2 class="section-title">Shop by Category</h2>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card" onclick="searchByCategory('<?php echo $category['name']; ?>')">
                            <div class="category-icon">
                                <?php
                                $icons = ['🎨', '💎', '🏠', '💻', '⚱️', '🖼️', '👕', '✂️'];
                                echo $icons[array_rand($icons)];
                                ?>
                            </div>
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <?php if (!empty($featuredProducts)): ?>
        <section class="products-section">
            <div class="container">
                <h2 class="section-title">Featured Products</h2>
                <div class="products-grid">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)">
                            <div class="product-image">
                                <?php if ($product['image1']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image1']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-seller">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                                <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Trending Products -->
        <?php if (!empty($trendingProducts)): ?>
        <section class="products-section" style="background: white;">
            <div class="container">
                <h2 class="section-title">Trending Now</h2>
                <div class="products-grid">
                    <?php foreach ($trendingProducts as $product): ?>
                        <div class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)">
                            <div class="product-image">
                                <?php if ($product['image1']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image1']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-seller">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                                <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="search.php">All Categories</a></li>
                        <li><a href="search.php?category=Handmade">Handmade</a></li>
                        <li><a href="search.php?category=Vintage">Vintage</a></li>
                        <li><a href="search.php?category=Digital">Digital</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Sell</h3>
                    <ul>
                        <li><a href="register.php">Start Selling</a></li>
                        <li><a href="add-product.php">List an Item</a></li>
                        <li><a href="#">Seller Handbook</a></li>
                        <li><a href="#">Seller Tools</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>About</h3>
                    <ul>
                        <li><a href="#">About EtsyClone</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Investors</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 EtsyClone. All rights reserved. Made with ❤️ for artisans worldwide.</p>
            </div>
        </div>
    </footer>

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

        function searchByCategory(category) {
            window.location.href = `search.php?category=${encodeURIComponent(category)}`;
        }

        function viewProduct(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading animation for product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
        });
    </script>
</body>
</html>
