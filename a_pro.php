<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Debug: Check session variables
error_log("a_pro.php - User ID: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("a_pro.php - User Role: " . ($_SESSION['user_role'] ?? 'not set'));
error_log("a_pro.php - User Email: " . ($_SESSION['user_email'] ?? 'not set'));

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    error_log("Admin access denied - Redirecting to signin. User role: " . ($_SESSION['user_role'] ?? 'not set'));
    header('Location: signin.php');
    exit;
}

error_log("Admin access granted to: " . $_SESSION['user_email']);

$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

// Initialize PDO connection
try {
    $pdo = $database->getConnection();
    // Test the connection
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}

// Check if we're editing an existing product
$is_edit = false;
$product_id = null;
$existing_product = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $is_edit = true;
    
    // Fetch existing product data
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $existing_product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing_product) {
                header('Location: a_products.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error fetching product: " . $e->getMessage());
            header('Location: a_products.php');
            exit;
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    try {
        // Check if database connection is available
        if (!$pdo) {
            throw new Exception("Database connection not available.");
        }
        
        switch ($_POST['ajax_action']) {
            case 'add_product':
                // Basic validation
                $required_fields = ['name', 'category_id', 'price', 'stock_quantity', 'description'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Please fill in all required fields.");
                    }
                }

                // Validate numeric fields
                if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
                    throw new Exception("Please enter a valid price.");
                }

                if (!is_numeric($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0) {
                    throw new Exception("Please enter a valid stock quantity.");
                }

                if (!empty($_POST['discount']) && (!is_numeric($_POST['discount']) || $_POST['discount'] < 0 || $_POST['discount'] > 100)) {
                    throw new Exception("Discount must be between 0 and 100 percent.");
                }

                // Generate SKU if empty
                $sku = !empty($_POST['sku']) ? clean_input($_POST['sku']) : generateSKU($pdo);

                // Check if SKU already exists
                if (!empty($sku)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
                    $stmt->execute([$sku]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("SKU already exists. Please use a different SKU.");
                    }
                }

                // Prepare product data
                $product_data = [
                    'category_id' => intval($_POST['category_id']),
                    'name' => clean_input($_POST['name']),
                    'brand' => !empty($_POST['brand']) ? clean_input($_POST['brand']) : null,
                    'description' => clean_input($_POST['description']),
                    'short_description' => !empty($_POST['short_description']) ? clean_input($_POST['short_description']) : null,
                    'sku' => $sku,
                    'price' => floatval($_POST['price']),
                    'discount' => !empty($_POST['discount']) ? floatval($_POST['discount']) : 0.00,
                    'is_new' => isset($_POST['is_new']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'color' => !empty($_POST['color']) ? clean_input($_POST['color']) : null,
                    'size' => !empty($_POST['size']) ? clean_input($_POST['size']) : null,
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity'])
                ];

                // Handle main image upload
                if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadProductImage($_FILES['main_image']);
                    if ($upload_result['success']) {
                        $product_data['main_image'] = $upload_result['file_path'];
                    } else {
                        throw new Exception("Main image upload failed: " . $upload_result['error']);
                    }
                }

                // Handle gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['gallery_images']['name'][$key],
                                'tmp_name' => $tmp_name,
                                'size' => $_FILES['gallery_images']['size'][$key],
                                'type' => $_FILES['gallery_images']['type'][$key],
                                'error' => $_FILES['gallery_images']['error'][$key]
                            ];
                            $upload_result = uploadProductImage($file);
                            if ($upload_result['success']) {
                                $gallery_images[] = $upload_result['file_path'];
                            }
                        }
                    }
                }
                
                // Convert gallery images to string
                if (!empty($gallery_images)) {
                    $product_data['gallery_images'] = implode(',', $gallery_images);
                }

                // Generate slug from product name
                $base_slug = generateSlug($product_data['name']);
                $slug = $base_slug;
                $counter = 1;
                
                // Check if slug already exists and make it unique
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
                while (true) {
                    $stmt->execute([$slug]);
                    if ($stmt->fetchColumn() == 0) {
                        break; // Slug is unique
                    }
                    $slug = $base_slug . '-' . ($counter++);
                }
                
                $product_data['slug'] = $slug;

                // Insert product
                $columns = implode(', ', array_keys($product_data));
                $placeholders = ':' . implode(', :', array_keys($product_data));
                $sql = "INSERT INTO products ($columns) VALUES ($placeholders)";
                
                $stmt = $pdo->prepare($sql);
                foreach ($product_data as $key => $value) {
                    $stmt->bindValue(':' . $key, $value);
                }
                
                $stmt->execute();
                
                $product_id = $pdo->lastInsertId();

                // Log inventory activity
                logInventoryChange($pdo, $product_id, 'Added', $product_data['stock_quantity']);

                // Send notifications for new/featured products
                try {
                    if (file_exists(__DIR__ . '/includes/NotificationEngine.php')) {
                        require_once __DIR__ . '/includes/NotificationEngine.php';
                        $notificationEngine = new NotificationEngine($pdo, $functions);
                        
                        // Prepare full product data with ID
                        $notification_data = array_merge($product_data, ['product_id' => $product_id]);
                        
                        // Send notification
                        $notify_result = $notificationEngine->notifyNewProduct($notification_data);
                        error_log("Product notification sent: " . json_encode($notify_result));
                    }
                } catch (Exception $e) {
                    error_log("Error sending product notification: " . $e->getMessage());
                }

                echo json_encode([
                    'success' => true, 
                    'message' => "Product '{$product_data['name']}' added successfully!",
                    'product_id' => $product_id
                ]);
                break;
                
            case 'update_product':
                // Validate product ID for update
                if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
                    throw new Exception("Invalid product ID.");
                }
                
                $product_id = intval($_POST['product_id']);
                
                // Check if product exists
                $check_stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
                $check_stmt->execute([$product_id]);
                if (!$check_stmt->fetch()) {
                    throw new Exception("Product not found.");
                }

                // Basic validation
                $required_fields = ['name', 'category_id', 'price', 'stock_quantity', 'description'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Please fill in all required fields.");
                    }
                }

                // Validate numeric fields
                if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
                    throw new Exception("Please enter a valid price.");
                }

                if (!is_numeric($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0) {
                    throw new Exception("Please enter a valid stock quantity.");
                }

                if (!empty($_POST['discount']) && (!is_numeric($_POST['discount']) || $_POST['discount'] < 0 || $_POST['discount'] > 100)) {
                    throw new Exception("Discount must be between 0 and 100 percent.");
                }

                // Check if SKU already exists (excluding current product)
                if (!empty($_POST['sku'])) {
                    $sku = clean_input($_POST['sku']);
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ? AND product_id != ?");
                    $stmt->execute([$sku, $product_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("SKU already exists. Please use a different SKU.");
                    }
                }

                // Prepare product data for update
                $product_data = [
                    'category_id' => intval($_POST['category_id']),
                    'name' => clean_input($_POST['name']),
                    'brand' => !empty($_POST['brand']) ? clean_input($_POST['brand']) : null,
                    'description' => clean_input($_POST['description']),
                    'short_description' => !empty($_POST['short_description']) ? clean_input($_POST['short_description']) : null,
                    'sku' => !empty($_POST['sku']) ? clean_input($_POST['sku']) : null,
                    'price' => floatval($_POST['price']),
                    'discount' => !empty($_POST['discount']) ? floatval($_POST['discount']) : 0.00,
                    'is_new' => isset($_POST['is_new']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'color' => !empty($_POST['color']) ? clean_input($_POST['color']) : null,
                    'size' => !empty($_POST['size']) ? clean_input($_POST['size']) : null,
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity'])
                ];

                // Handle main image upload
                if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadProductImage($_FILES['main_image']);
                    if ($upload_result['success']) {
                        $product_data['main_image'] = $upload_result['file_path'];
                        
                        // Delete old main image if exists
                        if (!empty($existing_product['main_image']) && file_exists($existing_product['main_image'])) {
                            unlink($existing_product['main_image']);
                        }
                    } else {
                        throw new Exception("Main image upload failed: " . $upload_result['error']);
                    }
                }

                // Handle gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['gallery_images']['name'][$key],
                                'tmp_name' => $tmp_name,
                                'size' => $_FILES['gallery_images']['size'][$key],
                                'type' => $_FILES['gallery_images']['type'][$key],
                                'error' => $_FILES['gallery_images']['error'][$key]
                            ];
                            $upload_result = uploadProductImage($file);
                            if ($upload_result['success']) {
                                $gallery_images[] = $upload_result['file_path'];
                            }
                        }
                    }
                    
                    if (!empty($gallery_images)) {
                        // Get existing gallery images and merge with new ones
                        $existing_gallery = !empty($existing_product['gallery_images']) ? explode(',', $existing_product['gallery_images']) : [];
                        $all_gallery_images = array_merge($existing_gallery, $gallery_images);
                        $product_data['gallery_images'] = implode(',', $all_gallery_images);
                    }
                }

                // Regenerate slug if product name has changed
                if ($product_data['name'] !== $existing_product['name']) {
                    $base_slug = generateSlug($product_data['name']);
                    $slug = $base_slug;
                    $counter = 1;
                    
                    // Check if slug already exists (excluding current product)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND product_id != ?");
                    while (true) {
                        $stmt->execute([$slug, $product_id]);
                        if ($stmt->fetchColumn() == 0) {
                            break; // Slug is unique
                        }
                        $slug = $base_slug . '-' . ($counter++);
                    }
                    
                    $product_data['slug'] = $slug;
                }

                // Build update query
                $update_fields = [];
                $update_values = [];
                
                foreach ($product_data as $key => $value) {
                    $update_fields[] = "$key = ?";
                    $update_values[] = $value;
                }
                
                $update_values[] = $product_id; // For WHERE clause
                
                $sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE product_id = ?";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute($update_values);

                // Log inventory activity if stock changed
                if ($existing_product && $existing_product['stock_quantity'] != $product_data['stock_quantity']) {
                    $quantity_change = $product_data['stock_quantity'] - $existing_product['stock_quantity'];
                    logInventoryChange($pdo, $product_id, 'Updated', $quantity_change);
                }

                // Send notifications when a previously existing product becomes discounted/featured/new
                $became_discounted = ($product_data['discount'] ?? 0) > 0 && (($existing_product['discount'] ?? 0) <= 0);
                $became_featured = ($product_data['is_featured'] ?? 0) == 1 && (($existing_product['is_featured'] ?? 0) == 0);
                $became_new = ($product_data['is_new'] ?? 0) == 1 && (($existing_product['is_new'] ?? 0) == 0);

                if ($became_discounted || $became_featured || $became_new) {
                    try {
                        if (file_exists(__DIR__ . '/includes/NotificationEngine.php')) {
                            require_once __DIR__ . '/includes/NotificationEngine.php';
                            $notificationEngine = new NotificationEngine($pdo, $functions);

                            // Merge existing data with updates so notifications show current values
                            $notification_data = array_merge($existing_product ?? [], $product_data, ['product_id' => $product_id]);

                            $notify_result = $notificationEngine->notifyNewProduct($notification_data);
                            error_log("Product update notification sent: " . json_encode($notify_result));
                        }
                    } catch (Exception $e) {
                        error_log("Error sending product update notification: " . $e->getMessage());
                    }
                }

                echo json_encode([
                    'success' => true, 
                    'message' => "Product '{$product_data['name']}' updated successfully!",
                    'product_id' => $product_id
                ]);
                break;
                
            case 'delete_gallery_image':
                // Handle gallery image deletion
                if (!isset($_POST['product_id']) || !isset($_POST['image_path'])) {
                    throw new Exception("Missing required parameters.");
                }
                
                $product_id = intval($_POST['product_id']);
                $image_path = $_POST['image_path'];
                
                // Get current gallery images
                $stmt = $pdo->prepare("SELECT gallery_images FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $gallery_images = !empty($product['gallery_images']) ? explode(',', $product['gallery_images']) : [];
                    $updated_gallery = array_filter($gallery_images, function($img) use ($image_path) {
                        return $img !== $image_path;
                    });
                    
                    // Update product
                    $stmt = $pdo->prepare("UPDATE products SET gallery_images = ? WHERE product_id = ?");
                    $result = $stmt->execute([implode(',', $updated_gallery), $product_id]);
                    
                    // Delete physical file
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Image deleted successfully.']);
                } else {
                    throw new Exception("Product not found.");
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid AJAX action.']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Helper functions
function generateSKU($pdo) {
    $prefix = 'SKU';
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    $sku = $prefix . $timestamp . $random;
    
    // Check if SKU already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return generateSKU($pdo);
        }
    } catch (PDOException $e) {
        // If there's an error, just return the generated SKU
    }
    
    return $sku;
}

