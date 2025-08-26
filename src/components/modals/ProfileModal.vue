<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Edit Profile</h3>
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

          <div class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="first_name" class="form-label">First Name *</label>
                <input
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  class="form-input"
                  :class="{ 'border-red-500': errors.first_name }"
                  required
                />
                <p v-if="errors.first_name" class="form-error">{{ errors.first_name }}</p>
              </div>

              <div>
                <label for="last_name" class="form-label">Last Name *</label>
                <input
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  class="form-input"
                  :class="{ 'border-red-500': errors.last_name }"
                  required
                />
                <p v-if="errors.last_name" class="form-error">{{ errors.last_name }}</p>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="email" class="form-label">Email Address *</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="form-input"
                  :class="{ 'border-red-500': errors.email }"
                  required
                />
                <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
              </div>

              <div>
                <label for="phone" class="form-label">Phone Number</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  class="form-input"
                  :class="{ 'border-red-500': errors.phone }"
                />
                <p v-if="errors.phone" class="form-error">{{ errors.phone }}</p>
              </div>
            </div>

            <!-- Bio -->
            <div>
              <label for="bio" class="form-label">Bio</label>
              <textarea
                id="bio"
                v-model="form.bio"
                rows="4"
                class="form-input"
                :class="{ 'border-red-500': errors.bio }"
                placeholder="Tell us about yourself..."
              ></textarea>
              <p v-if="errors.bio" class="form-error">{{ errors.bio }}</p>
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
                <option value="">Select Timezone</option>
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

            <!-- Password Change Section -->
            <div class="border-t pt-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Change Password</h4>
              <p class="text-sm text-gray-600 mb-4">Leave blank to keep current password</p>

              <div class="space-y-4">
                <div>
                  <label for="current_password" class="form-label">Current Password</label>
                  <input
                    id="current_password"
                    v-model="form.current_password"
                    type="password"
                    class="form-input"
                    :class="{ 'border-red-500': errors.current_password }"
                  />
                  <p v-if="errors.current_password" class="form-error">{{ errors.current_password }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="new_password" class="form-label">New Password</label>
                    <input
                      id="new_password"
                      v-model="form.new_password"
                      type="password"
                      class="form-input"
                      :class="{ 'border-red-500': errors.new_password }"
                    />
                    <p v-if="errors.new_password" class="form-error">{{ errors.new_password }}</p>
                  </div>

                  <div>
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input
                      id="confirm_password"
                      v-model="form.confirm_password"
                      type="password"
                      class="form-input"
                      :class="{ 'border-red-500': errors.confirm_password }"
                    />
                    <p v-if="errors.confirm_password" class="form-error">{{ errors.confirm_password }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notification Preferences -->
            <div class="border-t pt-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h4>
              <div class="space-y-3">
                <label class="flex items-center">
                  <input
                    v-model="form.email_notifications"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">Email notifications for booking updates</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.sms_notifications"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">SMS notifications for booking reminders</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.calendar_sync"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">Sync bookings with calendar</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting">
            <div v-if="isSubmitting" class="loading-spinner-sm mr-2"></div>
            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, watch, onMounted, inject } from 'vue'
import { validateEmail, validatePhone, validatePassword } from '../../utils/helpers.js'

export default {
  name: 'ProfileModal',
  props: {
    profile: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['close', 'save'],
  setup(props, { emit }) {
    // Inject toast notification system
    const toast = inject('toast')
    
    const isSubmitting = ref(false)
    const form = reactive({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      bio: '',
      timezone: '',
      current_password: '',
      new_password: '',
      confirm_password: '',
      email_notifications: true,
      sms_notifications: false,
      calendar_sync: true
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
      { value: 'Asia/Dhaka', label: 'Bangladesh Standard Time (BST)' },
      { value: 'Australia/Sydney', label: 'Australian Eastern Time (AET)' }
    ]

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

    const validateForm = () => {
      // Clear previous errors
      Object.keys(errors).forEach(key => delete errors[key])

      // Required fields
      if (!form.first_name.trim()) {
        errors.first_name = 'First name is required'
      }

      if (!form.last_name.trim()) {
        errors.last_name = 'Last name is required'
      }

      if (!form.email.trim()) {
        errors.email = 'Email is required'
      } else if (!validateEmail(form.email)) {
        errors.email = 'Please enter a valid email address'
      }

      // Optional phone validation
      if (form.phone && !validatePhone(form.phone)) {
        errors.phone = 'Please enter a valid phone number'
      }

      // Password validation
      if (form.new_password || form.confirm_password || form.current_password) {
        if (!form.current_password) {
          errors.current_password = 'Current password is required to change password'
        }

        if (!form.new_password) {
          errors.new_password = 'New password is required'
        } else if (!validatePassword(form.new_password)) {
          errors.new_password = 'Password must be at least 8 characters long'
        }

        if (form.new_password !== form.confirm_password) {
          errors.confirm_password = 'Passwords do not match'
        }
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
        const profileData = {
          first_name: form.first_name,
          last_name: form.last_name,
          email: form.email,
          phone: form.phone,
          bio: form.bio,
          timezone: form.timezone,
          email_notifications: form.email_notifications,
          sms_notifications: form.sms_notifications,
          calendar_sync: form.calendar_sync
        }

        // Include password data if provided
        if (form.new_password) {
          profileData.current_password = form.current_password
          profileData.new_password = form.new_password
        }

        emit('save', profileData)
      } catch (error) {
        showAlert('error', 'Failed to save profile')
      } finally {
        isSubmitting.value = false
      }
    }

    // Initialize form with profile data
    const initializeForm = () => {
      if (props.profile) {
        Object.keys(form).forEach(key => {
          if (props.profile[key] !== undefined) {
            form[key] = props.profile[key]
          }
        })
      }
    }

    // Watch for profile changes
    watch(() => props.profile, initializeForm, { immediate: true })

    onMounted(initializeForm)

    return {
      isSubmitting,
      form,
      errors,
      alert,
      timezones,
      showAlert,
      validateForm,
      handleSubmit
    }
  }
}
</script>