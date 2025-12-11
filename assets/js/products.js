class ProductManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateWishlistCount();
    }

    bindEvents() {
        // Sort functionality
        const sortSelect = document.getElementById('sortProducts');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.handleSortChange(e.target.value);
            });
        }

        // Wishlist buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.wishlist-btn')) {
                this.handleWishlistToggle(e.target.closest('.wishlist-btn'));
            }
        });
    }

    handleSortChange(sortValue) {
        // Update URL with sort parameter
        const url = new URL(window.location);
        url.searchParams.set('sort', sortValue);
        window.location.href = url.toString();
    }

    async handleWishlistToggle(button) {
        const productId = button.dataset.product;
        const isInWishlist = button.dataset.inWishlist === 'true';

        if (!productId) return;

        // Toggle visual state immediately for better UX
        this.toggleWishlistButton(button, !isInWishlist);

        try {
            const action = isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';
            const formData = new FormData();
            formData.append('action', action);
            formData.append('product_id', productId);

            const response = await fetch('ajax/wishlist.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showWishlistToast(data.message, !isInWishlist);
                this.updateWishlistCount();
            } else {
                // Revert visual state if failed
                this.toggleWishlistButton(button, isInWishlist);
                this.showWishlistToast(data.message, false, true);
            }
        } catch (error) {
            console.error('Wishlist error:', error);
            // Revert visual state if error
            this.toggleWishlistButton(button, isInWishlist);
            this.showWishlistToast('Network error. Please try again.', false, true);
        }
    }

    toggleWishlistButton(button, isInWishlist) {
        const icon = button.querySelector('i');
        if (icon) {
            if (isInWishlist) {
                icon.className = 'mdi mdi-heart';
                button.classList.add('active');
                button.dataset.inWishlist = 'true';
            } else {
                icon.className = 'mdi mdi-heart-outline';
                button.classList.remove('active');
                button.dataset.inWishlist = 'false';
            }
        }
    }

    async updateWishlistCount() {
        try {
            const response = await fetch('ajax/wishlist.php?action=get_wishlist_count');
            const data = await response.json();
            
            if (data.success) {
                const wishlistCount = document.querySelector('.count-symbol.bg-warning');
                if (wishlistCount) {
                    if (data.count > 0) {
                        wishlistCount.textContent = data.count;
                        wishlistCount.style.display = 'flex';
                    } else {
                        wishlistCount.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error updating wishlist count:', error);
        }
    }

    showWishlistToast(message, isAdded = true, isError = false) {
        this.createToastContainer();
        const toastContainer = document.getElementById('cart-toast-container');
        const toastId = 'toast-' + Date.now();
        
        const bgClass = isError ? 'bg-danger' : (isAdded ? 'bg-success' : 'bg-info');
        const icon = isError ? 'mdi-alert-circle' : (isAdded ? 'mdi-heart' : 'mdi-heart-broken');
        
        const toastHTML = `
            <div id="${toastId}" class="toast show" role="alert">
                <div class="toast-header ${bgClass} text-white">
                    <i class="mdi ${icon} me-2"></i>
                    <strong class="me-auto">Wishlist</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.remove();
            }
        }, 3000);
    }

    createToastContainer() {
        if (!document.getElementById('cart-toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.id = 'cart-toast-container';
            toastContainer.className = 'cart-toast';
            document.body.appendChild(toastContainer);
        }
    }

    async updateWishlistPreview() {
    try {
        const response = await fetch('ajax/wishlist.php?action=get_wishlist_preview');
        const data = await response.json();
        this.renderWishlistPreview(data);
    } catch (error) {
        console.error('Error updating wishlist preview:', error);
    }
}

renderWishlistPreview(data) {
    const wishlistItemsContainer = document.getElementById('wishlist-preview-items');
    const wishlistSummary = document.getElementById('wishlist-summary');
    
    if (!wishlistItemsContainer) return;

    if (data.items && data.items.length > 0) {
        let itemsHTML = '';
        const displayItems = data.items.slice(0, 3); // Show only first 3 items
        
        displayItems.forEach(item => {
            itemsHTML += `
                <div class="wishlist-preview-item" data-product="${item.product_id}">
                    <div class="d-flex align-items-center p-2">
                        <img src="${item.image}" alt="${item.name}" class="wishlist-item-image rounded">
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 wishlist-item-title">${item.name}</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-primary">GHS ${item.price.toFixed(2)}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider my-1"></div>
            `;
        });
        
        // Add "more items" message if there are more than 3 items
        if (data.items.length > 3) {
            itemsHTML += `
                <div class="text-center p-2">
                    <small class="text-muted">+${data.items.length - 3} more items</small>
                </div>
                <div class="dropdown-divider"></div>
            `;
        }
        
        wishlistItemsContainer.innerHTML = itemsHTML;
        wishlistSummary.textContent = `${data.totalItems} item(s)`;
    } else {
        wishlistItemsContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="mdi mdi-heart-outline text-muted mb-2" style="font-size: 2.5rem;"></i>
                <p class="text-muted mb-0">Your wishlist is empty</p>
                ${!data.isLoggedIn ? '<small class="text-muted">Login to save items</small>' : ''}
            </div>
        `;
        wishlistSummary.textContent = '0 item(s)';
    }
}
}

// Initialize product manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.productManager = new ProductManager();
});