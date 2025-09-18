<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!bookings.length" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10m6-10v10m-6 0h6" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_bookings_found') }}</h3>
    </div>

    <!-- Bookings Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="booking in bookings"
        :key="booking.booking_id"
        class="card hover:shadow-lg transition-shadow duration-200 h-fit"
      >
        <div class="card-body">
          <!-- Header with title and status -->
          <div class="flex items-start justify-between mb-4">
            <h4 class="text-lg font-medium text-gray-900 flex-1 pr-2">
              {{ booking.title || (booking.host_first_name + ' ' + booking.host_last_name).trim() || __('meeting') }}
            </h4>
            <span :class="getStatusClass(booking.status || booking.booking_status)" class="badge whitespace-nowrap">
              {{ getStatusText(booking.status || booking.booking_status) }}
            </span>
          </div>

          <!-- Countdown Timer for Upcoming Meetings -->
          <div v-if="isUpcomingMeeting(booking) && isBookingConfirmed(booking)" class="mb-4">
            <CountdownTimer
              :meeting-date="booking.meeting_dates"
              :start-time="booking.start_time"
              @expired="onMeetingExpired(booking)"
              @urgent="onMeetingUrgent(booking)"
              @warning="onMeetingWarning(booking)"
            />
          </div>

          <!-- Booking details in vertical layout -->
          <div class="space-y-3 text-sm text-gray-600">
            <!-- Attendee Information -->
            <div>
              <p class="text-sm font-medium text-gray-700">{{ __('attendee') }}</p>
              <p class="text-sm text-gray-900 truncate">{{ getAttendeeName(booking.attendees) || __('not_available') }}</p>
            </div>

            <!-- Email -->
            <div>
              <p class="text-sm font-medium text-gray-700">{{ __('email') }}</p>
              <p class="text-sm text-gray-600 truncate">{{ getAttendeeEmail(booking.attendees) || __('not_available') }}</p>
            </div>

            <!-- Duration -->
            <div>
              <p class="text-sm font-medium text-gray-700">{{ __('duration') }}</p>
              <p class="text-sm text-gray-600">{{ booking.duration ? booking.duration + ' ' + __('min') : __('not_available') }}</p>
            </div>

            <!-- Booking Date -->
            <div>
              <p class="text-sm font-medium text-gray-700">{{ __('date_time') }}</p>
              <p class="text-sm text-gray-600">{{ formatDate(booking.meeting_dates) }}</p>
              <p class="text-xs text-gray-500">{{ booking.start_time }} - {{ booking.end_time }}</p>
            </div>
          </div>

          <!-- Notes/Comments -->
          <div v-if="booking.notes || booking.internal_note || booking.attendee_comment" class="mt-4 pt-3 border-t border-gray-200 text-sm text-gray-600">
            <div v-if="booking.notes" class="mb-2">
              <span class="font-medium">{{ __('notes') }}:</span> 
              <span class="break-words">{{ booking.notes }}</span>
            </div>
            <div v-if="booking.internal_note" class="mb-2">
              <span class="font-medium">{{ __('internal_note') }}:</span> 
              <span class="break-words">{{ booking.internal_note }}</span>
            </div>
            <div v-if="booking.attendee_comment" class="mb-2">
              <span class="font-medium">{{ __('attendee_comment') }}:</span> 
              <span class="break-words">{{ booking.attendee_comment }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="showActions" class="mt-4 pt-3 border-t border-gray-200 space-y-2">
            <button
              @click="$emit('view-details', booking.id)"
              class="btn-secondary text-xs w-full"
            >
              {{ __('view_details') }}
            </button>

            <div v-if="booking.status === 'pending'" class="grid grid-cols-2 gap-2">
              <button
                @click="$emit('update-status', booking.id, 'confirmed')"
                class="btn-success text-xs"
              >
                {{ __('confirm') }}
              </button>
              <button
                @click="$emit('update-status', booking.id, 'cancelled')"
                class="btn-danger text-xs"
              >
                {{ __('cancel') }}
              </button>
            </div>

            <div v-else-if="booking.status === 'confirmed'" class="grid grid-cols-2 gap-2">
              <button
                @click="$emit('update-status', booking.id, 'completed')"
                class="btn-success text-xs"
              >
                {{ __('mark_complete') }}
              </button>
              <button
                @click="$emit('update-status', booking.id, 'cancelled')"
                class="btn-danger text-xs"
              >
                Cancel
              </button>
            </div>

            <!-- Meeting Link Button for Host -->
            <button
              v-if="canShowMeetingButton(booking)"
              @click="handleMeetingAction(booking)"
              :class="getMeetingButtonClass(booking)"
              :disabled="!isMeetingAvailable(booking) && !isTestModeActiveForBooking(booking)"
              class="text-xs w-full"
            >
              {{ getMeetingButtonText(booking, 'host') }}
            </button>
          </div>

          <!-- Attendee Actions -->
          <div v-else-if="showAttendeeActions" class="mt-4 pt-3 border-t border-gray-200 space-y-2">
            <button
              @click="$emit('view-details', booking.id)"
              class="btn-secondary text-xs w-full"
            >
              View Details
            </button>

            <!-- Meeting Link Button for Attendee -->
            <button
              v-if="canShowMeetingButton(booking)"
              @click="handleMeetingAction(booking)"
              :class="getMeetingButtonClass(booking)"
              :disabled="!isMeetingAvailable(booking) && !isTestModeActiveForBooking(booking)"
              class="text-xs w-full"
            >
              {{ getMeetingButtonText(booking, 'attendee') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { attendeeAPI, hostAPI } from '../utils/api.js'
import { isTestModeActiveForBooking } from '../utils/constants.js'
import { copyToClipboard, formatDateTime, getStatusClass, getStatusText } from '../utils/helpers.js'
import { __ } from '../utils/i18n.js'
import CountdownTimer from './CountdownTimer.vue'

export default {
  name: 'BookingsList',
  components: {
    CountdownTimer
  },
  props: {
    bookings: {
      type: Array,
      default: () => []
    },
    showActions: {
      type: Boolean,
      default: false
    },
    showAttendeeActions: {
      type: Boolean,
      default: false
    }
  },
  emits: [
    'view-details',
    'update-status',
    'show-alert',
    'meeting-expired',
    'meeting-urgent',
    'meeting-warning'
  ],
  setup(props, { emit }) {
    const loadingMeetingLinks = ref(new Set())
    const copyJoinLink = async (url) => {
      const success = await copyToClipboard(url)
      if (success) {
        emit('link-copied')
        emit('show-alert', 'success', 'Join link copied to clipboard')
      } else {
        emit('show-alert', 'error', 'Failed to copy link')
      }
    }



    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString()
    }

    const getAttendeeName = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return null
      }
      return attendees[0].attendee_name || attendees[0].name || null
    }

    const getAttendeeEmail = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return null
      }
      return attendees[0].email || null
    }

    const formatLocation = (locationData) => {
      if (!locationData) return null
      
      try {
        const locations = JSON.parse(locationData)
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return null
        
        const firstLocation = locations[locationKeys[0]]
        return firstLocation.location || locationKeys[0]
      } catch (e) {
        return locationData
      }
    }

    const hasJoinLink = (locationData) => {
      if (!locationData) return false
      
      try {
        const locations = JSON.parse(locationData)
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return false
        
        const firstLocation = locations[locationKeys[0]]
        return !!(firstLocation.join_url || firstLocation.meeting_url)
      } catch (e) {
        return false
      }
    }

    const getPaymentStatusClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'paid':
        case 'completed':
          return 'bg-green-100 text-green-800'
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'failed':
        case 'cancelled':
          return 'bg-red-100 text-red-800'
        case 'refunded':
          return 'bg-blue-100 text-blue-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    }

    const getJoinLink = (locationData) => {
      if (!locationData) return null
      
      try {
        const locations = JSON.parse(locationData)
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return null
        
        const firstLocation = locations[locationKeys[0]]
        return firstLocation.join_url || firstLocation.meeting_url || null
      } catch (e) {
        return null
      }
    }

    const getPaymentStatusText = (status) => {
      switch (status) {
        case '1':
        case 1:
          return 'Paid'
        case '0':
        case 0:
          return 'Pending'
        case '-1':
        case -1:
          return 'Failed'
        default:
          return status || 'Unknown'
      }
    }

    // Meeting link functionality
    const canShowMeetingButton = (booking) => {
      // In test mode, always show meeting button for confirmed bookings
      if (isTestModeActiveForBooking(booking)) {
        return booking.status === 'confirmed'
      }
      
      // Show meeting button for confirmed bookings that have meeting data
      return booking.status === 'confirmed' && (hasJoinLink(booking.meeting_locations) || booking.meeting_id)
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
      
      return now >= fiveMinutesBefore && now <= meetingEndTime
    }

    const getMeetingButtonClass = (booking) => {
      if (!isMeetingAvailable(booking)) {
        return 'btn-secondary opacity-50 cursor-not-allowed'
      }
      
      // If testing mode is enabled, always show active button
      if (isTestModeActiveForBooking(booking)) {
        return 'btn-success animate-pulse'
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

    const getMeetingButtonText = (booking, userType) => {
      // If testing mode is enabled, always show Start Meeting for both hosts and attendees
      if (isTestModeActiveForBooking(booking)) {
        return __('start_meeting_btn', 'hydra-booking-customization')
      }
      
      if (!isMeetingAvailable(booking)) {
        return __('scheduled', 'hydra-booking-customization')
      }
      
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const meetingEndTime = new Date(booking.meeting_dates + ' ' + booking.end_time)
      
      if (now >= meetingDateTime && now <= meetingEndTime) {
        return userType === 'host' ? __('start_meeting_btn', 'hydra-booking-customization') : __('start_meeting_btn', 'hydra-booking-customization')
      } else {
        const minutesUntil = Math.ceil((meetingDateTime.getTime() - now.getTime()) / (1000 * 60))
        return __('available_in', 'hydra-booking-customization', minutesUntil)
      }
    }

    const handleMeetingAction = async (booking) => {
      // In test mode, bypass the availability check
      if (!isMeetingAvailable(booking) && !isTestModeActiveForBooking(booking)) {
        emit('show-alert', 'info', 'Meeting will be available 5 minutes before the scheduled time.')
        return
      }

      if (loadingMeetingLinks.value.has(booking.id)) {
        return // Already loading
      }

      try {
        loadingMeetingLinks.value.add(booking.id)
        
        // Try to get meeting link from API
        const api = props.showActions ? hostAPI : attendeeAPI
        const response = await api.getMeetingLink(booking.id)
        
        console.log('API Response:', response)
        console.log('Response status:', response.status)
        console.log('Response meeting_url:', response.meeting_url)
        console.log('Using API:', props.showActions ? 'hostAPI' : 'attendeeAPI')
        
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
            emit('show-alert', 'success', 'Meeting opened in new tab')
          } else {
            // In test mode, show a different message but still allow access
            if (isTestModeActiveForBooking(booking)) {
              emit('show-alert', 'info', 'Test mode: Meeting link would be available here in production.')
            } else {
              emit('show-alert', 'error', 'Meeting link not available. Please contact support.')
            }
          }
        }
      } catch (error) {
        console.error('Error getting meeting link:', error)
        // In test mode, show a more informative message
        if (isTestModeActiveForBooking(booking)) {
          emit('show-alert', 'info', 'Test mode: Meeting access would be available here in production.')
        } else {
          emit('show-alert', 'error', 'Failed to get meeting link. Please try again.')
        }
      } finally {
        loadingMeetingLinks.value.delete(booking.id)
      }
    }

    // Countdown Timer Methods
    const isUpcomingMeeting = (booking) => {
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      return meetingDateTime > now
    }

    const isBookingConfirmed = (booking) => {
      const status = booking.status || booking.booking_status || booking.attendee_status
      return status === 'confirmed' && 
             status !== 'cancelled' && 
             status !== 'canceled' && 
             status !== 'completed'
    }

    const onMeetingExpired = (booking) => {
      console.log('Meeting expired:', booking)
      // Emit event to parent component to refresh bookings
      emit('meeting-expired', booking)
    }

    const onMeetingUrgent = (booking, timeRemaining) => {
      console.log('Meeting starting soon:', booking, timeRemaining)
      // Could emit event for notifications
      emit('meeting-urgent', booking, timeRemaining)
    }

    const onMeetingWarning = (booking, timeRemaining) => {
      console.log('Meeting approaching:', booking, timeRemaining)
      // Could emit event for warnings
      emit('meeting-warning', booking, timeRemaining)
    }

    return {
      formatDateTime,
      formatDate,
      getStatusClass,
      getStatusText,
      copyJoinLink,
      getAttendeeName,
      getAttendeeEmail,
      formatLocation,
      hasJoinLink,
      getPaymentStatusClass,
      getPaymentStatusText,
      getJoinLink,
      // Meeting link functionality
      canShowMeetingButton,
      isMeetingAvailable,
      getMeetingButtonClass,
      getMeetingButtonText,
      handleMeetingAction,
      loadingMeetingLinks,
      isTestModeActiveForBooking,
      isUpcomingMeeting,
      isBookingConfirmed,
      onMeetingExpired,
      onMeetingUrgent,
      onMeetingWarning,
      __
    }
  }
}
</script>