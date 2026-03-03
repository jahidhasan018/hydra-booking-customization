/**
 * Standalone Toast Notification System for Vanilla JavaScript
 * Compatible with vue3-toastify styling but works without Vue
 */
(function(window) {
    'use strict';

    // Toast container
    let toastContainer = null;
    let toastCounter = 0;

    // Default configuration
    const defaultConfig = {
        position: 'top-right',
        timeout: 5000,
        closeOnClick: true,
        pauseOnHover: true,
        showCloseButton: true,
        maxToasts: 5
    };

    // Toast types and their styles
    const toastTypes = {
        success: {
            icon: '✓',
            className: 'toast-success',
            bgColor: '#10b981',
            textColor: '#ffffff'
        },
        error: {
            icon: '✕',
            className: 'toast-error',
            bgColor: '#ef4444',
            textColor: '#ffffff'
        },
        warning: {
            icon: '⚠',
            className: 'toast-warning',
            bgColor: '#f59e0b',
            textColor: '#ffffff'
        },
        info: {
            icon: 'ℹ',
            className: 'toast-info',
            bgColor: '#3b82f6',
            textColor: '#ffffff'
        }
    };

    /**
     * Initialize toast container
     */
    function initContainer() {
        if (toastContainer) return;

        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
            max-width: 400px;
        `;
        document.body.appendChild(toastContainer);

        // Add CSS styles
        addStyles();
    }

    /**
     * Add CSS styles for toasts
     */
    function addStyles() {
        if (document.getElementById('toast-styles')) return;

        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                pointer-events: auto;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 100%;
                word-wrap: break-word;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 14px;
                line-height: 1.4;
            }
            
            .toast.show {
                transform: translateX(0);
            }
            
            .toast.hide {
                transform: translateX(100%);
                opacity: 0;
            }
            
            .toast-icon {
                margin-right: 10px;
                font-weight: bold;
                font-size: 16px;
            }
            
            .toast-message {
                flex: 1;
                margin-right: 10px;
            }
            
            .toast-close {
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                font-size: 18px;
                line-height: 1;
                padding: 0;
                margin-left: 8px;
                opacity: 0.7;
                transition: opacity 0.2s;
            }
            
            .toast-close:hover {
                opacity: 1;
            }
            
            .toast-success {
                background-color: #10b981;
                color: #ffffff;
            }
            
            .toast-error {
                background-color: #ef4444;
                color: #ffffff;
            }
            
            .toast-warning {
                background-color: #f59e0b;
                color: #ffffff;
            }
            
            .toast-info {
                background-color: #3b82f6;
                color: #ffffff;
            }
            
            .toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background-color: rgba(255, 255, 255, 0.3);
                transition: width linear;
            }
            
            @media (max-width: 640px) {
                .toast-container {
                    left: 20px;
                    right: 20px;
                    top: 20px;
                    max-width: none;
                }
                
                .toast {
                    margin-bottom: 8px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Create a toast element
     */
    function createToast(type, message, options = {}) {
        const config = { ...defaultConfig, ...options };
        const toastType = toastTypes[type] || toastTypes.info;
        const toastId = ++toastCounter;

        const toast = document.createElement('div');
        toast.className = `toast ${toastType.className}`;
        toast.setAttribute('data-toast-id', toastId);
        toast.style.position = 'relative';

        // Create toast content
        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.textContent = toastType.icon;

        const messageEl = document.createElement('span');
        messageEl.className = 'toast-message';
        messageEl.textContent = message;

        toast.appendChild(icon);
        toast.appendChild(messageEl);

        // Add close button if enabled
        if (config.showCloseButton) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'toast-close';
            closeBtn.innerHTML = '×';
            closeBtn.onclick = () => removeToast(toast);
            toast.appendChild(closeBtn);
        }

        // Add progress bar for timed toasts
        if (config.timeout > 0) {
            const progress = document.createElement('div');
            progress.className = 'toast-progress';
            progress.style.width = '100%';
            toast.appendChild(progress);

            // Animate progress bar
            setTimeout(() => {
                progress.style.width = '0%';
                progress.style.transitionDuration = config.timeout + 'ms';
            }, 10);
        }

        return { toast, config, toastId };
    }

    /**
     * Show a toast notification
     */
    function showToast(type, message, options = {}) {
        initContainer();

        const { toast, config } = createToast(type, message, options);

        // Remove excess toasts
        const existingToasts = toastContainer.querySelectorAll('.toast');
        if (existingToasts.length >= config.maxToasts) {
            removeToast(existingToasts[0]);
        }

        // Add toast to container
        toastContainer.appendChild(toast);

        // Trigger show animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto-remove after timeout
        if (config.timeout > 0) {
            setTimeout(() => {
                removeToast(toast);
            }, config.timeout);
        }

        // Add event listeners
        if (config.closeOnClick) {
            toast.addEventListener('click', () => removeToast(toast));
        }

        if (config.pauseOnHover && config.timeout > 0) {
            let timeoutId;
            const progressBar = toast.querySelector('.toast-progress');
            
            toast.addEventListener('mouseenter', () => {
                if (progressBar) {
                    progressBar.style.animationPlayState = 'paused';
                }
            });
            
            toast.addEventListener('mouseleave', () => {
                if (progressBar) {
                    progressBar.style.animationPlayState = 'running';
                }
            });
        }

        return toast;
    }

    /**
     * Remove a toast
     */
    function removeToast(toast) {
        if (!toast || !toast.parentNode) return;

        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Remove all toasts
     */
    function removeAllToasts() {
        if (!toastContainer) return;

        const toasts = toastContainer.querySelectorAll('.toast');
        toasts.forEach(removeToast);
    }

    // Public API
    const ToastNotifications = {
        success: (message, options) => showToast('success', message, options),
        error: (message, options) => showToast('error', message, options),
        warning: (message, options) => showToast('warning', message, options),
        info: (message, options) => showToast('info', message, options),
        show: showToast,
        remove: removeToast,
        removeAll: removeAllToasts,
        
        // Legacy compatibility
        showAlert: function(type, message, options) {
            return this[type] ? this[type](message, options) : this.info(message, options);
        }
    };

    // Export to global scope
    window.ToastNotifications = ToastNotifications;
    
    // Also export as HBC namespace for plugin consistency
    window.HBC = window.HBC || {};
    window.HBC.toast = ToastNotifications;

})(window);