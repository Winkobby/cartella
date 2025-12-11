<?php
class Functions
{
    private $db;

    public function __construct()
    {
        // Database will be initialized separately
        $this->db = null;
    }


    // Set database connection after initialization
    public function setDatabase($database)
    {
        $this->db = $database;
    }

    // Check if database is available
    private function checkDatabase()
    {
        if ($this->db === null) {
            throw new Exception("Database not initialized");
        }
        return true;
    }

    // Security functions
    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function getProductsByCategory($category_id, $limit = 24)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT p.*, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.category_id = ? AND p.stock_quantity > 0 
                    ORDER BY p.date_added DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$category_id, $limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getProductsByCategory: " . $e->getMessage());
            return [];
        }
    }

    public function getProductById($product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT p.*, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.product_id = ?";
            return $this->db->fetchSingle($sql, [$product_id]);
        } catch (Exception $e) {
            error_log("Error in getProductById: " . $e->getMessage());
            return false;
        }
    }

    public function searchProducts($query, $limit = 12)
    {
        try {
            $this->checkDatabase();
            $searchTerm = "%$query%";
            $sql = "SELECT p.*, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?) 
                    AND p.stock_quantity > 0 
                    ORDER BY p.date_added DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in searchProducts: " . $e->getMessage());
            return [];
        }
    }

    // Category functions
    public function getAllCategories()
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM categories ORDER BY category_name";
            return $this->db->fetchAll($sql) ?: [];
        } catch (Exception $e) {
            error_log("Error in getAllCategories: " . $e->getMessage());
            return [];
        }
    }

    public function getCategoryById($category_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM categories WHERE category_id = ?";
            return $this->db->fetchSingle($sql, [$category_id]);
        } catch (Exception $e) {
            error_log("Error in getCategoryById: " . $e->getMessage());
            return false;
        }
    }

    public function getCategoryBySlug($slug)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM categories WHERE slug = ?";
            return $this->db->fetchSingle($sql, [$slug]);
        } catch (Exception $e) {
            error_log("Error in getCategoryBySlug: " . $e->getMessage());
            return false;
        }
    }

    public function getProductBySlug($slug)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM products WHERE slug = ?";
            return $this->db->fetchSingle($sql, [$slug]);
        } catch (Exception $e) {
            error_log("Error in getProductBySlug: " . $e->getMessage());
            return false;
        }
    }

    public function getProductCountByCategory($category_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND stock_quantity > 0";
            $result = $this->db->fetchSingle($sql, [$category_id]);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error in getProductCountByCategory: " . $e->getMessage());
            return 0;
        }
    }

    // public function getDiscountedProducts($limit = 2)
    // {
    //     try {
    //         $this->checkDatabase();
    //         $sql = "SELECT product_id, name, discount FROM products 
    //                 WHERE discount > 0 AND stock_quantity > 0 
    //                 ORDER BY discount DESC 
    //                 LIMIT ?";
    //         return $this->db->fetchAll($sql, [$limit]) ?: [];
    //     } catch (Exception $e) {
    //         error_log("Error in getDiscountedProducts: " . $e->getMessage());
    //         return [];
    //     }
    // }

    public function getFeaturedBrands()
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' LIMIT 5";
            $results = $this->db->fetchAll($sql) ?: [];
            return array_column($results, 'brand');
        } catch (Exception $e) {
            error_log("Error in getFeaturedBrands: " . $e->getMessage());
            return ['Apple', 'Nike', 'Samsung', 'Sony', 'Adidas'];
        }
    }

    public function getTestimonials($limit = 3)
    {
        return [
            [
                'customer_name' => 'Charity Osei',
                'comment' => 'Great quality products and fast shipping. I\'ve been a customer for over a year and I\'m always satisfied with my purchases.',
                'rating' => 5,
                'user_id' => 1
            ],
            [
                'customer_name' => 'Daniel Onyame',
                'comment' => 'The customer service is exceptional. They helped me with a return and made the process so easy. Highly recommend!',
                'rating' => 4,
                'user_id' => 2
            ],
            [
                'customer_name' => 'Esther Dzikunu',
                'comment' => 'I love the variety of products available. Competitive prices and regular sales make it my go-to store.',
                'rating' => 5,
                'user_id' => 3
            ]
        ];
    }

    // Cart functions
    public function addToCart($product_id, $quantity = 1, $color = null, $size = null)
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cartKey = $product_id . '_' . $color . '_' . $size;

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            try {
                $this->checkDatabase();
                $product = $this->getProductById($product_id);
                if ($product) {
                    // Decode product name (handle double-encoded entities like &amp;apos; -> ')
                    $decodedName = $product['name'];
                    for ($i = 0; $i < 2; $i++) {
                        $candidate = html_entity_decode($decodedName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($candidate === $decodedName) {
                            break;
                        }
                        $decodedName = $candidate;
                    }
                    
                    $_SESSION['cart'][$cartKey] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'color' => $color,
                        'size' => $size,
                        'price' => $this->calculateDiscountedPrice($product['price'], $product['discount']),
                        'name' => $decodedName,
                        'image' => $product['main_image']
                    ];
                }
            } catch (Exception $e) {
                error_log("Error adding to cart: " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    public function removeFromCart($cartKey)
    {
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            return true;
        }
        return false;
    }

    public function getCartTotal()
    {
        $total = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }

    public function getCartItemCount()
    {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }

    // Price calculation
    public function calculateDiscountedPrice($price, $discount)
    {
        return $price - ($price * ($discount / 100));
    }

    public function formatPrice($price)
    {
        return 'GHS ' . number_format($price, 2);
    }

    // Utility functions
    public function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public function jsonResponse($success, $message = '', $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }

    // Get featured products (those marked as featured)
    public function getFeaturedProducts($limit = 6, $sort = 'newest')
    {
        try {
            $this->checkDatabase();

            $orderBy = '';
            switch ($sort) {
                case 'popular':
                    $orderBy = 'total_sold DESC, p.date_added DESC';
                    break;
                case 'price_low':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_high':
                    $orderBy = 'p.price DESC';
                    break;
                case 'discounted':
                    $orderBy = 'p.discount DESC, p.date_added DESC';
                    break;
                case 'rating':
                    $orderBy = 'average_rating DESC, p.date_added DESC';
                    break;
                case 'newest':
                default:
                    $orderBy = 'p.date_added DESC';
                    break;
            }

            $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                    COALESCE(AVG(r.rating), 0) as average_rating
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('processing', 'shipped', 'delivered')
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.is_featured = 1 AND p.stock_quantity > 0 
                GROUP BY p.product_id 
                ORDER BY $orderBy 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getFeaturedProducts: " . $e->getMessage());
            return [];
        }
    }

    // Get popular products (based on sales, ratings, and reviews)
    public function getPopularProducts($limit = 8)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*, 
                    c.category_name, 
                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(DISTINCT r.review_id) as review_count,
                    (COALESCE(SUM(oi.quantity), 0) * 2 + COALESCE(AVG(r.rating), 0) * 3 + COUNT(DISTINCT r.review_id)) as popularity_score
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('processing', 'shipped', 'delivered')
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.stock_quantity > 0 
                GROUP BY p.product_id 
                ORDER BY 
                    popularity_score DESC,
                    total_sold DESC,
                    average_rating DESC,
                    p.date_added DESC 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getPopularProducts: " . $e->getMessage());
            return [];
        }
    }

    // Get best-selling products (based purely on sales)
    public function getBestSellers($limit = 10)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    COALESCE(SUM(oi.quantity), 0) as total_sold
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('processing', 'shipped', 'delivered')
                WHERE p.stock_quantity > 0 
                GROUP BY p.product_id 
                HAVING total_sold > 0
                ORDER BY total_sold DESC 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getBestSellers: " . $e->getMessage());
            return [];
        }
    }

    // Get top-rated products
    public function getTopRatedProducts($limit = 8)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.stock_quantity > 0 
                GROUP BY p.product_id 
                HAVING average_rating > 0 AND review_count >= 1
                ORDER BY average_rating DESC, review_count DESC 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getTopRatedProducts: " . $e->getMessage());
            return [];
        }
    }

    // Get new arrivals
    public function getNewArrivals($limit = 12)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    COALESCE(AVG(r.rating), 0) as average_rating
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.stock_quantity > 0 AND p.is_new = 1
                GROUP BY p.product_id 
                ORDER BY p.date_added DESC 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getNewArrivals: " . $e->getMessage());
            return [];
        }
    }

    // Get discounted products (deals/sales)
    public function getDiscountedProducts($limit = 8)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*, 
                    c.category_name,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    (p.price * (1 - p.discount/100)) as final_price
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.stock_quantity > 0 AND p.discount > 0
                GROUP BY p.product_id 
                ORDER BY p.discount DESC, p.date_added DESC 
                LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getDiscountedProducts: " . $e->getMessage());
            return [];
        }
    }

    // Mark product as featured/unfeatured
    public function setFeaturedProduct($product_id, $is_featured = true)
    {
        try {
            $this->checkDatabase();

            $sql = "UPDATE products SET is_featured = ? WHERE product_id = ?";
            $result = $this->db->executeQuery($sql, [$is_featured ? 1 : 0, $product_id]);

            if ($result) {
                return ['success' => true, 'message' => 'Product featured status updated'];
            } else {
                return ['success' => false, 'message' => 'Failed to update product featured status'];
            }
        } catch (Exception $e) {
            error_log("Error in setFeaturedProduct: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    // Mark product as new/not new
    public function setNewProduct($product_id, $is_new = true)
    {
        try {
            $this->checkDatabase();

            $sql = "UPDATE products SET is_new = ? WHERE product_id = ?";
            $result = $this->db->executeQuery($sql, [$is_new ? 1 : 0, $product_id]);

            if ($result) {
                return ['success' => true, 'message' => 'Product new status updated'];
            } else {
                return ['success' => false, 'message' => 'Failed to update product new status'];
            }
        } catch (Exception $e) {
            error_log("Error in setNewProduct: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    // Check if user can review product (has purchased it)
    public function canUserReviewProduct($user_id, $product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT oi.order_item_id 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status IN ('delivered', 'processing', 'shipped') 
                LIMIT 1";
            $result = $this->db->fetchSingle($sql, [$user_id, $product_id]);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("Error in canUserReviewProduct: " . $e->getMessage());
            return false;
        }
    }

    // Get product statistics
    public function getProductStats($product_id)
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT 
                    p.*,
                    c.category_name,
                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as review_count,
                    (SELECT COUNT(*) FROM wishlist WHERE product_id = p.product_id) as wishlist_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('processing', 'shipped', 'delivered')
                LEFT JOIN reviews r ON p.product_id = r.product_id 
                WHERE p.product_id = ? 
                GROUP BY p.product_id";

            return $this->db->fetchSingle($sql, [$product_id]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getProductStats: " . $e->getMessage());
            return [];
        }
    }

    //END OG NEW

    // Wishlist functions
    public function addToWishlist($user_id, $product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
            $existing = $this->db->fetchSingle($sql, [$user_id, $product_id]);

            if ($existing) {
                return ['success' => false, 'message' => 'Product already in wishlist'];
            }

            $sql = "INSERT INTO wishlist (user_id, product_id, date_added) VALUES (?, ?, NOW())";
            $stmt = $this->db->executeQuery($sql, [$user_id, $product_id]);

            if ($stmt) {
                return ['success' => true, 'message' => 'Product added to wishlist'];
            } else {
                return ['success' => false, 'message' => 'Failed to add to wishlist'];
            }
        } catch (Exception $e) {
            error_log("Error in addToWishlist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function removeFromWishlist($user_id, $product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->db->executeQuery($sql, [$user_id, $product_id]);

            if ($stmt) {
                return ['success' => true, 'message' => 'Product removed from wishlist'];
            } else {
                return ['success' => false, 'message' => 'Failed to remove from wishlist'];
            }
        } catch (Exception $e) {
            error_log("Error in removeFromWishlist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function getWishlistItems($user_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT w.*, p.name, p.price, p.discount, p.main_image, p.stock_quantity, p.description, 
                       c.category_name, p.date_added as product_date_added
                FROM wishlist w 
                JOIN products p ON w.product_id = p.product_id 
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE w.user_id = ? 
                ORDER BY w.date_added DESC";
            return $this->db->fetchAll($sql, [$user_id]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getWishlistItems: " . $e->getMessage());
            return [];
        }
    }

    public function isInWishlist($user_id, $product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
            $result = $this->db->fetchSingle($sql, [$user_id, $product_id]);
            return $result ? true : false;
        } catch (Exception $e) {
            error_log("Error in isInWishlist: " . $e->getMessage());
            return false;
        }
    }

    public function getWishlistCount($user_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
            $result = $this->db->fetchSingle($sql, [$user_id]);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error in getWishlistCount: " . $e->getMessage());
            return 0;
        }
    }


    public function getProducts(
    $category_id = 'all',
    $search_query = '',
    $sort = 'newest',
    $page = 1,
    $per_page = 25,
    $price_min = '',
    $price_max = '',
    $brand = '',
    $filter = 'all'
) {
    try {
        $this->checkDatabase();

        $offset = ($page - 1) * $per_page;
        $params = [];
        $where_conditions = ["p.stock_quantity > 0"];

        // Category filter
        if ($category_id !== 'all') {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category_id;
        }

        // Search filter
        if (!empty($search_query)) {
            $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
            $search_term = "%$search_query%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Price range filter
        if ($price_min !== '') {
            $where_conditions[] = "p.price >= ?";
            $params[] = $price_min;
        }
        if ($price_max !== '') {
            $where_conditions[] = "p.price <= ?";
            $params[] = $price_max;
        }

        // Brand filter
        if (!empty($brand)) {
            $where_conditions[] = "p.brand = ?";
            $params[] = $brand;
        }

        // Filter for discounted products
        if ($filter === 'discounted' || $sort === 'discounted') {
            $where_conditions[] = "p.discount > 0";
        }

        // Filter for featured products
        if ($sort === 'featured') {
            $where_conditions[] = "p.is_featured = 1";
        }

        // Build WHERE clause
        $where_clause = "";
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }

        // Sort order
        $order_by = "";
        switch ($sort) {
            case 'popular':
                // Use a subquery to get popular products
                $order_by = "(SELECT COALESCE(SUM(oi.quantity), 0) 
                             FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.id 
                             WHERE oi.product_id = p.product_id 
                             AND o.status IN ('processing', 'shipped', 'delivered')) DESC,
                             p.date_added DESC";
                break;

            case 'price_low':
                $order_by = "p.price ASC";
                break;

            case 'price_high':
                $order_by = "p.price DESC";
                break;
            
            case 'featured':
                $order_by = "p.is_featured DESC, p.date_added DESC";
                break;

            case 'rating':
                $order_by = "(SELECT AVG(rating) FROM reviews WHERE product_id = p.product_id) DESC";
                break;

            case 'discounted':
                $order_by = "p.discount DESC, p.date_added DESC";
                break;

            case 'newest':
            default:
                $order_by = "p.is_new DESC, p.product_id DESC";
                break;
        }

        // Main products query
        $sql = "SELECT p.*, c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                $where_clause
                ORDER BY $order_by
                LIMIT ? OFFSET ?";

        $params[] = $per_page;
        $params[] = $offset;

        $products = $this->db->fetchAll($sql, $params) ?: [];

        // Total products count for pagination
        $count_sql = "SELECT COUNT(*) as total
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      $where_clause";

        $count_params = array_slice($params, 0, -2); // exclude LIMIT & OFFSET
        $total_result = $this->db->fetchSingle($count_sql, $count_params);
        $total_products = $total_result ? $total_result['total'] : 0;
        $total_pages = ceil($total_products / $per_page);

        return [
            'products' => $products,
            'total' => $total_products,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
    } catch (Exception $e) {
        error_log("Error in getProducts: " . $e->getMessage());
        return ['products' => [], 'total' => 0, 'total_pages' => 0, 'current_page' => $page];
    }
}


    // Get detailed product information
    public function getProductDetails($product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT p.*, c.category_name, 
                           (SELECT AVG(rating) FROM reviews WHERE product_id = p.product_id) as average_rating,
                           (SELECT COUNT(*) FROM reviews WHERE product_id = p.product_id) as review_count
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.product_id = ?";
            return $this->db->fetchSingle($sql, [$product_id]);
        } catch (Exception $e) {
            error_log("Error in getProductDetails: " . $e->getMessage());
            return false;
        }
    }

    // Get related products
    public function getRelatedProducts($product_id, $category_id, $limit = 4)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT p.*, c.category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.category_id = ? AND p.product_id != ? AND p.stock_quantity > 0 
                    ORDER BY RAND() 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$category_id, $product_id, $limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getRelatedProducts: " . $e->getMessage());
            return [];
        }
    }

    // Get product reviews
    public function getProductReviews($product_id, $limit = 10)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT r.*, u.full_name as customer_name 
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.user_id 
                    WHERE r.product_id = ? 
                    ORDER BY r.review_date DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$product_id, $limit]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getProductReviews: " . $e->getMessage());
            return [];
        }
    }

    // Submit product review
    public function submitReview($user_id, $product_id, $rating, $comment)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM reviews WHERE user_id = ? AND product_id = ?";
            $existing = $this->db->fetchSingle($sql, [$user_id, $product_id]);

            if ($existing) {
                return ['success' => false, 'message' => 'You have already reviewed this product'];
            }

            $sql = "INSERT INTO reviews (user_id, product_id, rating, comment, review_date) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->executeQuery($sql, [$user_id, $product_id, $rating, $comment]);

            if ($stmt) {
                return ['success' => true, 'message' => 'Review submitted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to submit review'];
            }
        } catch (Exception $e) {
            error_log("Error in submitReview: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    // Check if user can review product (has purchased it)
    // public function canUserReviewProduct($user_id, $product_id)
    // {
    //     try {
    //         $this->checkDatabase();
    //         $sql = "SELECT oi.order_item_id 
    //                 FROM order_items oi 
    //                 JOIN orders o ON oi.order_id = o.order_id 
    //                 WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered' 
    //                 LIMIT 1";
    //         $result = $this->db->fetchSingle($sql, [$user_id, $product_id]);
    //         return $result ? true : false;
    //     } catch (Exception $e) {
    //         error_log("Error in canUserReviewProduct: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // Get user's review for a specific product
    public function getUserReviewForProduct($user_id, $product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM reviews WHERE user_id = ? AND product_id = ?";
            return $this->db->fetchSingle($sql, [$user_id, $product_id]);
        } catch (Exception $e) {
            error_log("Error in getUserReviewForProduct: " . $e->getMessage());
            return false;
        }
    }

    // Get average rating for a product
    public function getAverageRating($product_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT AVG(rating) as average_rating FROM reviews WHERE product_id = ?";
            $result = $this->db->fetchSingle($sql, [$product_id]);

            if ($result && $result['average_rating'] !== null) {
                return round($result['average_rating'], 1);
            }

            return 0;
        } catch (Exception $e) {
            error_log("Error in getAverageRating: " . $e->getMessage());
            return 0;
        }
    }

    // Get all brands from products
    public function getAllBrands()
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand";
            $results = $this->db->fetchAll($sql) ?: [];
            return array_column($results, 'brand');
        } catch (Exception $e) {
            error_log("Error in getAllBrands: " . $e->getMessage());
            return [];
        }
    }

    // Get category name by ID
    public function getCategoryName($category_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT category_name FROM categories WHERE category_id = ?";
            $result = $this->db->fetchSingle($sql, [$category_id]);
            return $result ? $result['category_name'] : '';
        } catch (Exception $e) {
            error_log("Error in getCategoryName: " . $e->getMessage());
            return '';
        }
    }

    // Get cart items from session
    public function getCartItems()
    {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    }

    // Update cart quantity
    public function updateCartQuantity($product_id, $quantity, $color = null, $size = null)
    {
        if (!isset($_SESSION['cart'])) {
            return false;
        }

        $cartKey = $product_id . '_' . $color . '_' . $size;

        if (isset($_SESSION['cart'][$cartKey])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$cartKey]);
            } else {
                $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            }
            return true;
        }

        return false;
    }

    // Clear cart
    public function clearCart()
    {
        unset($_SESSION['cart']);
        return true;
    }

    // Get user data for checkout
    public function getUserData($user_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT user_id, email, full_name, phone, address FROM users WHERE user_id = ?";
            $userData = $this->db->fetchSingle($sql, [$user_id]);

            // Parse full_name into first_name and last_name for convenience
            if ($userData && !empty($userData['full_name'])) {
                $nameParts = explode(' ', $userData['full_name'], 2);
                $userData['first_name'] = $nameParts[0];
                $userData['last_name'] = isset($nameParts[1]) ? $nameParts[1] : '';
            }

            return $userData;
        } catch (Exception $e) {
            error_log("Error in getUserData: " . $e->getMessage());
            return [];
        }
    }

    // Create new order
    public function createOrder($user_id, $order_data)
    {
        try {
            $this->checkDatabase();
            $this->db->beginTransaction();

            // Insert order
            $sql = "INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method, order_status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            $order_params = [
                $user_id,
                $order_data['total_amount'],
                $order_data['shipping_address'],
                $order_data['billing_address'],
                $order_data['payment_method']
            ];

            $order_id = $this->db->executeQuery($sql, $order_params);

            if (!$order_id) {
                throw new Exception("Failed to create order");
            }

            // Insert order items
            $cart_items = $this->getCartItems();
            foreach ($cart_items as $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                        VALUES (?, ?, ?, ?)";
                $this->db->executeQuery($sql, [
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);

                // Update product stock
                $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
                $this->db->executeQuery($sql, [$item['quantity'], $item['product_id']]);
            }

            $this->db->commit();

            // Clear cart after successful order
            $this->clearCart();

            return $order_id;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error in createOrder: " . $e->getMessage());
            return false;
        }
    }

    // Get user orders
    public function getUserOrders($user_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                    FROM orders o 
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                    WHERE o.user_id = ? 
                    GROUP BY o.order_id 
                    ORDER BY o.created_at DESC";
            return $this->db->fetchAll($sql, [$user_id]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getUserOrders: " . $e->getMessage());
            return [];
        }
    }

    // Get order details
    public function getOrderDetails($order_id, $user_id = null)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT o.*, oi.*, p.name, p.main_image 
                    FROM orders o 
                    JOIN order_items oi ON o.order_id = oi.order_id 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE o.order_id = ?";

            $params = [$order_id];
            if ($user_id) {
                $sql .= " AND o.user_id = ?";
                $params[] = $user_id;
            }

            $sql .= " ORDER BY oi.order_item_id";

            return $this->db->fetchAll($sql, $params) ?: [];
        } catch (Exception $e) {
            error_log("Error in getOrderDetails: " . $e->getMessage());
            return [];
        }
    }

    // // Get popular products (based on orders)
    // public function getPopularProducts($limit = 8)
    // {
    //     try {
    //         $this->checkDatabase();
    //         $sql = "SELECT p.*, c.category_name, COUNT(oi.order_item_id) as order_count 
    //                 FROM products p 
    //                 LEFT JOIN categories c ON p.category_id = c.category_id 
    //                 LEFT JOIN order_items oi ON p.product_id = oi.product_id 
    //                 WHERE p.stock_quantity > 0 
    //                 GROUP BY p.product_id 
    //                 ORDER BY order_count DESC, p.date_added DESC 
    //                 LIMIT ?";
    //         return $this->db->fetchAll($sql, [$limit]) ?: [];
    //     } catch (Exception $e) {
    //         error_log("Error in getPopularProducts: " . $e->getMessage());
    //         return [];
    //     }
    // }

    // Get new arrivals
    // public function getNewArrivals($limit = 15)
    // {
    //     try {
    //         $this->checkDatabase();
    //         $sql = "SELECT p.*, c.category_name 
    //             FROM products p 
    //             LEFT JOIN categories c ON p.category_id = c.category_id 
    //             WHERE p.stock_quantity > 0 AND p.is_new = 1
    //             ORDER BY p.date_added DESC 
    //             LIMIT ?";
    //         return $this->db->fetchAll($sql, [$limit]) ?: [];
    //     } catch (Exception $e) {
    //         error_log("Error in getNewArrivals: " . $e->getMessage());
    //         return [];
    //     }
    // }

    public function getDiscountedProductsLink()
    {
        return 'products.php?filter=deals';
    }

    public function getNewArrivalsLink()
    {
        return 'products.php?sort=Newest';
    }

    public function getBestSellersLink()
    {
        return 'products.php?sort=popular';
    }

    // Get category link
    public function getCategoryLink($category_id)
    {
        return 'products.php?category=' . $category_id;
    }

    // Get product image with fallback
    public function getProductImage($imagePath)
    {
        if (empty($imagePath) || !file_exists($imagePath)) {
            return 'assets/images/placeholder-product.jpg';
        }
        return $imagePath;
    }

    // Get product image with fallback
    // public function getProductImage($imagePath)
    // {
    //     if (empty($imagePath)) {
    //         return 'assets/images/placeholder-product.jpg';
    //     }


    //     $normalizedPath = ltrim($imagePath, './');

    //     if (strpos($normalizedPath, '../') === 0) {
    //         $normalizedPath = substr($normalizedPath, 3);
    //     }


    //     $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $normalizedPath;


    //     if (file_exists($absolutePath)) {

    //         return $normalizedPath;
    //     }


    //     return 'assets/images/placeholder-product.jpg';
    // }
    public function getUserAvatar($user_id, $full_name = '')
    {
        try {
            $this->checkDatabase();

            $sql = "SELECT profile_image, full_name FROM users WHERE user_id = ?";
            $user = $this->db->fetchSingle($sql, [$user_id]);

            if ($user && !empty($user['profile_image'])) {
                return $user['profile_image'];
            }

            $name = $user ? $user['full_name'] : $full_name;
            if (empty($name)) {
                $sql = "SELECT full_name FROM users WHERE user_id = ?";
                $user_data = $this->db->fetchSingle($sql, [$user_id]);
                $name = $user_data ? $user_data['full_name'] : 'U';
            }

            $names = explode(' ', $name);
            $initial = strtoupper(substr($names[0], 0, 1));

            $colors = ['#4F46E5', '#7C3AED', '#EC4899', '#10B981', '#F59E0B', '#EF4444'];
            $color = $colors[array_rand($colors)];

            $svg = '<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg">';
            $svg .= '<rect width="40" height="40" fill="' . $color . '" rx="8"/>';
            $svg .= '<text x="20" y="26" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">' . $initial . '</text>';
            $svg .= '</svg>';

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (Exception $e) {
            error_log("Error in getUserAvatar: " . $e->getMessage());
            return $this->generateDefaultAvatar();
        }
    }

    private function generateDefaultAvatar()
    {
        $svg = '<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="40" height="40" fill="#6B7280" rx="8"/>';
        $svg .= '<text x="20" y="26" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">U</text>';
        $svg .= '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }


    public function getPriceRange($category = 'all', $brand = '', $search = '')
    {
        try {
            $this->checkDatabase();

            $where_conditions = ["p.stock_quantity > 0"];
            $params = [];

            if ($category !== 'all') {
                $where_conditions[] = "p.category_id = ?";
                $params[] = $category;
            }

            if (!empty($brand)) {
                $where_conditions[] = "p.brand = ?";
                $params[] = $brand;
            }

            if (!empty($search)) {
                $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $sql = "SELECT 
                MIN(p.price) as min_price,
                MAX(p.price) as max_price
            FROM products p
            WHERE $where_clause";

            $result = $this->db->fetchSingle($sql, $params);

            if (!$result || $result['min_price'] === null) {
                return ['min_price' => 0, 'max_price' => 1000];
            }

            $min_price = floor($result['min_price']);
            $max_price = ceil($result['max_price']);

            if ($max_price - $min_price < 10) {
                $max_price = $min_price + 10;
            }

            return [
                'min_price' => $min_price,
                'max_price' => $max_price
            ];
        } catch (Exception $e) {
            error_log("Error getting price range: " . $e->getMessage());
            return ['min_price' => 0, 'max_price' => 1000];
        }
    }

    public function clearWishlist($user_id)
    {
        try {
            $this->checkDatabase();
            $sql = "DELETE FROM wishlist WHERE user_id = ?";
            $stmt = $this->db->executeQuery($sql, [$user_id]);

            if ($stmt) {
                return ['success' => true, 'message' => 'Wishlist cleared successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to clear wishlist'];
            }
        } catch (Exception $e) {
            error_log("Error in clearWishlist: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function getNewsletterSubscribers($status = 'active')
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM newsletter_subscribers WHERE subscription_status = ? ORDER BY subscribed_at DESC";
            return $this->db->fetchAll($sql, [$status]) ?: [];
        } catch (Exception $e) {
            error_log("Error in getNewsletterSubscribers: " . $e->getMessage());
            return [];
        }
    }

    public function getSubscriberCount($status = 'active')
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT COUNT(*) as count FROM newsletter_subscribers WHERE subscription_status = ?";
            $result = $this->db->fetchSingle($sql, [$status]);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error in getSubscriberCount: " . $e->getMessage());
            return 0;
        }
    }

    public function getUserByEmail($email)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT user_id, email, full_name FROM users WHERE email = ?";
            error_log("Executing SQL: " . $sql . " with email: " . $email);
            $result = $this->db->fetchSingle($sql, [$email]);
            error_log("Query result: " . ($result ? 'FOUND' : 'NOT FOUND'));
            return $result;
        } catch (Exception $e) {
            error_log("Error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }

    public function truncateString($string, $length = 30)
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length) . '...';
    }
    /**
     * Get top-selling products based on order history with fallback options
     *
     * @param int $limit Number of products to return (default: 12)
     * @param string $period Time period to consider ('all_time', 'monthly', 'weekly')
     * @return array Array of top-selling products
     */
    public function getTopSellingProducts($limit = 12, $period = 'all_time')
    {
        try {
            $this->checkDatabase();

            $limit = max(1, min(12, intval($limit)));

            $checkQuery = "SHOW COLUMNS FROM order_items LIKE 'quantity'";
            $hasQuantity = $this->db->fetchSingle($checkQuery);

            if ($hasQuantity) {
                $query = "
                SELECT 
                    p.product_id,
                    p.name,
                    p.slug,
                    p.description,
                    p.price,
                    p.discount,
                    p.main_image,
                    p.stock_quantity,
                    p.is_new,
                    c.category_name,
                    COALESCE(SUM(oi.quantity), 0) AS total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE p.stock_quantity > 0
                AND o.status IN ('processing', 'delivered', 'shipped')
            ";
            } else {
                $query = "
                SELECT 
                    p.product_id,
                    p.name,
                    p.slug,
                    p.description,
                    p.price,
                    p.discount,
                    p.main_image,
                    p.stock_quantity,
                    p.is_new,
                    c.category_name,
                    COUNT(oi.order_item_id) AS total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE p.stock_quantity > 0
                AND o.status IN ('processing', 'delivered', 'shipped')
            ";
            }

            switch ($period) {
                case 'monthly':
                    $query .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'weekly':
                    $query .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                default:
                    break;
            }

            $query .= "
            GROUP BY p.product_id
            HAVING total_sold > 0
            ORDER BY total_sold DESC
            LIMIT ?
        ";

            $products = $this->db->fetchAll($query, [$limit]) ?: [];

            if (empty($products)) {
                error_log("No top-selling products found, using featured products as fallback");
                return $this->getFeaturedProducts($limit);
            }

            return $products;
        } catch (Exception $e) {
            error_log("Error in getTopSellingProducts: " . $e->getMessage());
            return $this->getFeaturedProducts($limit);
        }
    }

    public function highlightSearchTerms($text, $searchQuery)
    {
        if (empty($searchQuery) || empty($text)) {
            return htmlspecialchars($text);
        }

        $searchTerms = preg_split('/\s+/', $searchQuery);
        $highlightedText = htmlspecialchars($text);

        foreach ($searchTerms as $term) {
            if (strlen(trim($term)) > 2) {
                $pattern = '/(' . preg_quote($term, '/') . ')/i';
                $highlightedText = preg_replace(
                    $pattern,
                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>',
                    $highlightedText
                );
            }
        }

        return $highlightedText;
    }

    public function getTotalProductCount()
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Banner functions
    public function getActiveBanners()
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM banners WHERE is_active = 1 ORDER BY position ASC";
            return $this->db->fetchAll($sql) ?: [];
        } catch (Exception $e) {
            error_log("Error in getActiveBanners: " . $e->getMessage());
            return [];
        }
    }

    public function getAllBanners()
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM banners ORDER BY position ASC";
            return $this->db->fetchAll($sql) ?: [];
        } catch (Exception $e) {
            error_log("Error in getAllBanners: " . $e->getMessage());
            return [];
        }
    }

    public function getBannerById($banner_id)
    {
        try {
            $this->checkDatabase();
            $sql = "SELECT * FROM banners WHERE banner_id = ?";
            return $this->db->fetchSingle($sql, [$banner_id]);
        } catch (Exception $e) {
            error_log("Error in getBannerById: " . $e->getMessage());
            return false;
        }
    }

    public function saveBanner($banner_id, $title, $description, $image_url, $link_url, $button_text, $is_active, $position)
    {
        try {
            $this->checkDatabase();
            $pdo = $this->db->getConnection();

            if ($banner_id) {
                // Update
                $sql = "UPDATE banners SET title = ?, description = ?, image_url = ?, link_url = ?, button_text = ?, is_active = ?, position = ? WHERE banner_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $description, $image_url, $link_url, $button_text, $is_active, $position, $banner_id]);
                return $banner_id;
            } else {
                // Create
                $sql = "INSERT INTO banners (title, description, image_url, link_url, button_text, is_active, position) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$title, $description, $image_url, $link_url, $button_text, $is_active, $position]);
                return $pdo->lastInsertId();
            }
        } catch (Exception $e) {
            error_log("Error in saveBanner: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBanner($banner_id)
    {
        try {
            $this->checkDatabase();
            $pdo = $this->db->getConnection();
            $sql = "DELETE FROM banners WHERE banner_id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$banner_id]);
        } catch (Exception $e) {
            error_log("Error in deleteBanner: " . $e->getMessage());
            return false;
        }
    }

    
}


$functions = new Functions();
