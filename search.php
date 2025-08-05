<?php
require_once 'db.php';

// Get search parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$product_type = $_GET['product_type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build search query
$sql = "SELECT p.*, u.username as seller_name, c.name as category_name 
        FROM products p 
        JOIN users u ON p.seller_id = u.id 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

$params = [];

if (!empty($query)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

if (!empty($category)) {
    $sql .= " AND c.name = ?";
    $params[] = $category;
}

if (!empty($min_price)) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

if (!empty($product_type)) {
    $sql .= " AND p.product_type = ?";
    $params[] = $product_type;
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.views DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categoriesStmt->fetchAll();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Products - EtsyClone</title>
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

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem 0;
        }

        /* Filters Sidebar */
        .filters-sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .filters-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #ff6b35;
            padding-bottom: 0.5rem;
        }

        .filter-group {
            margin-bottom: 2rem;
        }

        .filter-group h3 {
            margin-bottom: 1rem;
            color: #555;
            font-size: 1.1rem;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 0.5rem;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .apply-filters {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .apply-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        /* Results Section */
        .results-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-info {
            font-size: 1.1rem;
            color: #666;
        }

        .sort-dropdown {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            background: white;
            cursor: pointer;
        }

        .sort-dropdown:focus {
            outline: none;
            border-color: #ff6b35;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            border-color: #ff6b35;
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

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-category {
            background: #e9ecef;
            color: #666;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .product-views {
            color: #999;
            font-size: 0.8rem;
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .no-results h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #999;
        }

        .no-results p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .browse-categories {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .browse-categories:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .filters-sidebar {
                position: static;
                margin-bottom: 2rem;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .search-container {
                order: 3;
                width: 100%;
            }

            .results-header {
                flex-direction: column;
                align-items: stretch;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
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
                        <input type="text" name="q" class="search-bar" placeholder="Search for handmade items, vintage goods..." value="<?php echo htmlspecialchars($query); ?>">
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
        <div class="main-content">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <h2 class="filters-title">Filters</h2>
                
                <form method="GET" action="search.php">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                    
                    <div class="filter-group">
                        <h3>Category</h3>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                        <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h3>Price Range</h3>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min $" value="<?php echo htmlspecialchars($min_price); ?>" min="0" step="0.01">
                            <input type="number" name="max_price" placeholder="Max $" value="<?php echo htmlspecialchars($max_price); ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="filter-group">
                        <h3>Product Type</h3>
                        <select name="product_type">
                            <option value="">All Types</option>
                            <option value="handmade" <?php echo $product_type === 'handmade' ? 'selected' : ''; ?>>Handmade</option>
                            <option value="vintage" <?php echo $product_type === 'vintage' ? 'selected' : ''; ?>>Vintage</option>
                            <option value="digital" <?php echo $product_type === 'digital' ? 'selected' : ''; ?>>Digital</option>
                        </select>
                    </div>

                    <button type="submit" class="apply-filters">Apply Filters</button>
                </form>
            </aside>

            <!-- Results Section -->
            <main class="results-section">
                <div class="results-header">
                    <div class="results-info">
                        <?php 
                        $totalResults = count($products);
                        echo $totalResults . ' result' . ($totalResults !== 1 ? 's' : '');
                        if (!empty($query)) {
                            echo ' for "' . htmlspecialchars($query) . '"';
                        }
                        ?>
                    </div>
                    
                    <form method="GET" action="search.php" style="display: inline;">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                        <input type="hidden" name="product_type" value="<?php echo htmlspecialchars($product_type); ?>">
                        
                        <select name="sort" class="sort-dropdown" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </form>
                </div>

                <?php if (empty($products)): ?>
                    <div class="no-results">
                        <h3>No products found</h3>
                        <p>Try adjusting your search criteria or browse our categories</p>
                        <a href="index.php" class="browse-categories">Browse All Categories</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
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
                                    <div class="product-meta">
                                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <span class="product-views"><?php echo $product['views']; ?> views</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
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

        function viewProduct(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }

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
