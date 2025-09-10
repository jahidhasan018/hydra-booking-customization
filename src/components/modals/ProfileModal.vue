<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">{{ __('edit_profile') }}</h3>
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
                <label for="first_name" class="form-label">{{ __('first_name') }} *</label>
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
                <label for="last_name" class="form-label">{{ __('last_name') }}</label>
                <input
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  class="form-input"
                  :class="{ 'border-red-500': errors.last_name }"
                />
                <p v-if="errors.last_name" class="form-error">{{ errors.last_name }}</p>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="email" class="form-label">{{ __('email_address') }} *</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="form-input"
                  :class="{ 'border-red-500': errors.email }"
                  disabled
                  required
                />
                <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
              </div>

              <div>
                <label for="phone" class="form-label">{{ __('phone_number') }}</label>
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
              <label for="bio" class="form-label">{{ __('bio') }}</label>
              <textarea
                id="bio"
                v-model="form.bio"
                rows="4"
                class="form-input"
                :class="{ 'border-red-500': errors.bio }"
                :placeholder="__('tell_about_yourself')"
              ></textarea>
              <p v-if="errors.bio" class="form-error">{{ errors.bio }}</p>
            </div>



            <!-- Password Change Section -->
            <div class="border-t pt-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('change_password') }}</h4>
              <p class="text-sm text-gray-600 mb-4">{{ __('leave_blank_password') }}</p>

              <div class="space-y-4">
                <div>
                  <label for="current_password" class="form-label">{{ __('current_password') }}</label>
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
                    <label for="new_password" class="form-label">{{ __('new_password') }}</label>
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
                    <label for="confirm_password" class="form-label">{{ __('confirm_new_password') }}</label>
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


          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            {{ __('cancel') }}
          </button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting">
            <div v-if="isSubmitting" class="loading-spinner-sm mr-2"></div>
            {{ isSubmitting ? __('saving') : __('save_changes') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { inject, onMounted, reactive, ref, watch } from 'vue'
import { validateEmail } from '../../utils/helpers.js'
import { __ } from '../../utils/i18n.js'

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
      current_password: '',
      new_password: '',
      confirm_password: ''
    })

    const errors = reactive({})
    const alert = reactive({
      show: false,
      type: '',
      message: ''
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

    const validateForm = () => {
      // Clear previous errors
      Object.keys(errors).forEach(key => {
        delete errors[key]
      })

      let isValid = true

      // Only validate if profile data has been loaded (avoid validation during initialization)
      if (!props.profile || Object.keys(props.profile).length === 0) {
        return false
      }

      // Required: First name
      if (!form.first_name || !form.first_name.trim()) {
        errors.first_name = __('first_name_required')
        isValid = false
      }

      // Required: Email
      if (!form.email || !form.email.trim()) {
        errors.email = __('email_required')
        isValid = false
      } else if (!validateEmail(form.email)) {
        errors.email = __('invalid_email')
        isValid = false
      }

      // Optional: Last name (no validation needed since it's optional now)

      // Password validation (only if changing password)
      if (form.new_password || form.confirm_password || form.current_password) {
        if (!form.current_password) {
          errors.current_password = __('current_password_required')
          isValid = false
        }
        if (!form.new_password) {
          errors.new_password = __('new_password_required')
          isValid = false
        }
        if (form.new_password !== form.confirm_password) {
          errors.confirm_password = __('passwords_not_match')
          isValid = false
        }
        if (form.new_password && form.new_password.length < 6) {
          errors.new_password = __('password_min_length')
          isValid = false
        }
      }

      return isValid
    }

    const handleSubmit = async () => {
      if (!validateForm()) {
        showAlert('error', __('fix_errors_below'))
        return
      }

      isSubmitting.value = true

      try {
        const profileData = {
          first_name: form.first_name,
          last_name: form.last_name,
          email: form.email,
          phone: form.phone,
          bio: form.bio
        }

        // Include password data if provided
        if (form.new_password) {
          profileData.current_password = form.current_password
          profileData.new_password = form.new_password
        }

        emit('save', profileData)
      } catch (error) {
        showAlert('error', __('failed_save_profile'))
      } finally {
        isSubmitting.value = false
      }
    }

    // Initialize form with profile data
    const initializeForm = () => {
      if (props.profile && Object.keys(props.profile).length > 0) {
        // Map profile data to form fields
        form.first_name = props.profile.first_name || props.profile.name || ''
        form.last_name = props.profile.last_name || ''
        form.email = props.profile.email || props.profile.user_email || ''
        form.phone = props.profile.phone || ''
        form.bio = props.profile.bio || ''
        
        // Clear password fields
        form.current_password = ''
        form.new_password = ''
        form.confirm_password = ''
        
        // Clear any existing errors when form is initialized with valid data
        Object.keys(errors).forEach(key => {
          delete errors[key]
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
      showAlert,
      validateForm,
      handleSubmit,
      __
    }
  }
}
</script>