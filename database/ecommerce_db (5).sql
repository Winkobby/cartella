-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 04:51 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `banner_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `button_text` varchar(50) DEFAULT 'Shop Now',
  `is_active` tinyint(1) DEFAULT 1,
  `position` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`banner_id`, `title`, `description`, `image_url`, `link_url`, `button_text`, `is_active`, `position`, `created_at`, `updated_at`) VALUES
(1, 'Summer Sale! Up to 50% Off', 'Limited time offer on selected items', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'products.php?filter=discounted', 'Shop Sale', 1, 1, '2025-12-10 04:26:20', '2025-12-10 04:26:20'),
(2, 'New Arrivals', 'Check out the latest products added to our store', 'assets/uploads/banner_1765416207_693a1d0f055a0.jpg', 'products.php?sort=newest', 'View New', 1, 2, '2025-12-10 04:26:20', '2025-12-11 01:23:27'),
(3, 'Free Shipping Offer', 'Get free shipping on orders over GHS500', 'https://images.unsplash.com/photo-1576941089067-2de3e3692519?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'products.php', 'Start Shopping', 1, 3, '2025-12-10 04:26:20', '2025-12-10 04:26:20');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `slug`, `description`) VALUES
(1, 'Electronics', 'electronics', 'Smartphones, laptops, and accessories'),
(2, 'Fashion', 'fashion', 'Clothes, shoes, and accessories'),
(3, 'Groceries', 'groceries', 'Daily household essentials'),
(4, 'Perfumes', 'perfumes', 'Body spray and duodurant'),
(5, 'Health & Beauty', 'health-beauty', 'Beauty & personal care'),
(6, 'Furnitures', 'furnitures', 'Furniture for rooms and other lucrative sites'),
(9, 'Phones & Tablets', 'phones-tablets', ''),
(10, 'Home & Kitchen', 'home-kitchen', 'Home management');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `subject`, `message`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Technical Support', 'Finding it difficult to process payment, kindly check the order status and confirm my payment', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'replied', '2025-12-07 01:23:34'),
(2, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Partnership', 'Please i want to partner with you in supplying you goods and services', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'new', '2025-12-07 05:08:05');

-- --------------------------------------------------------

--
-- Table structure for table `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed','shipping') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `user_usage_limit` int(11) DEFAULT 1,
  `applicable_categories` text DEFAULT NULL,
  `excluded_categories` text DEFAULT NULL,
  `applicable_products` text DEFAULT NULL,
  `excluded_products` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `start_date`, `end_date`, `usage_limit`, `used_count`, `user_usage_limit`, `applicable_categories`, `excluded_categories`, `applicable_products`, `excluded_products`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Welcome discount for new customers', 'percentage', '10.00', '50.00', '20.00', '2025-01-01 00:00:00', '2026-12-31 23:59:59', 1000, 46, 1, NULL, NULL, NULL, NULL, 1, '2025-11-25 10:00:00', '2025-11-26 10:18:18'),
(2, 'SAVE5', 'Flat GHS 5 off on any order', 'fixed', '5.00', '25.00', NULL, '2025-01-01 00:00:00', '2026-12-31 23:59:59', 5000, 123, 1, NULL, NULL, NULL, NULL, 1, '2025-11-25 10:00:00', '2025-11-25 10:00:00'),
(4, 'SUMMER25', 'Summer special 25% off', 'percentage', '25.00', '100.00', '50.00', '2025-06-01 00:00:00', '2025-08-31 23:59:59', 200, 67, 1, '[\"summer\",\"clothing\"]', '[\"electronics\"]', NULL, NULL, 0, '2025-11-25 10:00:00', '2025-12-08 03:49:23'),
(5, 'FIRSTORDER', '15% off on first order', 'percentage', '15.00', '30.00', '25.00', '2025-01-01 00:00:00', '2026-12-31 23:59:59', NULL, 235, 1, NULL, NULL, NULL, NULL, 1, '2025-11-25 10:00:00', '2025-12-07 07:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `usage_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coupon_usage`
--

INSERT INTO `coupon_usage` (`usage_id`, `coupon_id`, `user_id`, `order_id`, `discount_amount`, `used_at`) VALUES
(1, 1, 3, 75, '20.00', '2025-11-26 10:18:18'),
(2, 5, 3, 76, '25.00', '2025-11-29 17:11:13');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `log_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action` enum('Added','Removed','Sold','Returned') NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `log_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`log_id`, `product_id`, `action`, `quantity_changed`, `log_date`) VALUES
(1, 7, 'Added', 100, '2025-11-27 14:38:13'),
(2, 8, 'Added', 10, '2025-11-27 22:48:15'),
(3, 9, 'Added', 3, '2025-11-28 02:36:58'),
(4, 10, 'Added', 12, '2025-11-28 03:29:05'),
(5, 11, 'Added', 10, '2025-11-28 04:20:12'),
(6, 12, 'Added', 50, '2025-11-28 04:35:08'),
(7, 13, 'Added', 1, '2025-11-28 04:42:47'),
(8, 14, 'Added', 5, '2025-11-28 04:46:29'),
(9, 15, 'Added', 5, '2025-11-28 04:59:32'),
(10, 16, 'Added', 10, '2025-11-28 05:16:04'),
(11, 17, 'Added', 40, '2025-11-28 05:36:46'),
(12, 18, 'Added', 40, '2025-11-28 21:04:12'),
(13, 18, '', -13, '2025-12-01 08:19:09'),
(14, 15, 'Returned', 1, '2025-12-01 15:43:54'),
(15, 8, 'Returned', 1, '2025-12-01 15:43:54'),
(16, 15, 'Returned', 1, '2025-12-01 15:44:15'),
(17, 8, 'Returned', 1, '2025-12-01 15:44:15'),
(18, 15, 'Returned', 1, '2025-12-01 15:44:19'),
(19, 8, 'Returned', 1, '2025-12-01 15:44:19'),
(20, 2, '', -46, '2025-12-02 13:30:01'),
(21, 5, '', 4, '2025-12-02 13:33:15'),
(22, 19, 'Added', 10, '2025-12-02 21:42:51'),
(23, 20, 'Added', 10, '2025-12-02 21:45:57'),
(24, 21, 'Added', 22, '2025-12-03 10:49:18'),
(25, 22, 'Added', 10, '2025-12-03 10:54:01'),
(26, 23, 'Added', 10, '2025-12-03 19:13:54'),
(27, 24, 'Added', 5, '2025-12-03 19:19:52'),
(28, 25, 'Added', 9, '2025-12-03 19:46:02'),
(29, 26, 'Added', 20, '2025-12-04 17:52:14'),
(30, 20, 'Returned', 1, '2025-12-09 03:44:42'),
(31, 21, 'Returned', 1, '2025-12-09 03:44:42'),
(32, 22, 'Returned', 1, '2025-12-09 03:44:42'),
(33, 15, 'Returned', 1, '2025-12-09 03:44:42'),
(34, 20, 'Returned', 1, '2025-12-09 03:45:13'),
(35, 21, 'Returned', 1, '2025-12-09 03:45:13'),
(36, 22, 'Returned', 1, '2025-12-09 03:45:13'),
(37, 15, 'Returned', 1, '2025-12-09 03:45:13'),
(38, 20, 'Returned', 1, '2025-12-09 03:45:28'),
(39, 21, 'Returned', 1, '2025-12-09 03:45:28'),
(40, 22, 'Returned', 1, '2025-12-09 03:45:28'),
(41, 15, 'Returned', 1, '2025-12-09 03:45:28'),
(42, 27, 'Added', 50, '2025-12-09 15:12:06'),
(43, 28, 'Added', 10, '2025-12-09 16:04:18'),
(44, 29, 'Added', 4, '2025-12-09 16:29:24'),
(45, 30, 'Added', 10, '2025-12-10 04:10:03'),
(46, 31, 'Added', 10, '2025-12-11 02:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscription_status` enum('active','inactive','unsubscribed') DEFAULT 'active',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`subscriber_id`, `email`, `subscription_status`, `subscribed_at`, `unsubscribed_at`, `token`) VALUES
(1, 'osahgodwin34@gmail.com', 'active', '2025-12-09 02:27:49', NULL, '8bb78dbc66b9f8b1c5ae716456886d677b7caed148fe9dfd2a9cdeeb87cc539b'),
(2, '220005083@st.uew.edu.gh', 'active', '2025-11-26 14:35:51', NULL, '74cc89ac95b9cdf52514369a53aba685ce061e79c20d659bef1c07f7a83dc61c'),
(3, '202100661@st.uew.edu.gh', 'active', '2025-12-01 14:05:17', NULL, 'cae0290b326fe742165c968a123856bbf23bf7149210a54cd67c2da17852fed7');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_region` varchar(100) NOT NULL,
  `shipping_postal_code` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `momo_phone` varchar(20) DEFAULT NULL,
  `momo_network` varchar(50) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `coupon_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `shipping_city`, `shipping_region`, `shipping_postal_code`, `payment_method`, `payment_status`, `momo_phone`, `momo_network`, `subtotal`, `shipping_cost`, `tax_amount`, `discount_amount`, `total_amount`, `status`, `coupon_id`, `order_date`, `updated_at`) VALUES
