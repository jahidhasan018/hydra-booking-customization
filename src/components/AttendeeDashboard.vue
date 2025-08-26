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
      <div class="mb-8 hbc-dashboard-header">
        <div class="hbc-header-content">
          <div class="hbc-header-text">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('attendee_dashboard') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('manage_bookings_profile') }}</p>
          </div>
          <div class="hbc-header-actions">
            <button @click="handleLogout" class="hbc-logout-btn">{{ __('logout') }}</button>
          </div>
        </div>
      </div>

      <!-- Alert Messages -->
      <div v-if="alert.show" :class="alert.type" class="mb-6 animate-fade-in">
        <div class="flex justify-between items-center">
          <span>{{ alert.message }}</span>
          <button @click="clearAlert" class="text-lg leading-none hover:opacity-75">&times;</button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.total_bookings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('total_bookings') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-success-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.upcoming_bookings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('upcoming') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-warning-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-warning-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_bookings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('completed') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.cancelled_bookings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('cancelled') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="tab-nav mb-6">
        <nav class="flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ __(tab.name.toLowerCase().replace(' ', '_')) }}
          </button>
        </nav>
      </div>

      <!-- Filter Buttons for Bookings Tab -->
      <div v-if="activeTab === 'bookings'" class="mb-6">
        <div class="flex space-x-4">
          <button
            v-for="filter in bookingFilters"
            :key="filter.id"
            @click="currentFilter = filter.id; loadBookings()"
            :class="[
              'px-4 py-2 rounded-lg font-medium text-sm transition-colors duration-200',
              currentFilter === filter.id
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ filter.name }}
          </button>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-lg shadow">
        <!-- Bookings Tab -->
        <div v-show="activeTab === 'bookings'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('my_bookings') }}</h3>
            <button @click="loadBookings" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
              </svg>
              {{ __('refresh') }}
            </button>
          </div>

          <div v-if="bookings.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 0h6m-6 0V7a1 1 0 00-1 1v9a2 2 0 002 2h6a2 2 0 002-2V8a1 1 0 00-1-1V7" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_bookings') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('no_bookings_message') }}</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="booking in bookings"
              :key="booking.id || booking.booking_id"
              class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
            >
              <!-- Card Header -->
              <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h4 class="text-lg font-semibold text-gray-900 truncate">{{ booking.title || booking.meeting_title }}</h4>
                <span :class="getStatusClass(booking.status || booking.booking_status)" class="badge text-xs px-2 py-1 rounded-full">
                  {{ getStatusText(booking.status || booking.booking_status) }}
                </span>
              </div>

              <!-- Countdown Timer for Upcoming Meetings -->
              <div v-if="currentFilter === 'upcoming' && isUpcomingMeeting(booking)" class="px-6 py-4 border-b border-gray-100">
                <CountdownTimer
                  :meeting-date="booking.meeting_dates"
                  :start-time="booking.start_time"
                  @expired="onMeetingExpired(booking)"
                  @urgent="onMeetingUrgent(booking)"
                  @warning="onMeetingWarning(booking)"
                />
              </div>

              <!-- Card Content -->
              <div class="px-6 py-4 space-y-3">
                <!-- Host Name -->
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ __('host') }}</p>
                  <p class="text-sm text-gray-900 truncate">{{ (booking.host_first_name + ' ' + booking.host_last_name).trim() || booking.host_name || 'N/A' }}</p>
                </div>

                <!-- Email -->
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ __('email') }}</p>
                  <p class="text-sm text-gray-600 truncate">{{ booking.host_email || 'N/A' }}</p>
                </div>

                <!-- Duration -->
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ __('duration') }}</p>
                  <p class="text-sm text-gray-600">{{ booking.duration || 'N/A' }}</p>
                </div>

                <!-- Booking Date -->
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ __('date_time') }}</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(booking.meeting_dates, booking.start_time) }}</p>
                </div>
              </div>

              <!-- Card Actions -->
              <div class="px-6 py-4 border-t border-gray-100">
                <button
                  v-if="canShowMeetingButton(booking)"
                  @click="handleMeetingAction(booking)"
                  :class="getMeetingButtonClass(booking) + ' w-full'"
                  :disabled="!isMeetingAvailable(booking) && !isTestModeActiveForBooking(booking)"
                >
                  {{ getMeetingButtonText(booking) }}
                </button>
              </div>
            </div>
          </div>
        </div>



        <!-- Profile Tab -->
        <div v-show="activeTab === 'profile'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('profile_settings') }}</h3>
            <button @click="openProfileModal" class="btn-primary">
              {{ __('edit_profile') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="form-label">{{ __('first_name') }}</label>
              <p class="text-gray-700">{{ profile.first_name || 'Not set' }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('last_name') }}</label>
              <p class="text-gray-700">{{ profile.last_name || 'Not set' }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('email') }}</label>
              <p class="text-gray-700">{{ profile.email || 'Not set' }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('phone') }}</label>
              <p class="text-gray-700">{{ profile.phone || 'Not set' }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="form-label">{{ __('timezone') }}</label>
              <p class="text-gray-700">{{ profile.timezone || 'Not set' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Edit Modal -->
    <ProfileModal
      v-if="showProfileModal"
      :profile="profile"
      @close="showProfileModal = false"
      @save="updateProfile"
    />


  </div>
</template>

<script>
import { ref, reactive, onMounted, watch, inject } from 'vue'
import { attendeeAPI } from '../utils/api.js'
import { formatDateTime, getStatusClass, getStatusText, handleApiError, copyToClipboard } from '../utils/helpers.js'
import { isTestModeActiveForBooking } from '../utils/constants.js'
import { __ } from '../utils/i18n.js'
import ProfileModal from './modals/ProfileModal.vue'
import CountdownTimer from './CountdownTimer.vue'

export default {
  name: 'AttendeeDashboard',
  components: {
    ProfileModal,
    CountdownTimer
  },
  setup() {
    // Inject toast notification system
    const toast = inject('toast')
    
    // Reactive state
    const isLoading = ref(false)
    const activeTab = ref('bookings')
    const currentFilter = ref('upcoming')
    const bookings = ref([])
    const history = ref([])
    const profile = reactive({})
    const stats = reactive({})
    const alert = reactive({
      show: false,
      type: '',
      message: ''
    })
    const logoutUrl = ref(window.hbcAttendeeData?.logoutUrl || '/wp-login.php?action=logout')

    // Modal states
    const showProfileModal = ref(false)
    const loadingMeetingLinks = ref(new Set())

    // Tab configuration
    const tabs = [
      { id: 'bookings', name: 'My Bookings' },
      { id: 'profile', name: 'Profile' }
    ]

    // Booking filter configuration
    const bookingFilters = [
      { id: 'upcoming', name: 'Upcoming' },
      { id: 'completed', name: 'Completed' },
      { id: 'cancelled', name: 'Cancelled' }
    ]

    // Methods
    const showAlert = (type, message) => {
      // Use toast notifications instead of legacy alert system
      if (toast) {
        toast.showAlert(type, message)
      } else {
        // Fallback to legacy system if toast is not available
        alert.show = true
        alert.type = `alert-${type}`
        alert.message = message
        setTimeout(() => {
          alert.show = false
        }, 5000)
      }
    }

    const clearAlert = () => {
      alert.show = false
    }

    const loadBookings = async () => {
      try {
        isLoading.value = true
        
        // Get all bookings first
        const data = await attendeeAPI.getBookings('all')
        let allBookings = data.bookings || []
        
        // Process booking data from API
        
        // Get today's date for filtering
        const today = new Date().toISOString().split('T')[0]
        const now = new Date()
        
        // Apply filtering based on current filter
        if (currentFilter.value === 'upcoming') {
          // Show future bookings that are confirmed only (no completed, cancelled, or pending)
          allBookings = allBookings.filter(booking => {
            const bookingDate = booking.meeting_dates
            const bookingDateTime = new Date(bookingDate + ' ' + (booking.start_time || '00:00:00'))
            const isConfirmed = booking.attendee_status === 'confirmed' || booking.booking_status === 'confirmed'
            const isNotCompleted = booking.attendee_status !== 'completed' && booking.booking_status !== 'completed'
            const isNotCancelled = booking.attendee_status !== 'cancelled' && booking.attendee_status !== 'canceled' && 
                                   booking.booking_status !== 'cancelled' && booking.booking_status !== 'canceled'
            return bookingDateTime > now && isConfirmed && isNotCompleted && isNotCancelled
          })
        } else if (currentFilter.value === 'completed') {
          // Show completed bookings
          allBookings = allBookings.filter(booking => 
            booking.attendee_status === 'completed' || booking.booking_status === 'completed'
          )
        } else if (currentFilter.value === 'cancelled') {
          // Show cancelled bookings
          allBookings = allBookings.filter(booking => 
            booking.attendee_status === 'cancelled' || booking.attendee_status === 'canceled' ||
            booking.booking_status === 'cancelled' || booking.booking_status === 'canceled'
          )
        }
        
        bookings.value = allBookings
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }



    const loadProfile = async () => {
      try {
        const data = await attendeeAPI.getProfile()
        // Process profile data from API
        
        if (data) {
          // Map the profile data correctly from WordPress user data
          Object.assign(profile, {
            first_name: data.first_name || '',
            last_name: data.last_name || '',
            email: data.email || '',
            display_name: data.name || '',
            phone: data.phone || '',
            timezone: data.timezone || ''
          })
          
          // Profile data assigned successfully
        }
      } catch (error) {
        console.error('Failed to load profile:', error)
        showAlert('error', handleApiError(error))
      }
    }

    const loadStats = async () => {
      try {
        // Get all bookings to calculate stats
        const data = await attendeeAPI.getBookings('all')
        const allBookings = data.bookings || []
        
        // Get today's date for filtering
        const now = new Date()
        
        // Calculate statistics
        const totalBookings = allBookings.length
        
        const upcomingBookings = allBookings.filter(booking => {
          const bookingDate = booking.meeting_dates
          const bookingDateTime = new Date(bookingDate + ' ' + (booking.start_time || '00:00:00'))
          return bookingDateTime > now && 
                 (booking.attendee_status === 'confirmed' || booking.attendee_status === 'pending' || 
                  booking.booking_status === 'confirmed' || booking.booking_status === 'pending')
        }).length
        
        const completedBookings = allBookings.filter(booking => 
          booking.attendee_status === 'completed' || booking.booking_status === 'completed'
        ).length
        
        const cancelledBookings = allBookings.filter(booking => 
          booking.attendee_status === 'cancelled' || booking.attendee_status === 'canceled' ||
          booking.booking_status === 'cancelled' || booking.booking_status === 'canceled'
        ).length
        
        // Update stats
        Object.assign(stats, {
          total_bookings: totalBookings,
          upcoming_bookings: upcomingBookings,
          completed_bookings: completedBookings,
          cancelled_bookings: cancelledBookings
        })
      } catch (error) {
        console.error('Failed to load stats:', error)
        showAlert('error', 'Failed to load statistics')
      }
    }



    const openProfileModal = () => {
      showProfileModal.value = true
    }

    const updateProfile = async (profileData) => {
      try {
        isLoading.value = true
        await attendeeAPI.updateProfile(profileData)
        Object.assign(profile, profileData)
        showProfileModal.value = false
        showAlert('success', 'Profile updated successfully')
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }



    // Meeting link functionality
    const hasJoinLink = (locationData) => {
      if (!locationData) return false
      try {
        const locations = typeof locationData === 'string' ? JSON.parse(locationData) : locationData
        if (!locations || typeof locations !== 'object') return false
        
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return false
        
        const firstLocation = locations[locationKeys[0]]
        return !!(firstLocation.join_url || firstLocation.meeting_url)
      } catch (e) {
        return false
      }
    }

    const getJoinLink = (locationData) => {
      if (!locationData) return null
      try {
        const locations = typeof locationData === 'string' ? JSON.parse(locationData) : locationData
        if (!locations || typeof locations !== 'object') return null
        
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return null
        
        const firstLocation = locations[locationKeys[0]]
        return firstLocation.join_url || firstLocation.meeting_url || null
      } catch (e) {
        return null
      }
    }

    const canShowMeetingButton = (booking) => {
      // In test mode, always show meeting button for confirmed bookings
      if (isTestModeActiveForBooking(booking)) {
        return booking.status === 'confirmed' || booking.booking_status === 'confirmed'
      }
      
      // Show meeting button for confirmed bookings that have meeting data
      return (booking.status === 'confirmed' || booking.booking_status === 'confirmed') && 
             (hasJoinLink(booking.meeting_locations) || booking.meeting_id)
    }

    const isMeetingAvailable = (booking) => {
      // Check if test mode is active using centralized constant
      if (isTestModeActiveForBooking(booking)) {
        return true
      }
      
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const meetingEndTime = new Date(booking.meeting_dates + ' ' + booking.end_time)
      
      // Meeting is available 5 minutes before start time until end time
      const fiveMinutesBefore = new Date(meetingDateTime.getTime() - 5 * 60 * 1000)
      
      const timeBasedAvailable = now >= fiveMinutesBefore && now <= meetingEndTime
      return timeBasedAvailable
    }

    const getMeetingButtonClass = (booking) => {
      // If testing mode is enabled, always show active button
      if (isTestModeActiveForBooking(booking)) {
        return 'btn-success animate-pulse'
      }
      
      if (!isMeetingAvailable(booking)) {
        return 'btn-secondary opacity-50 cursor-not-allowed'
      }
      
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const meetingEndTime = new Date(booking.meeting_dates + ' ' + booking.end_time)
      
      if (now >= meetingDateTime && now <= meetingEndTime) {
        return 'btn-success animate-pulse' // Meeting is live
      } else {
        return 'btn-primary' // Meeting available soon
      }
    }

    const getMeetingButtonText = (booking) => {
      // If testing mode is enabled, always show Start Meeting
      if (isTestModeActiveForBooking(booking)) {
        return 'Start Meeting'
      }
      
      const meetingAvailable = isMeetingAvailable(booking)
      
      if (!meetingAvailable) {
        return 'Scheduled'
      }
      
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const meetingEndTime = new Date(booking.meeting_dates + ' ' + booking.end_time)
      
      if (now >= meetingDateTime && now <= meetingEndTime) {
        return 'Start Meeting'
      } else {
        const minutesUntil = Math.ceil((meetingDateTime.getTime() - now.getTime()) / (1000 * 60))
        return `Available in ${minutesUntil}m`
      }
    }

    const handleMeetingAction = async (booking) => {
      if (loadingMeetingLinks.value.has(booking.id || booking.booking_id)) {
        return // Already loading
      }

      try {
        loadingMeetingLinks.value.add(booking.id || booking.booking_id)
        
        // Try to get meeting link from API
        const response = await attendeeAPI.getMeetingLink(booking.id || booking.booking_id)
        
        console.log('API Response:', response)
        console.log('Response status:', response.status)
        console.log('Response meeting_url:', response.meeting_url)
        
        if (response.status && response.meeting_url) {
          // Clean the meeting URL by removing extra quotes and whitespace
          let cleanUrl = response.meeting_url.toString().trim()
          if (cleanUrl.startsWith('"') && cleanUrl.endsWith('"')) {
            cleanUrl = cleanUrl.slice(1, -1)
          }
          if (cleanUrl.startsWith("'") && cleanUrl.endsWith("'")) {
            cleanUrl = cleanUrl.slice(1, -1)
          }
          
          console.log('Cleaned URL:', cleanUrl)
          console.log('About to navigate to:', cleanUrl)
          
          // Navigate directly to the meeting URL in the same tab
          window.location.href = cleanUrl
        } else {
          // Fallback to existing join link if available
          const fallbackLink = getJoinLink(booking.meeting_locations)
          if (fallbackLink) {
            window.open(fallbackLink, '_blank', 'noopener,noreferrer')
            showAlert('success', 'Meeting opened in new tab')
          } else {
            // In test mode, show a different message but still allow access
            if (isTestModeActiveForBooking(booking)) {
              showAlert('info', 'Test mode: Meeting link would be available here in production.')
            } else {
              showAlert('error', 'Meeting link not available. Please contact support.')
            }
          }
        }
      } catch (error) {
        console.error('Error getting meeting link:', error)
        // In test mode, show a more informative message
        if (isTestModeActiveForBooking(booking)) {
          showAlert('info', 'Test mode: Meeting access would be available here in production.')
        } else {
          showAlert('error', 'Failed to get meeting link. Please try again.')
        }
      } finally {
        loadingMeetingLinks.value.delete(booking.id || booking.booking_id)
      }
    }

    // Initialize dashboard
    const init = async () => {
      isLoading.value = true
      try {
        await Promise.all([
          loadBookings(),
          loadProfile(),
          loadStats()
        ])
      } catch (error) {
        showAlert('error', 'Failed to load dashboard data')
      } finally {
        isLoading.value = false
      }
    }

    // Watch for tab changes and load appropriate data
    watch(activeTab, (newTab) => {
      if (newTab === 'bookings') {
        loadBookings()
      }
    })

    // Handle logout
    const handleLogout = async () => {
      if (confirm('Are you sure you want to logout?')) {
        try {
          const response = await fetch(window.hbcAttendeeData.ajaxUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hbc_logout&nonce=${window.hbcAttendeeData.logoutNonce}`
          })
          
          const data = await response.json()
          
          if (data.success) {
            window.location.href = data.data.login_url
          } else {
            showAlert('error', 'Logout failed. Please try again.')
          }
        } catch (error) {
          console.error('Error:', error)
          showAlert('error', 'Logout failed. Please try again.')
        }
      }
    }

    // Countdown Timer Methods
    const isUpcomingMeeting = (booking) => {
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      return meetingDateTime > now
    }

    const onMeetingExpired = (booking) => {
      console.log('Meeting expired:', booking)
      // Optionally refresh bookings to update status
      loadBookings()
    }

    const onMeetingUrgent = (booking, timeRemaining) => {
      console.log('Meeting starting soon:', booking, timeRemaining)
      // Could show additional notifications or update UI
    }

    const onMeetingWarning = (booking, timeRemaining) => {
      console.log('Meeting approaching:', booking, timeRemaining)
      // Could show warning notifications
    }

    onMounted(init)

    return {
      // State
      isLoading,
      activeTab,
      currentFilter,
      bookings,
      profile,
      stats,
      alert,
      showProfileModal,
      tabs,
      bookingFilters,
      logoutUrl,

      // Methods
      showAlert,
      clearAlert,
      loadBookings,
      openProfileModal,
      updateProfile,
      handleLogout,
      hasJoinLink,
      getJoinLink,
      canShowMeetingButton,
      isMeetingAvailable,
      getMeetingButtonClass,
      getMeetingButtonText,
      handleMeetingAction,
      isTestModeActiveForBooking,
      isUpcomingMeeting,
      onMeetingExpired,
      onMeetingUrgent,
      onMeetingWarning,

      // Utilities
      formatDateTime,
      getStatusClass,
      getStatusText,
      __
    }
  }
}
</script>