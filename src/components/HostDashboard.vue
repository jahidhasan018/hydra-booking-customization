<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
          <div class="loading-spinner"></div>
          <span class="text-gray-700">{{ __('loading') }}</span>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 hbc-dashboard-header">
        <div class="hbc-header-content">
          <div class="hbc-header-text">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('host_dashboard') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('manage_meetings_bookings') }}</p>
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

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                <p class="text-2xl font-semibold text-gray-900">{{ stats.today_meetings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('todays_meetings') }}</p>
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
                <p class="text-2xl font-semibold text-gray-900">{{ stats.completed_meetings || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('completed') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-semibold text-gray-900">{{ stats.active_join_links || 0 }}</p>
                <p class="text-sm text-gray-600">{{ __('active_links') }}</p>
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
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-lg shadow">
        <!-- Bookings Tab -->
        <div v-show="activeTab === 'bookings'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('bookings') }}</h3>
            <button @click="loadBookings(activeFilter)" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
              </svg>
              {{ __('refresh') }}
            </button>
          </div>

          <!-- Filter Buttons -->
          <div class="flex flex-wrap gap-2 mb-6">
            <button
              v-for="filter in filters"
              :key="filter.id"
              @click="activeFilter = filter.id"
              :class="[
                'inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200',
                activeFilter === filter.id
                  ? 'bg-primary-100 text-primary-700 border border-primary-200'
                  : 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200'
              ]"
            >
              <svg v-if="filter.icon === 'calendar'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="filter.icon === 'clock'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="filter.icon === 'check'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="filter.icon === 'x'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              <svg v-else-if="filter.icon === 'archive'" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" />
              </svg>
              {{ filter.name }}
            </button>
          </div>

          <BookingsList
            :bookings="bookings"
            :show-actions="true"
            @update-status="updateBookingStatus"
            @view-details="viewBookingDetails"
          />
        </div>

        <!-- Join Links Tab -->
        <div v-show="activeTab === 'join-links'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('join_links_management') }}</h3>
            <button @click="openJoinLinkModal" class="btn-primary">
              {{ __('generate_new_link') }}
            </button>
          </div>

          <JoinLinksList
            :join-links="joinLinks"
            @send-link="sendJoinLink"
            @copy-link="copyJoinLink"
          />
        </div>

        <!-- Profile Tab -->
        <div v-show="activeTab === 'profile'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('profile_settings') }}</h3>
            <button @click="openProfileModal" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
              </svg>
              {{ __('edit_profile') }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="form-label">{{ __('first_name') }}</label>
              <p class="text-gray-700">{{ profile.first_name || __('not_set') }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('last_name') }}</label>
              <p class="text-gray-700">{{ profile.last_name || __('not_set') }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('email') }}</label>
              <p class="text-gray-700">{{ profile.email || __('not_set') }}</p>
            </div>
            <div>
              <label class="form-label">{{ __('phone') }}</label>
              <p class="text-gray-700">{{ profile.phone || __('not_set') }}</p>
            </div>
            <div class="md:col-span-2">
              <label class="form-label">{{ __('bio') }}</label>
              <p class="text-gray-700">{{ profile.bio || __('not_set') }}</p>
            </div>
          </div>
        </div>

        <!-- History Tab -->
        <div v-show="activeTab === 'history'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('meeting_history') }}</h3>
            <button @click="loadBookings('history')" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
              </svg>
              {{ __('refresh') }}
            </button>
          </div>

          <BookingsList
            :bookings="bookings"
            :show-actions="false"
          />
        </div>
      </div>
    </div>

    <!-- Booking Details Modal -->
    <BookingDetailsModal
      v-if="showBookingModal"
      :booking="selectedBooking"
      @close="showBookingModal = false"
    />

    <!-- Join Link Modal -->
    <JoinLinkModal
      v-if="showJoinLinkModal"
      @close="showJoinLinkModal = false"
      @save="generateJoinLink"
    />

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
import { inject, onMounted, reactive, ref, watch } from 'vue'
import { hostAPI } from '../utils/api.js'
import { copyToClipboard, formatDateTime, getStatusClass, getStatusText, handleApiError } from '../utils/helpers.js'
import { __ } from '../utils/i18n.js'
import BookingsList from './BookingsList.vue'
import JoinLinksList from './JoinLinksList.vue'
import BookingDetailsModal from './modals/BookingDetailsModal.vue'
import JoinLinkModal from './modals/JoinLinkModal.vue'
import ProfileModal from './modals/ProfileModal.vue'

