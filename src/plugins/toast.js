import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import toastManager from '../utils/toast.js'

/**
 * Vue 3 Toast Plugin
 * Integrates vue3-toastify with our custom toast manager
 */
export default {
  install(app, options = {}) {
    // Configure vue3-toastify with custom options
    const defaultConfig = {
      autoClose: 5000,
      position: 'top-right',
      hideProgressBar: false,
      newestOnTop: true,
      closeOnClick: true,
      rtl: false,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      closeButton: 'button',
      icon: true,
      theme: 'auto',
      transition: 'bounce',
      ...options
    }

    // Install vue3-toastify
    app.use(Vue3Toastify, defaultConfig)

    // Make toast manager available globally
    app.config.globalProperties.$toast = toastManager
    app.config.globalProperties.$showAlert = toastManager.showAlert.bind(toastManager)

    // Provide toast manager for composition API
    app.provide('toast', toastManager)
    app.provide('showAlert', toastManager.showAlert.bind(toastManager))
  }
}

// Export toast manager for direct imports
export { toastManager, toast }