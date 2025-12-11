<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/settings_helper.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'Customer') !== 'Admin') {
    header('Location: signin.php');
    exit;
}

$database = new Database();
$pdo = $database->getConnection();
SettingsHelper::init($pdo);

$page_title = 'Customer Messages - Admin';
?>

<?php require_once 'includes/admin_header.php'; ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Customer Messages</h1>
                    <p class="text-gray-600">Manage and respond to customer inquiries efficiently</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search messages..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition transition-all duration-200 w-full ">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards - Clean White Design -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Messages Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Messages</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="stat-total">0</p>
                        <p class="text-xs text-gray-500 mt-1" id="stat-today">0 today</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- New Messages Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">New Messages</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="stat-new">0</p>
                        <div class="flex items-center mt-1">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                            <span class="text-xs text-gray-500">Requires attention</span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Reply Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pending Reply</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="stat-read">0</p>
                        <p class="text-xs text-gray-500 mt-1" id="stat-week">0 this week</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-xl">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Replied Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Replied</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="stat-replied">0</p>
                        <div class="flex items-center mt-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            <span class="text-xs text-gray-500">Completed</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

      
        <!-- Quick Actions - IMPROVED MOBILE RESPONSIVE TABS -->
<div class="mb-6">
    <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-2 hide-scrollbar">
        <!-- Mobile-Friendly Tabs with Icons -->
        <div class="bg-white rounded-xl shadow-sm p-1 inline-flex flex-nowrap overflow-x-auto hide-scrollbar">
            <button onclick="loadMessages('all')" class="filter-btn px-3 py-2 rounded-lg font-medium transition-all text-sm whitespace-nowrap flex items-center" data-filter="all">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span>All</span>
            </button>
            <button onclick="loadMessages('new')" class="filter-btn px-3 py-2 rounded-lg font-medium transition-all text-sm whitespace-nowrap flex items-center" data-filter="new">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>New</span>
                 <span id="new-count-badge" class="ml-1 px-1.5 py-0.5 text-xs bg-blue-500 text-white rounded-full hidden"></span>
            </button>
            <button onclick="loadMessages('read')" class="filter-btn px-3 py-2 rounded-lg font-medium transition-all text-sm whitespace-nowrap flex items-center" data-filter="read">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>Read</span>
            </button>
            <button onclick="loadMessages('replied')" class="filter-btn px-3 py-2 rounded-lg font-medium transition-all text-sm whitespace-nowrap flex items-center" data-filter="replied">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                </svg>
                <span>Replied</span>
            </button>
            <button onclick="loadMessages('archived')" class="filter-btn px-3 py-2 rounded-lg font-medium transition-all text-sm whitespace-nowrap flex items-center" data-filter="archived">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                <span>Archive</span>
            </button>
        </div>

        <!-- Mark All Read Button - Now positioned inline with tabs -->
        <button onclick="markAllAsRead()" class="px-3 py-2 bg-blue-50 text-blue-700 rounded-lg font-medium text-sm hover:bg-blue-100 transition-colors inline-flex items-center whitespace-nowrap flex-shrink-0">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            Mark Read
        </button>
    </div>
</div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <!-- Toolbar -->
            <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                    <label for="selectAll" class="text-sm font-medium text-gray-700">Select All</label>

                    <select id="bulkAction" class="ml-4 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">Bulk Actions</option>
                        <option value="mark_read">Mark as Read</option>
                        <option value="mark_replied">Mark as Replied</option>
                        <option value="archive">Archive</option>
                        <option value="delete">Delete</option>
                    </select>

                    <button onclick="applyBulkAction()" class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium text-sm hover:bg-purple-700 transition-colors">
                        Apply
                    </button>
                </div>

                <div class="text-sm text-gray-500">
                    Showing <span id="showingCount">0</span> of <span id="totalCount">0</span> messages
                </div>
            </div>

            <!-- Messages Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subject
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="messagesTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                                    <p class="mt-4 text-gray-600">Loading messages...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm text-gray-700" id="paginationInfo"></div>
                <nav class="flex items-center space-x-2" id="pagination">
                    <!-- Pagination buttons will be generated here -->
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div id="viewMessageModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Message Details</h3>
                            <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2" id="messageModalBody">
                            <!-- Message content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="showReplyForm()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Reply
                </button>
                <button type="button" onclick="closeMessageModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Reply to Customer</h3>
                        <form id="replyForm" class="space-y-4">
                            <input type="hidden" id="replyContactId" name="contact_id">
                            <input type="hidden" id="replyToEmail" name="to_email">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">To:</label>
                                <input type="text" id="replyToDisplay" readonly
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject:</label>
                                <input type="text" id="replySubject" name="subject" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                                <textarea id="replyMessage" name="message" rows="8" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="sendReply()" id="sendReplyBtn"
                    class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Send Reply
                </button>
                <button type="button" onclick="closeReplyModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentFilter = 'all';
    let currentPage = 1;
    let totalPages = 1;
    let selectedMessages = [];
    let currentMessageId = null;

    // Shared toast helper that prefers the global toast system and falls back gracefully
    function showToast(message, type = 'info') {
        if (window.toast && typeof window.toast[type] === 'function') {
            window.toast[type](message);
        } else if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadMessages('all');
        loadStats();
        updateFilterButton();

        // Add search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();

            if (searchTerm.length >= 2) {
                searchTimeout = setTimeout(() => {
                    searchMessages(searchTerm);
                }, 500);
            } else if (searchTerm.length === 0) {
                // Reset to normal view when search is cleared
                loadMessages(currentFilter, currentPage);
            }
        });
    });

