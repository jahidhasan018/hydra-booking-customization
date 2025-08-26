import { createApp } from 'vue'
import HostDashboard from './components/HostDashboard.vue'
import toastPlugin from './plugins/toast.js'
import './style.css'

// Function to mount the Vue app
function mountHostApp() {
  // Attempting to mount Host Dashboard
  
  const container = document.getElementById('host-dashboard-app')
  // Container found check
  
  if (container && !container.__vue_app__) {
    try {
      // Check if WordPress data is available
      // WordPress data availability checked
      
      // Create the Vue app
      const app = createApp(HostDashboard)
      
      // Install toast plugin
      app.use(toastPlugin)
      
      // Global error handler
      app.config.errorHandler = (err, vm, info) => {
        console.error('Vue Host Dashboard error:', err, info)
        // Show error message to user
        container.innerHTML = `
          <div style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33;">
            <h3>Dashboard Error</h3>
            <p>There was an error loading the dashboard. Please refresh the page or contact support.</p>
            <details style="margin-top: 10px;">
              <summary>Error Details</summary>
              <pre style="margin-top: 10px; font-size: 12px;">${err.message}</pre>
            </details>
          </div>
        `
      }
      
      // Mount the app
      app.mount(container)
      
      // Mark container as mounted
      container.__vue_app__ = app
      
      // Show the app and hide loading
      container.style.display = 'block'
      const loading = document.getElementById('hbc-loading-state')
      if (loading) {
        loading.style.display = 'none'
      }
      
      // Host Dashboard mounted successfully
    } catch (error) {
      console.error('Failed to mount Host Dashboard:', error)
      // Show fallback content
      container.innerHTML = `
        <div style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33;">
          <h3>Dashboard Unavailable</h3>
          <p>The dashboard could not be loaded. Please refresh the page or contact support.</p>
          <button onclick="location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Refresh Page
          </button>
        </div>
      `
    }
  } else if (container && container.__vue_app__) {
    // Host Dashboard already mounted
  } else {
    console.error('Host Dashboard container not found')
  }
}

// Try to mount immediately if DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountHostApp)
} else {
  // DOM is already ready
  mountHostApp()
}

// Also try after a short delay to handle WordPress timing issues
setTimeout(mountHostApp, 100)