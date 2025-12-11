# 🔔 Automated Notification System - Complete Documentation

## Overview

The CartMate Automated Notification System sends targeted emails to customers based on:
- Their notification preferences (set in Account → Notifications & Preferences)
- Specific events (new products, sales, order updates)
- Product/coupon attributes (is_new, is_featured, discount)

## ✨ Features

### 1. **New Product Notifications**
- ✅ Automatically sent when a product is marked with `is_new = 1`
- ✅ Sent to all users with "New Products" preference enabled
- ✅ Beautiful email template with product image, description, and price

### 2. **Featured Product Notifications**
- ✅ Automatically sent when a product is marked with `is_featured = 1`
- ✅ Sent to users with "Featured Products" preference enabled
- ✅ Different subject line and styling to highlight featured items

### 3. **Sales & Promotions Notifications**
- ✅ Automatically sent when an admin creates a coupon with a discount
- ✅ Sent to all users with "Sales & Promotions" preference enabled
- ✅ Shows coupon code, discount amount, validity period, minimum order

### 4. **Order Update Notifications**
- ✅ Automatically sent when order status changes (Processing, Shipped, Delivered, Cancelled)
- ✅ Sent only to users with "Order Updates" preference enabled
- ✅ Different icons and messages based on order status
- ✅ Includes tracking link

### 5. **Preference-Based Filtering**
- ✅ Respects customer notification preferences
- ✅ Only sends emails to opted-in customers
- ✅ Customers control what they receive in their account settings

## 🏗️ Architecture

### Files Created

```
/includes/NotificationEngine.php
├── notifyNewProduct()              - Handle new product notifications
├── notifyCouponCreated()          - Handle coupon/sales notifications
├── notifyOrderUpdate()            - Handle order status notifications
├── sendProductNotificationEmail() - Send individual product emails
├── sendCouponNotificationEmail()  - Send individual coupon emails
└── Email HTML generators         - Create beautiful email templates
```

### Files Modified

1. **`/a_pro.php`** - Product Admin Page
   - Added notification trigger after product creation
   - Sends emails respecting user preferences
   - Logs notification results

2. **`/a_coupons.php`** - Coupon Admin Page
   - Added notification trigger after coupon creation
   - Sends emails to opt-in users
   - Handles discount type (percentage/fixed)

3. **`/a_orders.php`** - Order Admin Page
   - Added notification trigger after status update
   - Respects user's order update preference
   - Includes order tracking information

## 📊 Database Schema

### Table: `user_notification_preferences`

```sql
CREATE TABLE user_notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    new_products TINYINT(1) DEFAULT 1,
    featured_products TINYINT(1) DEFAULT 1,
    sales_promotions TINYINT(1) DEFAULT 1,
    important_news TINYINT(1) DEFAULT 1,
    order_updates TINYINT(1) DEFAULT 1,
    newsletter TINYINT(1) DEFAULT 1,
    product_reviews TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**What Each Column Controls:**

| Column | Email Sent When | Default |
|--------|-----------------|---------|
| `new_products` | Admin adds product with `is_new=1` | 1 (enabled) |
| `featured_products` | Admin adds product with `is_featured=1` | 1 (enabled) |
| `sales_promotions` | Admin creates coupon with discount | 1 (enabled) |
| `important_news` | Major announcements | 1 (enabled) |
| `order_updates` | Order status changes | 1 (enabled) |
| `newsletter` | Newsletter campaign sent | 1 (enabled) |
| `product_reviews` | New reviews on customer's purchases | 0 (disabled) |

## 🔄 Notification Triggers

### Trigger 1: New Product Notification

**When:** Product is added with `is_new = 1`

**Who Gets It:** Users where `user_notification_preferences.new_products = 1`

**Code Location:** `/a_pro.php` lines ~170

```php
// In product creation AJAX action
$notificationEngine->notifyNewProduct($notification_data);
```

**Email Includes:**
- Product name
- Product image
- Product description
- Original price
- Discounted price (if discount > 0)
- "View Product" button

**Example Email Subject:** "🆕 New Product: Leather Bag Premium"

---

### Trigger 2: Featured Product Notification

**When:** Product is added with `is_featured = 1`

**Who Gets It:** Users where `user_notification_preferences.featured_products = 1`

**Code Location:** `/a_pro.php` lines ~170

```php
// Same trigger, different type detected
if ($is_featured) {
    $notification_type = 'featured_products';
}
```

**Email Includes:**
- Same as new product but with "Featured Product" badge
- ⭐ Icon in subject line

**Example Email Subject:** "⭐ Featured Product Alert: Premium Leather Bag"

---

### Trigger 3: Sales & Promotions Notification

**When:** Coupon is created with a discount

**Who Gets It:** Users where `user_notification_preferences.sales_promotions = 1`

**Code Location:** `/a_coupons.php` lines ~50

```php
// In coupon creation AJAX action
$notificationEngine->notifyCouponCreated($coupon_data);
```

**Email Includes:**
- Discount amount (e.g., "20% OFF" or "$10 OFF")
- Coupon code (large, easy to copy)
- Coupon description
- Validity period
- Minimum order requirement
- "Shop Now" button

**Example Email Subject:** "🎉 Exclusive Promotion: 20% OFF!"

---

### Trigger 4: Order Update Notification

**When:** Order status changes to Processing, Shipped, Delivered, or Cancelled

**Who Gets It:** Users where `user_notification_preferences.order_updates = 1`

**Code Location:** `/a_orders.php` lines ~88

```php
// After order status update
$notificationEngine->notifyOrderUpdate($order_id, $status, $email, $name);
```

**Email Includes:**
- Order number
- Order date
- Current status with icon
- Order total
- Tracking link
- Status-specific message

**Example Email Subjects:**
- "Order Update: 📦 Your order is being prepared"
- "Order Update: 🚚 Your order has been shipped"
- "Order Update: ✅ Your order has been delivered"

---

## 🚀 Implementation Details

### How Notifications Are Sent

1. **Admin Action Occurs**
   - Product created with `is_new=1`
   - Coupon created with discount
   - Order status updated

2. **Trigger Code Executes**
   ```php
   $notificationEngine = new NotificationEngine($pdo, $functions);
   $result = $notificationEngine->notifyNewProduct($data);
   ```

3. **Query Runs to Get Eligible Users**
   ```sql
   SELECT u.email, u.first_name 
   FROM users u
   INNER JOIN user_notification_preferences unp ON u.user_id = unp.user_id
   WHERE unp.new_products = 1 AND u.status = 'Active'
   ```

4. **Email Sent to Each User**
   - Beautiful HTML template
   - Personalized greeting with user's first name
   - Product/coupon details
   - Call-to-action button
   - Unsubscribe/preference update info

5. **Result Logged**
   ```
   Product notification sent: {"success": true, "emails_sent": 42}
   ```

### Code Flow Diagram

```
Admin Action (add product, create coupon, update order)
        ↓