/* Update your updateFilterButton function to add active class */
function updateFilterButton() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        if (btn.getAttribute('data-filter') === currentFilter) {
            btn.classList.add('bg-purple-100', 'text-purple-700');
            btn.classList.remove('text-gray-600', 'hover:bg-gray-100');
        } else {
            btn.classList.remove('bg-purple-100', 'text-purple-700');
            btn.classList.add('text-gray-600', 'hover:bg-gray-100');
        }
    });
}

    // Load statistics
    async function loadStats() {
    try {
        const response = await fetch('ajax/messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=get_message_stats'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // Update statistics cards
            document.getElementById('stat-total').textContent = data.stats.total || 0;
            document.getElementById('stat-new').textContent = data.stats.new || 0;
            document.getElementById('stat-read').textContent = data.stats.read || 0;
            document.getElementById('stat-replied').textContent = data.stats.replied || 0;
            document.getElementById('stat-today').textContent = data.stats.today + ' today';
            document.getElementById('stat-week').textContent = data.stats.week + ' this week';
            
            // Update tab badges with new message count
            updateTabBadges(data.stats);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Update tab badges with new message count
function updateTabBadges(stats) {
    const newBadge = document.getElementById('new-count-badge');
    if (newBadge && stats.new > 0) {
        newBadge.textContent = stats.new;
        newBadge.classList.remove('hidden');
    } else if (newBadge) {
        newBadge.classList.add('hidden');
    }
}

    // Load messages
    async function loadMessages(filter = 'all', page = 1) {
        currentFilter = filter;
        currentPage = page;
        updateFilterButton();

        const tbody = document.getElementById('messagesTableBody');
        tbody.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                    <p class="mt-4 text-gray-600">Loading messages...</p>
                </div>
            </td>
        </tr>
    `;

        try {
            const response = await fetch('ajax/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=get_messages&filter=${filter}&page=${page}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                renderMessages(data.messages);
                renderPagination(data.pagination);
                updateDisplayCounts(data.messages.length, data.pagination.total_records);
            } else {
                showErrorMessage(data.message || 'Error loading messages');
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorMessage('Failed to load messages. Please try again.');
        }
    }

    // Search messages
    async function searchMessages(searchTerm) {
        try {
            const response = await fetch('ajax/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=search_messages&search=${encodeURIComponent(searchTerm)}&field=all&page=1`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                renderMessages(data.messages);
                renderPagination(data.pagination);
                updateDisplayCounts(data.messages.length, data.pagination.total_records);

                // Update filter button to show search mode
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('bg-purple-100', 'text-purple-700');
                    btn.classList.add('text-gray-600', 'hover:bg-gray-100');
                });

                // Update page info
                document.getElementById('paginationInfo').textContent = `Search results for "${searchTerm}"`;
            } else {
                showErrorMessage(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Search error:', error);
            showErrorMessage('Search failed. Please try again.');
        }
    }

    // Mark all as read
    async function markAllAsRead() {
        showConfirmationModal(
            'Mark All Read',
            'Are you sure you want to mark all new messages as read?',
            async function() {
                try {
                    const response = await fetch('ajax/messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=mark_all_read'
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        // Use the existing toast system
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Messages marked as read', 'success');
                        } else {
                            console.log('Toast function not available');
                        }
                        loadMessages(currentFilter, currentPage);
                        loadStats();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Failed to mark messages as read', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Failed to mark messages as read', 'error');
                    }
                }
            }, {
                type: 'info',
                confirmText: 'Yes, Mark All Read',
                cancelText: 'Cancel'
            }
        );
    }

    // Update display counts
    function updateDisplayCounts(showing, total) {
        document.getElementById('showingCount').textContent = showing;
        document.getElementById('totalCount').textContent = total;
        document.getElementById('paginationInfo').textContent = total > 0 ? `Page ${currentPage} of ${totalPages}` : 'No messages found';
    }

    // Show error message
    function showErrorMessage(message) {
        const tbody = document.getElementById('messagesTableBody');
        tbody.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center text-red-600">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="text-lg font-medium">${message}</p>
                    <button onclick="loadMessages(currentFilter, currentPage)" 
                            class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Retry
                    </button>
                </div>
            </td>
        </tr>
    `;
    }

    // Render messages table
    function renderMessages(messages) {
        const tbody = document.getElementById('messagesTableBody');

        if (!messages || messages.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center text-gray-500">
                        <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-lg font-medium">No messages found</p>
                        <p class="text-sm">Try a different filter or check back later</p>
                    </div>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = messages.map(msg => {
            const statusBadge = getStatusBadge(msg.status);
            const isNew = msg.status === 'new';
            const messagePreview = msg.message_preview || (msg.message.length > 60 ? msg.message.substring(0, 60) + '...' : msg.message);
            const formattedDate = formatRelativeDate(msg.created_at || msg.created_at_formatted);

            return `
            <tr class="${isNew ? 'bg-blue-50' : 'hover:bg-gray-50'} transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <input type="checkbox" value="${msg.id}" onchange="updateSelected()" 
                               class="message-checkbox w-5 h-5 text-purple-600 rounded focus:ring-purple-500 mr-3">
                        <div>
                            <div class="font-medium text-gray-900">${escapeHtml(msg.name)}</div>
                            <div class="text-sm text-gray-500">${escapeHtml(msg.email)}</div>
                            ${msg.phone ? `<div class="text-xs text-gray-400">${escapeHtml(msg.phone)}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(msg.subject)}</div>
                    <div class="text-sm text-gray-500 truncate max-w-xs">${escapeHtml(messagePreview)}</div>
                </td>
                <td class="px-6 py-4">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    ${formattedDate}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <button onclick="viewMessage(${msg.id})" 
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <button onclick="quickReply(${msg.id}, '${escapeHtml(msg.email)}')" 
                                class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Reply">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                        </button>
                        <button onclick="deleteMessage(${msg.id})" 
                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'new': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">New</span>',
            'read': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Read</span>',
            'replied': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Replied</span>',
            'archived': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">Archived</span>'
        };
        return badges[status] || '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>';
    }

    // Format relative date
    function formatRelativeDate(dateString) {
        if (!dateString) return 'N/A';

        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 60) {
                return `${diffMins} min ago`;
            } else if (diffHours < 24) {
                return `${diffHours} hr ago`;
            } else if (diffDays < 7) {
                return `${diffDays} days ago`;
            } else {
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        } catch (e) {
            return dateString;
        }
    }

    // Render pagination
    function renderPagination(pagination) {
        if (!pagination) return;

        totalPages = pagination.total_pages || 1;
        const container = document.getElementById('pagination');

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        // Previous button
        html += `
        <button onclick="loadMessages(currentFilter, ${currentPage - 1})" 
                ${currentPage === 1 ? 'disabled' : ''}
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </button>
    `;

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
            <button onclick="loadMessages(currentFilter, ${i})" 
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium ${i === currentPage ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'text-gray-500 hover:bg-gray-50'}">
                ${i}
            </button>
        `;
        }

        // Next button
        html += `
        <button onclick="loadMessages(currentFilter, ${currentPage + 1})" 
                ${currentPage === totalPages ? 'disabled' : ''}
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </button>
    `;

        container.innerHTML = html;
    }

    // View message details
    async function viewMessage(id) {
        currentMessageId = id;
        try {
            const response = await fetch('ajax/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=get_message_details&id=${id}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                const msg = data.message;

                let browserInfo = '';
                if (msg.browser_info) {
                    browserInfo = `
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="font-medium">Browser:</span> ${msg.browser_info.browser} on ${msg.browser_info.platform}
                    </div>
                `;
                }

                document.getElementById('messageModalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">From</h4>
                            <p class="mt-1 text-gray-900">${escapeHtml(msg.name)}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Email</h4>
                            <a href="mailto:${escapeHtml(msg.email)}" class="mt-1 text-purple-600 hover:text-purple-700">${escapeHtml(msg.email)}</a>
                        </div>
                        ${msg.phone ? `
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Phone</h4>
                            <p class="mt-1 text-gray-900">${escapeHtml(msg.phone)}</p>
                        </div>
                        ` : ''}
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Date</h4>
                            <p class="mt-1 text-gray-900">${formatFullDate(msg.created_at)}</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Subject</h4>
                        <p class="mt-1 text-lg font-medium text-gray-900">${escapeHtml(msg.subject)}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Message</h4>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-700 whitespace-pre-wrap">${escapeHtml(msg.message)}</p>
                        </div>
                    </div>
                    
                    ${msg.ip_address ? `
                    <div class="text-xs text-gray-500">
                        <span class="font-medium">IP Address:</span> ${escapeHtml(msg.ip_address)}
                    </div>
                    ` : ''}
                    
                    ${browserInfo}
                </div>
            `;

                showModal('viewMessageModal');

                // Mark as read if it's new
                if (msg.status === 'new') {
                    updateMessageStatus(id, 'read');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            // Use toast system for error
            if (typeof showToast === 'function') {
                showToast('Error loading message details', 'error');
            }
        }
    }

    // Show modal
    function showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Hide modal
    function hideModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close message modal
    function closeMessageModal() {
        hideModal('viewMessageModal');
    }

    // Close reply modal
    function closeReplyModal() {
        hideModal('replyModal');
    }

    // Show reply form
    function showReplyForm() {
        hideModal('viewMessageModal');
        setTimeout(() => {
            quickReply(currentMessageId);
        }, 300);
    }

    // Quick reply
    async function quickReply(id, email = null) {
        if (!email) {
            try {
                const response = await fetch('ajax/messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=get_message_details&id=${id}`
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    setupReplyModal(id, data.message.email, data.message.name, data.message.subject);
                }
            } catch (error) {
                console.error('Error:', error);
                // Use toast system for error
                if (typeof showToast === 'function') {
                    showToast('Error loading message details', 'error');
                }
            }
        } else {
            setupReplyModal(id, email);
        }
    }

    // Setup reply modal
    function setupReplyModal(id, email, name = '', originalSubject = '') {
        document.getElementById('replyContactId').value = id;
        document.getElementById('replyToEmail').value = email;
        document.getElementById('replyToDisplay').value = name ? `${name} <${email}>` : email;
        document.getElementById('replySubject').value = originalSubject ? `Re: ${originalSubject}` : '';
        document.getElementById('replyMessage').value = '';

        showModal('replyModal');
    }

    // Send reply
    async function sendReply() {
        const form = document.getElementById('replyForm');
        const formData = new FormData(form);
        formData.append('action', 'send_reply');

        const btn = document.getElementById('sendReplyBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
        Sending...
    `;

        try {
            const response = await fetch('ajax/messages.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Use toast system
                if (typeof showToast === 'function') {
                    showToast('Reply sent successfully!', 'success');
                }
                closeReplyModal();
                loadMessages(currentFilter, currentPage);
                loadStats();
            } else {
                // Use toast system
                if (typeof showToast === 'function') {
                    showToast('Error: ' + (data.message || 'Failed to send reply'), 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            // Use toast system
            if (typeof showToast === 'function') {
                showToast('Error sending reply. Please try again.', 'error');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Update message status
    async function updateMessageStatus(id, status) {
        try {
            const response = await fetch('ajax/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_message_status&id=${id}&status=${status}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                loadMessages(currentFilter, currentPage);
                loadStats();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Delete message
    async function deleteMessage(id) {
        showConfirmationModal(
            'Delete Message',
            'Are you sure you want to delete this message? This action cannot be undone.',
            async function() {
                try {
                    const response = await fetch('ajax/messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=delete_message&id=${id}`
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        // Use toast system
                        if (typeof showToast === 'function') {
                            showToast('Message deleted successfully', 'success');
                        }
                        loadMessages(currentFilter, currentPage);
                        loadStats();
                    } else {
                        // Use toast system
                        if (typeof showToast === 'function') {
                            showToast('Error deleting message', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    // Use toast system
                    if (typeof showToast === 'function') {
                        showToast('Error deleting message', 'error');
                    }
                }
            }, {
                type: 'error',
                confirmText: 'Yes, Delete',
                cancelText: 'Cancel'
            }
        );
    }

    // Select all checkboxes
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.message-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateSelected();
    }

    // Update selected messages array
    function updateSelected() {
        selectedMessages = Array.from(document.querySelectorAll('.message-checkbox:checked')).map(cb => cb.value);
    }

    // Apply bulk action
    async function applyBulkAction() {
        const action = document.getElementById('bulkAction').value;
        if (!action) {
            // Use toast system
            if (typeof showToast === 'function') {
                showToast('Please select an action', 'error');
            }
            return;
        }

        if (selectedMessages.length === 0) {
            // Use toast system
            if (typeof showToast === 'function') {
                showToast('Please select at least one message', 'error');
            }
            return;
        }

        const actionText = action.replace('_', ' ');
        showConfirmationModal(
            'Bulk Action',
            `Are you sure you want to ${actionText} ${selectedMessages.length} message(s)?`,
            async function() {
                try {
                    const response = await fetch('ajax/messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=bulk_action&bulk_action=${action}&ids=${selectedMessages.join(',')}`
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        // Use toast system
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Action completed successfully', 'success');
                        }
                        loadMessages(currentFilter, currentPage);
                        loadStats();
                        selectedMessages = [];
                        document.getElementById('selectAll').checked = false;
                    } else {
                        // Use toast system
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + (data.message || 'Action failed'), 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    // Use toast system
                    if (typeof showToast === 'function') {
                        showToast('Error performing action', 'error');
                    }
                }
            }, {
                type: 'warning',
                confirmText: 'Yes, Apply',
                cancelText: 'Cancel'
            }
        );
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatFullDate(dateString) {
        if (!dateString) return 'N/A';

        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }
</script>

<style>
    /* Improved Mobile Responsiveness */
@media (max-width: 640px) {
    /* Better tabs layout */
    .bg-white.rounded-xl.shadow-sm.p-1 {
        display: flex;
        width: auto;
        min-width: min-content;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .bg-white.rounded-xl.shadow-sm.p-1::-webkit-scrollbar {
        display: none;
    }
    
    .filter-btn {
        flex-shrink: 0;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
        margin: 0 1px;
    }
    
    /* Adjust table for mobile */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Compact cards on mobile */
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.5rem !important;
    }
    
    .stat-card {
        padding: 0.75rem !important;
    }
    
    .text-3xl {
        font-size: 1.5rem !important;
        line-height: 2rem !important;
    }
    
    /* Better header spacing */
    .container.mx-auto.px-4.py-8 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
    }
    
    /* Hide scrollbar but keep functionality */
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
}

