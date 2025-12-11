# Cartella (CartMate) E-commerce Platform

Cartella is a PHP-based e-commerce platform with an admin dashboard, product/catalog management, carts, checkout, Paystack payments, notifications, and wishlist/reviews.

## Features
- User auth, account area, wishlist, reviews
- Product/catalog management, discounts, featured/new flags
- Cart, checkout, order tracking, Paystack webhook handler
- Notification engine (email), newsletter opt-in, contact forms
- Admin panel for products, categories, coupons, orders, banners, users

## Requirements
- PHP 8.x, Composer
- MySQL 5.7+/MariaDB 10.3+
- Web server (Apache/nginx). `.htaccess` included for Apache.

## Setup
1) Clone
```bash
git clone https://github.com/Winkobby/cartella.git
cd cartella
```
2) Install deps
```bash
composer install
```
3) Configure environment
- Copy `.env.example` to `.env` (or create) and set DB, SMTP, PAYSTACK keys, `WHATSAPP_NUMBER`, etc.
- Ensure `includes/config.php` loads environment vars (already wired for .env).

4) Database
- Create a database, then run the SQL in `database/notification_preferences_table.sql` and `database/contacts_table.sql` (and any other schema files you maintain).
- Optionally run helper scripts in `tools/` (e.g., `setup_triggers.php`, `add_product_slugs.php`).

5) File permissions
- Ensure `logs/` and `assets/images/uploads/` are writable by the web server.

6) Run locally
- Serve via Apache or `php -S localhost:8000` from project root (ensure routing for assets).

## Key Endpoints / Pages
- Storefront: `index.php`, `products.php`, `product.php`
- Cart/Checkout: `cart.php`, `checkout.php`, `confirmation.php`
- User: `signin.php`, `signup.php`, `account.php`
- Admin: `a_index.php`, `a_products.php`, `a_orders.php`, `a_users.php`
- Webhook: `paystack_webhook.php`

## Tests
- Integration smoke tests are provided as helper pages under `/test_*`. No automated PHPUnit suite is included.

## Notes
- Logs are stored under `logs/` (email logs, etc.).
- Notification preferences and newsletter opt-ins live in `user_notification_preferences`.
- Contact form entries use `database/contacts_table.sql` schema.

## Deployment
- Set production `.env` values (DB, SMTP, PAYSTACK keys).
- Ensure proper file permissions for uploads and logs.
- Point your virtual host/document root to the project root.

## License
This project is licensed under the MIT License. See [LICENSE](LICENSE).
