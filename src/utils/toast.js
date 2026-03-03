import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

/**
 * Centralized Toast Notification Manager
 * Provides a consistent interface for displaying toast notifications throughout the plugin
 */
class ToastManager {
  constructor() {
    this.defaultOptions = {
      position: 'top-right',
      timeout: 5000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      closeButton: 'button',
      icon: true,
      rtl: false
    }
  }

  /**
   * Show a success toast notification
   * @param {string} message - The message to display
   * @param {object} options - Additional options to override defaults
   */
  success(message, options = {}) {
    return toast.success(message, {
      ...this.defaultOptions,
      ...options
    })
  }

  /**
   * Show an error toast notification
   * @param {string} message - The message to display
   * @param {object} options - Additional options to override defaults
   */
  error(message, options = {}) {
    return toast.error(message, {
      ...this.defaultOptions,
      timeout: 7000, // Longer timeout for errors
      ...options
    })
  }

  /**
   * Show a warning toast notification
   * @param {string} message - The message to display
   * @param {object} options - Additional options to override defaults
   */
  warning(message, options = {}) {
    return toast.warning(message, {
      ...this.defaultOptions,
      ...options
    })
  }

  /**
   * Show an info toast notification
   * @param {string} message - The message to display
   * @param {object} options - Additional options to override defaults
   */
  info(message, options = {}) {
    return toast.info(message, {
      ...this.defaultOptions,
      ...options
    })
  }

  /**
   * Show a loading toast notification
   * @param {string} message - The message to display
   * @param {object} options - Additional options to override defaults
   */
  loading(message, options = {}) {
    return toast.loading(message, {
      ...this.defaultOptions,
      timeout: false, // Loading toasts don't auto-dismiss
      closeOnClick: false,
      ...options
    })
  }

  /**
   * Update an existing toast notification
   * @param {string|number} toastId - The ID of the toast to update
   * @param {object} options - The new options for the toast
   */
  update(toastId, options) {
    return toast.update(toastId, options)
  }

  /**
   * Dismiss a specific toast notification
   * @param {string|number} toastId - The ID of the toast to dismiss
   */
  dismiss(toastId) {
    return toast.dismiss(toastId)
  }

  /**
   * Dismiss all toast notifications
   */
  dismissAll() {
    return toast.dismiss()
  }

  /**
   * Legacy compatibility method for existing showAlert calls
   * @param {string} type - The type of alert (success, error, warning, info)
   * @param {string} message - The message to display
   * @param {object} options - Additional options
   */
  showAlert(type, message, options = {}) {
    switch (type) {
      case 'success':
        return this.success(message, options)
      case 'error':
        return this.error(message, options)
      case 'warning':
        return this.warning(message, options)
      case 'info':
        return this.info(message, options)
      default:
        return this.info(message, options)
    }
  }

  /**
   * Show a promise-based toast that updates based on promise state
   * @param {Promise} promise - The promise to track
   * @param {object} messages - Object containing pending, success, and error messages
   * @param {object} options - Additional options
   */
  promise(promise, messages, options = {}) {
    return toast.promise(promise, messages, {
      ...this.defaultOptions,
      ...options
    })
  }
}

// Create and export a singleton instance
const toastManager = new ToastManager()

// Export both the class and the instance
export { ToastManager, toastManager as default }

// Export individual methods for convenience
export const { success, error, warning, info, loading, showAlert, promise } = toastManager