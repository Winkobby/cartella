-- Create notification preferences table
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `new_products` tinyint(1) DEFAULT 1,
  `featured_products` tinyint(1) DEFAULT 1,
  `sales_promotions` tinyint(1) DEFAULT 1,
  `important_news` tinyint(1) DEFAULT 1,
  `order_updates` tinyint(1) DEFAULT 1,
  `newsletter` tinyint(1) DEFAULT 1,
  `product_reviews` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
