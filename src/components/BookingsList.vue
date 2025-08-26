<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!bookings.length" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10m6-10v10m-6 0h6" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
      <p class="mt-1 text-sm text-gray-500">No bookings match the current criteria.</p>
    </div>

    <!-- Bookings List -->
    <div v-else class="space-y-4">
      <div
        v-for="booking in bookings"
        :key="booking.booking_id"
        class="card hover:shadow-md transition-shadow duration-200"
      >
        <div class="card-body">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-3">
                <h4 class="text-lg font-medium text-gray-900">
                  {{ booking.title || (booking.host_first_name + ' ' + booking.host_last_name).trim() || 'Meeting' }}
                </h4>
                <span :class="getStatusClass(booking.status || booking.booking_status)" class="badge">
                  {{ getStatusText(booking.status || booking.booking_status) }}
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600">
                <!-- Date and Time -->
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">{{ formatDate(booking.meeting_dates) }}</div>
                    <div class="text-xs text-gray-500">{{ booking.start_time }} - {{ booking.end_time }}</div>
                  </div>
                </div>

                <!-- Duration and Timezone -->
                <div v-if="booking.duration" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">{{ booking.duration }} min</div>
                    <div class="text-xs text-gray-500">{{ booking.availability_time_zone || 'UTC' }}</div>
                  </div>
                </div>

                <!-- Attendee Information -->
                <div v-if="getAttendeeName(booking.attendees)" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">{{ getAttendeeName(booking.attendees) }}</div>
                    <div class="text-xs text-gray-500">{{ getAttendeeEmail(booking.attendees) }}</div>
                  </div>
                </div>

                <!-- Location -->
                <div v-if="formatLocation(booking.meeting_locations)" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">{{ formatLocation(booking.meeting_locations) }}</div>
                    <div v-if="booking.meeting_locations && hasJoinLink(booking.meeting_locations)" class="text-xs text-green-600">
                      Join link available
                    </div>
                  </div>
                </div>

                <!-- Payment Status -->
                <div v-if="booking.meeting_payment_status" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                  </svg>
                  <span :class="getPaymentStatusClass(booking.meeting_payment_status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ getPaymentStatusText(booking.meeting_payment_status) }}
                  </span>
                </div>

                <!-- Host Information -->
                <div v-if="booking.host_first_name || booking.host_last_name" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">Host: {{ (booking.host_first_name + ' ' + booking.host_last_name).trim() }}</div>
                    <div v-if="booking.host_email" class="text-xs text-gray-500">{{ booking.host_email }}</div>
                  </div>
                </div>

                <!-- Booking Created -->
                <div v-if="booking.created_at" class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">Booked</div>
                    <div class="text-xs text-gray-500">{{ formatDate(booking.created_at) }}</div>
                  </div>
                </div>

                <!-- Booking ID -->
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <div class="font-medium">ID: {{ booking.id }}</div>
                    <div class="text-xs text-gray-500">Booking reference</div>
                  </div>
                </div>
              </div>

              <!-- Notes/Comments -->
              <div v-if="booking.notes || booking.internal_note || booking.attendee_comment" class="mt-3 text-sm text-gray-600">
                <div v-if="booking.notes" class="mb-1">
                  <span class="font-medium">Notes:</span> {{ booking.notes }}
                </div>
                <div v-if="booking.internal_note" class="mb-1">
                  <span class="font-medium">Internal Note:</span> {{ booking.internal_note }}
                </div>
                <div v-if="booking.attendee_comment" class="mb-1">
                  <span class="font-medium">Attendee Comment:</span> {{ booking.attendee_comment }}
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="showActions" class="flex flex-col space-y-2 ml-4">
              <button
                @click="$emit('view-details', booking.id)"
                class="btn-secondary text-xs"
              >
                View Details
              </button>

              <div v-if="booking.status === 'pending'" class="flex flex-col space-y-1">
                <button
                  @click="$emit('update-status', booking.id, 'confirmed')"
                  class="btn-success text-xs"
                >
                  Confirm
                </button>
                <button
                  @click="$emit('update-status', booking.id, 'cancelled')"
                  class="btn-danger text-xs"
                >
                  Cancel
                </button>
              </div>

              <div v-else-if="booking.status === 'confirmed'" class="flex flex-col space-y-1">
                <button
                  @click="$emit('update-status', booking.id, 'completed')"
                  class="btn-success text-xs"
                >
                  Mark Complete
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
                class="text-xs"
              >
                {{ getMeetingButtonText(booking, 'host') }}
              </button>
              

            </div>

            <!-- Attendee Actions -->
            <div v-else-if="showAttendeeActions" class="flex flex-col space-y-2 ml-4">
              <button
                @click="$emit('view-details', booking.id)"
                class="btn-secondary text-xs"
              >
                View Details
              </button>

              <div v-if="booking.status === 'confirmed' && canCancel(booking)" class="flex flex-col space-y-1">
                <button
                  @click="$emit('cancel-booking', booking.id)"
                  class="btn-danger text-xs"
                >
                  Cancel
                </button>
                <button
                  @click="$emit('reschedule-booking', booking.id)"
                  class="btn-warning text-xs"
                >
                  Reschedule
                </button>
              </div>

              <!-- Meeting Link Button for Attendee -->
              <button
                v-if="canShowMeetingButton(booking)"
                @click="handleMeetingAction(booking)"
                :class="getMeetingButtonClass(booking)"
                :disabled="!isMeetingAvailable(booking) && !isTestModeActiveForBooking(booking)"
                class="text-xs"
              >
                {{ getMeetingButtonText(booking, 'attendee') }}
              </button>
              

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { attendeeAPI, hostAPI } from '../utils/api.js'
import { copyToClipboard, formatDateTime, getStatusClass, getStatusText } from '../utils/helpers.js'
import { isTestModeActiveForBooking } from '../utils/constants.js'

export default {
  name: 'BookingsList',
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
    'cancel-booking',
    'reschedule-booking',
    'show-alert'
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

    const canCancel = (booking) => {
      // Allow cancellation if booking is at least 24 hours away
      const bookingTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const now = new Date()
      const timeDiff = bookingTime.getTime() - now.getTime()
      const hoursDiff = timeDiff / (1000 * 3600)
      return hoursDiff >= 24
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
        return 'Start Meeting'
      }
      
      if (!isMeetingAvailable(booking)) {
        return 'Scheduled'
      }
      
      const now = new Date()
      const meetingDateTime = new Date(booking.meeting_dates + ' ' + booking.start_time)
      const meetingEndTime = new Date(booking.meeting_dates + ' ' + booking.end_time)
      
      if (now >= meetingDateTime && now <= meetingEndTime) {
        return userType === 'host' ? 'Start Meeting' : 'Start Meeting'
      } else {
        const minutesUntil = Math.ceil((meetingDateTime.getTime() - now.getTime()) / (1000 * 60))
        return `Available in ${minutesUntil}m`
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



    return {
      formatDateTime,
      formatDate,
      getStatusClass,
      getStatusText,
      copyJoinLink,
      canCancel,
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
      isTestModeActiveForBooking
    }
  }
}
</script>