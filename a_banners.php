<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Location: signin.php');
    exit;
}


$database = new Database();
$functions = new Functions();
$functions->setDatabase($database);

$action = $_GET['action'] ?? 'list';
$banner_id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'list';
    
    if ($action === 'save') {
        $banner_id = $_POST['banner_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $link_url = $_POST['link_url'] ?? '';
        $button_text = $_POST['button_text'] ?? 'Shop Now';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $position = (int)($_POST['position'] ?? 0);

        // Handle image upload
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file = $_FILES['banner_image'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($file_ext, $allowed_ext)) {
                $error = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP';
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = 'Image size must be less than 5MB';
            } else {
                $new_filename = 'banner_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if (!$error) {
            if (!$title) {
                $error = 'Title is required.';
            } elseif (!$image_url) {
                $error = 'Image is required. Please upload an image or provide a URL.';
            } else {
                $result = $functions->saveBanner($banner_id, $title, $description, $image_url, $link_url, $button_text, $is_active, $position);
                if ($result) {
                    $message = $banner_id ? 'Banner updated successfully!' : 'Banner created successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to save banner. Please try again.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $banner_id = $_POST['banner_id'] ?? null;
        if ($banner_id && $functions->deleteBanner($banner_id)) {
            $message = 'Banner deleted successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to delete banner.';
        }
    }
}

$banner = null;
if ($action === 'edit' && $banner_id) {
    $banner = $functions->getBannerById($banner_id);
    if (!$banner) {
        $error = 'Banner not found.';
        $action = 'list';
    }
}

$banners = $functions->getAllBanners();
?>
   <?php require_once 'includes/admin_header.php'; ?>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
     

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <div class="container mx-auto px-3 lg:px-4 max-w-7xl">
                    
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Banner Management</h1>
                                <p class="text-gray-600 mt-1">Manage homepage banners</p>
                            </div>
                            <?php if ($action === 'list'): ?>
                                <a href="?action=add" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                                    + Add Banner
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <!-- Banners List -->
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <?php if (!empty($banners)): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Title</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Position</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Created</th>
                                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <?php foreach ($banners as $b): ?>
                                                <tr class="hover:bg-gray-50 transition">
                                                    <td class="px-6 py-4 text-sm">
                                                        <div class="flex items-center gap-3">
                                                            <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="w-12 h-12 object-cover rounded">
                                                            <div>
                                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($b['title']); ?></p>
                                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($b['description'], 0, 50) . '...'); ?></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo $b['position']; ?></td>
                                                    <td class="px-6 py-4 text-sm">
                                                        <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?php echo $b['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                                                            <?php echo $b['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                                                    <td class="px-6 py-4 text-right text-sm">
                                                        <a href="?action=edit&id=<?php echo $b['banner_id']; ?>" class="text-purple-600 hover:text-purple-700 font-medium">Edit</a>
                                                        <form method="POST" class="inline-block ml-3" onsubmit="return confirm('Delete this banner?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="banner_id" value="<?php echo $b['banner_id']; ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-8 text-center text-gray-500">
                                    <p>No banners found. <a href="?action=add" class="text-purple-600 hover:text-purple-700">Create one</a></p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($action === 'add' || $action === 'edit'): ?>
                        <!-- Banner Form -->
                        <div class="bg-white rounded-lg shadow-sm p-8 max-w-2xl items-center mx-auto">
                            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="banner_id" value="<?php echo $banner['banner_id'] ?? ''; ?>">
                                <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($banner['image_url'] ?? ''); ?>">

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                                    <input type="text" name="title" required value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                                        placeholder="e.g., Summer Sale">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea name="description" rows="3" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                                        placeholder="e.g., Limited time offer on selected items"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Banner Image *</label>
                                    <div class="space-y-3">
                                        <input type="file" name="banner_image" accept="image/*" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                                            onchange="previewImage(event)">
                                        <p class="text-xs text-gray-500">Upload JPG, PNG, GIF or WEBP (Max 5MB). Recommended size: 1920x600px</p>
                                        <?php if ($banner && !empty($banner['image_url'])): ?>
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">Current Image:</p>
                                                <img id="imagePreview" src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Preview" class="max-h-40 rounded border">
                                            </div>
                                        <?php else: ?>
                                            <img id="imagePreview" src="" alt="" class="max-h-40 rounded border hidden">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
                                    <input type="text" name="link_url" value="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                                        placeholder="products.php?sort=newest or https://example.com">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
                                    <input type="text" name="button_text" value="<?php echo htmlspecialchars($banner['button_text'] ?? 'Shop Now'); ?>" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500"
                                        placeholder="Shop Now">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                                        <input type="number" name="position" value="<?php echo $banner['position'] ?? 0; ?>" min="0" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500">
                                    </div>

                                    <div class="flex items-end">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_active" <?php echo ($banner['is_active'] ?? 1) ? 'checked' : ''; ?> class="w-4 h-4 rounded text-purple-600">
                                            <span class="text-sm font-medium text-gray-700">Active</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                                        <?php echo $action === 'edit' ? 'Update Banner' : 'Create Banner'; ?>
                                    </button>
                                    <a href="a_banners.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">Cancel</a>
                                </div>
                            </form>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
<?php require_once 'includes/footer.php'; ?>