function uploadProductImage($file) {
    $upload_dir = 'assets/uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file type
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
    $file_path = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => true, 'file_path' => $file_path];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

function logInventoryChange($pdo, $product_id, $action, $quantity) {
    try {
        $sql = "INSERT INTO inventory_log (product_id, action, quantity_changed) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $action, $quantity]);
    } catch (PDOException $e) {
        error_log("Inventory log error: " . $e->getMessage());
    }
}

function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    // Don't use htmlspecialchars here - store raw text, encode only on display
    return $data;
}

function generateSlug($text) {
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace spaces with hyphens
    $slug = str_replace(' ', '-', $slug);
    
    // Remove special characters, keep only alphanumeric and hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    
    return $slug;
}

// Initialize variables
$error_message = '';
$success_message = '';

// Check if database connection is available
if (!$pdo) {
    $error_message = "Database connection not available. Please check your configuration.";
} else {
    // Get categories for dropdown
    $categories = [];
    try {
        $stmt = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error loading categories: " . $e->getMessage();
    }

    // Get existing brands for datalist
    $brands = [];
    try {
        $stmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
        $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Silently fail for brands
    }
}

$page_title = $is_edit ? 'Edit Product' : 'Add New Product';
$meta_description = $is_edit ? 'Edit product details' : 'Add a new product to your inventory';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Header Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $is_edit ? 'Edit Product' : 'Add New Product'; ?>
                    </h1>
                    <p class="text-gray-600 mt-2">
                        <?php echo $is_edit ? 'Update the product details below' : 'Fill in the product details below to add to your inventory'; ?>
                    </p>
                    <?php if ($is_edit && $existing_product): ?>
                        <div class="mt-2 text-sm text-gray-500">
                            Product ID: <?php echo $existing_product['product_id']; ?> | 
                            SKU: <?php echo htmlspecialchars($existing_product['sku'] ?? 'N/A'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <a href="a_products.php" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Products
                    </a>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="ajax-messages"></div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg mb-6 flex items-center animate-fade-in">
                <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$pdo): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg mb-6">
                <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Database connection error. Please check your configuration.
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-8" id="product-form" <?php echo (!$pdo) ? 'onsubmit="return false;"' : ''; ?>>
            <?php if ($is_edit): ?>
                <input type="hidden" name="product_id" value="<?php echo $existing_product['product_id']; ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Basic Info & Images -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Basic Information Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Basic Information
                                <span class="text-red-500 ml-1">*</span>
                            </h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Product Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" required 
                                        value="<?php echo htmlspecialchars($existing_product['name'] ?? ($_POST['name'] ?? '')); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Enter product name">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                    <input type="text" name="sku" 
                                        value="<?php echo htmlspecialchars($existing_product['sku'] ?? ($_POST['sku'] ?? '')); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Auto-generated if empty">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate SKU</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo (($existing_product['category_id'] ?? ($_POST['category_id'] ?? '')) == $category['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                                    <input type="text" name="brand" list="brands" 
                                        value="<?php echo htmlspecialchars($existing_product['brand'] ?? ($_POST['brand'] ?? '')); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="Enter brand name">
                                    <datalist id="brands">
                                        <?php foreach ($brands as $brand_item): ?>
                                            <option value="<?php echo htmlspecialchars($brand_item); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                                <textarea name="short_description" rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    placeholder="Brief product description (appears in product listings)"><?php echo htmlspecialchars($existing_product['short_description'] ?? ($_POST['short_description'] ?? '')); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="description" required rows="8"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                                    placeholder="Detailed product description with features, specifications, etc."><?php echo htmlspecialchars($existing_product['description'] ?? ($_POST['description'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Inventory Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Pricing & Inventory
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Price (GHS) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-3 text-gray-500">₵</span>
                                        <input type="number" name="price" required step="0.01" min="0" 
                                            value="<?php echo $existing_product['price'] ?? ($_POST['price'] ?? ''); ?>"
                                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Discount (%)</label>
                                    <div class="relative">
                                        <span class="absolute right-3 top-3 text-gray-500">%</span>
                                        <input type="number" name="discount" min="0" max="100" step="0.01" 
                                            value="<?php echo $existing_product['discount'] ?? ($_POST['discount'] ?? '0.00'); ?>"
                                            class="w-full pr-10 pl-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                            placeholder="0">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Stock Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="stock_quantity" required min="0" 
                                        value="<?php echo $existing_product['stock_quantity'] ?? ($_POST['stock_quantity'] ?? '0'); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Images & Settings -->
                <div class="space-y-8">
                    <!-- Product Images Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Product Images
                            </h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Main Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Main Image</label>
                                <?php if ($is_edit && !empty($existing_product['main_image'])): ?>
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">Current Main Image:</p>
                                        <div class="relative inline-block group">
                                            <img src="<?php echo htmlspecialchars($existing_product['main_image']); ?>" 
                                                 class="w-32 h-32 object-cover rounded-lg border">
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center transition-colors hover:border-blue-500 cursor-pointer" 
                                     id="main-image-upload-area">
                                    <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-sm text-gray-600 mb-1">
                                        <?php echo $is_edit ? 'Upload New Main Image' : 'Upload Main Image'; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">Recommended: 500×500px</p>
                                    <input type="file" name="main_image" accept="image/*" class="hidden" id="main-file-input">
                                </div>
                                <div id="main-image-preview" class="mt-3"></div>
                            </div>

                            <!-- Gallery Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gallery Images</label>
                                <?php if ($is_edit && !empty($existing_product['gallery_images'])): ?>
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">Current Gallery Images:</p>
                                        <div class="grid grid-cols-3 gap-2 mb-3" id="existing-gallery">
                                            <?php 
                                            $gallery_images = explode(',', $existing_product['gallery_images']);
                                            foreach ($gallery_images as $image): 
                                                if (!empty($image)):
                                            ?>
                                                <div class="relative group">
                                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                                         class="w-full h-24 object-cover rounded-lg border">
                                                    <button type="button" 
                                                            onclick="deleteExistingImage('<?php echo $image; ?>')"
                                                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center transition-colors hover:border-blue-500 cursor-pointer" 
                                     id="gallery-images-upload-area">
                                    <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-sm text-gray-600 mb-1">
                                        <?php echo $is_edit ? 'Add More Gallery Images' : 'Upload Gallery Images'; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">Up to 5 additional images</p>
                                    <input type="file" name="gallery_images[]" multiple accept="image/*" class="hidden" id="gallery-files-input">
                                </div>
                                <div id="gallery-images-preview" class="grid grid-cols-3 gap-2 mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Attributes Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-orange-50 to-amber-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Product Attributes
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                                    <input type="text" name="color" 
                                        value="<?php echo htmlspecialchars($existing_product['color'] ?? ($_POST['color'] ?? '')); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="e.g., Black, Red">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
                                    <input type="text" name="size" 
                                        value="<?php echo htmlspecialchars($existing_product['size'] ?? ($_POST['size'] ?? '')); ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="e.g., Large, 42">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                <input type="number" name="weight" step="0.01" min="0" 
                                    value="<?php echo $existing_product['weight'] ?? ($_POST['weight'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Product Settings Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Product Settings
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_new" id="is_new" value="1" 
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                           <?php echo (($existing_product['is_new'] ?? ($_POST['is_new'] ?? 0)) == 1) ? 'checked' : ''; ?>>
                                    <label for="is_new" class="ml-2 text-sm font-medium text-gray-700">
                                        Mark as New Arrival
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                           <?php echo (($existing_product['is_featured'] ?? ($_POST['is_featured'] ?? 0)) == 1) ? 'checked' : ''; ?>>
                                    <label for="is_featured" class="ml-2 text-sm font-medium text-gray-700">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex flex-col space-y-3">
                            <button type="submit" id="submit-btn"
                                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3 px-6 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition flex items-center justify-center font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <?php echo $is_edit ? 'Update Product' : 'Add Product'; ?>
                            </button>
                            <a href="a_products.php"
                               class="w-full text-center text-gray-600 py-3 px-6 rounded-lg hover:bg-gray-50 transition font-medium">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// AJAX Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('product-form');
    const submitBtn = document.getElementById('submit-btn');
    
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Show confirmation modal
            const action = <?php echo $is_edit ? "'update'" : "'add'"; ?>;
            const actionText = <?php echo $is_edit ? "'Update'" : "'Add'"; ?>;
            
            showConfirmationModal(
                actionText + ' Product',
                'Are you sure you want to ' + action + ' this product?',
                async () => {
                    await submitForm(action);
                },
                {
                    type: 'info',
                    confirmText: actionText + ' Product',
                    cancelText: 'Cancel',
                    confirmColor: 'blue'
                }
            );
        });
    }
});

