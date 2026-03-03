import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  
  // Build configuration
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    
    // Multiple entry points for different dashboards
    rollupOptions: {
      input: {
        'attendee-dashboard': resolve(__dirname, 'src/attendee-dashboard.js'),
        'host-dashboard': resolve(__dirname, 'src/host-dashboard.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name].[ext]'
      }
    },
    
    // Generate manifest for WordPress integration
    manifest: true,
    
    // Optimize for production
    minify: 'esbuild',
    
    // Generate source maps for development
    sourcemap: process.env.NODE_ENV === 'development',
    
    // Target modern browsers
    target: 'es2015',
    
    // CSS configuration
    cssCodeSplit: false
  },
  
  // CSS configuration with Tailwind
  css: {
    postcss: './postcss.config.js'
  },
  
  // Resolve configuration
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      '@components': resolve(__dirname, 'src/components'),
      '@utils': resolve(__dirname, 'src/utils'),
      '@api': resolve(__dirname, 'src/api')
    },
    extensions: ['.js', '.vue', '.json', '.css']
  },
  
  // Define global constants
  define: {
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false,
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'production')
  },
  
  // Development server configuration
  server: {
    port: 3000,
    open: false,
    cors: true
  },
  
  // Base path for assets
  base: './'
})