/* Very small screens adjustments */
@media (max-width: 400px) {
    .bg-white.rounded-xl.shadow-sm.p-1 {
        padding: 0.25rem !important;
    }
    
    .filter-btn {
        padding: 0.375rem 0.5rem !important;
        font-size: 0.7rem !important;
    }
    
    .grid-cols-4 {
        grid-template-columns: 1fr !important;
    }
    
    /* Make action buttons more compact */
    button[onclick="markAllAsRead()"] {
        padding: 0.375rem 0.5rem !important;
        font-size: 0.7rem !important;
    }
}

/* Active state for filter buttons */
.filter-btn.active {
    background-color: #f3f4f6;
    color: #374151;
    font-weight: 600;
}


    /* Better mobile responsiveness for tabs */
    @media (max-width: 640px) {
        .filter-btn {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.75rem !important;
        }

        /* Make tabs wrap properly */
        .bg-white.rounded-xl.shadow-sm.p-1 {
            max-width: 100%;
            overflow-x: auto;
            flex-wrap: nowrap;
        }

        /* Alternative: Stack tabs vertically on very small screens */
        @media (max-width: 400px) {
            .bg-white.rounded-xl.shadow-sm.p-1 {
                flex-direction: column;
                width: 100%;
            }

            .filter-btn {
                width: 100%;
                text-align: center;
                border-radius: 0.375rem !important;
                margin-bottom: 2px;
            }
        }

        /* Adjust table columns for mobile */
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        .px-6 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }

        /* Adjust statistics cards */
        .grid-cols-4 {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 0.75rem !important;
        }

        .stat-card {
            padding: 0.75rem !important;
        }

        .text-3xl {
            font-size: 1.5rem !important;
        }
    }

    /* Alternative: Truncate tab text on smaller screens */


   
    #viewMessageModal,
    #replyModal {
        transition: opacity 0.3s ease-in-out;
    }

    /* Notification animation */
    .notification {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .filter-btn.active {
        background-color: #f3f4f6;
        color: #374151;
    }

    .message-checkbox:checked {
        background-color: #8b5cf6;
        border-color: #8b5cf6;
    }

    /* Smooth transitions */
    #viewMessageModal,
    #replyModal {
        transition: opacity 0.3s ease-in-out;
    }

    /* Notification animation */
    .notification {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        .container {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        table {
            font-size: 0.875rem;
        }

        .px-6 {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .grid-cols-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>