Trigger code in a_pro.php/a_coupons.php/a_orders.php
        ↓
Load NotificationEngine.php
        ↓
Query database for eligible users
        ↓
For each user:
  ├─ Create personalized email
  ├─ Send via PHPMailerWrapper
  └─ Log result
        ↓
Return summary (emails_sent count)
```

## 📧 Email Templates

All emails include:
- **Header:** Beautiful gradient background with icon
- **Body:** Personalized content with product/coupon details
- **CTA Button:** "View Product", "Shop Now", "Track Order"
- **Footer:** Notification preference info and unsubscribe link

### Template Features
- ✅ Responsive design (works on mobile)
- ✅ Personalized with customer name
- ✅ Product images embedded
- ✅ Tailored to each notification type
- ✅ Clear call-to-action buttons
- ✅ Preference management links

## 🧪 Testing

### Test Page: `/test_notifications.php`

Access admin page to test notifications:

**Available Tests:**
1. **Test New Product Notification**
   - Simulates adding a new product
   - Sends to all users with "New Products" enabled

2. **Test Coupon Notification**
   - Simulates creating a 20% discount coupon
   - Sends to all users with "Sales" enabled

3. **Test Order Update Notification**
   - Simulates order being shipped
   - Sends to first active order customer

### How to Test

1. Go to `/test_notifications.php`
2. Click "Send Test Notification" button
3. Check server logs: `/logs/emails/`
4. View result message

## ⚙️ Configuration

### Email Settings

Required for emails to work:
- `PHPMailerWrapper.php` must exist in `/includes/`
- SMTP settings configured in `config.php`
- Valid sender email address

### Checking Email Status

View email logs:
```
/logs/emails/
```

Email sent log entries:
```
Product notification sent: {"success": true, "emails_sent": 42}
Coupon notification sent: {"success": true, "emails_sent": 38}
```

## 🔐 Security

- ✅ Session authentication required
- ✅ User preferences checked before sending
- ✅ SQL injection prevention via prepared statements
- ✅ Email validation before sending
- ✅ Error handling and logging
- ✅ No sensitive data exposed

## 📈 Performance

- ✅ Efficient database queries
- ✅ Respects user preferences to avoid spam
- ✅ Batch processing for multiple users
- ✅ Asynchronous-ready (no page blocking)

## 🐛 Troubleshooting

### Issue: "Emails not sending"

**Solution 1: Check PHPMailerWrapper**
```php
if (!class_exists('PHPMailerWrapper')) {
    error_log("PHPMailerWrapper not found");
}
```

**Solution 2: Check SMTP Configuration**
```php
// In includes/config.php
DEFINE('SMTP_HOST', 'your-smtp-host');
DEFINE('SMTP_PORT', 587);
DEFINE('SMTP_USER', 'your-email@example.com');
DEFINE('SMTP_PASS', 'your-password');
```

**Solution 3: Check Server Logs**
```bash
tail -f /logs/emails/*.log
```

### Issue: "Wrong customers receiving emails"

**Solution 1: Verify Preferences**
```sql
SELECT user_id, new_products, featured_products, sales_promotions, order_updates 
FROM user_notification_preferences 
WHERE user_id = 123;
```

**Solution 2: Check User Status**
```sql
SELECT user_id, status, email FROM users WHERE user_id = 123;
```

### Issue: "Database errors"

**Solution: Ensure table exists**
```sql
SHOW TABLES LIKE 'user_notification_preferences';
```

If missing, run:
```
http://localhost/cartmate/database/init_notification_preferences.php
```

## 📝 Admin Guide

### For Adding Products

1. Go to Admin → Products → Add Product
2. Fill in product details
3. **Check "Mark as New"** to notify new product subscribers
4. **Check "Mark as Featured"** to notify featured product subscribers
5. Click "Add Product"
6. ✅ Emails sent automatically to eligible customers

### For Creating Coupons

1. Go to Admin → Coupons → Create Coupon
2. Fill in coupon details
3. Set discount (any discount > 0 triggers notification)
4. Click "Create"
5. ✅ Emails sent automatically to eligible customers

### For Updating Orders

1. Go to Admin → Orders → View Order
2. Change order status (Processing, Shipped, Delivered, Cancelled)
3. Click "Update Status"
4. ✅ Email sent automatically to customer (if they have order updates enabled)

## 👥 Customer Guide

### For Managing Preferences

1. Log in to account
2. Click "Notifications & Preferences" tab
3. Toggle each notification type:
   - 🟢 Green/On = Will receive emails
   - ⚪ Gray/Off = Won't receive emails
4. Changes save automatically

### Notification Types

- **🆕 New Products** - Get alerted about new items
- **⭐ Featured Products** - Get alerted about special featured products
- **🏷️ Sales & Promotions** - Get alerted about discounts and coupons
- **📢 Important News** - Get alerted about important updates
- **📦 Order Updates** - Get alerted when order status changes
- **📧 Newsletter** - Subscribe to regular newsletters
- **⭐ Product Reviews** - Get notified about new customer reviews

## 🎯 Best Practices

### For Admins

1. **Use "Mark as New" for NEW products only** - Don't mark existing products as new
2. **Use "Mark as Featured" strategically** - Feature products regularly but not too often
3. **Create relevant coupons** - Don't send too many discount coupons (maybe 1-2 per week)
4. **Monitor email engagement** - Check how many customers have each notification enabled
5. **Test notifications** - Use the test page to verify emails are working

### For Customers

1. **Enable notifications you care about** - Disable ones you don't want
2. **Check your email** - Make sure store emails aren't going to spam
3. **Update preferences as needed** - Change anytime in your account
4. **Use coupon codes promptly** - Discounts usually expire within 7-30 days

## 📊 Usage Statistics

To get notification statistics:

```php
$stats_query = "
    SELECT 
        COUNT(DISTINCT u.user_id) as total_users,
        SUM(CASE WHEN unp.new_products = 1 THEN 1 ELSE 0 END) as new_products_enabled,
        SUM(CASE WHEN unp.featured_products = 1 THEN 1 ELSE 0 END) as featured_enabled,
        SUM(CASE WHEN unp.sales_promotions = 1 THEN 1 ELSE 0 END) as sales_enabled,
        SUM(CASE WHEN unp.order_updates = 1 THEN 1 ELSE 0 END) as orders_enabled
    FROM users u
    LEFT JOIN user_notification_preferences unp ON u.user_id = unp.user_id
    WHERE u.status = 'Active'
";
```

## 🚀 Production Deployment

Before going live:

1. ✅ Test all notification triggers
2. ✅ Verify email sending works
3. ✅ Check SMTP credentials
4. ✅ Monitor logs for errors
5. ✅ Test with real product/coupon creation
6. ✅ Verify customer receives emails
7. ✅ Check email templates render correctly
8. ✅ Ensure images load in emails
9. ✅ Test preference updates work
10. ✅ Monitor email deliverability

## 📞 Support

For issues or questions:
1. Check `/test_notifications.php` admin page
2. Review server logs in `/logs/emails/`
3. Verify database table exists
4. Check SMTP configuration
5. Test with simple notification first

---

**Status:** ✅ Production Ready
**Version:** 1.0
**Last Updated:** December 2024
**Created For:** CartMate E-Commerce Platform
