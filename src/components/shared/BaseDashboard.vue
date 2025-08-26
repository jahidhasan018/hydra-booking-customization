<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="loading-spinner"></div>
          <span class="text-gray-700">Loading...</span>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ config.title }}</h1>
        <p class="mt-2 text-gray-600">{{ config.subtitle }}</p>
      </div>

      <!-- Alert Messages -->
      <div v-if="alert.show" :class="alert.type" class="mb-6 animate-fade-in">
        <div class="flex justify-between items-center">
          <span>{{ alert.message }}</span>
          <button @click="clearAlert" class="text-lg leading-none hover:opacity-75">&times;</button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div :class="config.statsGridClass" class="gap-6 mb-8">
        <div v-for="stat in config.stats" :key="stat.key" class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div :class="stat.iconBgClass" class="w-8 h-8 rounded-full flex items-center justify-center">
                  <component :is="stat.icon" :class="stat.iconClass" class="w-5 h-5" />
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats[stat.key] || 0 }}</p>
                <p class="text-sm text-gray-600">{{ stat.label }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="tab-nav mb-6">
        <nav class="flex space-x-8">
          <button
            v-for="tab in config.tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-lg shadow">
        <slot 
          :activeTab="activeTab" 
          :stats="stats" 
          :isLoading="isLoading"
          :alert="alert"
          :showAlert="showAlert"
          :clearAlert="clearAlert"
        ></slot>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'

// Icon components (using simple SVG paths for now)
const CalendarIcon = {
  template: `<svg fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
  </svg>`
}

const ClockIcon = {
  template: `<svg fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
  </svg>`
}

const CheckIcon = {
  template: `<svg fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
  </svg>`
}

const XIcon = {
  template: `<svg fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
  </svg>`
}

const LinkIcon = {
  template: `<svg fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
  </svg>`
}

export default {
  name: 'BaseDashboard',
  components: {
    CalendarIcon,
    ClockIcon,
    CheckIcon,
    XIcon,
    LinkIcon
  },
  props: {
    config: {
      type: Object,
      required: true,
      validator: (config) => {
        return config.title && config.subtitle && config.stats && config.tabs
      }
    },
    stats: {
      type: Object,
      default: () => ({})
    },
    isLoading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['tab-change'],
  setup(props, { emit }) {
    // Reactive state
    const activeTab = ref(props.config.tabs[0]?.id || '')
    const alert = reactive({
      show: false,
      type: '',
      message: ''
    })

    // Methods
    const showAlert = (type, message) => {
      alert.show = true
      alert.type = `alert-${type}`
      alert.message = message
      setTimeout(() => {
        alert.show = false
      }, 5000)
    }

    const clearAlert = () => {
      alert.show = false
    }

    // Watch for tab changes
    const handleTabChange = (tabId) => {
      activeTab.value = tabId
      emit('tab-change', tabId)
    }

    return {
      activeTab,
      alert,
      showAlert,
      clearAlert,
      handleTabChange
    }
  }
}
</script>

<style scoped>
.loading-spinner {
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3498db;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.card-body {
  @apply p-6;
}

.alert-success {
  @apply bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg;
}

.alert-error {
  @apply bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg;
}

.alert-warning {
  @apply bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg;
}

.alert-info {
  @apply bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg;
}
</style>