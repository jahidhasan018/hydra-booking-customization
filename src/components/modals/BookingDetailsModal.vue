<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">{{ __('booking_details') }}</h3>
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
            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('meeting_details') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">{{ __('meeting_title') }}</label>
                <p class="text-gray-700">{{ booking.data?.meeting_title || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('date') }}</label>
                <p class="text-gray-700">{{ booking.data?.meeting_dates || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('time') }}</label>
                <p class="text-gray-700">{{ booking.data?.start_time || __('not_available') }} - {{ booking.data?.end_time || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('duration') }}</label>
                <p class="text-gray-700">{{ booking.data?.duration || __('not_available') }} {{ __('minutes') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('booking_type') }}</label>
                <p class="text-gray-700">{{ booking.data?.booking_type || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('status') }}</label>
                <span :class="getStatusClass(booking.data?.status)" class="badge">
                  {{ getStatusText(booking.data?.status) || booking.data?.status || __('not_available') }}
                </span>
              </div>
              <div v-if="booking.data?.internal_note" class="md:col-span-2">
                <label class="form-label">{{ __('internal_note') }}</label>
                <p class="text-gray-700">{{ booking.data.internal_note }}</p>
              </div>
            </div>
          </div>

          <!-- Attendee Information -->
          <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('attendee_details') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">{{ __('name') }}</label>
                <p class="text-gray-700">{{ getAttendeeName(booking.data?.attendees) || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('email') }}</label>
                <p class="text-gray-700">{{ getAttendeeEmail(booking.data?.attendees) || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('attendee_id') }}</label>
                <p class="text-gray-700">{{ booking.data?.attendee_id || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('booking_id') }}</label>
                <p class="text-gray-700">{{ booking.data?.id || booking.data?.booking_id || __('not_available') }}</p>
              </div>
              <div>
                <label class="form-label">{{ __('created_at') }}</label>
                <p class="text-gray-700">{{ formatDateTime(booking.data?.created_at) || __('not_available') }}</p>
              </div>
              <div v-if="booking.data?.attendee_comment" class="md:col-span-2">
                <label class="form-label">{{ __('comment') }}</label>
                <p class="text-gray-700">{{ booking.data.attendee_comment }}</p>
              </div>
              <div class="md:col-span-2">
                <label class="form-label">{{ __('booked_at') }}</label>
                <p class="text-gray-700">{{ formatDateTime(booking.data?.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- Activity Timeline -->
          <div v-if="booking.data?.activity && booking.data.activity.length" class="bg-green-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('activity_details') }}</h4>
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
            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('join_links') }}</h4>
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
{{ __('copy_link') }}
              </button>
            </div>
          </div>

          <!-- Custom Fields -->
          <div v-if="booking.data?.custom_fields && Object.keys(booking.data.custom_fields).length" class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('additional_information') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="(value, key) in booking.data.custom_fields"
                :key="key"
                class="space-y-1"
              >
                <label class="form-label">{{ formatFieldName(key) }}</label>
                <p class="text-gray-700">{{ value || __('not_available') }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <div class="loading-spinner mx-auto mb-4"></div>
          <p class="text-gray-600">{{ __('loading_booking_details') }}</p>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-secondary">
          {{ __('close') }}
        </button>
        <button
          v-if="booking && getAttendeeEmail(booking.data?.attendees)"
          @click="sendEmail"
          class="btn-primary"
        >
          {{ __('send_email') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateTime, getStatusClass, getStatusText, copyToClipboard } from '../../utils/helpers.js'
import { __ } from '../../utils/i18n.js'
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
      if (!dateTime) return __('not_available')
      return new Date(dateTime).toLocaleDateString()
    }

    const formatTime = (dateTime) => {
      if (!dateTime) return __('not_available')
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
        emit('show-alert', 'success', __('join_link_copied'))
      } else {
        emit('show-alert', 'error', __('failed_copy_link'))
      }
    }

    const sendEmail = () => {
      emit('send-email', props.booking)
    }

    const getAttendeeName = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return __('not_available')
      }
      return attendees[0].attendee_name || attendees[0].name || __('not_available')
    }

    const getAttendeeEmail = (attendees) => {
      if (!attendees || !Array.isArray(attendees) || attendees.length === 0) {
        return __('not_available')
      }
      return attendees[0].email || __('not_available')
    }

    const formatLocation = (locationData) => {
      if (!locationData) return __('not_available')
      
      try {
        const locations = JSON.parse(locationData)
        const locationKeys = Object.keys(locations)
        if (locationKeys.length === 0) return __('not_available')
        
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