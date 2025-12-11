class CartManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.createToastContainer();
    }

    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn')) {
                this.handleAddToCart(e.target.closest('.add-to-cart-btn'));
            }
        });

        // Update cart count on page load
        this.updateCartCount();
    }

    handleAddToCart(button) {
        const productId = button.dataset.product;
        const productName = button.dataset.name;
        const productPrice = parseFloat(button.dataset.price);
        const productImage = button.dataset.image;

        // Show loading state
        this.setButtonLoading(button, true);

        // Simulate API call (replace with actual AJAX call)
        setTimeout(() => {
            this.addToCart(productId, productName, productPrice, productImage)
                .then(() => {
                    this.setButtonSuccess(button);
                    this.showSuccessToast(productName);
                    this.updateCartPreview();
                    this.updateCartCount();
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    this.setButtonError(button);
                })
                .finally(() => {
                    setTimeout(() => {
                        this.resetButton(button);
                    }, 2000);
                });
        }, 500);
    }

    async addToCart(productId, name, price, image) {
        return new Promise((resolve, reject) => {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('csrf_token', this.getCSRFToken());

            // AJAX request
            fetch('ajax/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(data.message);
                }
            })
            .catch(error => reject(error));
        });
    }

    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add('loading');
            button.disabled = true;
        } else {
            button.classList.remove('loading');
            button.disabled = false;
        }
    }

    setButtonSuccess(button) {
        button.classList.add('success');
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = 'mdi mdi-check';
        }
    }

    setButtonError(button) {
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = 'mdi mdi-close';
        }
        button.style.backgroundColor = '#dc3545';
    }

    resetButton(button) {
        button.classList.remove('loading', 'success');
        button.style.backgroundColor = '';
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = 'mdi mdi-cart-plus';
        }
        button.disabled = false;
    }

    updateCartCount() {
        // AJAX call to get updated cart count
        fetch('ajax/cart.php?action=get_cart_count')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    if (data.count > 0) {
                        cartCount.textContent = data.count;
                        cartCount.classList.remove('d-none');
                    } else {
                        cartCount.classList.add('d-none');
                    }
                }
            });
    }

    updateCartPreview() {
        // AJAX call to get updated cart preview
        fetch('ajax/cart.php?action=get_cart_preview')
            .then(response => response.json())
            .then(data => {
                this.renderCartPreview(data);
            });
    }

    renderCartPreview(data) {
        const cartItemsContainer = document.getElementById('cart-preview-items');
        const cartSummary = document.getElementById('cart-summary');
        
        if (!cartItemsContainer) return;

        if (data.items && data.items.length > 0) {
            let itemsHTML = '';
            data.items.forEach(item => {
                itemsHTML += `
                    <div class="cart-preview-item" data-key="${item.key}">
                        <div class="d-flex align-items-center">
                            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 cart-item-title">${item.name}</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Qty: ${item.quantity}</small>
                                    <strong class="text-primary">GHS ${(item.price * item.quantity).toFixed(2)}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                `;
            });
            cartItemsContainer.innerHTML = itemsHTML;
            cartSummary.textContent = `${data.totalItems} item(s) - GHS ${data.totalAmount.toFixed(2)}`;
        } else {
            cartItemsContainer.innerHTML = `
                <div class="text-center py-3">
                    <i class="mdi mdi-cart-off text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">Your cart is empty</p>
                </div>
            `;
            cartSummary.textContent = '0 item(s) - GHS 0.00';
        }
    }

    createToastContainer() {
        if (!document.getElementById('cart-toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.id = 'cart-toast-container';
            toastContainer.className = 'cart-toast';
            document.body.appendChild(toastContainer);
        }
    }

    showSuccessToast(productName) {
        const toastContainer = document.getElementById('cart-toast-container');
        const toastId = 'toast-' + Date.now();
        
        const toastHTML = `
            <div id="${toastId}" class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="mdi mdi-check-circle me-2"></i>
                    <strong class="me-auto">Added to Cart</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <strong>${productName}</strong> has been added to your cart.
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

    getCSRFToken() {
        // This should be set in your HTML template
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
}

// Initialize cart manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.cartManager = new CartManager();
});