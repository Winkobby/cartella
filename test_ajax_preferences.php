<?php
/**
 * Test script to verify notification preferences AJAX endpoint
 * Access this page to test the AJAX functionality
 */

// Start session to test authenticated requests
session_start();

// Check if user is logged in - if not, redirect to signin
if (!isset($_SESSION['user_id'])) {
    // For testing, we can set a test user_id if provided
    if (isset($_GET['test_user_id'])) {
        $_SESSION['user_id'] = (int)$_GET['test_user_id'];
        $_SESSION['user_name'] = 'Test User';
    } else {
        header('Location: signin.php');
        exit;
    }
}

$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences AJAX Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .info { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .success { background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0; border-radius: 4px; color: #2e7d32; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 4px; color: #c62828; }
        button { background: #2196F3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px 5px 5px 0; }
        button:hover { background: #1976D2; }
        button.secondary { background: #757575; }
        button.secondary:hover { background: #616161; }
        .test-section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 4px; border: 1px solid #e0e0e0; }
        .test-section h3 { margin-bottom: 15px; color: #1976D2; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5; }
        .response-box { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin-top: 10px; max-height: 300px; overflow-y: auto; }
        .test-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        label { display: block; margin: 10px 0 5px 0; font-weight: 500; }
        input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Notification Preferences AJAX Test</h1>
        
        <div class="info">
            <strong>ℹ️ Current User ID:</strong> <?php echo htmlspecialchars($user_id) ?: 'Not set'; ?><br>
            <strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? '✓ Active' : '✗ Inactive'; ?>
        </div>

        <!-- Test 1: GET Preferences -->
        <div class="test-section">
            <h3>Test 1: Fetch User Preferences (GET)</h3>
            <p>Calls: <code>GET /ajax/preferences.php?action=get_preferences</code></p>
            <button onclick="testGetPreferences()">Test GET Preferences</button>
            <div id="response1" class="response-box" style="display:none;"></div>
        </div>

        <!-- Test 2: Update Single Preference -->
        <div class="test-section">
            <h3>Test 2: Update Single Preference (POST)</h3>
            <p>Updates one preference and syncs newsletter if needed</p>
            <label>Preference Key:</label>
            <select id="prefKey" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="new_products">new_products</option>
                <option value="featured_products">featured_products</option>
                <option value="sales_promotions">sales_promotions</option>
                <option value="important_news">important_news</option>
                <option value="order_updates">order_updates</option>
                <option value="newsletter">newsletter</option>
                <option value="product_reviews">product_reviews</option>
            </select>
            <label style="margin-top:10px;">Value:</label>
            <select id="prefValue" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="1">1 (Enabled)</option>
                <option value="0">0 (Disabled)</option>
            </select>
            <button onclick="testUpdatePreference()" style="margin-top:10px;">Test Update Preference</button>
            <div id="response2" class="response-box" style="display:none;"></div>
        </div>

        <!-- Test 3: Update Multiple Preferences -->
        <div class="test-section">
            <h3>Test 3: Update Multiple Preferences (POST)</h3>
            <p>Updates multiple preferences at once</p>
            <div class="test-grid">
                <label><input type="checkbox" id="pref_new_products" checked> New Products</label>
                <label><input type="checkbox" id="pref_featured_products" checked> Featured Products</label>
                <label><input type="checkbox" id="pref_sales_promotions" checked> Sales & Promotions</label>
                <label><input type="checkbox" id="pref_important_news" checked> Important News</label>
                <label><input type="checkbox" id="pref_order_updates" checked> Order Updates</label>
                <label><input type="checkbox" id="pref_newsletter" checked> Newsletter</label>
                <label><input type="checkbox" id="pref_product_reviews"> Product Reviews</label>
            </div>
            <button onclick="testUpdateAllPreferences()" style="margin-top:10px;">Test Update All Preferences</button>
            <div id="response3" class="response-box" style="display:none;"></div>
        </div>

        <!-- Test 4: Response Headers -->
        <div class="test-section">
            <h3>Test 4: Verify Response Headers</h3>
            <p>Check that response headers are correct (Content-Type: application/json)</p>
            <button onclick="testResponseHeaders()">Test Headers</button>
            <div id="response4" class="response-box" style="display:none;"></div>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #666;">
            <p><strong>Note:</strong> You must be logged in to run these tests. Session user_id is: <code><?php echo htmlspecialchars($user_id) ?: 'Not set'; ?></code></p>
            <p>If you're not logged in, <a href="signin.php">click here to sign in</a>, then return to this page.</p>
        </div>
    </div>

    <script>
        function showResponse(elementId, title, data, isSuccess = true) {
            const elem = document.getElementById(elementId);
            elem.style.display = 'block';
            
            let html = `<strong style="color: ${isSuccess ? '#2e7d32' : '#c62828'};">
                ${isSuccess ? '✓' : '✗'} ${title}
            </strong><pre style="margin-top: 10px;">`;
            
            if (typeof data === 'string') {
                html += htmlEscape(data);
            } else {
                html += JSON.stringify(data, null, 2);
            }
            
            html += '</pre>';
            elem.innerHTML = html;
        }

        function htmlEscape(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function testGetPreferences() {
            fetch('/cartmate/ajax/preferences.php?action=get_preferences')
                .then(response => {
                    console.log('Response Status:', response.status);
                    console.log('Response Headers:', {
                        'Content-Type': response.headers.get('Content-Type'),
                        'Content-Length': response.headers.get('Content-Length')
                    });
                    return response.text();
                })
                .then(text => {
                    console.log('Raw Response:', text);
                    try {
                        const data = JSON.parse(text);
                        showResponse('response1', 'GET Preferences', data, data.success);
                    } catch (e) {
                        showResponse('response1', 'GET Preferences - JSON Parse Error', 
                            `Error: ${e.message}\n\nRaw Response:\n${text}`, false);
                    }
                })
                .catch(error => {
                    showResponse('response1', 'GET Preferences - Fetch Error', 
                        `Network Error: ${error.message}`, false);
                });
        }

        function testUpdatePreference() {
            const prefKey = document.getElementById('prefKey').value;
            const prefValue = document.getElementById('prefValue').value;
            
            const formData = new FormData();
            formData.append('action', 'update_preference');
            formData.append('preference_key', prefKey);
            formData.append('value', prefValue);
            
            fetch('/cartmate/ajax/preferences.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        showResponse('response2', `Update ${prefKey}=${prefValue}`, data, data.success);
                    } catch (e) {
                        showResponse('response2', 'Update Preference - JSON Parse Error', 
                            `Error: ${e.message}\n\nRaw Response:\n${text}`, false);
                    }
                })
                .catch(error => {
                    showResponse('response2', 'Update Preference - Fetch Error', 
                        `Network Error: ${error.message}`, false);
                });
        }

        function testUpdateAllPreferences() {
            const preferences = {
                new_products: document.getElementById('pref_new_products').checked ? 1 : 0,
                featured_products: document.getElementById('pref_featured_products').checked ? 1 : 0,
                sales_promotions: document.getElementById('pref_sales_promotions').checked ? 1 : 0,
                important_news: document.getElementById('pref_important_news').checked ? 1 : 0,
                order_updates: document.getElementById('pref_order_updates').checked ? 1 : 0,
                newsletter: document.getElementById('pref_newsletter').checked ? 1 : 0,
                product_reviews: document.getElementById('pref_product_reviews').checked ? 1 : 0
            };
            
            fetch('/cartmate/ajax/preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_all_preferences',
                    ...preferences
                })
            })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        showResponse('response3', 'Update All Preferences', data, data.success);
                    } catch (e) {
                        showResponse('response3', 'Update All - JSON Parse Error', 
                            `Error: ${e.message}\n\nRaw Response:\n${text}`, false);
                    }
                })
                .catch(error => {
                    showResponse('response3', 'Update All - Fetch Error', 
                        `Network Error: ${error.message}`, false);
                });
        }

        function testResponseHeaders() {
            fetch('/cartmate/ajax/preferences.php?action=get_preferences')
                .then(response => {
                    const headers = {};
                    response.headers.forEach((value, name) => {
                        headers[name] = value;
                    });
                    
                    showResponse('response4', 'Response Headers', {
                        status: response.status,
                        statusText: response.statusText,
                        headers: headers
                    }, response.status === 200);
                })
                .catch(error => {
                    showResponse('response4', 'Headers Test - Error', error.message, false);
                });
        }

        // Auto-test on page load
        console.log('Test page loaded. Use the buttons to run tests.');
    </script>
</body>
</html>
