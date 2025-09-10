<template>
  <div class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">{{ __('generate_join_link') }}</h3>
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
            <!-- Meeting Selection -->
            <div>
              <label for="meeting_id" class="form-label">{{ __('select_meeting') }} *</label>
              <select
                id="meeting_id"
                v-model="form.meeting_id"
                class="form-input"
                :class="{ 'border-red-500': errors.meeting_id }"
                required
                @change="onMeetingChange"
              >
                <option value="">{{ __('choose_meeting') }}</option>
                <option
                  v-for="meeting in availableMeetings"
                  :key="meeting.id"
                  :value="meeting.id"
                >
                  {{ meeting.title }} - {{ formatDateTime(meeting.date_time) }}
                </option>
              </select>
              <p v-if="errors.meeting_id" class="form-error">{{ errors.meeting_id }}</p>
            </div>

            <!-- Link Type -->
            <div>
              <label class="form-label">{{ __('link_type') }} *</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.link_type"
                    type="radio"
                    value="one-time"
                    class="form-radio"
                  />
                  <span class="ml-2">
                    <span class="font-medium">{{ __('one_time_link') }}</span>
                    <span class="block text-sm text-gray-500">{{ __('can_only_used_once') }}</span>
                  </span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.link_type"
                    type="radio"
                    value="reusable"
                    class="form-radio"
                  />
                  <span class="ml-2">
                    <span class="font-medium">{{ __('reusable_link') }}</span>
                    <span class="block text-sm text-gray-500">{{ __('can_used_multiple_times') }}</span>
                  </span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.link_type"
                    type="radio"
                    value="limited"
                    class="form-radio"
                  />
                  <span class="ml-2">
                    <span class="font-medium">{{ __('limited_use_link') }}</span>
                    <span class="block text-sm text-gray-500">{{ __('can_used_specific_times') }}</span>
                  </span>
                </label>
              </div>
              <p v-if="errors.link_type" class="form-error">{{ errors.link_type }}</p>
            </div>

            <!-- Max Uses (for limited type) -->
            <div v-if="form.link_type === 'limited'">
              <label for="max_uses" class="form-label">{{ __('maximum_uses') }} *</label>
              <input
                id="max_uses"
                v-model.number="form.max_uses"
                type="number"
                min="1"
                max="100"
                class="form-input"
                :class="{ 'border-red-500': errors.max_uses }"
                required
              />
              <p v-if="errors.max_uses" class="form-error">{{ errors.max_uses }}</p>
            </div>

            <!-- Custom URL -->
            <div>
              <label for="custom_url" class="form-label">{{ __('custom_url_optional') }}</label>
              <div class="flex">
                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                  {{ baseUrl }}/join/
                </span>
                <input
                  id="custom_url"
                  v-model="form.custom_url"
                  type="text"
                  class="form-input rounded-l-none"
                  :class="{ 'border-red-500': errors.custom_url }"
                  placeholder="my-meeting-link"
                  @input="validateCustomUrl"
                />
              </div>
              <p class="text-sm text-gray-500 mt-1">
                {{ __('leave_blank_random_url') }}
              </p>
              <p v-if="errors.custom_url" class="form-error">{{ errors.custom_url }}</p>
            </div>

            <!-- Expiration -->
            <div>
              <label class="form-label">{{ __('link_expiration') }}</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.expiration_type"
                    type="radio"
                    value="never"
                    class="form-radio"
                  />
                  <span class="ml-2">{{ __('never_expires') }}</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.expiration_type"
                    type="radio"
                    value="after_meeting"
                    class="form-radio"
                  />
                  <span class="ml-2">{{ __('expires_after_meeting') }}</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.expiration_type"
                    type="radio"
                    value="custom"
                    class="form-radio"
                  />
                  <span class="ml-2">{{ __('custom_expiration_date') }}</span>
                </label>
              </div>
            </div>

            <!-- Custom Expiration Date -->
            <div v-if="form.expiration_type === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="expiration_date" class="form-label">{{ __('expiration_date') }} *</label>
                <input
                  id="expiration_date"
                  v-model="form.expiration_date"
                  type="date"
                  class="form-input"
                  :class="{ 'border-red-500': errors.expiration_date }"
                  :min="minExpirationDate"
                  required
                />
                <p v-if="errors.expiration_date" class="form-error">{{ errors.expiration_date }}</p>
              </div>

              <div>
                <label for="expiration_time" class="form-label">{{ __('expiration_time') }} *</label>
                <input
                  id="expiration_time"
                  v-model="form.expiration_time"
                  type="time"
                  class="form-input"
                  :class="{ 'border-red-500': errors.expiration_time }"
                  required
                />
                <p v-if="errors.expiration_time" class="form-error">{{ errors.expiration_time }}</p>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="form-label">{{ __('description_optional') }}</label>
              <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="form-input"
                :class="{ 'border-red-500': errors.description }"
                :placeholder="__('add_description_join_link')"
              ></textarea>
              <p v-if="errors.description" class="form-error">{{ errors.description }}</p>
            </div>

            <!-- Security Options -->
            <div class="border-t pt-4">
              <h4 class="text-lg font-medium text-gray-900 mb-3">{{ __('security_options') }}</h4>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.require_password"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ __('require_password_join') }}</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.waiting_room"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ __('enable_waiting_room') }}</span>
                </label>

                <label class="flex items-center">
                  <input
                    v-model="form.host_approval"
                    type="checkbox"
                    class="form-checkbox"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ __('require_host_approval') }}</span>
                </label>
              </div>
            </div>

            <!-- Password Field -->
            <div v-if="form.require_password">
              <label for="password" class="form-label">{{ __('meeting_password') }} *</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="form-input"
                :class="{ 'border-red-500': errors.password }"
                required
              />
              <p v-if="errors.password" class="form-error">{{ errors.password }}</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            {{ __('cancel') }}
          </button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting">
            <div v-if="isSubmitting" class="loading-spinner-sm mr-2"></div>
            {{ isSubmitting ? __('generating') : __('generate_link') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, inject } from 'vue'
import { formatDateTime } from '../../utils/helpers.js'
import { hostAPI } from '../../utils/api.js'
import { __ } from '../../utils/i18n.js'

export default {
  name: 'JoinLinkModal',
  emits: ['close', 'save'],
  setup(props, { emit }) {
    // Inject toast notification system
    const toast = inject('toast')
    
    const isSubmitting = ref(false)
    const availableMeetings = ref([])

    const form = reactive({
      meeting_id: '',
      link_type: 'one-time',
      max_uses: 1,
      custom_url: '',
      expiration_type: 'after_meeting',
      expiration_date: '',
      expiration_time: '',
      description: '',
      require_password: false,
      password: '',
      waiting_room: false,
      host_approval: false
    })

    const errors = reactive({})
    const alert = reactive({
      show: false,
      type: '',
      message: ''
    })

    // Base URL for join links (would come from WordPress settings)
    const baseUrl = computed(() => {
      return window.location.origin
    })

    // Minimum expiration date (today)
    const minExpirationDate = computed(() => {
      return new Date().toISOString().split('T')[0]
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

    const loadAvailableMeetings = async () => {
      try {
        const data = await hostAPI.getBookings('upcoming')
        availableMeetings.value = data.bookings || []
      } catch (error) {
        showAlert('error', __('failed_load_meetings'))
      }
    }

    const onMeetingChange = () => {
      // Reset custom URL when meeting changes
      form.custom_url = ''
    }

    const validateCustomUrl = () => {
      if (form.custom_url) {
        // Remove invalid characters and convert to lowercase
        form.custom_url = form.custom_url
          .toLowerCase()
          .replace(/[^a-z0-9-]/g, '')
          .replace(/--+/g, '-')
          .replace(/^-|-$/g, '')
      }
    }

    const validateForm = () => {
      // Clear previous errors
      Object.keys(errors).forEach(key => delete errors[key])

      // Required fields
      if (!form.meeting_id) {
        errors.meeting_id = __('please_select_meeting')
      }

      if (!form.link_type) {
        errors.link_type = __('please_select_link_type')
      }

      if (form.link_type === 'limited') {
        if (!form.max_uses || form.max_uses < 1) {
          errors.max_uses = __('max_uses_at_least_one')
        }
      }

      // Custom URL validation
      if (form.custom_url) {
        if (form.custom_url.length < 3) {
          errors.custom_url = __('custom_url_min_length')
        } else if (form.custom_url.length > 50) {
          errors.custom_url = __('custom_url_max_length')
        }
      }

      // Expiration validation
      if (form.expiration_type === 'custom') {
        if (!form.expiration_date) {
          errors.expiration_date = __('expiration_date_required')
        }
        if (!form.expiration_time) {
          errors.expiration_time = __('expiration_time_required')
        }

        if (form.expiration_date && form.expiration_time) {
          const expirationDateTime = new Date(`${form.expiration_date}T${form.expiration_time}`)
          const now = new Date()
          
          if (expirationDateTime <= now) {
            errors.expiration_date = __('expiration_future_required')
          }
        }
      }

      // Password validation
      if (form.require_password && !form.password) {
        errors.password = __('password_required_when_enabled')
      }

      return Object.keys(errors).length === 0
    }

    const handleSubmit = async () => {
      if (!validateForm()) {
        showAlert('error', __('fix_errors_below'))
        return
      }

      isSubmitting.value = true

      try {
        const linkData = {
          meeting_id: form.meeting_id,
          link_type: form.link_type,
          max_uses: form.link_type === 'limited' ? form.max_uses : null,
          custom_url: form.custom_url || null,
          expiration_type: form.expiration_type,
          expiration_date: form.expiration_type === 'custom' ? form.expiration_date : null,
          expiration_time: form.expiration_type === 'custom' ? form.expiration_time : null,
          description: form.description || null,
          require_password: form.require_password,
          password: form.require_password ? form.password : null,
          waiting_room: form.waiting_room,
          host_approval: form.host_approval
        }

        emit('save', linkData)
      } catch (error) {
        showAlert('error', __('failed_generate_join_link'))
      } finally {
        isSubmitting.value = false
      }
    }

    // Watch for link type changes
    watch(() => form.link_type, (newType) => {
      if (newType !== 'limited') {
        form.max_uses = 1
      }
    })

    // Watch for password requirement changes
    watch(() => form.require_password, (requirePassword) => {
      if (!requirePassword) {
        form.password = ''
      }
    })

    onMounted(loadAvailableMeetings)

    return {
      isSubmitting,
      availableMeetings,
      form,
      errors,
      alert,
      baseUrl,
      minExpirationDate,
      showAlert,
      loadAvailableMeetings,
      onMeetingChange,
      validateCustomUrl,
      validateForm,
      handleSubmit,
      formatDateTime
    }
  }
}
</script>