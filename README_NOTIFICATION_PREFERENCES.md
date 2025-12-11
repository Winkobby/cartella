# Notification Preferences & Newsletter Subscription Feature

## Overview
This feature allows customers to manage their advertisement and notification preferences directly from their account page. They can toggle switches to subscribe/unsubscribe from different types of notifications, and when they subscribe to the newsletter, they are automatically added to the newsletter subscribers list.

## Features Implemented

### 1. **Database Table**
- **Table Name**: `user_notification_preferences`
- **Columns**:
  - `preference_id`: Unique identifier
  - `user_id`: Reference to user
  - `new_products`: Toggle for new product notifications (default: 1)
  - `featured_products`: Toggle for featured product notifications (default: 1)
  - `sales_promotions`: Toggle for sales and promotions (default: 1)
  - `important_news`: Toggle for important announcements (default: 1)
  - `order_updates`: Toggle for order status updates (default: 1)
  - `newsletter`: Toggle for newsletter subscription (default: 1)
  - `product_reviews`: Toggle for product review notifications (default: 0)
  - `created_at`: Timestamp when preferences were created
  - `updated_at`: Timestamp when preferences were last updated

### 2. **User Interface**
The account page now includes a **"Notifications & Preferences"** tab with:

#### Newsletter Subscription Section
- Toggle switch to enable/disable newsletter
- Description about benefits of subscription
- Automatically subscribes user to `newsletter_subscribers` table when enabled

#### Notification Types
Users can enable/disable notifications for:
1. **New Products** - Get notified when new products are launched
2. **Featured Products** - Receive updates on featured and recommended items
3. **Sales & Promotions** - Don't miss special discounts and promotions
4. **Important News** - Stay informed about important updates
5. **Order Updates** - Get notifications about order status and shipments
6. **Product Reviews** - Receive alerts about product reviews

Each notification type has:
- Descriptive icon
- Clear label
- Description of what the user will receive
- Toggle switch for easy enable/disable

### 3. **Backend AJAX Handler**
**File**: `/ajax/preferences.php`

Provides the following actions:

#### GET - `get_preferences`
Retrieves user's current notification preferences. If no preferences exist, creates default ones automatically.

```
GET: /ajax/preferences.php?action=get_preferences
Response: {
    "success": true,
    "preferences": {
        "preference_id": 1,
        "user_id": 5,
        "new_products": 1,
        "featured_products": 1,
        "sales_promotions": 1,
        "important_news": 1,
        "order_updates": 1,
        "newsletter": 1,
        "product_reviews": 0,
        ...
    }
}
```

#### POST - `update_preference`
Updates a single preference and optionally syncs with newsletter_subscribers table.

```
POST: /ajax/preferences.php
Parameters:
- action: update_preference
- preference_key: (string) preference key name
- value: (int) 1 or 0

Response: {
    "success": true,
    "message": "Preference updated successfully",
    "preference_key": "new_products",
    "value": 1
}
```

#### POST - `update_all_preferences`
Updates multiple preferences at once.

```
POST: /ajax/preferences.php?action=update_all_preferences
Request Body (JSON):
{
    "new_products": 1,
    "featured_products": 1,
    "sales_promotions": 0,
    "important_news": 1,
    "order_updates": 1,
    "newsletter": 1,
    "product_reviews": 0
}

Response: {
    "success": true,
    "message": "All preferences updated successfully",
    "updated_preferences": {...}
}
```

### 4. **Newsletter Integration**
When a user enables the newsletter preference:
- They are automatically added to the `newsletter_subscribers` table
- Their subscription status is set to "active"
- A subscription token is generated for unsubscribe links

When disabled:
- Their subscription status is set to "inactive"
- They receive an unsubscribed_at timestamp

### 5. **Frontend JavaScript Functions**

#### `loadNotificationPreferences()`
Fetches current preferences from server and updates the UI

#### `updatePreferenceUI(preferences)`
Updates all checkboxes based on preference object

#### `updatePreference(preferenceKey, value)`
Sends preference update to server with real-time feedback:
- Shows success message for 3 seconds
- Displays toast notification
- Reverts checkbox if update fails
- Handles errors gracefully

## Installation Instructions

### Step 1: Create the Database Table

Run the initialization script:
```
http://localhost/cartmate/database/init_notification_preferences.php
```

Or manually execute the SQL in phpMyAdmin:
```sql
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
```

### Step 2: Files Created/Modified

**New Files:**
- `/database/notification_preferences_table.sql` - SQL schema file
- `/database/init_notification_preferences.php` - Database initialization script
- `/ajax/preferences.php` - AJAX handler for preference management

**Modified Files:**
- `/account.php` - Added "Notifications & Preferences" tab with UI and JavaScript functions

### Step 3: Clear Cache (If Needed)
Clear your browser cache to ensure the latest JavaScript is loaded.

## Usage

### For Customers

1. **Access Preferences**
   - Log in to their account
   - Click on "Notifications & Preferences" in the sidebar
   - All current preferences are automatically loaded

2. **Toggle Preferences**
   - Click on any toggle switch to enable/disable notifications
   - Changes are saved instantly
   - Success message appears briefly
   - Toast notification shows the action taken

3. **Newsletter Subscription**
   - Toggle the newsletter switch at the top
   - When enabled, customer is added to newsletter list
   - When disabled, customer is removed from active subscribers

### For Administrators

**Sending Newsletter Emails:**
You can send newsletter emails to subscribers:

```php
// Get active subscribers
$query = "SELECT email FROM newsletter_subscribers WHERE subscription_status = 'active'";

// Get preferences for advanced targeting
$query = "SELECT u.email FROM users u 
          JOIN user_notification_preferences p ON u.user_id = p.user_id 
          WHERE p.newsletter = 1 AND p.new_products = 1";
```

## Error Handling

The system includes comprehensive error handling:

1. **Database Errors**: Logged and returned with user-friendly messages
2. **Network Errors**: Checkboxes revert if update fails
3. **Missing Preferences**: Auto-creates default preferences on first load
4. **Invalid Inputs**: Validates preference keys server-side

## Performance Considerations

- Preferences are loaded only when the notifications tab is opened
- Individual toggles update immediately without page reload
- Updates use efficient UPDATE queries
- Newsletter integration is lightweight

## Security

- User authentication is required
- Only users can modify their own preferences
- Foreign key constraint ensures data integrity
- Input validation on both frontend and backend
- SQL injection prevention using prepared statements

## Testing Checklist

- [x] Database table created successfully
- [x] New "Notifications & Preferences" tab visible in account page
- [x] All 7 notification toggle switches display correctly
- [x] Toggling preferences saves to database
- [x] Toggling newsletter preference updates newsletter_subscribers table
- [x] Success messages appear when preferences change
- [x] Preferences load correctly when tab is opened
- [x] Default preferences created for new users
- [x] All icons and descriptions display properly
- [x] Mobile responsive design works correctly

## Future Enhancement Ideas

1. Email delivery when users toggle preferences
2. Frequency selection (daily, weekly, never)
3. Notification history/log
4. SMS notification option
5. Push notifications for web app
6. Preference import/export
7. Bulk notification campaigns
8. A/B testing for notification content

## Support & Troubleshooting

### Issue: "Preferences page not loading"
- **Solution**: Check if database table was created. Run the init script.

### Issue: "Changes not saving"
- **Solution**: Verify user is logged in and `ajax/preferences.php` is accessible.

### Issue: "Newsletter not syncing"
- **Solution**: Ensure `newsletter_subscribers` table exists with `email` column.

## Contact
For issues or feature requests, please contact the development team.
