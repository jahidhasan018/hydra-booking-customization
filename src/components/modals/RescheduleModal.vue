<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Reschedule Booking</h3>
        <button @click="$emit('close')" class="modal-close">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="modal-body">
          <!-- Alert Messages -->
          <div v-if="alert.show" :class="alert.type" class="mb-4">
            {{ alert.message }}
          </div>

          <!-- Current Booking Info -->
          <div v-if="booking" class="bg-gray-50 p-4 rounded-lg mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-2">Current Booking</h4>
            <div class="text-sm text-gray-600">
              <p><strong>Date:</strong> {{ formatDateTime(booking.date_time) }}</p>
              <p><strong>Duration:</strong> {{ booking.duration }} minutes</p>
              <p v-if="booking.host_name"><strong>Host:</strong> {{ booking.host_name }}</p>
            </div>
          </div>

          <div class="space-y-6">
            <!-- Date Selection -->
            <div>
              <label for="new_date" class="form-label">New Date *</label>
              <input
                id="new_date"
                v-model="form.new_date"
                type="date"
                class="form-input"
                :class="{ 'border-red-500': errors.new_date }"
                :min="minDate"
                required
              />
              <p v-if="errors.new_date" class="form-error">{{ errors.new_date }}</p>
            </div>

            <!-- Time Selection -->
            <div>
              <label for="new_time" class="form-label">New Time *</label>
              <select
                id="new_time"
                v-model="form.new_time"
                class="form-input"
                :class="{ 'border-red-500': errors.new_time }"
                required
              >
                <option value="">Select Time</option>
                <option
                  v-for="slot in availableTimeSlots"
                  :key="slot.value"
                  :value="slot.value"
                  :disabled="!slot.available"
                >
                  {{ slot.label }} {{ !slot.available ? '(Unavailable)' : '' }}
                </option>
              </select>
              <p v-if="errors.new_time" class="form-error">{{ errors.new_time }}</p>
            </div>

            <!-- Duration -->
            <div>
              <label for="duration" class="form-label">Duration (minutes)</label>
              <select
                id="duration"
                v-model="form.duration"
                class="form-input"
                :class="{ 'border-red-500': errors.duration }"
              >
                <option value="15">15 minutes</option>
                <option value="30">30 minutes</option>
                <option value="45">45 minutes</option>
                <option value="60">1 hour</option>
                <option value="90">1.5 hours</option>
                <option value="120">2 hours</option>
              </select>
              <p v-if="errors.duration" class="form-error">{{ errors.duration }}</p>
            </div>

            <!-- Timezone -->
            <div>
              <label for="timezone" class="form-label">Timezone</label>
              <select
                id="timezone"
                v-model="form.timezone"
                class="form-input"
                :class="{ 'border-red-500': errors.timezone }"
              >
                <option
                  v-for="tz in timezones"
                  :key="tz.value"
                  :value="tz.value"
                >
                  {{ tz.label }}
                </option>
              </select>
              <p v-if="errors.timezone" class="form-error">{{ errors.timezone }}</p>
            </div>

            <!-- Reason for Rescheduling -->
            <div>
              <label for="reason" class="form-label">Reason for Rescheduling</label>
              <textarea
                id="reason"
                v-model="form.reason"
                rows="3"
                class="form-input"
                :class="{ 'border-red-500': errors.reason }"
                placeholder="Please provide a reason for rescheduling (optional)"
              ></textarea>
              <p v-if="errors.reason" class="form-error">{{ errors.reason }}</p>
            </div>

            <!-- Notification Options -->
            <div class="border-t pt-4">
              <h4 class="text-lg font-medium text-gray-900 mb-3">Notifications</h4>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.notify_host"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">Notify host about rescheduling</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.send_confirmation"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">Send confirmation email</span>
                </label>
              </div>
            </div>

            <!-- Loading Available Slots -->
            <div v-if="loadingSlots" class="text-center py-4">
              <div class="loading-spinner mx-auto mb-2"></div>
              <p class="text-sm text-gray-600">Loading available time slots...</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting || loadingSlots">
            <div v-if="isSubmitting" class="loading-spinner-sm mr-2"></div>
            {{ isSubmitting ? 'Rescheduling...' : 'Reschedule Booking' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, inject } from 'vue'
import { formatDateTime } from '../../utils/helpers.js'
import { attendeeAPI } from '../../utils/api.js'

export default {
  name: 'RescheduleModal',
  props: {
    booking: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'save'],
  setup(props, { emit }) {
    // Inject toast notification system
    const toast = inject('toast')
    
    const isSubmitting = ref(false)
    const loadingSlots = ref(false)
    const availableTimeSlots = ref([])

    const form = reactive({
      new_date: '',
      new_time: '',
      duration: 30,
      timezone: 'America/New_York',
      reason: '',
      notify_host: true,
      send_confirmation: true
    })

    const errors = reactive({})
    const alert = reactive({
      show: false,
      type: '',
      message: ''
    })

    // Common timezones
    const timezones = [
      { value: 'America/New_York', label: 'Eastern Time (ET)' },
      { value: 'America/Chicago', label: 'Central Time (CT)' },
      { value: 'America/Denver', label: 'Mountain Time (MT)' },
      { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
      { value: 'Europe/London', label: 'Greenwich Mean Time (GMT)' },
      { value: 'Europe/Paris', label: 'Central European Time (CET)' },
      { value: 'Asia/Tokyo', label: 'Japan Standard Time (JST)' },
      { value: 'Asia/Shanghai', label: 'China Standard Time (CST)' },
      { value: 'Asia/Kolkata', label: 'India Standard Time (IST)' },
      { value: 'Australia/Sydney', label: 'Australian Eastern Time (AET)' }
    ]

    // Minimum date (tomorrow)
    const minDate = computed(() => {
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      return tomorrow.toISOString().split('T')[0]
    })

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

    const generateTimeSlots = () => {
      const slots = []
      for (let hour = 9; hour <= 17; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
          const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`
          const displayTime = new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
          })
          
          slots.push({
            value: timeString,
            label: displayTime,
            available: true // This would be determined by actual availability check
          })
        }
      }
      return slots
    }

    const loadAvailableSlots = async () => {
      if (!form.new_date) {
        availableTimeSlots.value = []
        return
      }

      loadingSlots.value = true
      try {
        // In a real implementation, this would fetch available slots from the API
        // For now, we'll generate mock slots
        const slots = generateTimeSlots()
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 500))
        
        availableTimeSlots.value = slots
      } catch (error) {
        showAlert('error', 'Failed to load available time slots')
        availableTimeSlots.value = generateTimeSlots()
      } finally {
        loadingSlots.value = false
      }
    }

    const validateForm = () => {
      // Clear previous errors
      Object.keys(errors).forEach(key => delete errors[key])

      // Required fields
      if (!form.new_date) {
        errors.new_date = 'New date is required'
      } else {
        const selectedDate = new Date(form.new_date)
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        
        if (selectedDate <= today) {
          errors.new_date = 'Please select a future date'
        }
      }

      if (!form.new_time) {
        errors.new_time = 'New time is required'
      }

      if (!form.duration || form.duration < 15) {
        errors.duration = 'Duration must be at least 15 minutes'
      }

      return Object.keys(errors).length === 0
    }

    const handleSubmit = async () => {
      if (!validateForm()) {
        showAlert('error', 'Please fix the errors below')
        return
      }

      isSubmitting.value = true

      try {
        const rescheduleData = {
          booking_id: props.booking.id,
          new_date: form.new_date,
          new_time: form.new_time,
          duration: form.duration,
          timezone: form.timezone,
          reason: form.reason,
          notify_host: form.notify_host,
          send_confirmation: form.send_confirmation
        }

        emit('save', rescheduleData)
      } catch (error) {
        showAlert('error', 'Failed to reschedule booking')
      } finally {
        isSubmitting.value = false
      }
    }

    // Initialize form with booking data
    const initializeForm = () => {
      if (props.booking) {
        form.duration = props.booking.duration || 30
        form.timezone = props.booking.timezone || 'America/New_York'
      }
    }

    // Watch for date changes to load available slots
    watch(() => form.new_date, loadAvailableSlots)

    // Watch for booking changes
    watch(() => props.booking, initializeForm, { immediate: true })

    onMounted(() => {
      initializeForm()
      availableTimeSlots.value = generateTimeSlots()
    })

    return {
      isSubmitting,
      loadingSlots,
      availableTimeSlots,
      form,
      errors,
      alert,
      timezones,
      minDate,
      showAlert,
      validateForm,
      handleSubmit,
      formatDateTime
    }
  }
}
</script>