export default {
  name: 'HostDashboard',
  components: {
    BookingsList,
    JoinLinksList,
    BookingDetailsModal,
    JoinLinkModal,
    ProfileModal
  },
  setup() {
    // Inject toast notification system
    const toast = inject('toast')
    
    // Reactive state
    const isLoading = ref(false)
    const activeTab = ref('bookings')
    const bookings = ref([])
    const joinLinks = ref([])
    const profile = reactive({})
    const stats = reactive({})
    const alert = reactive({
      show: false,
      type: '',
      message: ''
    })
    const activeFilter = ref('today')
    const logoutUrl = ref(window.hbcHostData?.logoutUrl || '/wp-login.php?action=logout')

    // Modal states
    const showBookingModal = ref(false)
    const showJoinLinkModal = ref(false)
    const showProfileModal = ref(false)
    const selectedBooking = ref(null)

    // Tab configuration
    const tabs = [
      { id: 'bookings', name: __('bookings') },
      { id: 'join-links', name: __('join_links') },
      { id: 'profile', name: __('profile') }
    ]

    // Filter configuration
    const filters = [
      { id: 'today', name: __('today_filter'), icon: 'clock' },
      { id: 'completed', name: __('completed_filter'), icon: 'check' },
      { id: 'cancelled', name: __('cancelled_filter'), icon: 'x' },
      { id: 'history', name: __('all_history_filter'), icon: 'archive' }
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

    const loadBookings = async (filterType = 'upcoming') => {
      try {
        isLoading.value = true
        
        // Map filter types to API parameters
        let apiType = filterType
        if (filterType === 'history') {
          apiType = 'past'
        } else if (filterType === 'completed') {
          apiType = 'past'
        } else if (filterType === 'cancelled') {
          apiType = 'past'
        }
        
        const data = await hostAPI.getBookings(apiType)
        // Process bookings data from API
        
        // The API returns an object with { status: true, bookings: [...] }
        let allBookings = (data && data.bookings && Array.isArray(data.bookings)) ? data.bookings : []
        
        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0]
        const now = new Date()
        
        // Apply client-side filtering for specific statuses and dates
        if (filterType === 'today') {
          // Only show today's bookings that are confirmed or pending
          allBookings = allBookings.filter(booking => {
            const bookingDate = booking.meeting_dates
            return bookingDate === today && 
                   (booking.status === 'confirmed' || booking.status === 'pending')
          })
        } else if (filterType === 'upcoming') {
          // Only show future bookings (not today) that are confirmed or pending
          allBookings = allBookings.filter(booking => {
            const bookingDate = booking.meeting_dates
            const bookingDateTime = new Date(bookingDate + ' ' + (booking.start_time || '00:00:00'))
            return bookingDate > today && 
                   (booking.status === 'confirmed' || booking.status === 'pending')
          })
        } else if (filterType === 'completed') {
          // Show bookings that are marked as completed OR have ended (past end time)
          allBookings = allBookings.filter(booking => {
            if (booking.status === 'completed') {
              return true
            }
            // Check if meeting has ended
            const bookingDate = booking.meeting_dates
            const endTime = booking.end_time || '23:59:59'
            const bookingEndDateTime = new Date(bookingDate + ' ' + endTime)
            const now = new Date()
            return bookingEndDateTime < now
          })
        } else if (filterType === 'cancelled') {
          allBookings = allBookings.filter(booking => 
            booking.status === 'cancelled' || booking.status === 'canceled'
          )
        }
        
        bookings.value = allBookings
        // Filtered bookings assigned successfully
      } catch (error) {
        console.error('Bookings load error:', error) // Debug log
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }

    const loadJoinLinks = async () => {
      try {
        const data = await hostAPI.getJoinLinks()
        // Process join links data from API
        
        // Check if data has join_links property or if it's the array directly
        joinLinks.value = data.join_links || (Array.isArray(data) ? data : [])
        
        // Join links assigned successfully
      } catch (error) {
        console.error('Join links load error:', error) // Debug log
        showAlert('error', handleApiError(error))
      }
    }

    const loadProfile = async () => {
      try {
        const data = await hostAPI.getProfile()
        // Process profile data from API
        
        if (data && data.profile) {
          // Map the profile data correctly
          const profileData = data.profile
          const hostData = profileData.host_data || {}
          
          Object.assign(profile, {
            first_name: profileData.first_name || hostData.first_name || '',
            last_name: profileData.last_name || hostData.last_name || '',
            email: profileData.email || hostData.email || '',
            display_name: profileData.display_name || '',
            phone: hostData.phone_number || '',
            bio: profileData.description || hostData.about || '',
            avatar: hostData.avatar || '',
            featured_image: hostData.featured_image || '',
            status: hostData.status || '',
            availability_type: hostData.availability_type || '',
            availability_id: hostData.availability_id || ''
          })
          
          // Profile data assigned successfully
        }
      } catch (error) {
        console.error('Profile load error:', error) // Debug log
        showAlert('error', handleApiError(error))
      }
    }

    const loadStats = async () => {
      try {
        const data = await hostAPI.getStats()
        // Process stats data from API
        
        // The backend returns stats directly, not wrapped in a stats object
        Object.assign(stats, data || {})
        
        // Stats data assigned successfully
      } catch (error) {
        console.error('Failed to load stats:', error)
        showAlert('error', __('failed_load_statistics'))
      }
    }

    const updateBookingStatus = async (bookingId, status) => {
      try {
        isLoading.value = true
        await hostAPI.updateBookingStatus(bookingId, status)
        showAlert('success', __('booking_status_updated').replace('{status}', status))
        await loadBookings(activeTab.value)
        await loadStats()
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }

    const viewBookingDetails = async (bookingId) => {
      try {
        const response = await hostAPI.getBookingDetails(bookingId)
        
        // The API response interceptor returns response.data, so the booking data should be directly available
        // Check for different possible response structures
        let bookingData = null
        
        if (response && response.data) {
          bookingData = response.data
        } else if (response && response.booking) {
          bookingData = response.booking
        } else if (response) {
          bookingData = response
        }
        
        selectedBooking.value = bookingData
        showBookingModal.value = true
      } catch (error) {
        console.error('Error fetching booking details:', error)
        showAlert('error', __('booking_details_error') + (error.response?.data?.message || error.message))
      }
    }

    const openJoinLinkModal = () => {
      showJoinLinkModal.value = true
    }

    const generateJoinLink = async (linkData) => {
      try {
        isLoading.value = true
        await hostAPI.generateJoinLink(
          linkData.meetingId,
          linkData.linkType,
          linkData.customUrl
        )
        showJoinLinkModal.value = false
        showAlert('success', __('join_link_generated'))
        await loadJoinLinks()
        await loadStats()
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }

    const sendJoinLink = async (linkId) => {
      try {
        isLoading.value = true
        await hostAPI.sendJoinLink(linkId)
        showAlert('success', __('join_link_sent'))
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }

    const copyJoinLink = async (url) => {
      const success = await copyToClipboard(url)
      if (success) {
        showAlert('success', __('join_link_copied'))
      } else {
        showAlert('error', __('failed_copy_link'))
      }
    }

    const openProfileModal = () => {
      showProfileModal.value = true
    }

    const updateProfile = async (profileData) => {
      try {
        isLoading.value = true
        await hostAPI.updateProfile(profileData)
        Object.assign(profile, profileData)
        showProfileModal.value = false
        showAlert('success', __('profile_updated'))
      } catch (error) {
        showAlert('error', handleApiError(error))
      } finally {
        isLoading.value = false
      }
    }

    // Initialize dashboard
    const init = async () => {
      isLoading.value = true
      try {
        await Promise.all([
          loadBookings(activeFilter.value),
          loadJoinLinks(),
          loadProfile(),
          loadStats()
        ])
      } catch (error) {
        showAlert('error', __('failed_load_dashboard'))
      } finally {
        isLoading.value = false
      }
    }

    // Watch for tab changes and load appropriate data
    watch(activeTab, (newTab) => {
      if (newTab === 'bookings') {
        loadBookings(activeFilter.value)
      } else if (newTab === 'join-links') {
        loadJoinLinks()
      }
    })

    // Watch for filter changes and load appropriate booking data
    watch(activeFilter, (newFilter) => {
      if (activeTab.value === 'bookings') {
        loadBookings(newFilter)
      }
    })

    // Handle logout
    const handleLogout = async () => {
      if (confirm(__('logout_confirmation'))) {
        try {
          const response = await fetch(window.hbcHostData.ajaxUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hbc_logout&nonce=${window.hbcHostData.logoutNonce}`
          })
          
          const data = await response.json()
          
          if (data.success) {
            window.location.href = data.data.login_url
          } else {
            showAlert('error', __('logout_failed_retry'))
          }
        } catch (error) {
          console.error('Error:', error)
          showAlert('error', __('logout_failed_retry'))
        }
      }
    }

    onMounted(init)

    return {
      // State
      isLoading,
      activeTab,
      activeFilter,
      bookings,
      joinLinks,
      profile,
      stats,
      alert,
      showBookingModal,
      showJoinLinkModal,
      showProfileModal,
      selectedBooking,
      tabs,
      filters,
      logoutUrl,

      // Methods
      showAlert,
      clearAlert,
      loadBookings,
      updateBookingStatus,
      viewBookingDetails,
      openJoinLinkModal,
      generateJoinLink,
      sendJoinLink,
      copyJoinLink,
      openProfileModal,
      updateProfile,
      handleLogout,

      // Utilities
      formatDateTime,
      getStatusClass,
      getStatusText,
      __
    }
  }
}
</script>