(18, 'ORD2025112369230F4899C0F', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Ashanti', '', 'mtn_momo', 'pending', '0543212041', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 13:42:32', '2025-11-23 13:42:32'),
(19, 'ORD202511236923100A48B01', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0543212041', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 13:45:46', '2025-11-23 13:45:46'),
(20, 'ORD20251123692311735FC29', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0543212041', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 13:51:47', '2025-11-23 13:51:47'),
(22, 'ORD20251123692315ECE3F94', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', '', '', '', 'mtn_momo', 'pending', '+233241311105', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 14:10:52', '2025-11-23 14:10:52'),
(26, 'ORD20251123692317E1B61D5', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0559280979', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 14:19:13', '2025-11-23 14:19:13'),
(27, 'ORD20251123692319216DEA5', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0559280979', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 14:24:33', '2025-11-23 14:24:33'),
(28, 'ORD2025112369231987561D9', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0256601548', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'pending', NULL, '2025-11-23 14:26:15', '2025-11-23 14:26:15'),
(29, 'ORD2025112369231CB969BAB', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'completed', '0534689326', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'shipped', NULL, '2025-11-23 14:39:53', '2025-12-09 06:54:55'),
(32, 'ORD2025112369232535A25C7', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'mtn_momo', 'pending', '0535935433', 'MTN', '0.90', '0.00', '0.00', '0.00', '0.90', 'processing', NULL, '2025-11-23 15:16:05', '2025-11-23 15:16:28'),
(42, 'ORD202511246923ADE610309', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'paystack_inline', 'completed', NULL, NULL, '2.80', '0.00', '0.00', '0.00', '2.80', 'processing', NULL, '2025-11-24 00:59:18', '2025-11-26 01:08:24'),
(43, 'ORD202511246923B1FC62312', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'paystack_inline', 'completed', NULL, NULL, '200.00', '0.00', '0.00', '0.00', '200.00', 'processing', NULL, '2025-11-24 01:16:44', '2025-11-26 01:08:24'),
(45, 'ORD202511246923C0ECB532B', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', 'Kasseh', 'Ashanti', '', 'paystack_inline', 'completed', NULL, NULL, '9225.50', '0.00', '0.00', '0.00', '9225.50', 'processing', NULL, '2025-11-24 02:20:28', '2025-11-26 01:08:24'),
(46, 'ORD2025112469243DDC419EF', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'ACCRA, GHANA', '', '', '', 'paystack_inline', 'completed', NULL, NULL, '884.00', '0.00', '0.00', '0.00', '884.00', 'delivered', NULL, '2025-11-24 11:13:32', '2025-11-27 17:59:01'),
(47, 'ORD20251125692542E05CC4C', 4, 'Carl Osah', '220005083@st.uew.edu.gh', '0543212041', 'ACCRA, GHANA', 'Kasseh', 'Greater Accra', '', 'paystack_inline', 'completed', NULL, NULL, '260.00', '0.00', '0.00', '0.00', '260.00', 'processing', NULL, '2025-11-25 05:47:12', '2025-11-26 01:08:24'),
(49, 'ORD2025112569258ABDD622B', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '224.00', '0.00', '0.00', '0.00', '224.00', 'processing', NULL, '2025-11-25 10:53:49', '2025-11-26 01:08:24'),
(55, 'ORD202511256925968125E93', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '224.00', '0.00', '0.00', '0.00', '224.00', 'processing', NULL, '2025-11-25 11:44:01', '2025-11-26 01:08:24'),
(56, 'ORD202511256925D507BA20B', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '897.80', '0.00', '0.00', '0.00', '897.80', 'processing', NULL, '2025-11-25 16:10:47', '2025-11-26 01:08:24'),
(63, 'ORD20251126692667A5E6496', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '449.40', '0.00', '0.00', '20.00', '429.40', 'processing', 1, '2025-11-26 02:36:21', '2025-11-26 02:53:26'),
(64, 'ORD20251126692669764B3B1', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '64.00', '0.00', '0.00', '6.40', '57.60', 'processing', 1, '2025-11-26 02:44:06', '2025-11-26 02:53:26'),
(67, 'ORD2025112669266E7605BB4', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'delivered', NULL, '2025-11-26 03:05:26', '2025-11-27 18:22:50'),
(68, 'ORD20251126692671AC652F7', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'processing', NULL, '2025-11-26 03:19:08', '2025-11-26 03:19:16'),
(69, 'ORD20251126692671E0EB50A', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'shipped', NULL, '2025-11-26 03:20:00', '2025-12-09 03:25:55'),
(70, 'ORD2025112669267214001DD', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'processing', NULL, '2025-11-26 03:20:52', '2025-11-26 03:21:07'),
(71, 'ORD202511266926729925A8C', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'delivered', NULL, '2025-11-26 03:23:05', '2025-11-27 18:26:37'),
(73, 'ORD20251126692673710C6D5', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Upper East \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '0.00', '288.00', 'delivered', NULL, '2025-11-26 03:26:41', '2025-11-27 18:26:37'),
(74, 'ORD202511266926753E29434', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                 ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '260.00', '0.00', '0.00', '0.00', '260.00', 'shipped', NULL, '2025-11-26 03:34:22', '2025-11-27 18:14:31'),
(75, 'ORD202511266926D3EA1A598', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                             ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '288.00', '0.00', '0.00', '20.00', '268.00', 'processing', 1, '2025-11-26 10:18:18', '2025-11-26 10:23:32'),
(76, 'ORD20251129692B2931EDC13', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '3031.00', '0.00', '0.00', '25.00', '3006.00', 'shipped', 5, '2025-11-29 17:11:13', '2025-11-29 17:22:47'),
(94, 'ORD20251201692DAE671FEAB', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '2194.20', '0.00', '0.00', '0.00', '2194.20', 'delivered', NULL, '2025-12-01 15:04:07', '2025-12-01 15:54:41'),
(95, 'ORD20251201692DB13440BB0', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '485.74', '0.00', '0.00', '0.00', '485.74', 'delivered', NULL, '2025-12-01 15:16:04', '2025-12-02 12:37:51'),
(96, 'ORD20251201692DC2629C40C', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Adasco', '\n                                    Ada', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '2379.20', '0.00', '0.00', '0.00', '2379.20', 'delivered', NULL, '2025-12-01 16:29:22', '2025-12-01 16:32:17'),
(97, 'ORD20251201692DC5D40F9A6', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '1172.74', '0.00', '0.00', '0.00', '1172.74', 'delivered', NULL, '2025-12-01 16:44:04', '2025-12-09 03:55:15'),
(106, 'ORD20251203693041CC862B2', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '4730.00', '0.00', '0.00', '0.00', '4730.00', 'shipped', NULL, '2025-12-03 13:57:32', '2025-12-05 13:52:46'),
(109, 'ORD2025120369304740D3E80', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'pending', NULL, '2025-12-03 14:20:48', '2025-12-03 14:20:48'),
(110, 'ORD202512036930479C48AC8', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'pending', NULL, '2025-12-03 14:22:27', '2025-12-09 03:54:04'),
(111, 'ORD202512036930486C7DBEE', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'cancelled', NULL, '2025-12-03 14:25:48', '2025-12-08 07:39:45'),
(112, 'ORD20251203693049FDAFA66', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'cancelled', NULL, '2025-12-03 14:32:30', '2025-12-09 03:45:28'),
(113, 'ORD2025120369304A1FC0DB8', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'pending', NULL, '2025-12-03 14:33:04', '2025-12-03 14:33:04'),
(114, 'ORD2025120369304C0462F3C', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '2943.85', '0.00', '0.00', '0.00', '2943.85', 'shipped', NULL, '2025-12-03 14:41:08', '2025-12-05 13:52:46'),
(115, 'ORD2025120369304D92B6A99', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Adasco', '\n                                    Ada', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '204.85', '20.00', '0.00', '0.00', '224.85', 'shipped', NULL, '2025-12-03 14:47:46', '2025-12-05 13:52:46'),
(116, 'ORD2025120369304F9600750', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '280.00', '20.00', '0.00', '0.00', '300.00', 'shipped', NULL, '2025-12-03 14:56:22', '2025-12-05 13:52:46'),
(117, 'ORD2025120369305240E047C', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Adasco', '\n                                    Ada', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '187.74', '20.00', '0.00', '0.00', '207.74', 'delivered', NULL, '2025-12-03 15:07:44', '2025-12-09 03:19:55'),
(118, 'ORD20251203693053B930983', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '182.00', '20.00', '0.00', '0.00', '202.00', 'delivered', NULL, '2025-12-03 15:14:01', '2025-12-07 06:12:38'),
(119, 'ORD20251203693056D7B7984', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', 'Adasco', '\n                                    Ada', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '200.00', '20.00', '0.00', '0.00', '220.00', 'delivered', NULL, '2025-12-03 15:27:19', '2025-12-09 03:15:06'),
(120, 'ORD2025120369309AD972ACC', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '599.00', '0.00', '0.00', '0.00', '599.00', 'delivered', NULL, '2025-12-03 20:17:29', '2025-12-09 02:36:00'),
(121, 'ORD2025120569328318981D0', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'pending', NULL, NULL, '432.80', '20.00', '0.00', '0.00', '452.80', 'processing', NULL, '2025-12-05 07:00:40', '2025-12-07 17:17:52'),
(122, 'ORD2025120569328446E6150', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '432.80', '20.00', '0.00', '0.00', '452.80', 'shipped', NULL, '2025-12-05 07:05:42', '2025-12-05 13:52:46'),
(123, 'ORD2025120569328B7CC1F2C', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '203.40', '20.00', '0.00', '0.00', '223.40', 'delivered', NULL, '2025-12-05 07:36:29', '2025-12-07 03:59:37'),
(125, 'ORD202512106938E77A191C7', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '244.60', '20.00', '0.00', '0.00', '264.60', 'processing', NULL, '2025-12-10 03:22:34', '2025-12-10 03:22:54'),
(126, 'ORD20251211693A3919455D3', 3, 'Godwin Osah', 'osahgodwin34@gmail.com', '+233241311105', '105 Olonka Street, Kasseh Ada', '\n                                    Kasseh', 'Greater Accra \n                                    ', '00233\n              ', 'paystack_inline', 'completed', NULL, NULL, '254.80', '20.00', '0.00', '0.00', '274.80', 'processing', NULL, '2025-12-11 03:23:05', '2025-12-11 03:23:31');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `product_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `total_price`, `price`, `color`, `size`) VALUES
(4, 18, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(5, 19, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(6, 20, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(8, 22, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(12, 26, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(13, 27, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(14, 28, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(15, 29, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(18, 32, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(29, 42, 3, NULL, NULL, 2, NULL, '0.50', NULL, NULL),
(30, 42, 2, NULL, NULL, 2, NULL, '0.90', NULL, NULL),
(31, 43, 4, NULL, NULL, 2, NULL, '100.00', NULL, NULL),
(35, 45, 3, NULL, NULL, 1, NULL, '0.50', NULL, NULL),
(36, 45, 1, NULL, NULL, 1, NULL, '9025.00', NULL, NULL),
(37, 45, 4, NULL, NULL, 2, NULL, '100.00', NULL, NULL),
(38, 46, 5, NULL, NULL, 2, NULL, '160.00', NULL, NULL),
(39, 46, 4, NULL, NULL, 5, NULL, '100.00', NULL, NULL),
(40, 46, 6, NULL, NULL, 1, NULL, '64.00', NULL, NULL),
(41, 47, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(42, 47, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(45, 49, 6, NULL, NULL, 1, NULL, '64.00', NULL, NULL),
(46, 49, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(60, 55, 6, NULL, NULL, 1, NULL, '64.00', NULL, NULL),
(61, 55, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(62, 56, 2, NULL, NULL, 2, NULL, '0.90', NULL, NULL),
(63, 56, 6, NULL, NULL, 4, NULL, '64.00', NULL, NULL),
(64, 56, 5, NULL, NULL, 4, NULL, '160.00', NULL, NULL),
(69, 63, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(70, 63, 5, NULL, NULL, 2, NULL, '160.00', NULL, NULL),
(71, 63, 2, NULL, NULL, 1, NULL, '0.90', NULL, NULL),
(72, 63, 3, NULL, NULL, 1, NULL, '0.50', NULL, NULL),
(73, 64, 6, NULL, NULL, 1, NULL, '64.00', NULL, NULL),
(80, 67, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(81, 67, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(82, 68, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(83, 68, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(84, 69, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(85, 69, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(86, 70, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(87, 70, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(88, 71, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(89, 71, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(92, 73, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(93, 73, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(94, 74, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(95, 74, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(96, 75, 6, NULL, NULL, 2, NULL, '64.00', NULL, NULL),
(97, 75, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(98, 76, 6, NULL, NULL, 2, NULL, '168.00', NULL, NULL),
(99, 76, 5, NULL, NULL, 1, NULL, '160.00', NULL, NULL),
(100, 76, 12, NULL, NULL, 1, NULL, '2535.00', NULL, NULL),
(135, 94, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(136, 94, 8, NULL, NULL, 1, NULL, '2095.20', NULL, NULL),
(137, 95, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(138, 95, 16, NULL, NULL, 2, NULL, '93.87', NULL, NULL),
(139, 95, 15, NULL, NULL, 2, NULL, '99.00', NULL, NULL),
(140, 96, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(141, 96, 13, NULL, NULL, 1, NULL, '185.00', NULL, NULL),
(142, 96, 8, NULL, NULL, 1, NULL, '2095.20', NULL, NULL),
(143, 97, 16, NULL, NULL, 1, NULL, '93.87', NULL, NULL),
(144, 97, 14, NULL, NULL, 1, NULL, '281.20', NULL, NULL),
(145, 97, 10, NULL, NULL, 1, NULL, '797.67', NULL, NULL),
(154, 106, 19, NULL, NULL, 1, NULL, '4730.00', NULL, NULL),
(163, 109, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(164, 109, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(165, 109, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(166, 109, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(167, 110, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(168, 110, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(169, 110, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(170, 110, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(171, 111, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(172, 111, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(173, 111, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(174, 111, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(175, 112, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(176, 112, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(177, 112, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(178, 112, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(179, 113, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(180, 113, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(181, 113, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(182, 113, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(183, 114, 20, NULL, NULL, 1, NULL, '2640.00', NULL, NULL),
(184, 114, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(185, 114, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(186, 114, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(187, 115, 22, NULL, NULL, 1, NULL, '113.85', NULL, NULL),
(188, 115, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(189, 116, 3, NULL, NULL, 2, NULL, '140.00', NULL, NULL),
(190, 117, 16, NULL, NULL, 2, NULL, '93.87', NULL, NULL),
(191, 118, 21, NULL, NULL, 2, NULL, '91.00', NULL, NULL),
(192, 119, 4, NULL, NULL, 2, NULL, '100.00', NULL, NULL),
(193, 120, 21, NULL, NULL, 1, NULL, '91.00', NULL, NULL),
(194, 120, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(195, 120, 9, NULL, NULL, 2, NULL, '204.00', NULL, NULL),
(196, 121, 26, NULL, NULL, 3, NULL, '80.60', NULL, NULL),
(197, 121, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(198, 121, 21, NULL, NULL, 1, NULL, '91.00', NULL, '39'),
(199, 122, 26, NULL, NULL, 3, NULL, '80.60', NULL, NULL),
(200, 122, 4, NULL, NULL, 1, NULL, '100.00', NULL, NULL),
(201, 122, 21, NULL, NULL, 1, NULL, '91.00', NULL, '39'),
(202, 123, 25, NULL, NULL, 2, NULL, '101.70', NULL, '(L x W x H cm): N/A'),
(206, 125, 27, NULL, NULL, 2, NULL, '72.80', NULL, NULL),
(207, 125, 15, NULL, NULL, 1, NULL, '99.00', NULL, NULL),
(208, 126, 31, NULL, NULL, 1, NULL, '156.00', NULL, NULL),
(209, 126, 30, NULL, NULL, 1, NULL, '98.80', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_log`
--

CREATE TABLE `order_status_log` (
  `log_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_status_log`
--

INSERT INTO `order_status_log` (`log_id`, `order_id`, `status`, `notes`, `updated_by`, `created_at`) VALUES
(1, 74, 'shipped', '', NULL, '2025-11-27 18:14:31'),
(2, 67, 'delivered', '', NULL, '2025-11-27 18:22:50'),
(3, 76, 'shipped', 'Shipped 29th November 2025', 6, '2025-11-29 17:22:47'),
(4, 94, 'shipped', '', 6, '2025-12-01 15:38:22'),
(6, 94, 'delivered', '', 6, '2025-12-01 15:54:41'),
(7, 123, 'delivered', '', 6, '2025-12-07 03:59:37'),
(8, 118, 'delivered', '', 6, '2025-12-07 06:12:38'),
(9, 121, 'processing', '', 6, '2025-12-07 17:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`, `used`) VALUES
(3, 'osahgodwin34@gmail.com', '3b2c52f6269d0f82df65fd8206795f31d4e9ae217d399c6533a0e73144d8ec9b88766b87a0e231af78225de4e5343e93e802', '2025-11-26 16:45:38', '2025-11-26 17:45:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` enum('paystack','flutterwave','custom') DEFAULT 'paystack',
  `momo_number` varchar(20) DEFAULT NULL,
  `momo_network` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `provider_reference` varchar(255) DEFAULT NULL,
  `verification_attempts` int(11) DEFAULT 0,
  `last_verification_attempt` timestamp NULL DEFAULT NULL,
  `requires_otp` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `amount`, `payment_status`, `payment_date`, `payment_method`, `momo_number`, `momo_network`, `transaction_id`, `provider_reference`, `verification_attempts`, `last_verification_attempt`, `requires_otp`, `updated_at`, `created_at`) VALUES
(18, 18, '0.90', 'pending', '2025-11-23 13:42:32', '', NULL, NULL, NULL, 'MOMO_1763905352_18', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(19, 19, '0.90', 'pending', '2025-11-23 13:45:46', '', NULL, NULL, NULL, 'MOMO_1763905546_19', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(20, 20, '0.90', 'pending', '2025-11-23 13:51:47', '', NULL, NULL, NULL, 'MOMO_1763905907_20', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(22, 22, '0.90', '', '2025-11-23 14:10:52', '', NULL, NULL, NULL, 'MOMO_1763907052_22', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(26, 26, '0.90', '', '2025-11-23 14:19:13', '', NULL, NULL, NULL, 'MOMO_1763907553_26', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(27, 27, '0.90', '', '2025-11-23 14:24:33', '', NULL, NULL, NULL, 'MOMO_1763907873_27', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(28, 28, '0.90', '', '2025-11-23 14:26:15', '', NULL, NULL, NULL, 'MOMO_1763907975_28', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(29, 29, '0.90', 'completed', '2025-11-23 14:39:53', '', NULL, NULL, NULL, 'MOMO_1763908793_29', 0, NULL, 0, '2025-12-09 06:54:33', '2025-11-23 16:15:58'),
(32, 32, '0.90', '', '2025-11-23 15:16:05', '', NULL, NULL, NULL, 'MOMO_1763910965_32', 0, NULL, 0, '2025-11-23 16:15:58', '2025-11-23 16:15:58'),
(42, 42, '2.80', 'completed', '2025-11-24 00:59:18', '', NULL, NULL, NULL, 'ORD_42_1763945958285', 0, NULL, 0, '2025-11-24 00:59:38', '2025-11-24 00:59:18'),
(43, 43, '200.00', 'completed', '2025-11-24 01:16:45', '', NULL, NULL, NULL, 'ORD_43_1763947006023', 0, NULL, 0, '2025-11-24 01:17:28', '2025-11-24 01:16:45'),
(45, 45, '9225.50', 'completed', '2025-11-24 02:20:28', '', NULL, NULL, NULL, 'ORD_45_1763950829204', 0, NULL, 0, '2025-11-24 02:20:40', '2025-11-24 02:20:28'),
(46, 46, '884.00', 'completed', '2025-11-24 11:13:32', '', NULL, NULL, NULL, 'ORD_46_1763982812410', 0, NULL, 0, '2025-11-24 11:13:45', '2025-11-24 11:13:32'),
(47, 47, '260.00', 'completed', '2025-11-25 05:47:12', '', NULL, NULL, NULL, 'ORD_47_1764049632558', 0, NULL, 0, '2025-11-25 05:47:21', '2025-11-25 05:47:12'),
(49, 49, '224.00', 'completed', '2025-11-25 10:53:49', '', NULL, NULL, NULL, 'ORD_49_1764068029919', 0, NULL, 0, '2025-11-25 10:53:59', '2025-11-25 10:53:49'),
(55, 55, '224.00', 'completed', '2025-11-25 11:44:01', '', NULL, NULL, NULL, 'ORD_55_1764071041197', 0, NULL, 0, '2025-11-25 11:44:10', '2025-11-25 11:44:01'),
(56, 56, '897.80', 'completed', '2025-11-25 16:10:47', '', NULL, NULL, NULL, 'ORD_56_1764087048240', 0, NULL, 0, '2025-11-25 16:11:21', '2025-11-25 16:10:47'),
(59, 63, '429.40', 'completed', '2025-11-26 02:36:21', '', NULL, NULL, NULL, 'ORD_63_1764124582095', 0, NULL, 0, '2025-11-26 02:37:46', '2025-11-26 02:36:21'),
(60, 64, '57.60', 'completed', '2025-11-26 02:44:06', '', NULL, NULL, NULL, 'ORD_64_1764125046390', 0, NULL, 0, '2025-11-26 02:44:16', '2025-11-26 02:44:06'),
(63, 67, '288.00', 'completed', '2025-11-26 03:05:26', '', NULL, NULL, NULL, 'ORD_67_1764126326072', 0, NULL, 0, '2025-11-26 03:05:39', '2025-11-26 03:05:26'),
(64, 68, '288.00', 'completed', '2025-11-26 03:19:08', '', NULL, NULL, NULL, 'ORD_68_1764127148481', 0, NULL, 0, '2025-11-26 03:19:16', '2025-11-26 03:19:08'),
(65, 69, '288.00', 'completed', '2025-11-26 03:20:00', '', NULL, NULL, NULL, 'ORD_69_1764127200999', 0, NULL, 0, '2025-11-26 03:20:10', '2025-11-26 03:20:00'),
(66, 70, '288.00', 'completed', '2025-11-26 03:20:52', '', NULL, NULL, NULL, 'ORD_70_1764127253164', 0, NULL, 0, '2025-11-26 03:21:07', '2025-11-26 03:20:52'),
(67, 71, '288.00', 'completed', '2025-11-26 03:23:05', '', NULL, NULL, NULL, 'ORD_71_1764127385866', 0, NULL, 0, '2025-11-26 03:23:35', '2025-11-26 03:23:05'),
(69, 73, '288.00', 'completed', '2025-11-26 03:26:41', '', NULL, NULL, NULL, 'ORD_73_1764127601986', 0, NULL, 0, '2025-11-26 03:26:51', '2025-11-26 03:26:41'),
(70, 74, '260.00', 'completed', '2025-11-26 03:34:22', '', NULL, NULL, NULL, 'ORD_74_1764128062309', 0, NULL, 0, '2025-11-26 03:34:33', '2025-11-26 03:34:22'),
(71, 75, '268.00', 'completed', '2025-11-26 10:18:18', '', NULL, NULL, NULL, 'ORD_75_1764152298283', 0, NULL, 0, '2025-11-26 10:18:32', '2025-11-26 10:18:18'),
(72, 76, '3006.00', 'completed', '2025-11-29 17:11:13', '', NULL, NULL, NULL, 'ORD_76_1764436274568', 0, NULL, 0, '2025-11-29 17:11:30', '2025-11-29 17:11:13'),
(90, 94, '2194.20', 'completed', '2025-12-01 15:04:07', '', NULL, NULL, NULL, 'ORD_94_1764601448219', 0, NULL, 0, '2025-12-01 15:04:25', '2025-12-01 15:04:07'),
(91, 95, '485.74', 'completed', '2025-12-01 15:16:04', '', NULL, NULL, NULL, 'ORD_95_1764602167839', 0, NULL, 0, '2025-12-01 15:16:17', '2025-12-01 15:16:04'),
(92, 96, '2379.20', 'completed', '2025-12-01 16:29:22', '', NULL, NULL, NULL, 'ORD_96_1764606566203', 0, NULL, 0, '2025-12-01 16:29:36', '2025-12-01 16:29:22'),
(93, 97, '1172.74', 'completed', '2025-12-01 16:44:04', '', NULL, NULL, NULL, 'ORD_97_1764607450788', 0, NULL, 0, '2025-12-01 16:44:19', '2025-12-01 16:44:04'),
(102, 106, '4730.00', 'completed', '2025-12-03 13:57:32', '', NULL, NULL, NULL, 'ORD_106_1764770259474', 0, NULL, 0, '2025-12-03 13:58:00', '2025-12-03 13:57:32'),
(105, 109, '2943.85', 'pending', '2025-12-03 14:20:48', '', NULL, NULL, NULL, 'PSK_1764771648_109', 0, NULL, 0, '2025-12-03 14:20:48', '2025-12-03 14:20:48'),
(106, 110, '2943.85', 'pending', '2025-12-03 14:22:30', '', NULL, NULL, NULL, 'PSK_1764771750_110', 0, NULL, 0, '2025-12-03 14:22:30', '2025-12-03 14:22:30'),
(107, 111, '2943.85', 'pending', '2025-12-03 14:25:49', '', NULL, NULL, NULL, 'PSK_1764771949_111', 0, NULL, 0, '2025-12-03 14:25:49', '2025-12-03 14:25:49'),
(108, 112, '2943.85', 'pending', '2025-12-03 14:32:31', '', NULL, NULL, NULL, 'PSK_1764772351_112', 0, NULL, 0, '2025-12-03 14:32:31', '2025-12-03 14:32:31'),
(109, 113, '2943.85', 'pending', '2025-12-03 14:33:09', '', NULL, NULL, NULL, 'PSK_1764772389_113', 0, NULL, 0, '2025-12-03 14:33:09', '2025-12-03 14:33:09'),
(110, 114, '2943.85', 'completed', '2025-12-03 14:41:11', '', NULL, NULL, NULL, 'ORD_114_1764772879370', 0, NULL, 0, '2025-12-03 14:41:53', '2025-12-03 14:41:11'),
(111, 115, '224.85', 'completed', '2025-12-03 14:47:46', '', NULL, NULL, NULL, 'ORD_115_1764773266837', 0, NULL, 0, '2025-12-03 14:48:29', '2025-12-03 14:47:46'),
(112, 116, '300.00', 'completed', '2025-12-03 14:56:28', '', NULL, NULL, NULL, 'ORD_116_1764773790678', 0, NULL, 0, '2025-12-03 14:56:44', '2025-12-03 14:56:28'),
(113, 117, '207.74', 'completed', '2025-12-03 15:07:45', '', NULL, NULL, NULL, 'ORD_117_1764774465765', 0, NULL, 0, '2025-12-03 15:08:19', '2025-12-03 15:07:45'),
(114, 118, '202.00', 'completed', '2025-12-03 15:14:01', '', NULL, NULL, NULL, 'ORD_118_1764774847254', 0, NULL, 0, '2025-12-03 15:14:15', '2025-12-03 15:14:01'),
(115, 119, '220.00', 'completed', '2025-12-03 15:27:20', '', NULL, NULL, NULL, 'ORD_119_1764775668553', 0, NULL, 0, '2025-12-03 15:29:54', '2025-12-03 15:27:20'),
(116, 120, '599.00', 'completed', '2025-12-03 20:17:29', '', NULL, NULL, NULL, 'ORD_120_1764793056219', 0, NULL, 0, '2025-12-03 20:17:47', '2025-12-03 20:17:29'),
(117, 121, '452.80', 'pending', '2025-12-05 07:00:40', '', NULL, NULL, NULL, 'PSK_1764918040_121', 0, NULL, 0, '2025-12-05 07:00:40', '2025-12-05 07:00:40'),
(118, 122, '452.80', 'completed', '2025-12-05 07:05:43', '', NULL, NULL, NULL, 'ORD_122_1764918349067', 0, NULL, 0, '2025-12-05 07:07:52', '2025-12-05 07:05:43'),
(119, 123, '223.40', 'completed', '2025-12-05 07:36:29', '', NULL, NULL, NULL, 'ORD_123_1764920196599', 0, NULL, 0, '2025-12-05 07:37:06', '2025-12-05 07:36:29'),
(121, 125, '264.60', 'completed', '2025-12-10 03:22:34', '', NULL, NULL, NULL, 'ORD_125_1765336964532', 0, NULL, 0, '2025-12-10 03:22:54', '2025-12-10 03:22:34'),
(122, 126, '274.80', 'completed', '2025-12-11 03:23:05', '', NULL, NULL, NULL, 'ORD_126_1765423389042', 0, NULL, 0, '2025-12-11 03:23:31', '2025-12-11 03:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `log_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `status` enum('initiated','pending_confirmation','verified','failed') NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payment_webhooks`
--

CREATE TABLE `payment_webhooks` (
  `webhook_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `provider` enum('paystack','flutterwave','custom') NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) DEFAULT 0.00,
  `is_new` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `main_image` varchar(255) DEFAULT NULL,
  `gallery_images` text DEFAULT NULL,
  `date_added` datetime DEFAULT current_timestamp(),
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `slug`, `brand`, `description`, `short_description`, `sku`, `price`, `discount`, `is_new`, `is_featured`, `color`, `size`, `weight`, `stock_quantity`, `main_image`, `gallery_images`, `date_added`, `last_updated`, `average_rating`, `review_count`) VALUES
(1, 9, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple', 'Latest iPhone with A17 Pro chip', NULL, 'APL-IP15PM-256', '9500.00', '20.00', 0, 0, 'Silver', '256GB', NULL, 10, 'assets/uploads/product_1764313030_692947c646f48.jpg', 'assets/uploads/product_1764313030_692947c6537fd.jpg', '2025-11-07 09:13:34', '2025-12-09 16:58:45', '0.00', 0),
(2, 2, 'Nike Air Max 270', 'nike-air-max-270', 'Nike', 'Comfortable sports sneakers', NULL, 'NK-AM270-BLK', '300.00', '10.00', 0, 1, 'Black', '42', NULL, 24, 'assets/uploads/product_1764961562_69332d1a0d46e.jpg', 'assets/uploads/product_1764961562_69332d1a0eeab.jpg,assets/uploads/product_1764961562_69332d1a120c8.jpg,assets/uploads/product_1764961562_69332d1a13dd6.jpg,assets/uploads/product_1764961562_69332d1a14a8d.jpg', '2025-11-07 09:13:34', '2025-12-09 16:58:45', '0.00', 0),
(3, 3, 'Riz Basmati Rice (4.5kg)', 'riz-basmati-rice-45kg', 'Royal Feast', 'Get your 4.5kg of Riz Basmati Rice here. Order Now.\r\nPlace your orders now. We supply to various restaurants and food centres across Ghana\r\nTalk to us today and let’s process your order', 'Premium long grain rice', 'RF-AM270-RBR', '200.00', '30.00', 1, 0, NULL, NULL, '4.50', 13, 'assets/uploads/product_1764311231_692940bf445f1.jpg', NULL, '2025-11-07 09:13:34', '2025-12-09 16:58:45', '0.00', 0),
(4, 4, 'Solo Knight', 'solo-knight', 'Top', 'Quality and affordable perfume of High Class', 'Quality Perfume', NULL, '200.00', '50.00', 1, 1, 'Dark Blue', 'Normal', '30.50', 6, 'assets/uploads/smart_watch.jpg', NULL, '2025-11-21 14:40:02', '2025-12-09 16:58:45', '5.00', 1),
(5, 2, 'Kafui Black Leather Bag', 'kafui-black-leather-bag', 'Top', 'Quality and affordable Leather bag of High Class. This product is made by Kafui Leather Works ltd in Ho, Volta region.', 'Quality and durable leather bag made in Ghana', NULL, '200.00', '20.00', 0, 1, 'Dark Blue', 'Normal', '30.50', 5, 'assets/uploads/leather-bag.jpg', NULL, '2025-11-21 14:44:36', '2025-12-09 16:58:45', '0.00', 0),
(6, 4, 'Day by Day', 'day-by-day', 'Extra', 'Quality and affordable perfume of High Class', 'Quality body Lotion', NULL, '210.00', '20.00', 1, 1, 'White', 'Normal', '30.50', 7, 'assets/uploads/product_1764283395_6928d4031dd13.png', 'assets/uploads/product_1764285370_6928dbbad17e7.jpg,assets/uploads/product_1764285370_6928dbbad2198.jpg,assets/uploads/product_1764285370_6928dbbad24fe.jpg', '2025-11-21 14:54:46', '2025-12-11 03:21:37', '4.00', 1),
(7, 1, 'Macbook', 'macbook', 'Apple', 'The MacBook is a premium, high-performance laptop designed by Apple, known for its sleek aluminum build, long battery life, fast processors, and smooth macOS experience. It delivers excellent display quality, reliable performance, and seamless integration with the Apple ecosystem, making it ideal for work, creativity, and everyday use.', 'A fast, lightweight, and premium Apple laptop built for performance and portability.', 'SKU17642542939174', '20000.00', '5.00', 1, 1, 'Silver, Black, Red', '34 x 56', '45.00', 100, 'assets/uploads/product_1764254293_69286255242c9.webp', 'assets/uploads/product_1764254293_6928625524d2b.jpg,assets/uploads/product_1764254293_6928625525d6a.jpg,assets/uploads/product_1764254293_6928625526adb.jpg,assets/uploads/product_1764254293_692862552785e.jpg', '2025-11-27 14:38:13', '2025-12-09 16:58:45', '0.00', 0),
(8, 1, 'Roch 100 Litres - RCF-120N-B Chest Freezer - Silver+ FREE IRON', 'roch-100-litres-rcf-120n-b-chest-freezer-silver-free-iron', 'Roch', 'Besides keeping your food fresh and nutritious, ROCH RCF-120N-B Chest Freezer - 100 Litres - Silver works with an impressive refrigerant, this means that your drinks and food kept in this refrigerator cools in less than a few minutes. It features a huge capacity of 100 litres, key lock and an adjustable thermostat.\r\n\r\nUnlike conventional refrigerators, ROCH RCF-120N-B Chest Freezer - 100 Litres - Silver displays a small innovative touch that allows it to adapt its operation following the moisture level and use (opening the door, exterior temperature). This helps maintain a more stable temperature and reducing the compressor intake to lengthen its service life while reducing engine noise.\r\n\r\n100 Litres Net Capacity\r\nLed Lighting\r\nEnergy Savings\r\nGrey Body\r\nGrip Handle\r\nRemovable Storage Basket', 'Litres - Silver works with an impressive refrigerant, this means that your drinks and food kept in this refrigerator cools in less than a few minutes.', 'SKU17642836956661', '2619.00', '20.00', 0, 1, 'Silver', NULL, '80.00', 13, 'assets/uploads/product_1764283695_6928d52f3b470.jpg', 'assets/uploads/product_1764283695_6928d52f3b9fa.jpg,assets/uploads/product_1764283695_6928d52f3c1d1.jpg,assets/uploads/product_1764283695_6928d52f3e966.jpg', '2025-11-27 22:48:15', '2025-12-09 16:58:45', '0.00', 0),
(9, 2, 'Men T-shirts And Jogger Pants Set', 'men-t-shirts-and-jogger-pants-set', 'T-shirt', 'Shop our 2pcs t-shirts made from high-quality waffle fabric for a soft, breathable, and stretchy feel. Perfect for laid-back weekends, this T-shirt and shorts set can be styled as lounge wear or casual daywear. Pair it with sneakers and a cap for an easygoing look or dress it up with jeans. Each package includes 2 T-shirts and 2 solid shorts.', 'Shop our 2pcs t-shirts made from high-quality waffle fabric for a soft, breathable, and stretchy feel.', 'SKU17642974176329', '204.00', '0.00', 1, 1, 'Black', 'X,XL', '0.80', 3, 'assets/uploads/product_1764297417_69290ac929683.jpg', NULL, '2025-11-28 02:36:57', '2025-12-09 16:58:45', '0.00', 0),
(10, 1, 'Roch 20L - Microwave Oven - RMW-20LMX7- B(B) - Black', 'roch-20l-microwave-oven-rmw-20lmx7-bb-black', 'Roch', 'The stylish and elegant design makes the Roch RMW-20LMX7-B(B) Microwave Oven  a lifestyle item suitable for any kitchen. It features a manual operational and 700 Watt microwave power source. The Roch RMW-20L8M-B(B)  20L  Microwave Oven is a perfect one for your home use. \r\n\r\nThe control panel of the Oven is very stylish and comfortable to use with the feather touch technology for easy access of the buttons.\r\n\r\nThis stylish microwave oven is a perfect one for the daily use. It cooks the food with a good speed and lets you get the natural test of your food. It has a feather touch control panel with a proper design and the markings over it which makes it easy to understand all in one go.\r\n\r\nSPECIFICATIONS\r\n\r\nPower Levels: Offers 5 power levels to adjust according to cooking requirements.\r\nDoor: Equipped with a pull handle door for effortless opening and closing.\r\nControl: Mechanical control with intuitive rotary controls.\r\nCapacity : 20L, providing ample space for various dish sizes.\r\nPower : 5 levels &amp;amp; Timer: 30 minutes\r\nDefrost Setting: Defrost setting for convenient thawing of frozen foods.\r\nFeature: End of cooking signal\r\nTimer: 35 minutes timer for precise cooking durations.\r\nOutput power : 700 Watt\r\nInput power : 1050 Watt\r\nVoltage input : 230V-50Hz\r\nMicrowave frequency : 2450 MHz\r\nWeight : 10Kg', 'The stylish and elegant design makes the Roch RMW-20LMX7-B(B) Microwave Oven  a lifestyle item suitable for any kitchen.', 'SKU17643005459781', '1139.53', '30.00', 1, 1, 'Black', NULL, '10.00', 12, 'assets/uploads/product_1764300545_692917018f49a.jpg', 'assets/uploads/product_1764300545_692917019060f.jpg,assets/uploads/product_1764300545_6929170192444.jpg', '2025-11-28 03:29:05', '2025-12-09 16:58:45', '5.00', 1),
(11, 9, 'TECNO Pop 10c - 64GB ROM - 2GB RAM - 13MP Rear/8MP Front - 5000mAh - Grey', 'tecno-pop-10c-64gb-rom-2gb-ram-13mp-rear8mp-front-5000mah-grey', 'TECNO', 'Tecno Pop 10 is an upcoming smartphone with Android S Go 6.67 inches, Super AMOLED HD Display, 4G Network, Dual single rear camera, Back 8MP, Front 13MP Main sensor, HD Video Recording, Octa-Core, 3GB RAM, 64GB storage, 5000mAh Battery, USB 2.0, Rear Fingerprint, Plastic Body.\r\n\r\nSpecification: \r\n\r\nFree link\r\n\r\nFree call without signal\r\n\r\n\r\nDisplay\r\n \r\n\r\nType: HD + Dot Notch Screen\r\nSize: 6.67 inches, 91.3 cm2 (~78.4% screen-to-body ratio)\r\nResolution: 720 x 1560 pixels, 19.5:9 ratio (~282 ppi density)\r\nPlatform\r\n \r\n\r\nAndroid 15 (Go edition), HiOS\r\nCPU: Quad-core 1.3 GHz Cortex-A7\r\nMain Camera\r\n \r\n\r\nCamera Type: Double Camera\r\nCamera Sensor(s): Main: 13MP\r\nCamera Features: HDR, Pro, Lowlight, Portrait, Short Video, Pano Vid, LED flash\r\nVideo Resolution:720p@30fps\r\nSelfie Camera\r\n\r\nCamera Type: Dual Camera\r\nCamera Sensor(s): 8-megapixel\r\nCamera Features: Face Unlock, Face Beauty,\r\nVideo Resolution: QVGA\r\nStorage\r\n\r\nRAM: 3GB\r\nBuilt-in Storage: 64GB\r\nMemory Card Support: Yes, up to 64 GB\r\nInternet &amp;amp;amp; Connectivity\r\n\r\nGPRS: Yes\r\nEDGE: Yes\r\n4G/3G/WCDMA/HSPA: Yes\r\nUSB: Micro USB 2.0\r\nBluetooth: Bluetooth 4.2\r\nWi-fi: Wi-Fi 802.11 b/g/n, Wi-Fi Direct, hotspot\r\nGPS: Yes, with A-GPS\r\nFM Radio: Yes\r\nEntertainment\r\n\r\nMusic Player: MP3/WAV/eAAC+/Flac player\r\nVideo Player: XviD/MP4/H.264 player\r\nFM Radio: Yes\r\nLoudspeaker: Yes\r\n3.5mm Jack: Yes', 'Tecno Pop 10 is an upcoming smartphone with Android S Go 6.67 inches, Super AMOLED HD Display, 4G Network, Dual single rear camera, Back 8MP, Front 13MP Main sensor, HD Video Recording, Octa-Core, 3GB RAM, 64GB storage, 5000mAh Battery, USB 2.0, Rear Fingerprint, Plastic Body.', 'SKU17643036126826', '1120.00', '0.50', 1, 0, 'Gray', NULL, '0.10', 10, 'assets/uploads/product_1764303612_692922fc1a12e.jpg', 'assets/uploads/product_1764303612_692922fc1d254.jpg', '2025-11-28 04:20:12', '2025-12-09 16:58:45', '0.00', 0),
(12, 9, 'Samsung Galaxy A52 5G 128 GB HDD- 6GB RAM - Awesome Black', 'samsung-galaxy-a52-5g-128-gb-hdd-6gb-ram-awesome-black', 'Samsung', 'ABOUT SAMSUNG GALAXY A52 5G 128 GB  6 GB\r\nSamsung Galaxy A52 5G 128 GB 6 GB was launched in different color options like Awesome Black, Awesome White, Awesome Blue, Awesome Violet. In addition to this, the mobile measures 159.9 mm x 75.1 mm x 8.4 mm; and weighs around 189 grams. The mobile from Samsung features(16.51 cm) display that has a resolution of 1080 x 2400 Pixels.\r\n \r\n\r\nFurthermore, the aspect ratio of the Samsung Galaxy A52 5G 128 GB 6 GB is 20:9 so that you can enjoy vivid and crystal clear visuals while watching videos, playing games, or streaming movies online..\r\n \r\n\r\nYou can enjoy seamless performance on your phone as it is equipped with Octa core (2.2 GHz, Dual core, Kryo 570 + 1.8 GHz, Hexa Core, Kryo 570) Qualcomm Snapdragon 750G. The phone comes with 6 GB and 128 GB inbuilt storage so that you can store your local files, songs, videos and more without worrying about space constraints. Besides, the Samsung Galaxy A52 5G 128 GB 6 GB runs Android v11 operating system and is packed with 4500 mAh battery that lets you enjoy watching movies, playing games and do a lot more without worrying about battery drainage.\r\n \r\n\r\nVarious connectivity options on the Samsung Galaxy A52 5G 128 GB 6 GB include WiFi - Yes, Wi-Fi 802.11, a/ac/b/g/n/n 5GHz, Mobile Hotspot, Bluetooth - Yes, v5.0, and 5G supported by device , 4G, 3G, 2G. Sensors on the mobile include Accelerometer, Gyro Sensor, Geomagnetic Sensor, Hall Sensor, Light Sensor, Virtual Proximity Sensor.', 'Samsung Galaxy A52 5G 128 GB 6 GB was launched in different color options like Awesome Black, Awesome White, Awesome Blue, Awesome Violet', 'SKU17643045088619', '3900.00', '35.00', 0, 1, 'Awesome Black', '15.96 x 7.48 x 0.81 cm', '0.19', 50, 'assets/uploads/product_1764304508_6929267c06105.jpg', 'assets/uploads/product_1764304508_6929267c06fdc.jpg,assets/uploads/product_1764304508_6929267c07897.jpg,assets/uploads/product_1764304508_6929267c08671.jpg,assets/uploads/product_1764304508_6929267c08ccd.jpg,assets/uploads/product_1764304508_6929267c0a89b.jpg', '2025-11-28 04:35:08', '2025-12-09 16:58:45', '0.00', 0),
(13, 1, 'Electric Kettle 2Liters - Black/Silver', 'electric-kettle-2liters-blacksilver', NULL, 'Electric Kettle for Boiling Water the Purest Taste, No Plastic Contact with Hot Water: It comes with food-grade 304 stainless steel(No rust risk) on the inside inner pot, inner lid, spout &amp; rim. No any plastic in contact with hot water. Safe healthy drinking water.\r\nSmall Electric Kettle, Stainless Steel, Double Wall Construction: Made with double-wall construction, boil water faster and use far less energy than stove top kettles. Stainless Steel Water Kettle also keeps your water warm much longer and Warm to touch when heating.\r\n\r\nPortable Hot Water Kettle Electric, Safe Tech &amp; Auto Shut Off : Auto shut-off within 20 seconds after the water is fully boiling. Boil-dry safety feature where it turns off if it detects there is no water inside, energy saving, and durability;\r\n\r\nEasy To Clean Water Heater Kettle: A wide mouth hot water boiler means both easy cleaning and filling, it allows for easy access to clean those deposits or &quot;rust&quot; Away.', 'Electric Kettle for Boiling Water the Purest Taste, No Plastic Contact with Hot Water: It comes with food-grade 304 stainless steel(No rust risk) on the inside inner pot, inner lid, spout &amp; rim. No any plastic in contact with hot water.', 'SKU17643049674566', '250.00', '26.00', 1, 0, 'Silver', 'Black/Silver', '0.30', 1, 'assets/uploads/product_1764304967_69292847a3af0.jpg', NULL, '2025-11-28 04:42:47', '2025-12-09 16:58:45', '0.00', 0),
(14, 1, 'Marado Powerful Multifunctional Stainless Steel Electric Flask Kettle- Multicolored', 'marado-powerful-multifunctional-stainless-steel-electric-flask-kettle-multicolored', 'Marado', 'Order for this electric Flask Kettle on Cartella and have it delivered right to your doorstep.The Electric Flask Kettle has been designed for your convenience and safety. This electric kettle has a power 15000W motor to heat water quickly, plus other amazing features such as an automatic shutdown, water level indicator which lets vou adiust the amount of water placed in the teapot. Comes in different colors; Gold, Red, and Blue', 'The Electric Flask Kettle has been designed for your convenience and safety', 'SKU17643051895267', '380.00', '26.00', 0, 0, 'Multi', NULL, '2.00', 5, 'assets/uploads/product_1764305189_692929255cc44.jpg', '', '2025-11-28 04:46:29', '2025-12-11 03:51:02', '0.00', 0),
(15, 5, 'Disaar 2 x Anti-Aging Collagen Oil Whitening &amp;amp; Brightening - 100 ml', 'disaar-2-x-anti-aging-collagen-oil-whitening-ampamp-brightening-100-ml', 'Disaar', 'Unlock youthful radiance with Disaar Anti-Aging Collagen Oil, a luxurious blend designed to combat the signs of aging while whitening and brightening your complexion. Enriched with collagen, this powerful oil works to improve skin elasticity, reducing the appearance of fine lines and wrinkles. Its unique formula penetrates deep into the skin, delivering intense hydration and nourishment for a plump, youthful look.\r\n\r\nInfused with potent whitening and brightening agents, this oil helps diminish dark spots, uneven skin tone, and hyperpigmentation, revealing a luminous glow. The lightweight texture absorbs quickly, leaving your skin feeling silky smooth and revitalized without any greasy residue. Whether used as a standalone treatment or mixed with your favorite moisturizer, this collagen oil enhances your skincare routine, providing visible results with regular use.\r\n\r\nExperience the transformative effects of Disaar Anti-Aging Collagen Oil as it rejuvenates your skin, promoting a more youthful, vibrant appearance. Perfect for all skin types, this oil is your secret weapon in achieving a brighter, more radiant complexion while addressing the signs of aging.', 'Unlock youthful radiance with Disaar Anti-Aging Collagen Oil, a luxurious blend designed to combat the signs of aging while whitening and brightening your complexion.', 'SKU17643059727950', '165.00', '40.00', 0, 0, 'White', '4 x 8 x 13 cm', '0.20', 11, 'assets/uploads/product_1764306195_69292d13e9966.jpg', NULL, '2025-11-28 04:59:32', '2025-12-11 03:51:02', '0.00', 0),
(16, 5, 'Dr meinaier Turmeric Hair, Body Oil - 220ml', 'dr-meinaier-turmeric-hair-body-oil-220ml', 'Dr meinaier', 'Dr. Meinaier Turmeric Hair, Face &amp;amp; Body Oil is a versatile, all-in-one solution designed to nourish and rejuvenate your skin and hair. Infused with natural turmeric extract, this oil offers potent anti-inflammatory and antioxidant properties, helping to soothe irritated skin, reduce the appearance of dark spots, and promote a radiant complexion. Its lightweight, non-greasy formula absorbs quickly, making it suitable for daily use on all skin and hair types.\r\n\r\nFor hair care, apply a small amount to the scalp and hair strands to moisturize, reduce dryness, and stimulate hair growth. On the face and body, gently massage the oil into the skin to hydrate, improve elasticity, and enhance overall skin tone. Regular use can lead to softer skin, reduced blemishes, and healthier-looking hair.\r\n\r\nOrder now on Jumia Ghana at the best price and have it delivered right at your doorstep.', NULL, 'SKU17643069633112', '149.00', '37.00', 0, 0, 'Gold', '3 x 5 x 12 cm', '0.22', 10, 'assets/uploads/product_1764306964_69293014057dd.jpg', NULL, '2025-11-28 05:16:04', '2025-12-11 03:51:02', '5.00', 1),
(17, 5, 'Roushun Vitamin C Anti-Aging - 30 ml', 'roushun-vitamin-c-anti-aging-30-ml', 'Roushun', 'Roushun Vitamin C Anti-Aging Brightening Serum is a powerful solution designed to rejuvenate and brighten your skin. Infused with Vitamin C, this serum works to diminish the appearance of dark spots, age spots, and hyperpigmentation, leaving your complexion looking more radiant and even-toned. Its lightweight, fast-absorbing formula penetrates deeply into the skin, promoting collagen production to reduce fine lines and wrinkles, helping you achieve a youthful and glowing look.\r\n\r\nThis serum not only brightens but also offers anti-aging benefits, thanks to its potent antioxidant properties. It shields the skin from environmental damage while promoting natural cell turnover, resulting in smoother and firmer skin. With regular use, you’ll notice an improvement in skin texture, a reduction in visible signs of aging, and a more luminous complexion.\r\n\r\nPerfect for all skin types, Roushun Vitamin C Anti-Aging Brightening Serum is an essential addition to your skincare routine. Whether used in the morning for a daily glow or at night for a restorative treatment, this serum provides hydration and long-lasting protection, helping you maintain healthy, youthful skin.\r\n\r\nReduces dark spots, age spots, and hyperpigmentation.\r\nPromotes collagen production for smoother, firmer skin.\r\nLightweight formula that absorbs quickly into the skin.\r\nProtects against environmental damage and aging.\r\nLeaves skin bright, radiant, and youthful-looking.\r\nVolume 30 ml,', NULL, 'SKU17643082066678', '76.00', '30.00', 0, 0, NULL, '4 x 4 x 10 cm', '0.10', 40, 'assets/uploads/product_1764308206_692934ee0c831.jpg', NULL, '2025-11-28 05:36:46', '2025-12-09 16:58:45', '0.00', 0),
(18, 1, 'Hp Color LaserJet Pro MFP M182n (7KW54A) - White', 'hp-color-laserjet-pro-mfp-m182n-7kw54a-white', 'HP', 'HP Color LaserJet Pro MFP M182n (7KW54A)\r\nAn efficient MFP for high-quality colour and productivity. 1 Save time with Smart Tasks in HP Smart app, and print and scan from your phone. 2 Get security essentials to help maintain privacy and control.\r\n\r\n \r\n\r\nFunctions: Print, copy, scan\r\n\r\nPrint speed: Print speed up to 17 ppm (black) and 17 ppm (colour)\r\n\r\nPrint quality black (best): Up to 600 x 600 dpi\r\n\r\nDynamic security enabled printer. This printer is intended to work only with cartridges that have a new or reused HP chip, and it uses dynamic security measures to block cartridges using a non-HP chip. Periodic firmware updates will maintain the effectiveness of these measures and block cartridges that previously worked. A reused HP chip enables the use of reused, , and refilled cartridges. More at: http://www.hp.com/learn/ds\r\n\r\nKey Features\r\nPrint, copy, scan, fax, dual band wireless\r\nPrint speed letter: Up to 22 ppm (black and color)\r\nHigh yield toner available\r\nDynamic security enabled printer\r\nFunctions: Print, Copy, Scan, Fax', NULL, 'SKU17643638524232', '8620.00', '22.00', 0, 0, 'White', NULL, '15.70', 27, 'assets/uploads/product_1764363852_692a0e4c87b1a.jpg', 'assets/uploads/product_1764363852_692a0e4c892bc.jpg,assets/uploads/product_1764363852_692a0e4c89917.jpg,assets/uploads/product_1764363852_692a0e4c89fd1.jpg,assets/uploads/product_1764363852_692a0e4c8a415.jpg', '2025-11-28 21:04:12', '2025-12-09 16:58:45', '0.00', 0),
(19, 1, 'Hp M32F Display Monitor - Silver', 'hp-m32f-display-monitor-silver', 'HP', 'Designed to redefine comfort, wellness, and sustainability 1, play or work on the screen that redefines high definition.\r\n\r\nThe New Definition of High-Definition\r\nThis FHD display feels as good as it looks, packing 99% sRGB color gamut for colour accuracy and Freesync to keep your eyes up to speed with your imagination\r\n\r\nStreamlined &amp;amp;amp;amp; Seamless\r\nStreamline your setup with its slim profile, innovative cable containment, and seamless design for side-by-side screens.\r\n\r\nEasily personalize and adjust your display.\r\nCustomize your display with easy, intuitive HP Display Center software that lets you tailor your settings, partition screens, and even dim the screen.\r\n\r\nHP Eye Ease with Eyesafe® Certification\r\nAn always-on blue light filter that keeps your eyes comfortable with zero impact on color accuracy.\r\n\r\nVA panel\r\nGet wide, 178-degree viewing angles and better black levels for deep, rich contrast.\r\n\r\nFHD display / 1080p display\r\nBrilliant visuals and unforgettable quality from a stunning FHD display', NULL, 'SKU17647117716975', '5500.00', '14.00', 0, 0, 'Silver', '31.5 inches', '11.03', 10, 'assets/uploads/product_1764711771_692f5d5b27cdf.jpg', 'assets/uploads/product_1764711771_692f5d5b28d48.jpg', '2025-12-02 21:42:51', '2025-12-11 03:50:16', '0.00', 0),
(20, 1, 'DELL 24 SE2425H Monitor - 23.8-Inch Full HD', 'dell-24-se2425h-monitor-238-inch-full-hd', 'DELL', '<p>Improved eye comfort Enjoy visual well-being with ComfortView Plus, engineered to minimize harmful blue light exposure without sacrificing color accuracy. Coupled with a 75Hz refresh rate, this monitor delivers a T&Uuml;V Rheinland 3-star certified viewing experience. T&Uuml;V Rheinland certified - Low Blue Light Hardware Solution and Flicker Free (ID 0217010014). With software-enabled low blue light technology versus built-in ComfortView Plus. Screen images simulated. Dependable from every angle Wide viewing angle: Get consistent views across a wide 178&deg;/178&deg; viewing angle. 3000:1 contrast ratio: Experience deeper blacks, brighter whites and vivid color. Stay organized: Boost productivity with Easy Arrange, setting up applications, emails and windows neatly on one screen.</p>\r\n<p><strong>Requires Dell Display Manager software installation.</strong></p>', NULL, 'SKU17647119572370', '3000.00', '12.00', 0, 0, 'Black', '24 Inches', '2.40', 13, 'assets/uploads/product_1764711957_692f5e157772d.jpg', 'assets/uploads/product_1764711957_692f5e1578ca6.jpg,assets/uploads/product_1764711957_692f5e1579a1c.jpg', '2025-12-02 21:45:57', '2025-12-11 03:50:16', '5.00', 1),
(21, 2, 'Mateamoda Women Shoes Sandals Heels Ladies Shoes Casual Shoes', 'mateamoda-women-shoes-sandals-heels-ladies-shoes-casual-shoes', 'Mateamoda', 'Dear customer, welcome to MATEAMODA store. Thank you for your love for our products.\r\n\r\nMATEAMODA focuses on African Fashion and is committed to providing you with better products, fashionable designs and higher quality services.\r\n\r\nWe provide varieties of fashion products with diverse patterns and styles. Whether you are looking for fashion bags, shoes, or clothing, you can always find what you need in our store.\r\n\r\nThis product you are currently browsing is fashionable, practical and cost-effective, making it to be a must-have one. At the same time, you can also click on the store name to enter our store and explore more fashionable surprises.\r\n\r\nNow, we will introduce more details of this product for you.\r\n\r\nLet&#039;s start your fashion journey at MATEAMODA right now!\r\n\r\nProduct Details\r\nUpper Material: PU(Artificial leather)\r\n\r\nInner Material: PU(Artificial leather)\r\n\r\nSole: Synthetic\r\n\r\nHeel Type: Chunky heel\r\n\r\nHeel Height: 5.5cm\r\n\r\nClosure Type: Hook and loop\r\n\r\nStyle: Fashion\r\n\r\nFeatures: Classic; Elegant\r\n\r\nNotice\r\n1. The product size is measured by hand, and there may be an error of 1-3 cm, which belongs to the normal range.\r\n\r\n2. The same size of different products are different. There is a size chart in the product link, please choose the size that suits you according to the size chart, instead of your usual experience.\r\n\r\n3. During the shooting process of the product, due to the difference in light, there may be a slight color difference between the real object and the pictures, which is a normal phenomenon.\r\n\r\n4. The product may be squeezed during delivery. If the product you received is not as flat as it looks in the pictures, please don&#039;t worry. This is a normal phenomenon, and it will be fine after using for a while.\r\n\r\n5. The product will have some smell of the material itself when produced from the factory. This is normal for new products. Take the product out of the packaging and the smell will disappear in a few days.\r\n\r\n6. Please store in a ventilated and cool place. Do not place in damp places or expose to strong sunlight for a long time.', NULL, 'SKU17647589589616', '140.00', '35.00', 0, 1, 'Black', '38,  39, 40, 41', NULL, 25, 'assets/uploads/product_1764758958_693015ae6e208.jpg', 'assets/uploads/product_1764758958_693015ae712fc.jpg,assets/uploads/product_1764758958_693015ae72ce3.jpg,assets/uploads/product_1764758958_693015ae74abc.jpg,assets/uploads/product_1764758958_693015ae75229.jpg,assets/uploads/product_1764758958_693015ae756dd.jpg,assets/uploads/product_1764758958_693015ae77e5e.jpg', '2025-12-03 10:49:18', '2025-12-09 16:58:45', '0.00', 0),
(22, 2, 'Mateamoda Women Shoes Sandals Heels Slippers', 'mateamoda-women-shoes-sandals-heels-slippers', 'Mateamoda', 'Dear customer, welcome to MATEAMODA store. Thank you for your love for our products.\r\n\r\nMATEAMODA focuses on African Fashion and is committed to providing you with better products, fashionable designs and higher quality services.\r\n\r\nWe provide varieties of fashion products with diverse patterns and styles. Whether you are looking for fashion bags, shoes, or clothing, you can always find what you need in our store.\r\n\r\nThis product you are currently browsing is fashionable, practical and cost-effective, making it to be a must-have one. At the same time, you can also click on the store name to enter our store and explore more fashionable surprises.\r\n\r\nNow, we will introduce more details of this product for you.\r\n\r\nLet&amp;amp;#039;s start your fashion journey at MATEAMODA right now!\r\n\r\nProduct Details\r\nUpper Material: PU(Artificial leather)\r\n\r\nInner Material: PU(Artificial leather)\r\n\r\nSole: Synthetic\r\n\r\nHeel Type: Chunky heel\r\n\r\nHeel Height: 6.5 cm\r\n\r\nClosure type：Pull on\r\n\r\nStyle: Fashion\r\n\r\nFeatures: Shinny; Beautiful; Classic', NULL, 'SKU17647592419225', '207.00', '45.00', 0, 1, 'Silver', '38,  39, 40', NULL, 13, 'assets/uploads/product_1764789075_69308b53088df.jpg', 'assets/uploads/product_1764759241_693016c9837c4.jpg,assets/uploads/product_1764759241_693016c984394.jpg,assets/uploads/product_1764759241_693016c9875ce.jpg', '2025-12-03 10:54:01', '2025-12-09 16:58:45', '0.00', 0),
(23, 1, 'Roch 142 Liters Double Door Refrigerator (RFR-160BN-B) - Silver', 'roch-142-liters-double-door-refrigerator-rfr-160bn-b-silver', 'Roch', 'Offering you a reliable freezing performance, RFRFR-160BN-B 142 Liters Double door refrigerator Silver is an ideal appliance for every home. \r\n\r\nYou can have peace of mind as the highly efficient compressor makes sure your food items are safe and sound. It also comes with an adjustable thermostat, removable storage basket, key lock, and a Fast Freezing Function. \r\n\r\nThis double  Door is a perfect fit for everyone who desires to get value for his or her money.', NULL, 'SKU17647892349847', '5372.00', '44.00', 0, 1, 'Silver', NULL, '23.50', 10, 'assets/uploads/product_1764789268_69308c141c790.jpg', 'assets/uploads/product_1764789268_69308c141fd9e.jpg,assets/uploads/product_1764789268_69308c1420f54.jpg,assets/uploads/product_1764789268_69308c14238b3.jpg', '2025-12-03 19:13:54', '2025-12-09 16:58:45', '0.00', 0),
(24, 1, 'Roch RAC-S12R4- E – Split Air Conditioner - 1.5HP - White', 'roch-rac-s12r4-e-split-air-conditioner-15hp-white', 'Roch', 'Make hot weather bearable with any of our range of energy efficient cooling system options‎,‎ air conditioners‎,‎ Split units units and more which are a reliable solution for your home‎&amp;amp;#039;‎s air cleaning and cooling needs‎.‎ Bruhm wide range of air conditioners offers portable air conditioners which are the ideal climate‎‐control solution to cool any room‎.‎‎', NULL, 'SKU17647895929891', '3500.00', '17.00', 0, 1, 'White', NULL, '3.00', 5, 'assets/uploads/product_1764789717_69308dd54e194.jpg', NULL, '2025-12-03 19:19:52', '2025-12-09 16:58:45', '0.00', 0),
(25, 2, 'GALUIN Men&#039;s Trendy Casual Sneakers - Black/orange', 'galuin-men039s-trendy-casual-sneakers-blackorange', 'GALUIN', 'Our boutique specialises in African fashion. Believe me, you own one of our products is enough to show off to your friends.To avoid choosing the wrong size, please read the size label carefully. Please choose the size according to the &amp;lsquo;foot length&amp;rsquo;. If your feet are thick and wide, please buy one size bigger; if the shoes are too big, we suggest wearing insoles. Size Reference (Our shoes are EU size). EU 37 = China 37 = Foot length 23.5 cm. EU 38 = China 38 = foot length 24.0 cm. EU 39 = China 39 = foot length 24.5 cm. EU 40 = China 40 = foot length 25.0 cm. EU 41 = China 41 = foot length 25.5 cm. EU 42 = China 42 = foot length 26.0 cm. EU 43 = China 43 = foot length 26.5 cm. EU 44 = China 44 = foot length 27.0 cm. EU size 45 = China size 45 = foot length 27.5 cm.&lt;/p&gt;\r\n&lt;p&gt;This stylish shoe attracts attention with its elegant and beautiful appearance. The soft,&amp;nbsp;cosy and breathable knitted upper provides your legs with breathable space, Soft and protective, they make every step easy. Durable. Stable on the ground.\r\n\r\n1. Lightweight and comfortable&amp;nbsp; material: the upper is usually made of breathable materials, such as mesh or synthetic fibre, which can provide enough ventilation and breathability to prevent excessive sweating of the feet.  sole: the sole is made of lightweight rubber material, which reduces the weight of the shoe and allows the wearer to feel ace  insole and lining: soft materials are used to provide good cushioning and support, allowing the wearer to feel comfortable during exercise\r\n2. Stable support&amp;nbsp; Sole design: the sole is designed with special features such as raised or recessed patterns to provide better grip and stability, which is important for maintaining balance and preventing slips and falls  Upper and laces: Designed to provide good wrapping, hold the foot in the shoe, reduce rocking and twisting, and provide stable support.\r\n\r\n3. Fashionable appearance&amp;nbsp; design: fashionable and simple, in line with the current trend. Colours and patterns are very diverse to meet the preferences and needs of different consumers\r\n\r\n4. Multi-functional application Applicability: suitable for a variety of different sports, such as running, basketball, tennis and so on. It can provide good support and cushioning, and adapt to different sports venues and environments\r\n\r\n5. Durable Materials: Made of high quality materials and fine craftsmanship, able to withstand long time use and various complex sports environments. The sole is made of wear-resistant rubber material with good anti-abrasion performance Are you still looking for a shop with the best ideas, inspiration and soul? Keep an eye on us, we always surprise you. If it&#039;s good, please give us 5 stars. Your praise is our motivation to keep working hard.', NULL, 'SKU17647911606335', '143.24', '29.00', 1, 1, 'Black/orange', '(L x W x H cm): N/A', '0.30', 9, 'assets/uploads/product_1764791160_69309378ce40c.jpg', 'assets/uploads/product_1764791160_69309378cf605.jpg,assets/uploads/product_1764791160_69309378cff4b.jpg,assets/uploads/product_1764791160_69309378d14bf.jpg', '2025-12-03 19:46:00', '2025-12-09 16:58:45', '5.00', 1),
(26, 5, 'Simple Hydrating Light Moisturiser - 125ml', 'simple-hydrating-light-moisturiser-125ml', 'Simple', 'Keep your skin hydrated all day long with Simple Hydrating Light Moisturizer. This light, silky formula provides up to 12 hours of hydration and instantly doubles skin&amp;#039;s hydration. It is made with added vitamins and a blend of skin-loving ingredients. This moisturizer contains no dyes, artificial perfumes, or harsh chemicals that can upset skin so it won&amp;#039;t clog pores or feel greasy on your skin.\r\n\r\nPerfect Even for Sensitive Skin\r\n\r\nWith no dyes, artificial perfumes, or harsh chemicals that can upset skin, Simple Hydrating Light Moisturizer is ideal for sensitive skin. Its hypoallergenic and noncomedogenic formula is dermatologist tested.\r\n\r\nGlycerin and Borage Seed Oil for Hydration\r\n\r\nSimple Hydrating Light Moisturizer contains glycerin and borage seed oil known to keep your skin soft and supple. Borage seed oil has essential fatty acids, which are known to be a part of the skin&amp;#039;s natural hydration process.\r\n\r\nTwo Vitamins\r\n\r\nSimple Hydrating Light Moisturizer is enriched with pro-vitamin B5 and vitamin E. Vitamin B5 is known to restore, soften, and smooth, while vitamin E is known to help your skin retain moisture.', NULL, 'SKU17648707301530', '155.00', '48.00', 0, 1, NULL, NULL, '0.01', 20, 'assets/uploads/product_1764870730_6931ca4abead4.jpg', 'assets/uploads/product_1764870730_6931ca4ac0941.jpg,assets/uploads/product_1764870730_6931ca4ac17e5.jpg,assets/uploads/product_1764870730_6931ca4ac2096.jpg', '2025-12-04 17:52:10', '2025-12-11 03:48:00', '0.00', 0),
(27, 10, 'Portable USB Rechargeable Blender Mixer Smart Juicer- 380ml - Green', 'portable-usb-rechargeable-blender-mixer-smart-juicer-380ml-green', NULL, 'The 380ml USB Rechargeable Blender is a compact and versatile personal juicer designed for on-the-go lifestyles. This sleek device combines portability with powerful blending capabilities, making it perfect for creating fresh smoothies, juices, and protein shakes wherever you are.\r\n\r\nWith its 380ml capacity, this blender is ideal for single-serving portions. The clear, BPA-free bottle allows you to watch your ingredients transform into a delicious blend. The 6-blade system, powered by a 100W motor, efficiently purees fruits, vegetables, and ice for smooth, consistent results.\r\n\r\nConvenience is key with this portable blender. Its USB rechargeable feature means you can power it up from your laptop, power bank, or any USB outlet. The 3.7V battery provides cordless operation, perfect for use at the office, gym, or while traveling. A full charge enables multiple blending cycles, ensuring you&amp;#039;re never without your favorite healthy drinks.\r\n\r\nStanding at 9.4 inches tall, this blender is compact enough to fit in most bags or backpacks. The attached carry strap adds to its portability. The one-touch operation makes it incredibly user-friendly – simply press the power button to start blending.\r\n\r\nSafety features include a smart sensor that prevents the blender from operating when not properly assembled. The durable construction ensures longevity, while the easy-to-clean design (simply add water and blend to clean) makes maintenance a breeze.\r\n\r\nWhether you&amp;#039;re a fitness enthusiast, busy professional, or someone who enjoys fresh, homemade drinks, this USB rechargeable blender offers a convenient solution. Its stylish pink color adds a fun, trendy touch to your kitchen or workspace. Experience the ease of creating nutritious beverages anytime, anywhere with this portable, powerful blender.', NULL, 'SKU17652931253624', '280.00', '74.00', 1, 1, 'Green', NULL, '0.50', 50, 'assets/uploads/product_1765293125_69383c45d1ab0.jpg', 'assets/uploads/product_1765293125_69383c45d4ec3.jpg', '2025-12-09 15:12:05', '2025-12-09 16:58:45', '0.00', 0),
(28, 10, 'Shake N Take 0.8L - 2 in 1 Durable Sports Bottle Smoothie Blender - Blue', 'shake-n-take-08l-2-in-1-durable-sports-bottle-smoothie-blender-blue', 'Shake N Take', 'Having a blender that can help mix smoothies, protein shakes and cocktails, and have you drink it directly just makes life easier. It is easy to use, just plug in and choose what fruit or protein to blend and you have it ready to drink without having to pour it into a cup first with its Flip-Up Straw and a lid that is twisted on to avoid spills. \r\n\r\nThis Sports Bottle Blender is made from the finest materials that allow a clear view of bottle content, durability, long life span, rust-free blade and lightweight for easy carriage. \r\n\r\nN/B: The item comes in four different colours which are Green, Blue, Purple and Yellow so when you place an order expect to get any of the colours available.\r\n\r\n\r\nSPECIFICATIONS\r\nCapacity: up to 22-ounce bottle (up to 0.65 litres)\r\nMaterial: ABS plastic and PET impact\r\nBlade Type: Stainless steel \r\nComes with a tube &amp;quot;Flip-Up Straw&amp;quot; and twist-on lid to prevent spilt drinks\r\nCompact size fits auto cup holder\r\nHigh-powered base motor and spill-resistant\r\nGreat for mixing protein shakes, smoothies and cocktails\r\nDishwasher Safe\r\nStainless steel blades for durability, quality, and long lifespan, whether frozen yoghurt fruit smoothie or milk\r\nLightweight, compact and easy to carry\r\nGlass Top Modern Sport\r\nGauge internally the precise blending components for making shakes, smoothies, juices and other beverages', NULL, 'SKU17652962573409', '250.00', '30.00', 1, 1, 'Green, Blue, Purple and Yellow', NULL, '1.00', 10, 'assets/uploads/product_1765296257_6938488138290.jpg', NULL, '2025-12-09 16:04:17', '2025-12-09 16:58:45', '0.00', 0),
(29, 6, 'Wardrobe 4Doors 2Drawers Madri - Brown/Cream', 'wardrobe-4doors-2drawers-madri-browncream', NULL, 'Create Your Own Unique Storage Solution With This Wooden Wardrobe, Uniquely Designed Wardrobe For Your Storage Purpose, Comes With 1 Short Rail For Perfect Hanging Of Your Clothes, Stylish Chrome Handles For Easy Opening Of Your Wardrobe, 4 Doors, 5 Compartment Shelves &amp; 2 Drawers Provides Ample Space To Store All Of Your Clothes, Contemporary Design Makes It An Easy Addition To Any Bedroom Decor, Delivered in flat pack.\r\n\r\nOrder on Jumia Ghana and have it delivered to you at your doorsteps \r\n \r\n\r\nSPECIFICATIONS\r\n \r\n\r\nWardrobe Dimension (L x B x H): 110 x 47 x 197 cm\r\nAvailable Color: Brown And Cream\r\nIn The Box: 1PC\r\nMaterial: Wooden', NULL, 'SKU17652977645052', '15800.00', '33.00', 1, 1, NULL, NULL, NULL, 4, 'assets/uploads/product_1765297764_69384e646fcfb.jpg', 'assets/uploads/product_1765297764_69384e6471d95.jpg', '2025-12-09 16:29:24', '2025-12-09 16:58:45', '0.00', 0),
(30, 2, 'GALUIN Men\'s Loafers Slip-on Casual Sneakers Comfortable Shoes Grey', 'GALUIN Men\'s Loafers Slip-on Casual Sneakers Comfortable Shoes Grey', 'GALUIN', 'Dear customers, welcome to GALUIN Fashion. Thank you for your interest in our products. We offer a wide range of fashion products in beautiful patterns, bright colours and trendy styles. Whether you are looking for fashionable men&amp;amp;#039;s and women&amp;amp;#039;s casual shoes, sports shoes, or fashionable casual suits for men and women, you will always find what you need in our shop. The product you are browsing is stylish, practical and cost-effective, making it a must-have for you. Meanwhile, you can also click on the shop name to enter our shop and explore more fashionable surprises.\r\n\r\nNow, we will introduce you more details of this product.\r\n\r\nSimple and stylish: the classic black colour scheme, with a white sole and red and blue side panels, is simple yet stylish.\r\n\r\nVersatility: The black colour makes it easy to match with all styles of clothing, whether it&amp;amp;#039;s casual, sportswear or simple formal wear, it&amp;amp;#039;s suitable for many occasions.\r\n\r\nWearing experience\r\n\r\nComfortable fit: The upper material is soft and the laces can be adjusted to fit the foot closely, providing a comfortable wrap and reducing friction and discomfort when walking.\r\n\r\n \r\n\r\nCushioning and lightweight: The thick sole has a certain cushioning performance, which can reduce the impact of the ground when walking, and the overall weight is light, which will not cause too much burden on the feet, suitable for long time wearing.', NULL, 'SKU17653398037095', '190.00', '48.00', 1, 1, 'Grey', 'EU 40,EU 41,EU 42,EU 43,EU 44', '0.20', 10, 'assets/uploads/product_1765340022_6938f37634d9a.jpg', 'assets/uploads/product_1765340022_6938f3763b6c9.jpg,assets/uploads/product_1765340022_6938f3763d341.jpg,assets/uploads/product_1765340022_6938f3763f903.jpg,assets/uploads/product_1765340022_6938f37640231.jpg', '2025-12-10 04:10:03', '2025-12-10 05:00:45', '0.00', 0),
(31, 2, 'Oraimo\'s Watch 6N 1.83\'\' Full Touch Color Screen Smart Watch - Black', 'oraimos-watch-6n-183-full-touch-color-screen-smart-watch-black', 'Oraimo', 'Product Parameters:\r\nMATERIAL：ABS /PC，44.6*37.1*10.6mm\r\nDisplay：1.83\'\' TFT 240*284\r\nBATTERY CAPACITY：300mAh\r\nCharging : DC 5V 100mA，3H\r\nmajor function : meter step、Bluetooth call、Heartrate、Blood Oxygen\r\nWaterproof：IP68\r\nAPP：oraimo health', NULL, 'SKU17654186631336', '325.00', '52.00', 1, 1, 'Black', NULL, '0.11', 10, 'assets/uploads/product_1765418775_693a2717479d2.jpg', 'assets/uploads/product_1765418775_693a27174ca9a.jpg,assets/uploads/product_1765418775_693a271752d1d.jpg,assets/uploads/product_1765418775_693a2717790f9.jpg,assets/uploads/product_1765418775_693a27177bdf5.jpg', '2025-12-11 02:04:23', '2025-12-11 02:06:15', '0.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `review_date` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `product_id`, `rating`, `comment`, `status`, `review_date`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 4, 'Great product', 'approved', '2025-11-20 16:30:27', '2025-11-20 16:30:27', '2025-12-08 04:26:23'),
(2, 3, 5, 5, 'The products is really exactly what was shown on the site.. \nVery neat and nice bag. Kudos #Cartella', 'approved', '2025-12-02 07:54:00', '2025-12-02 07:54:00', '2025-12-08 04:26:23'),
(3, 3, 12, 4, 'Great Product from Cartella.', 'approved', '2025-12-02 09:12:27', '2025-12-02 09:12:27', '2025-12-08 04:26:23'),
(4, 3, 21, 5, 'I like it, very nice product from cartella', 'approved', '2025-12-05 07:12:27', '2025-12-05 07:12:27', '2025-12-08 04:26:23'),
(5, 3, 10, 5, 'Great product!', 'approved', '2025-12-08 04:32:09', '2025-12-08 04:32:09', '2025-12-08 04:32:09'),
(6, 3, 20, 5, 'Great product.. quality screen resolution very sharp', 'approved', '2025-12-08 04:37:14', '2025-12-08 04:37:14', '2025-12-08 04:37:14'),
(7, 3, 25, 5, 'Great and quality product from cartella store. Kudos', 'approved', '2025-12-09 08:23:24', '2025-12-09 08:23:24', '2025-12-09 08:23:24'),
(8, 3, 4, 5, 'Really love the product. Indeed quality stock', 'approved', '2025-12-09 08:25:11', '2025-12-09 08:25:11', '2025-12-09 08:25:11'),
(9, 3, 16, 5, 'Love it. it\'s a good one', 'approved', '2025-12-09 08:31:19', '2025-12-09 08:31:19', '2025-12-09 08:31:19'),
(10, 3, 6, 4, 'High rated product from Cartella', 'approved', '2025-12-11 03:21:37', '2025-12-11 03:21:37', '2025-12-11 03:21:37');

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_rating_on_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
    UPDATE `products` p
    SET p.average_rating = (
        SELECT COALESCE(AVG(r.rating), 0)
        FROM `reviews` r
        WHERE r.product_id = OLD.product_id 
        AND r.status = 'approved'
    ),
    p.review_count = (
        SELECT COUNT(*)
        FROM `reviews` r
        WHERE r.product_id = OLD.product_id 
        AND r.status = 'approved'
    )
    WHERE p.product_id = OLD.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_on_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE `products` p
        SET p.average_rating = (
            SELECT COALESCE(AVG(r.rating), 0)
            FROM `reviews` r
            WHERE r.product_id = NEW.product_id 
            AND r.status = 'approved'
        ),
        p.review_count = (
            SELECT COUNT(*)
            FROM `reviews` r
            WHERE r.product_id = NEW.product_id 
            AND r.status = 'approved'
        )
        WHERE p.product_id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_on_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    -- Update for the old product if status or rating changed
    IF OLD.status != NEW.status OR OLD.rating != NEW.rating THEN
        UPDATE `products` p
        SET p.average_rating = (
            SELECT COALESCE(AVG(r.rating), 0)
            FROM `reviews` r
            WHERE r.product_id = OLD.product_id 
            AND r.status = 'approved'
        ),
        p.review_count = (
            SELECT COUNT(*)
            FROM `reviews` r
            WHERE r.product_id = OLD.product_id 
            AND r.status = 'approved'
        )
        WHERE p.product_id = OLD.product_id;
    END IF;
    
    -- Also update for the new product (in case product_id changes, though it shouldn't)
    UPDATE `products` p
    SET p.average_rating = (
        SELECT COALESCE(AVG(r.rating), 0)
        FROM `reviews` r
        WHERE r.product_id = NEW.product_id 
        AND r.status = 'approved'
    ),
    p.review_count = (
        SELECT COUNT(*)
        FROM `reviews` r
        WHERE r.product_id = NEW.product_id 
        AND r.status = 'approved'
    )
    WHERE p.product_id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Cartella', 'string', 'Site title', '2025-12-03 11:30:49'),
(2, 'contact_email', 'cartella@gmail.com', 'string', 'Primary contact email', '2025-12-03 11:30:49'),
(3, 'maintenance_mode', '0', 'boolean', 'Maintenance mode on/off', '2025-12-07 06:21:54'),
(4, 'shipping_enabled', '1', 'boolean', 'Is shipping enabled', '2025-12-03 11:30:49'),
(5, 'shipping_cost', '20', 'decimal', 'Default shipping cost', '2025-12-03 11:52:03'),
(6, 'free_shipping_threshold', '500', 'decimal', 'Free shipping threshold amount', '2025-12-03 11:35:01'),
(7, 'rating_enabled', '1', 'boolean', 'Enable product ratings', '2025-12-03 11:30:49'),
(8, 'rating_threshold', '0', 'int', 'Min rating to show', '2025-12-03 11:35:44'),
(9, 'smtp_host', 'smtp.gmail.com', 'string', 'SMTP host', '2025-12-03 11:55:12'),
(10, 'smtp_port', '465', 'int', 'SMTP port', '2025-12-03 11:55:12'),
(11, 'smtp_user', 'kampusgig@gmail.com', 'string', 'SMTP username', '2025-12-03 11:55:12'),
(12, 'smtp_from', 'noreply@cartella.com', 'string', 'Email from address', '2025-12-03 11:55:12'),
(60, 'smtp_pass', 'hqmxyufaxgtmymna', 'string', 'SMTP password', '2025-12-03 11:55:12'),
(77, 'maintenance_start', '2025-12-03 12:59:00', 'datetime', 'Maintenance window start (Y-m-d H:i:s)', '2025-12-03 12:59:38'),
(78, 'maintenance_end', '2025-12-03 13:20:00', 'datetime', 'Maintenance window end (Y-m-d H:i:s)', '2025-12-03 13:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `shipping`
--

CREATE TABLE `shipping` (
  `shipping_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `shipping_address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `courier_service` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Transit','Delivered','Returned') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `status_logs`
--

CREATE TABLE `status_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` varchar(255) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `status_logs`
--

INSERT INTO `status_logs` (`id`, `order_id`, `old_status`, `new_status`, `notes`, `changed_by`, `changed_at`) VALUES
(3, 94, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-01 15:53:06'),
(4, 94, 'delivered', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-01 15:53:57'),
(5, 95, 'processing', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:30:36'),
(6, 96, 'processing', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:28'),
(7, 96, 'shipped', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:29'),
(8, 96, 'shipped', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:30'),
(9, 96, 'shipped', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:30'),
(10, 96, 'shipped', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:33'),
(11, 96, 'shipped', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-01 16:31:33'),
(12, 96, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-01 16:32:17'),
(13, 96, 'delivered', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-01 16:32:17'),
(14, 95, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-02 12:37:52'),
(15, 120, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-09 02:35:57'),
(16, 120, 'delivered', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-09 02:36:00'),
(17, 119, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-09 03:15:14'),
(18, 117, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-09 03:20:02'),
(19, 69, 'processing', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-09 03:26:03'),
(20, 110, 'cancelled', 'pending', '', 'winkobbyosah@gmail.com', '2025-12-09 03:54:09'),
(21, 97, 'shipped', 'delivered', '', 'winkobbyosah@gmail.com', '2025-12-09 03:55:32'),
(22, 29, 'processing', 'shipped', '', 'winkobbyosah@gmail.com', '2025-12-09 06:55:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('Customer','Admin') DEFAULT 'Customer',
  `is_active` tinyint(1) DEFAULT 1,
  `date_joined` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `address`, `profile_image`, `role`, `is_active`, `date_joined`, `last_login`, `reset_token`, `remember_token`, `token_expiry`) VALUES
(3, 'Godwin Osah', NULL, NULL, 'osahgodwin34@gmail.com', '$2y$10$XBmaYSIGBMkxWRHNWQkmW.oiKiW/A47rt6SiLpsFjPfWZJenVRF/C', '+233241311105', 'ACCRA, GHANA', NULL, 'Customer', 1, '2025-11-08 16:16:38', '2025-12-11 03:19:01', '3dd2fe50c9caf5d3476df78b50607d479a3d89b09109ab1b0be4130af5d04dbe', '6a4af263bdb471c4221d95db343f83479519839925fc4454c6c3eb17222f5ae5', '2026-01-10 03:19:01'),
(4, 'Carl Osah', 'Carl', 'Osah', '220005083@st.uew.edu.gh', '$2y$10$Vsbw9VDVQz19kKONLvdcKuHTVdlo3SaHxcPG4Sc9lMMuAc6qGdz4y', '0543212041', NULL, NULL, 'Customer', 1, '2025-11-25 05:45:46', '2025-11-25 05:45:58', NULL, NULL, NULL),
(5, 'John Doe', 'John', 'Doe', '202100661@st.uew.edu.gh', '$2y$10$k3eeaBWLWje5CNMVpogMa.qWjsuEJMKOv02IeaBRuyX4RvujcX/Zi', '0543212041', NULL, NULL, 'Customer', 1, '2025-11-26 20:13:53', '2025-11-26 20:17:06', NULL, NULL, NULL),
(6, 'Stephen Doe', NULL, NULL, 'winkobbyosah@gmail.com', '$2y$10$PLhmlM5Yd0HFzUi7W/jbIOKhz28WsoKFkewA9KbBuWglrVrJ.KCd.', '0200850054', 'Ada, Kasseh Accra', NULL, 'Admin', 1, '2025-11-27 20:14:30', '2025-12-11 03:46:19', NULL, 'e25389ec99683f5e2e61766fa5d1159daad5601da4bb93db34845ed42d51a566', '2026-01-03 17:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','other') NOT NULL DEFAULT 'home',
  `street_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Ghana',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `address_type`, `street_address`, `city`, `region`, `postal_code`, `country`, `is_default`, `created_at`, `updated_at`) VALUES
(4, 3, 'home', '105 Olonka Street, Kasseh Ada', 'Kasseh', 'Greater Accra', '00233', 'Ghana', 1, '2025-11-25 11:30:03', '2025-12-05 12:05:17'),
(5, 3, 'work', 'Adasco', 'Ada', 'Greater Accra', '00233', 'Ghana', 0, '2025-11-26 04:13:39', '2025-12-05 12:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_notification_preferences`
--

CREATE TABLE `user_notification_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `new_products` tinyint(1) DEFAULT 1,
  `featured_products` tinyint(1) DEFAULT 1,
  `sales_promotions` tinyint(1) DEFAULT 1,
  `important_news` tinyint(1) DEFAULT 1,
  `order_updates` tinyint(1) DEFAULT 1,
  `newsletter` tinyint(1) DEFAULT 1,
  `product_reviews` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_notification_preferences`
--

INSERT INTO `user_notification_preferences` (`preference_id`, `user_id`, `new_products`, `featured_products`, `sales_promotions`, `important_news`, `order_updates`, `newsletter`, `product_reviews`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 1, 1, 1, 1, 0, '2025-12-09 02:17:29', '2025-12-09 02:55:35');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `date_added`) VALUES
(140, 6, 10, '2025-11-28 03:57:58'),
(141, 6, 11, '2025-11-28 17:19:39'),
(143, 6, 13, '2025-11-28 19:41:54'),
(144, 6, 16, '2025-11-28 21:00:00'),
(147, 6, 18, '2025-11-29 08:12:55'),
(148, 6, 12, '2025-11-29 09:24:43'),
(166, 3, 4, '2025-12-04 19:29:27'),
(167, 3, 25, '2025-12-05 06:06:57'),
(170, 3, 15, '2025-12-11 03:39:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`banner_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_position` (`position`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_active` (`is_active`,`start_date`,`end_date`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`subscriber_id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_status` (`subscription_status`),
  ADD KEY `idx_subscribed_at` (`subscribed_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `fk_orders_coupon` (`coupon_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_orderitems_order` (`order_id`);

--
-- Indexes for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `payment_webhooks`
--
ALTER TABLE `payment_webhooks`
  ADD PRIMARY KEY (`webhook_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  ADD KEY `idx_reviews_product` (`product_id`),
  ADD KEY `idx_reviews_status` (`status`),
  ADD KEY `idx_reviews_user_status` (`user_id`,`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shipping`
--
ALTER TABLE `shipping`
  ADD PRIMARY KEY (`shipping_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_token_expiry` (`token_expiry`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_default` (`user_id`,`is_default`);

--
-- Indexes for table `user_notification_preferences`
--
ALTER TABLE `user_notification_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `banner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_webhooks`
--
ALTER TABLE `payment_webhooks`
  MODIFY `webhook_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `shipping`
--
ALTER TABLE `shipping`
  MODIFY `shipping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `status_logs`
--
ALTER TABLE `status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_notification_preferences`
--
ALTER TABLE `user_notification_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD CONSTRAINT `fk_contact_replies_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contact_replies_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD CONSTRAINT `order_status_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_log_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_webhooks`
--
ALTER TABLE `payment_webhooks`
  ADD CONSTRAINT `payment_webhooks_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shipping`
--
ALTER TABLE `shipping`
  ADD CONSTRAINT `shipping_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD CONSTRAINT `status_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_notification_preferences`
--
ALTER TABLE `user_notification_preferences`
  ADD CONSTRAINT `user_notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
