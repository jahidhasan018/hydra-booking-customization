<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Booking Details</h3>
        <button @click="$emit('close')" class="modal-close">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">

        
        <div v-if="booking" class="space-y-6">
          <!-- Meeting Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Meeting Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Meeting Title</label>
                <p class="text-gray-700">{{ booking.data?.meeting_title || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Date</label>
                <p class="text-gray-700">{{ booking.data?.meeting_dates || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Time</label>
                <p class="text-gray-700">{{ booking.data?.start_time || 'N/A' }} - {{ booking.data?.end_time || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Duration</label>
                <p class="text-gray-700">{{ booking.data?.duration || 'N/A' }} minutes</p>
              </div>
              <div>
                <label class="form-label">Booking Type</label>
                <p class="text-gray-700">{{ booking.data?.booking_type || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Status</label>
                <span :class="getStatusClass(booking.data?.status)" class="badge">
                  {{ getStatusText(booking.data?.status) || booking.data?.status || 'N/A' }}
                </span>
              </div>
              <div v-if="booking.data?.internal_note" class="md:col-span-2">
                <label class="form-label">Internal Note</label>
                <p class="text-gray-700">{{ booking.data.internal_note }}</p>
              </div>
            </div>
          </div>

          <!-- Attendee Information -->
          <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Attendee Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Name</label>
                <p class="text-gray-700">{{ getAttendeeName(booking.data?.attendees) || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Email</label>
                <p class="text-gray-700">{{ getAttendeeEmail(booking.data?.attendees) || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Attendee ID</label>
                <p class="text-gray-700">{{ booking.data?.attendee_id || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Booking ID</label>
                <p class="text-gray-700">{{ booking.data?.id || booking.data?.booking_id || 'N/A' }}</p>
              </div>
              <div>
                <label class="form-label">Created At</label>
                <p class="text-gray-700">{{ formatDateTime(booking.data?.created_at) || 'N/A' }}</p>
              </div>
              <div v-if="booking.data?.attendee_comment" class="md:col-span-2">
                <label class="form-label">Comment</label>
                <p class="text-gray-700">{{ booking.data.attendee_comment }}</p>
              </div>
              <div class="md:col-span-2">
                <label class="form-label">Booked At</label>
                <p class="text-gray-700">{{ formatDateTime(booking.data?.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Activity Timeline -->
          <div v-if="booking.data?.activity && booking.data.activity.length" class="bg-green-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Activity Details</h4>
            <div class="space-y-3">
              <div
                v-for="activity in booking.data.activity"
                :key="activity.id"
                class="flex items-start space-x-3 p-3 bg-white rounded border"
              >
                <div class="flex-shrink-0">
                  <div class="w-2 h-2 bg-green-400 rounded-full mt-2"></div>
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900">{{ activity.action }}</p>
                    <p class="text-xs text-gray-500">{{ formatDateTime(activity.created_at) }}</p>
                  </div>
                  <p v-if="activity.description" class="text-sm text-gray-600 mt-1">
                    {{ activity.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Join Link Information -->
          <div v-if="booking.data?.join_links && booking.data.join_links.length > 0" class="bg-purple-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Join Links</h4>
            <div v-for="(link, index) in booking.data.join_links" :key="index" class="flex items-center justify-between mb-2">
              <code class="bg-white px-3 py-2 rounded border text-sm font-mono">
                {{ link }}
              </code>
              <button
                @click="copyJoinLink(link)"
                class="btn-primary ml-3"
              >
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                  <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                </svg>
                Copy Link
              </button>
            </div>
          </div>

          <!-- Custom Fields -->
          <div v-if="booking.data?.custom_fields && Object.keys(booking.data.custom_fields).length" class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="(value, key) in booking.data.custom_fields"
                :key="key"
                class="space-y-1"
              >
                <label class="form-label">{{ formatFieldName(key) }}</label>
                <p class="text-gray-700">{{ value || 'N/A' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <div class="loading-spinner mx-auto mb-4"></div>
          <p class="text-gray-600">Loading booking details...</p>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">
          Close
        </button>
        <button
          v-if="booking && getAttendeeEmail(booking.data?.attendees)"
          @click="sendEmail"
          class="btn-primary"
        >
          Send Email
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateTime, getStatusClass, getStatusText, copyToClipboard } from '../../utils/helpers.js'
import { watch } from 'vue'

export default {
  name: 'BookingDetailsModal',
  props: {
    booking: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'send-email'],
  setup(props, { emit }) {
    // Watch for changes to the booking prop
    watch(() => props.booking, (newBooking, oldBooking) => {
      // Booking prop changed
    }, { immediate: true, deep: true })
    
    const formatDate = (dateTime) => {
      if (!dateTime) return 'N/A'
      return new Date(dateTime).toLocaleDateString()
    }

    const formatTime = (dateTime) => {
      if (!dateTime) return 'N/A'
      return new Date(dateTime).toLocaleTimeString()
    }

    const formatFieldName = (fieldName) => {
      return fieldName
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
    }

    const copyJoinLink = async (url) => {
      const success = await copyToClipboard(url)
      if (success) {
        emit('link-copied')
        emit('show-alert', 'success', 'Join link copied to clipboard')
      } else {
        emit('show-alert', 'error', 'Failed to copy link')
      }
    }

    const sendEmail = () => {
      emit('send-email', props.booking)
    }

    const getAttendeeName = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return 'N/A'
      }
      return attendees[0].attendee_name || attendees[0].name || 'N/A'
    }

    const getAttendeeEmail = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return 'N/A'
      }
      return attendees[0].email || 'N/A'
    }

    const formatLocation = (locationData) => {
      if (!locationData) return 'N/A'
      
      try {
        const locations = JSON.parse(locationData)
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return 'N/A'
        
        const firstLocation = locations[locationKeys[0]]
        return firstLocation.location || locationKeys[0]
      } catch (e) {
        return locationData
      }
    }

    return {
      formatDateTime,
      formatDate,
      formatTime,
      formatFieldName,
      getStatusClass,
      getStatusText,
      copyJoinLink,
      sendEmail,
      getAttendeeName,
      getAttendeeEmail,
      formatLocation
    }
  }
}
</script>