// Form validation
function validateForm() {
    const form = document.getElementById('product-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
            
            field.addEventListener('input', function() {
                this.classList.remove('border-red-500');
            });
        }
    });
    
    if (!isValid) {
        showNotification('Please fill in all required fields', 'error');
        const firstError = form.querySelector('.border-red-500');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

// AJAX form submission
async function submitForm(action) {
    const form = document.getElementById('product-form');
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.innerHTML;
    
    try {
        // Show loading state
        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${action === 'update' ? 'Updating Product...' : 'Adding Product...'}
        `;
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        formData.append('ajax_action', action === 'update' ? 'update_product' : 'add_product');
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            
            // Redirect to products page after success
            setTimeout(() => {
                window.location.href = 'a_products.php';
            }, 1500);
        } else {
            showNotification(result.message, 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Delete existing gallery image
async function deleteExistingImage(imagePath) {
    showConfirmationModal(
        'Delete Image',
        'Are you sure you want to delete this image?',
        async () => {
            try {
                const formData = new FormData();
                formData.append('ajax_action', 'delete_gallery_image');
                formData.append('product_id', <?php echo $is_edit ? $existing_product['product_id'] : '0'; ?>);
                formData.append('image_path', imagePath);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    // Remove the image element from DOM
                    document.querySelector(`img[src="${imagePath}"]`).closest('.relative').remove();
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting image:', error);
                showNotification('Error deleting image', 'error');
            }
        },
        {
            type: 'error',
            confirmText: 'Delete',
            cancelText: 'Cancel'
        }
    );
}

// Image Upload Functionality
function setupImageUpload(uploadAreaId, fileInputId, previewAreaId, isMultiple = false) {
    const uploadArea = document.getElementById(uploadAreaId);
    const fileInput = document.getElementById(fileInputId);
    const previewArea = document.getElementById(previewAreaId);
    
    if (!uploadArea || !fileInput || !previewArea) return;

    uploadArea.addEventListener('click', () => fileInput.click());
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('border-blue-500', 'bg-blue-50');
    });

    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        fileInput.files = e.dataTransfer.files;
        handleFiles(fileInput.files, previewArea, isMultiple);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files, previewArea, isMultiple);
    });
}

function handleFiles(files, previewArea, isMultiple) {
    const maxFiles = isMultiple ? 5 : 1;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (!isMultiple) {
        previewArea.innerHTML = '';
    }

    Array.from(files).slice(0, maxFiles).forEach(file => {
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type: ' + file.name, 'error');
            return;
        }

        if (file.size > maxSize) {
            showNotification('File too large: ' + file.name, 'error');
            return;
        }

        displayImagePreview(file, previewArea, isMultiple);
    });
}

function displayImagePreview(file, previewArea, isMultiple) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const previewDiv = document.createElement('div');
        previewDiv.className = 'relative group bg-gray-100 rounded-lg overflow-hidden';
        
        if (isMultiple) {
            previewDiv.className += ' col-span-1';
            previewDiv.innerHTML = `
                <img src="${e.target.result}" class="w-full h-24 object-cover">
                <button type="button" onclick="removeImage(this)" 
                    class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
        } else {
            previewDiv.innerHTML = `
                <img src="${e.target.result}" class="w-full h-48 object-cover">
                <button type="button" onclick="removeImage(this)" 
                    class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
        }
        
        previewArea.appendChild(previewDiv);
    };
    reader.readAsDataURL(file);
}

function removeImage(button) {
    button.closest('.relative').remove();
}

// Show notification function (uses the global toast system)
function showNotification(message, type = 'info') {
    try {
        if (window.toast && typeof window.toast[type] === 'function') {
            window.toast[type](message);
            return;
        }

        if (window.toast && typeof window.toast.show === 'function') {
            window.toast.show(message, type);
            return;
        }
    } catch (e) {
        console.error('Toast error:', e);
    }

    // Fallback simple notification
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Initialize image upload areas
document.addEventListener('DOMContentLoaded', function() {
    setupImageUpload('main-image-upload-area', 'main-file-input', 'main-image-preview', false);
    setupImageUpload('gallery-images-upload-area', 'gallery-files-input', 'gallery-images-preview', true);
});
</script>

<style>
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .grid-cols-1.lg\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
}

/* Loading state */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php require_once 'includes/footer.php'; ?>