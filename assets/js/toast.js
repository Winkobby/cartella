 class ToastNotification {
            constructor() {
              this.container = document.getElementById('toastContainer');
              this.autoHideDelay = 5000; // 5 seconds
            }

            show(message, type = 'info', title = null) {
              const toastId = 'toast-' + Date.now();
              const icons = {
                success: 'mdi-check-circle',
                error: 'mdi-alert-circle',
                warning: 'mdi-alert',
                info: 'mdi-information'
              };

              const defaultTitles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Information'
              };

              const toastTitle = title || defaultTitles[type];
              const toastIcon = icons[type] || 'mdi-information';

              const toastHTML = `
            <div class="toast toast-${type}" id="${toastId}" role="alert">
                <div class="toast-header">
                    <div class="toast-title">
                        <i class="mdi ${toastIcon}"></i>
                        <span>${toastTitle}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
                <div class="toast-progress" style="width: 100%"></div>
            </div>
        `;

              this.container.insertAdjacentHTML('beforeend', toastHTML);
              const toastElement = document.getElementById(toastId);

              // Show toast with animation
              setTimeout(() => {
                toastElement.classList.add('show');
                this.startProgressBar(toastElement);
              }, 100);

              // Auto hide
              const autoHide = setTimeout(() => {
                this.hide(toastElement);
              }, this.autoHideDelay);

              // Click to dismiss
              toastElement.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-close') || e.target.closest('.btn-close')) {
                  clearTimeout(autoHide);
                  this.hide(toastElement);
                }
              });

              // Pause on hover
              toastElement.addEventListener('mouseenter', () => {
                toastElement.querySelector('.toast-progress').style.animationPlayState = 'paused';
              });

              toastElement.addEventListener('mouseleave', () => {
                toastElement.querySelector('.toast-progress').style.animationPlayState = 'running';
              });
            }

            startProgressBar(toastElement) {
              const progressBar = toastElement.querySelector('.toast-progress');
              progressBar.style.transition = `width ${this.autoHideDelay}ms linear`;
              progressBar.style.width = '0%';
            }

            hide(toastElement) {
              toastElement.classList.remove('show');
              toastElement.classList.add('hide');

              setTimeout(() => {
                if (toastElement.parentNode) {
                  toastElement.parentNode.removeChild(toastElement);
                }
              }, 300);
            }

            // Convenience methods
            success(message, title = null) {
              this.show(message, 'success', title);
            }

            error(message, title = null) {
              this.show(message, 'error', title);
            }

            warning(message, title = null) {
              this.show(message, 'warning', title);
            }

            info(message, title = null) {
              this.show(message, 'info', title);
            }
          }

          // Initialize toast system
          const toast = new ToastNotification();

          // Make it globally available
          window.toast = toast;